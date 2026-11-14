<?php

namespace App\Console\Commands;

use App\Services\ObligationStatusSync;
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
    protected $description = 'Run daily bill processing: synchronize obligation statuses, generate reminders, and refresh risk assessments (Section 10.5)';

    /**
     * Execute the console command.
     */
    public function handle(ReminderGenerator $reminderGenerator, RiskAssessmentService $riskAssessmentService): int
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

        $this->info('Daily bill processing completed successfully.');

        return self::SUCCESS;
    }
}
