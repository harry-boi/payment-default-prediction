<?php

namespace App\Http\Controllers;

use App\Models\Reminder;
use App\Services\ObligationStatusSync;
use App\Services\ReminderEmailService;
use App\Services\ReminderGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Throwable;

class ReminderController extends Controller
{
    /**
     * Display a listing of reminders and alert notifications (Section 9.2).
     */
    public function index(Request $request, ReminderGenerator $generator): View
    {
        $user = $request->user();

        // Locked Decision #4: Automatically sync statuses and generate reminders for signed-in user
        ObligationStatusSync::syncForUser($user);
        $generator->generateForUser($user);

        $query = $user->reminders()
            ->with(['salaryObligation.employee', 'salaryObligation.latestRiskAssessment'])
            ->orderByDesc('id');

        // Filter by Read/Unread
        if ($request->input('status') === 'unread') {
            $query->where('is_read', false);
        } elseif ($request->input('status') === 'read') {
            $query->where('is_read', true);
        }

        // Filter by Type (upcoming vs overdue)
        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        $reminders = $query->paginate(15)->withQueryString();

        $unreadCount = $user->reminders()->where('is_read', false)->count();
        $unreadUpcomingCount = $user->reminders()->where('is_read', false)->where('type', 'upcoming')->count();
        $unreadOverdueCount = $user->reminders()->where('is_read', false)->where('type', 'overdue')->count();

        return view('reminders.index', compact(
            'reminders',
            'unreadCount',
            'unreadUpcomingCount',
            'unreadOverdueCount'
        ));
    }

    /**
     * Mark a specific reminder as read.
     */
    public function markAsRead(Reminder $reminder): RedirectResponse
    {
        Gate::authorize('update', $reminder);

        $reminder->markAsRead();

        return back()->with('success', 'Reminder marked as read.');
    }

    /**
     * Mark all active reminders as read for the authenticated user.
     */
    public function markAllAsRead(Request $request): RedirectResponse
    {
        $request->user()->reminders()->where('is_read', false)->update(['is_read' => true]);

        return back()->with('success', 'All notifications and alerts marked as read.');
    }

    /**
     * Email a summary of the authenticated user's active reminders to them.
     */
    public function emailSummary(Request $request, ReminderGenerator $generator, ReminderEmailService $emailService): RedirectResponse
    {
        $user = $request->user();

        ObligationStatusSync::syncForUser($user);
        $generator->generateForUser($user);

        if (! $emailService->isConfigured()) {
            return back()->with('error', 'Email alerts are not configured yet. Set the Resend API key and the sender address first.');
        }

        try {
            $emailedCount = $emailService->sendSummaryForUser($user);
        } catch (Throwable $e) {
            return back()->with('error', 'The reminder email could not be sent. Please try again.');
        }

        if ($emailedCount === 0) {
            return back()->with('success', 'There are no active reminders to email right now.');
        }

        return back()->with('success', "Reminder summary emailed to {$user->email} ({$emailedCount} reminders included).");
    }
}
