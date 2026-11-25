<?php

namespace App\Services;

use App\Models\Reminder;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class ReminderEmailService
{
    /**
     * Whether the email alert channel has the credentials it needs.
     */
    public function isConfigured(): bool
    {
        return ! empty(config('services.resend.key')) && ! empty(config('services.resend.from'));
    }

    /**
     * Email a summary of a user's active reminders.
     *
     * With $onlyNew, only reminders that have never been emailed are sent
     * (the daily scheduler path). Otherwise every active reminder is
     * included (the manual "email me this summary" path). Successfully
     * emailed reminders are stamped so the daily path never repeats them.
     *
     * @return int Number of reminders included in the email sent
     */
    public function sendSummaryForUser(User $user, bool $onlyNew = false): int
    {
        $query = $user->reminders()
            ->with(['salaryObligation.employee', 'salaryObligation.latestRiskAssessment']);

        if ($onlyNew) {
            $query->whereNull('emailed_at');
        }

        $reminders = $query->get()
            ->sortBy(fn (Reminder $reminder) => $reminder->isOverdue() ? 0 : 1)
            ->values();

        if ($reminders->isEmpty()) {
            return 0;
        }

        $this->deliver($user, $reminders);

        Reminder::whereIn('id', $reminders->pluck('id'))
            ->update(['emailed_at' => now()]);

        return $reminders->count();
    }

    /**
     * Email every user who has reminders that have never been emailed.
     * A failure for one user is logged and does not stop the others.
     *
     * @return int Number of digest emails sent
     */
    public function sendAllNewDigests(): int
    {
        if (! $this->isConfigured()) {
            return 0;
        }

        $sent = 0;

        $userIds = Reminder::whereNull('emailed_at')->distinct()->pluck('user_id');

        foreach (User::whereIn('id', $userIds)->get() as $user) {
            try {
                if ($this->sendSummaryForUser($user, onlyNew: true) > 0) {
                    $sent++;
                }
            } catch (Throwable $e) {
                Log::warning("Reminder digest email failed for user {$user->id}: {$e->getMessage()}");
            }
        }

        return $sent;
    }

    /**
     * Build and send one digest email through the Resend API.
     *
     * @param  Collection<int, Reminder>  $reminders
     */
    protected function deliver(User $user, Collection $reminders): void
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Email alerts are not configured. Set RESEND_API_KEY and MAIL_FROM_ADDRESS.');
        }

        $overdue = $reminders->filter(fn (Reminder $reminder) => $reminder->isOverdue())->values();
        $upcoming = $reminders->filter(fn (Reminder $reminder) => $reminder->isUpcoming())->values();

        $subjectParts = [];
        if ($overdue->isNotEmpty()) {
            $subjectParts[] = $overdue->count().' overdue';
        }
        if ($upcoming->isNotEmpty()) {
            $subjectParts[] = $upcoming->count().' due soon';
        }
        $subject = 'Salary reminders: '.implode(', ', $subjectParts);

        $fromName = config('services.resend.from_name', 'Smart Bill Reminder');
        $fromAddress = config('services.resend.from');

        $response = Http::withToken((string) config('services.resend.key'))
            ->acceptJson()
            ->post('https://api.resend.com/emails', [
                'from' => "{$fromName} <{$fromAddress}>",
                'to' => [$user->email],
                'subject' => $subject,
                'html' => view('emails.reminder-digest', [
                    'user' => $user,
                    'overdue' => $overdue,
                    'upcoming' => $upcoming,
                ])->render(),
                'text' => $this->plainTextBody($user, $overdue, $upcoming),
            ]);

        if ($response->failed()) {
            Log::warning('Resend rejected a reminder digest email.', [
                'user_id' => $user->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException('The email service could not deliver the reminder email.');
        }
    }

    /**
     * Plain-text version of the digest for mail clients without HTML.
     *
     * @param  Collection<int, Reminder>  $overdue
     * @param  Collection<int, Reminder>  $upcoming
     */
    protected function plainTextBody(User $user, Collection $overdue, Collection $upcoming): string
    {
        $lines = ["Hello {$user->name},", '', 'The following salary obligations need your attention.', ''];

        foreach ($overdue as $reminder) {
            $lines[] = 'OVERDUE: '.$this->reminderLine($reminder);
        }

        foreach ($upcoming as $reminder) {
            $lines[] = 'DUE SOON: '.$this->reminderLine($reminder);
        }

        $lines[] = '';
        $lines[] = 'Open Smart Bill Reminder to review these obligations and record payments.';

        return implode("\n", $lines);
    }

    /**
     * One digest line: employee, amount, due date and risk where known.
     */
    protected function reminderLine(Reminder $reminder): string
    {
        $obligation = $reminder->salaryObligation;

        $line = $obligation->employee->name.' - '.$obligation->formatted_amount
            .' - due '.$obligation->due_date->format('M d, Y');

        if ($obligation->latestRiskAssessment) {
            $line .= ' - '.$obligation->latestRiskAssessment->risk_level->label()
                .' ('.$obligation->latestRiskAssessment->score.'/85)';
        }

        return $line;
    }
}
