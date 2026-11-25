<?php

namespace App\Console\Commands;

use App\Services\ObligationStatusSync;
use App\Services\ReminderEmailService;
use App\Services\ReminderGenerator;
use App\Services\RiskAssessmentService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ProcessDailyBills extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bills:process-daily {--date= : Pretend today is this specific date (YYYY-MM-DD)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run daily bill processing: synchronize obligation statuses, generate reminders, refresh risk assessments, and email reminder digests (Section 10.5)';

    /**
     * Execute the console command.
     */
    public function handle(ReminderGenerator $reminderGenerator, RiskAssessmentService $riskAssessmentService, ReminderEmailService $reminderEmailService): int
    {
        $dateOption = $this->option('date');
        $effectiveDate = $dateOption ? Carbon::parse($dateOption)->startOfDay() : Carbon::now()->startOfDay();

        $this->info("Processing daily bills for reference date: {$effectiveDate->format('Y-m-d')}...");

        // 1. Sync obligation status (Upcoming vs Overdue) for everyone
        $this->comment('Step 1: Synchronizing obligation statuses across all employers...');
        $syncedCount = ObligationStatusSync::syncAll($effectiveDate);
        $this->line(" -> {$syncedCount} obligations updated.");

        // 2. Generate upcoming & overdue reminders for everyone
        $this->comment('Step 2: Generating forward-looking and overdue reminders...');
        $remindersCount = $reminderGenerator->generateAll($effectiveDate);
        $this->line(" -> {$remindersCount} new reminders generated.");

        // 3. Refresh payment default risk scores for every open obligation
        $this->comment('Step 3: Recalculating default risk scores for all open obligations...');
        $assessedCount = $riskAssessmentService->assessAllOpen($effectiveDate);
        $this->line(" -> {$assessedCount} open obligations evaluated.");

        // 4. Email a digest of newly generated reminders to each income payer.
        // A mail failure must never fail the daily run, so it is reported, not thrown.
        $this->comment('Step 4: Emailing reminder digests for new reminders...');
        if ($reminderEmailService->isConfigured()) {
            $digestCount = $reminderEmailService->sendAllNewDigests();
            $this->line(" -> {$digestCount} reminder digest emails sent.");
        } else {
            $this->line(' -> Email alerts are not configured (RESEND_API_KEY / MAIL_FROM_ADDRESS); skipped.');
        }

        $this->info('Daily bill processing completed successfully.');

        return self::SUCCESS;
    }
}
