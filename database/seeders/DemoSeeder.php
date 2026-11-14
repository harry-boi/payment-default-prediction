<?php

namespace Database\Seeders;

use App\Enums\EmployeeStatus;
use App\Enums\ObligationStatus;
use App\Enums\PaymentStatus;
use App\Enums\ReminderType;
use App\Enums\SalaryFrequency;
use App\Models\Employee;
use App\Models\Payment;
use App\Models\Reminder;
use App\Models\SalaryObligation;
use App\Models\User;
use App\Services\ObligationStatusSync;
use App\Services\ReminderGenerator;
use App\Services\RiskAssessmentService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Seeds the demo income payer (Effiong Dominic Eyoh), 6 employees,
     * 30 salary obligations with payment history, varied risk levels (Low, Medium, High), and reminders.
     */
    public function run(): void
    {
        $today = Carbon::now()->startOfDay();

        // 1. Primary demo user account
        $user = User::updateOrCreate(
            ['email' => 'demo@example.com'],
            [
                'name' => 'Effiong Dominic Eyoh',
                'password' => Hash::make('password'),
            ]
        );

        // 2. Six realistic employees across different roles and salary structures
        $employeesData = [
            [
                'key' => 'emem',
                'name' => 'Emem Udoh',
                'job_title' => 'Senior Accountant',
                'phone' => '08031234567',
                'salary_amount' => 18000000, // ₦180,000.00
                'salary_frequency' => SalaryFrequency::Monthly,
                'status' => EmployeeStatus::Active,
            ],
            [
                'key' => 'bassey',
                'name' => 'Bassey Okon',
                'job_title' => 'Operations Supervisor',
                'phone' => '08029876543',
                'salary_amount' => 4000000, // ₦40,000.00
                'salary_frequency' => SalaryFrequency::Monthly,
                'status' => EmployeeStatus::Active,
            ],
            [
                'key' => 'aniefiok',
                'name' => 'Aniefiok Edet',
                'job_title' => 'Logistics Officer',
                'phone' => '08145551212',
                'salary_amount' => 6000000, // ₦60,000.00
                'salary_frequency' => SalaryFrequency::Biweekly,
                'status' => EmployeeStatus::Active,
            ],
            [
                'key' => 'inemesit',
                'name' => 'Inemesit Akpan',
                'job_title' => 'Security Guard',
                'phone' => '07034449898',
                'salary_amount' => 5000000, // ₦50,000.00
                'salary_frequency' => SalaryFrequency::Monthly,
                'status' => EmployeeStatus::Active,
            ],
            [
                'key' => 'kufre',
                'name' => 'Kufre Essien',
                'job_title' => 'Dispatch Rider',
                'phone' => '08091112233',
                'salary_amount' => 3500000, // ₦35,000.00
                'salary_frequency' => SalaryFrequency::Weekly,
                'status' => EmployeeStatus::Active,
            ],
            [
                'key' => 'nsikak',
                'name' => 'Nsikak Sunday',
                'job_title' => 'IT Support Specialist',
                'phone' => '08187776655',
                'salary_amount' => 12000000, // ₦120,000.00
                'salary_frequency' => SalaryFrequency::Monthly,
                'status' => EmployeeStatus::Active,
            ],
        ];

        $employees = [];
        foreach ($employeesData as $data) {
            $key = $data['key'];
            unset($data['key']);
            $employees[$key] = Employee::updateOrCreate(
                ['user_id' => $user->id, 'phone' => $data['phone']],
                $data
            );
        }

        // 3. Seed 30 Salary Obligations and completed Payments
        // Emem Udoh: 3 paid on time (0% late rate), 1 upcoming due in 12 days (Low Risk: Score 20)
        $this->seedPaidObligation($employees['emem'], $today->copy()->subMonths(3), 0, 'TRX-EMEM-001', 'Monthly Salary - 3 Months Ago');
        $this->seedPaidObligation($employees['emem'], $today->copy()->subMonths(2), 0, 'TRX-EMEM-002', 'Monthly Salary - 2 Months Ago');
        $this->seedPaidObligation($employees['emem'], $today->copy()->subMonths(1), 0, 'TRX-EMEM-003', 'Monthly Salary - Last Month');
        $this->seedOpenObligation($employees['emem'], $today->copy()->addDays(12), 'Next Monthly Salary');

        // Bassey Okon: 3 paid (1 on time, 2 late), 1 overdue by 4 days (Medium Risk: Score 40)
        $this->seedPaidObligation($employees['bassey'], $today->copy()->subMonths(3), 4, 'TRX-BASS-001', 'Monthly Salary - Late Payment (4 days)');
        $this->seedPaidObligation($employees['bassey'], $today->copy()->subMonths(2), 0, 'TRX-BASS-002', 'Monthly Salary - Paid On Time');
        $this->seedPaidObligation($employees['bassey'], $today->copy()->subMonths(1), 7, 'TRX-BASS-003', 'Monthly Salary - Late Payment (7 days)');
        $this->seedOpenObligation($employees['bassey'], $today->copy()->subDays(4), 'Overdue Monthly Salary');

        // Aniefiok Edet: 4 paid (3 late, 1 on time), 1 upcoming due tomorrow (High Risk: Score 70)
        $this->seedPaidObligation($employees['aniefiok'], $today->copy()->subWeeks(8), 5, 'TRX-ANIE-001', 'Biweekly Salary - Late Payment (5 days)');
        $this->seedPaidObligation($employees['aniefiok'], $today->copy()->subWeeks(6), 0, 'TRX-ANIE-002', 'Biweekly Salary - Paid On Time');
        $this->seedPaidObligation($employees['aniefiok'], $today->copy()->subWeeks(4), 3, 'TRX-ANIE-003', 'Biweekly Salary - Late Payment (3 days)');
        $this->seedPaidObligation($employees['aniefiok'], $today->copy()->subWeeks(2), 6, 'TRX-ANIE-004', 'Biweekly Salary - Late Payment (6 days)');
        $this->seedOpenObligation($employees['aniefiok'], $today->copy()->addDays(1), 'Upcoming Biweekly Cycle');

        // Inemesit Akpan: 4 paid on time (0% late rate), 1 upcoming due in 5 days (Medium Risk: Score 35)
        $this->seedPaidObligation($employees['inemesit'], $today->copy()->subMonths(4), 0, 'TRX-INEM-001', 'Monthly Salary - 4 Months Ago');
        $this->seedPaidObligation($employees['inemesit'], $today->copy()->subMonths(3), 0, 'TRX-INEM-002', 'Monthly Salary - 3 Months Ago');
        $this->seedPaidObligation($employees['inemesit'], $today->copy()->subMonths(2), 0, 'TRX-INEM-003', 'Monthly Salary - 2 Months Ago');
        $this->seedPaidObligation($employees['inemesit'], $today->copy()->subMonths(1), 0, 'TRX-INEM-004', 'Monthly Salary - Last Month');
        $this->seedOpenObligation($employees['inemesit'], $today->copy()->addDays(5), 'Current Monthly Salary');

        // Kufre Essien: 8 weekly paid (6 on time, 2 late -> 25% late rate), 1 upcoming in 2 days (Medium Risk: Score 50)
        $this->seedPaidObligation($employees['kufre'], $today->copy()->subWeeks(8), 0, 'TRX-KUFR-001', 'Weekly Salary Cycle W-8');
        $this->seedPaidObligation($employees['kufre'], $today->copy()->subWeeks(7), 0, 'TRX-KUFR-002', 'Weekly Salary Cycle W-7');
        $this->seedPaidObligation($employees['kufre'], $today->copy()->subWeeks(6), 2, 'TRX-KUFR-003', 'Weekly Salary Cycle W-6 (Late 2 days)');
        $this->seedPaidObligation($employees['kufre'], $today->copy()->subWeeks(5), 0, 'TRX-KUFR-004', 'Weekly Salary Cycle W-5');
        $this->seedPaidObligation($employees['kufre'], $today->copy()->subWeeks(4), 0, 'TRX-KUFR-005', 'Weekly Salary Cycle W-4');
        $this->seedPaidObligation($employees['kufre'], $today->copy()->subWeeks(3), 0, 'TRX-KUFR-006', 'Weekly Salary Cycle W-3');
        $this->seedPaidObligation($employees['kufre'], $today->copy()->subWeeks(2), 3, 'TRX-KUFR-007', 'Weekly Salary Cycle W-2 (Late 3 days)');
        $this->seedPaidObligation($employees['kufre'], $today->copy()->subWeeks(1), 0, 'TRX-KUFR-008', 'Weekly Salary Cycle W-1');
        $this->seedOpenObligation($employees['kufre'], $today->copy()->addDays(2), 'Current Weekly Wages');

        // Nsikak Sunday: 2 paid (1 on time, 1 late -> 50% late rate), 1 upcoming in 3 days (Medium Risk)
        $this->seedPaidObligation($employees['nsikak'], $today->copy()->subMonths(2), 0, 'TRX-NSIK-001', 'Monthly Salary - 2 Months Ago');
        $this->seedPaidObligation($employees['nsikak'], $today->copy()->subMonths(1), 5, 'TRX-NSIK-002', 'Monthly Salary - Late Payment (5 days)');
        $this->seedOpenObligation($employees['nsikak'], $today->copy()->addDays(3), 'Current Monthly Salary');

        // 4. Synchronize obligation statuses
        ObligationStatusSync::syncForUser($user, $today);

        // 5. Generate Reminders using the engine
        $reminderGenerator = app(ReminderGenerator::class);
        $reminderGenerator->generateForUser($user, $today);

        // 6. Run Risk Assessments across all open obligations
        $riskService = app(RiskAssessmentService::class);
        $riskService->assessOpenObligationsForUser($user, $today);

        // 7. Seed two historical archived/read reminders for realistic activity
        $pastPaidObligation = SalaryObligation::where('employee_id', $employees['emem']->id)
            ->where('status', ObligationStatus::Paid)
            ->first();

        if ($pastPaidObligation) {
            Reminder::firstOrCreate(
                [
                    'salary_obligation_id' => $pastPaidObligation->id,
                    'type' => ReminderType::Upcoming,
                ],
                [
                    'user_id' => $user->id,
                    'message' => "Salary for Emem Udoh (₦180,000.00) was due on {$pastPaidObligation->due_date->format('M d, Y')}.",
                    'is_read' => true,
                ]
            );
        }
    }

    /**
     * Helper to create a paid obligation with a corresponding payment record.
     */
    protected function seedPaidObligation(
        Employee $employee,
        Carbon $dueDate,
        int $delayDays,
        string $reference,
        string $period
    ): SalaryObligation {
        $obligation = SalaryObligation::create([
            'employee_id' => $employee->id,
            'amount' => $employee->salary_amount,
            'due_date' => $dueDate->toDateString(),
            'frequency' => $employee->salary_frequency,
            'status' => ObligationStatus::Paid,
            'notes' => 'Settled salary payment',
            'payment_period' => $period,
        ]);

        Payment::create([
            'salary_obligation_id' => $obligation->id,
            'amount_paid' => $obligation->amount,
            'paid_at' => $delayDays > 0 ? $dueDate->copy()->addDays($delayDays)->toDateString() : $dueDate->toDateString(),
            'status' => $delayDays > 0 ? PaymentStatus::PaidLate : PaymentStatus::PaidOnTime,
            'delay_days' => $delayDays,
            'reference' => $reference,
        ]);

        return $obligation;
    }

    /**
     * Helper to create an open obligation (will be categorized by ObligationStatusSync).
     */
    protected function seedOpenObligation(
        Employee $employee,
        Carbon $dueDate,
        string $period
    ): SalaryObligation {
        $status = $dueDate->isPast() ? ObligationStatus::Overdue : ObligationStatus::Upcoming;

        return SalaryObligation::create([
            'employee_id' => $employee->id,
            'amount' => $employee->salary_amount,
            'due_date' => $dueDate->toDateString(),
            'frequency' => $employee->salary_frequency,
            'status' => $status,
            'notes' => 'Scheduled salary payment',
            'payment_period' => $period,
        ]);
    }
}
