# Smart Bill Reminder: Implementation Guide for Antigravity

**Project:** Smart Bill Reminder of Payment Default Prediction System for Income Payers  
**Author:** Effiong Dominic Eyoh, Software and Web Development, Akwa Ibom State Polytechnic  
**Source of truth:** The project report, Chapters 3 and 4 (Tables 3.1 to 3.6, algorithms in Section 3.8, implementation in Sections 4.3 to 4.7)

---

## 1. How to use this document

This guide tells an AI coding agent in Antigravity how to build the system described in Chapters 3 and 4 of the report. Save it in the project root as `IMPLEMENTATION.md` and give the agent the starter prompt in Section 15.

### Rules for the agent:
1. **Work one phase at a time (Section 11).** After each phase, run the tests, report what was built, and stop for approval before the next phase.
2. **Do not add features, packages or pages that this guide does not list.**
3. **Do not change any locked decision in Section 3.** If one looks wrong, say so and wait.
4. **Keep the code simple and readable.** This is a final year project that will be explained and defended, not a production product.
5. **Every phase ends with a working, clickable application.** Never leave the app broken between phases.
6. **The developer works on Windows 10 with Visual Studio Code**, so every command given must work in Windows PowerShell.

---

## 2. What the system does

An income payer is an employer who pays salaries. The system has one human actor, the **Income Payer**, and one secondary actor, the **System Scheduler**, which runs once a day. It implements functional requirements FR1 to FR13 of the report:

1. Register, log in, log out and manage a profile.
2. Add, edit, view and deactivate employees, with salary amount and salary frequency.
3. Create salary obligations with due dates, and see upcoming and overdue ones.
4. Show in-application reminders seven days before a due date and again when an obligation becomes overdue.
5. Record payments and keep a payment history (Paid On Time or Paid Late, with delay days).
6. Calculate a rule-based payment-risk score (0 to 85), classify it as Low, Medium or High, and show the factors behind it.
7. Show reports on upcoming, paid, overdue and high-risk obligations.

> **Note:** Reminders appear inside the application only. There is no email and no SMS.

---

## 3. Locked decisions

These were decided in the report or with the supervisor and must not change.

1. **Three-layer architecture:** browser layer (Blade, Tailwind CSS, Alpine.js), application layer (Laravel routes, controllers, requests, models, services, scheduler), data layer (SQLite). Inside it, Laravel MVC.
2. **Authentication with Laravel Breeze (Blade stack)**, plus authorization with Laravel policies.
3. **Money is stored as integer kobo (₦1 = 100 kobo).** The report’s Tables 3.2 to 3.4 say Decimal; keep the report’s column names but make those columns unsigned big integers holding kobo. Never use float or decimal for money.
4. **Overdue status is kept current by a daily scheduled command and also refreshed whenever the dashboard loads.**
5. **There is no partial-payment status.** A payment always settles the full obligation, and payment status is only Paid On Time or Paid Late.
6. **Each risk indicator belongs to exactly one level:** A is employee level, B and C are employer level, D is obligation level (Section 7).
7. **A new employee has no history.** Indicator A is zero for them and the interface says "No history yet".
8. **When a payment is recorded on a recurring obligation, the next obligation is created automatically.**
9. **Stack:** PHP, Laravel, Blade, Tailwind CSS, Alpine.js, SQLite. Development method is Agile with object-oriented analysis and design.
10. **The risk score is an ordinal indicator, not a probability.** The scale is the report’s 85-point raw scale.
11. **If this guide and the report disagree on a detail, the report wins and the agent should be told to adjust.**

---

## 4. Environment and setup

**Requirements:** PHP 8.3 or newer, Composer, Node.js LTS with npm, Git, Windows 10 or newer.

### Setup steps for Phase 0:
1. Create a new Laravel project named `smart-bill-reminder` using Laravel 12.x, the version cited in the report’s references.
2. Use SQLite. Confirm `database/database.sqlite` exists and `.env` has `DB_CONNECTION=sqlite`.
3. Install Laravel Breeze with the Blade stack and Pest for tests. No dark mode, no TypeScript, no Livewire, no Inertia. Do not turn on email verification.
4. Set the application timezone to `Africa/Lagos` in `config/app.php`.
5. Run migrations, run npm install and the asset build, start the server, and confirm register, login and the dashboard page work.
6. Initialise Git and commit after every phase with a clear message.

---

## 5. Data model

These are Tables 3.1 to 3.6 of the report. Foreign keys cascade on delete. Every table has `id`, `created_at` and `updated_at`. Column names below are exactly the report’s.

### 5.1 users (Table 3.1)
Use Breeze’s default users table: `id`, `name`, `email` (unique), `password`, timestamps. Add nothing.

### 5.2 employees (Table 3.2)
| Column | Type | Notes |
| :--- | :--- | :--- |
| `user_id` | foreignId | the income payer who owns the employee |
| `name` | string | required |
| `phone` | string, nullable | |
| `job_title` | string, nullable | |
| `salary_amount` | unsignedBigInteger | kobo |
| `salary_frequency` | string | weekly, biweekly or monthly |
| `status` | string | active or inactive, default active |

### 5.3 salary_obligations (Table 3.3)
| Column | Type | Notes |
| :--- | :--- | :--- |
| `employee_id` | foreignId | the owner is found through the employee |
| `amount` | unsignedBigInteger | kobo |
| `due_date` | date | expected payment date |
| `frequency` | string | weekly, biweekly or monthly |
| `status` | string | upcoming, overdue or paid |
| `notes` | text, nullable | |
| `payment_period` | string, nullable | for example "October 2026"; see Section 16 |

*Add indexes on `(employee_id, status)` and `(due_date)`.*

### 5.4 payments (Table 3.4)
| Column | Type | Notes |
| :--- | :--- | :--- |
| `salary_obligation_id` | foreignId | |
| `amount_paid` | unsignedBigInteger | kobo; must equal the obligation amount |
| `paid_at` | date | actual payment date |
| `status` | string | paid_on_time or paid_late (no partial) |
| `delay_days` | unsignedInteger | default 0 |
| `reference` | string, nullable | |

### 5.5 reminders (Table 3.5)
| Column | Type | Notes |
| :--- | :--- | :--- |
| `user_id` | foreignId | recipient |
| `salary_obligation_id` | foreignId | |
| `type` | string | upcoming or overdue |
| `message` | text | |
| `is_read` | boolean | default false |

*Add a unique index on `(salary_obligation_id, type)`, so each obligation gets at most one reminder of each type.*

### 5.6 risk_assessments (Table 3.6)
| Column | Type | Notes |
| :--- | :--- | :--- |
| `salary_obligation_id` | foreignId | |
| `score` | unsignedTinyInteger | 0 to 85 |
| `risk_level` | string | low, medium or high |
| `late_rate` | decimal(5,2) | percentage, not money |
| `overdue_count` | unsignedInteger | |
| `outstanding_ratio` | decimal(8,2) | percentage, not money |
| `due_proximity_points`| unsignedTinyInteger | |
| `factors` | text, nullable | JSON: the points for A, B, C and D, the no_history flag, and one plain sentence per factor |

*Cast factors to an array in the model.*

### 5.7 Relationships
- `User` has many `Employees` and `Reminders`, and has many `SalaryObligations` through `Employees`.
- `Employee` belongs to `User` and has many `SalaryObligations`.
- `SalaryObligation` belongs to `Employee` and has many `Payments`, `Reminders` and `RiskAssessments`.
- `Payment`, `Reminder` and `RiskAssessment` each belong to a `SalaryObligation` (`Reminder` also belongs to a `User`).

---

## 6. Business rules

### 6.1 Money handling
Create `App\Support\Money` with two static methods:
1. `toKobo(string $naira): int`: Accepts "45000", "45,000" or "45000.50". Strip commas, validate with a regular expression allowing at most two decimal places, and convert with string or integer arithmetic. Never multiply a float.
2. `format(int $kobo): string`: Returns the naira sign, thousands separators and two decimals, for example `₦45,000.50`.

*Forms accept naira. Storage and calculation use kobo. Views call Money::format.*

### 6.2 Obligation status (algorithm 3.8.4)
1. A new obligation is upcoming if its due date is today or later, and overdue if its due date has already passed.
2. An unpaid obligation becomes overdue when `due_date` is before today.
3. Paid is final and never changes.

Put this rule in one service, `App\Services\ObligationStatusSync`, with a method for one user’s obligations and a method for everyone’s. The dashboard and the daily command both call it, so there is one copy of the rule.

### 6.3 Recording a payment (algorithm 3.8.5)
Inside one database transaction, in `App\Services\PaymentRecorder`:
1. Reject the request if the obligation is already paid.
2. Require `amount_paid` to equal the obligation amount.
3. If `paid_at` is on or before `due_date`, status is `paid_on_time` and `delay_days` is 0. Otherwise status is `paid_late` and `delay_days` is the number of days between `due_date` and `paid_at`.
4. Save the payment and set the obligation status to paid.
5. Mark that obligation’s reminders as read.
6. If the employee is active, create the next obligation: same employee, amount, frequency and notes; due date is the paid obligation’s `due_date` plus one week, two weeks, or one month (Carbon `addMonthNoOverflow` for monthly). Its status comes from `ObligationStatusSync`.
7. Run a risk assessment for every open obligation of this employer, because the payment changes the employee’s late rate.

*Because a paid obligation can never be paid again, the next obligation is created exactly once.*

### 6.4 First obligation from the employee form
The employee form (Input Design B) has a *First/next payment date* field. On the add-employee form only, if a date is given, create the employee’s first obligation with the employee’s `salary_amount`, `salary_frequency` and that date, in the same transaction as the employee.

---

## 7. Payment default risk scoring (algorithm 3.8.7, Section 4.7)

The score has four components and a maximum of 85 points. The service is `App\Services\RiskAssessmentService`. All thresholds live in `config/risk.php` so they can be changed without touching code.

| Component | Level | Rule | Points |
| :--- | :--- | :--- | :--- |
| **A. Historical late-payment rate** | Employee | Late payments ÷ completed payments of this employee × 100: up to 10% | 0 |
| | | above 10% up to 30% | 10 |
| | | above 30% up to 50% | 20 |
| | | above 50% | 30 |
| | | no completed payments (`no_history`) | 0 |
| **B. Currently overdue obligations** | Employer | Count of the employer's other overdue obligations, any employee, excluding the one being scored: 0 | 0 |
| | | 1 | 15 |
| | | 2 or more | 25 |
| **C. Outstanding ratio** | Employer | Outstanding amount = sum of the amounts of those same other overdue obligations. Ratio = outstanding ÷ current obligation amount × 100: no outstanding amount | 0 |
| | | above 0 and below 25% | 5 |
| | | 25% up to 50% | 10 |
| | | above 50% | 15 |
| **D. Due proximity** | Obligation | Days from today to due_date: more than 7 | 0 |
| | | 4 to 7 | 5 |
| | | 1 to 3 | 10 |
| | | due today or overdue | 15 |

**Risk Score = A + B + C + D.**  
Classification:
- **0 to 28:** Low
- **29 to 56:** Medium
- **57 to 85:** High

### Rules for the service:
1. It assesses open obligations only (upcoming or overdue). A paid obligation keeps its last assessment.
2. "Late" means a payment with status `paid_late`.
3. Use integer arithmetic for money comparisons, for example outstanding × 100 against amount × 25, so no float is involved. `late_rate` and `outstanding_ratio` are rounded to two decimals only for storage and display.
4. It saves a `risk_assessments` row with the score, level, late_rate, overdue_count, outstanding_ratio, due_proximity_points and the factors. A new row is added only when the result differs from the latest row for that obligation, so history is kept without daily duplicates.
5. The factors text must read as plain sentences, for example:
   - "Employee paid late in 2 of 4 payments (50%): 20 points"
   - "No history yet for this employee: 0 points"
6. Run it when an obligation is created, after a payment (6.3, step 7), in the daily command, when the dashboard loads (for that user’s open obligations), and when the user presses *Run assessment*.
7. Write a unit test for every row of the table above, with fixed dates.

---

## 8. Authentication and authorization (Section 4.4.1)

1. Use Breeze’s registration (name, email, password, confirmation), login, logout, password reset and profile pages as they are. The profile page covers the Manage profile use case.
2. Every route except the welcome page, login, register and password reset is behind the `auth` middleware. Passwords are hashed (Breeze default).
3. Create `EmployeePolicy`, `SalaryObligationPolicy` and `ReminderPolicy`.
   - An employee is allowed only when `employee.user_id` equals the signed-in user’s id.
   - An obligation is allowed only when its employee’s `user_id` equals the signed-in user’s id.
   - A reminder is allowed only when `reminder.user_id` matches.
   - Payments and risk assessments are reached through their obligation and use the same check. Use `$this->authorize` in controllers.
4. Every list query is scoped to the signed-in user (use the User to Employees to SalaryObligations relationships). A user must never see or change another user’s records, even by editing the URL.
5. Write feature tests that sign in as user A and try to view, edit and pay user B’s records. Each must return 403 or 404.

---

## 9. Screens and routes

Use Breeze’s app layout with a top navigation bar: Dashboard, Employees, Salary Obligations, Payments, Reminders (with an unread count), Risk Assessment, Reports, Profile, Logout. Style with Tailwind. Use Alpine.js only for dropdowns, the mobile menu, confirmation modals and showing or hiding risk factors.

| Screen | Route | Purpose |
| :--- | :--- | :--- |
| Welcome | `GET /` | short description with login and register buttons |
| Dashboard | `GET /dashboard` | overview (below) |
| Employees | `/employees`: index, create, store, show, edit, update; `PATCH /employees/{employee}/status` | list, add, edit, view, deactivate or reactivate (no delete) |
| Salary obligations | `/salary-obligations`: index, create, store, show | list with filters for status and employee, create, view |
| Record payment | `GET` and `POST /salary-obligations/{salaryObligation}/payments` | payment form and save |
| Payment history | `GET /payments` | all payments |
| Reminders | `GET /reminders`; `PATCH /reminders/{reminder}/read`; `POST /reminders/read-all` | list and mark as read |
| Risk assessment | `GET /risk-assessments`; `POST /salary-obligations/{salaryObligation}/risk-assessment` | latest assessment of each open obligation; run an assessment |
| Reports | `GET /reports` with type, employee_id, from and to | structured report with totals |

### 9.1 Input forms (Section 3.6, Figures 3.3 to 3.7)
- **Registration:** name, email, password, password confirmation
- **Login:** email, password
- **Employee:** name, phone (optional), job role, salary amount in naira, salary frequency, first/next payment date (add form only, optional), status (edit form)
- **Salary obligation:** employee (active employees only), amount in naira (defaults to employee's salary), due date, payment period (optional), notes (optional); frequency is copied from employee's salary frequency (not typed).
- **Record payment:** obligation (fixed by URL), amount paid (prefilled, must equal obligation amount), payment date (defaults to today), payment note or reference (optional). Status is calculated, never typed.

*Validate every form with a Form Request class.*

### 9.2 Output screens (Section 3.7, Figures 3.8 to 3.12)
1. **Dashboard:** cards for total employees (active), amount due soon (upcoming obligations due within the reminder window), number of upcoming payments, number of overdue payments, and number of high-risk obligations. Below cards: upcoming payments table (Employee, Amount, Due Date, Days Remaining, Risk), overdue payments table (Employee, Amount, Due Date, Days Overdue, Status), a panel of unread reminders, and quick links to employees, salary scheduling, payments and reports.
2. **Risk assessment page:** table with Employee, Obligation, Score, Risk Level, Main Factors, and a Run assessment button on each row.
3. **Payment history:** table with Employee, Expected Date, Paid Date, Amount, Status, Delay Days.
4. **Reports:** types upcoming, paid, overdue and high_risk, optional employee filter and date range (on due date), with a count and total amount at the bottom.
5. **Coloured badges:** green for Low and Paid On Time, amber for Medium and Upcoming, red for High, Overdue and Paid Late. Overdue and high-risk rows are visually prominent.
*Show every amount with `Money::format`.*

---

## 10. Reminders and scheduler (algorithm 3.8.6, Section 4.4.6)

1. Put the reminder window in `config/reminders.php` as `window_days = 7`.
2. `App\Services\ReminderGenerator` goes through unpaid obligations. With days remaining = due_date minus today: if 0 to window_days, create an upcoming reminder; if due_date is before today, create an overdue reminder. The reminder’s user is the employee’s owner.
3. Create a reminder only if none exists yet for that obligation and type (the unique index enforces this), so no duplicates appear. When an obligation is paid, its reminders are marked as read (6.3).
4. Messages:
   - "Salary for {employee} ({amount}) is due on {date}."
   - "Salary for {employee} ({amount}) was due on {date} and is overdue."
5. Create the Artisan command `bills:process-daily`. It runs, in order: `ObligationStatusSync` for everyone, `ReminderGenerator` for everyone, and a risk assessment for every open obligation. Register it in `routes/console.php` to run daily at 06:00.
6. The command accepts an optional `--date=YYYY-MM-DD` option that pretends today is that date, so reminders and overdue changes can be demonstrated at the project defence. For a local demonstration on Windows, run `php artisan schedule:work`.
7. When the dashboard loads, it also runs `ObligationStatusSync`, `ReminderGenerator` and the risk refresh for the signed-in user only, so the demo works even if the scheduler is not running (locked decision 4).

---

## 11. Build phases

After each phase the agent stops, lists the files created or changed, shows test results, and waits for approval.

- **Phase 0: Project setup**  
  Do everything in Section 4. Done when register, login, logout and an empty dashboard work and the Pest example tests pass.
- **Phase 1: Layout and welcome page**  
  Navigation bar, Tailwind layout, welcome page, flash messages, reusable Blade components for status badge and risk badge. Done when a new user can register and sees the navigation.
- **Phase 2: Employees**  
  Migration, model, factory, EmployeePolicy, Form Requests, controller and views. `App\Support\Money` with unit tests. Add, edit, view, deactivate and reactivate (no delete). Done when user can manage employees, cannot see another's employees, and tests pass.
- **Phase 3: Salary obligations and status**  
  Migration, model, factory, SalaryObligationPolicy, Form Requests, controller (index, create, store, show) and views, with filters for status and employee. `ObligationStatusSync` with unit tests. First/next payment date on add-employee form creates first obligation.
- **Phase 4: Payments and recurrence**  
  Payments migration and model, `PaymentRecorder`, payment form with confirmation modal, payment history page. Paid On Time and Paid Late with delay days, automatic next obligation creation exactly once.
- **Phase 5: Risk assessment**  
  `config/risk.php`, `RiskAssessmentService`, `risk_assessments` table, risk assessment page and Run assessment button. Risk badge in obligation lists and factors on obligation page. Unit tests for every row of Section 7 table.
- **Phase 6: Reminders and scheduler**  
  Reminders migration and model, `ReminderPolicy`, `ReminderGenerator`, reminders page, unread count in nav, and `bills:process-daily` command with `--date`. No duplicate reminders.
- **Phase 7: Dashboard**  
  Build cards, tables and reminder panel from Section 9.2. Numbers match data and page loads without errors for user with no data.
- **Phase 8: Reports**  
  Reports page with 4 types (upcoming, paid, overdue, high_risk), employee filter and date range, with counts and total amounts.
- **Phase 9: Seed data, tests and polish**  
  `DemoSeeder` (user `demo@example.com`, password `password`, 6 employees, ~30 obligations, various history and risk levels). Run full test suite. Check phone width. Short README. Mapping table of Tests 1 to 14 of Table 4.1 to Pest tests.

---

## 12. Testing (Sections 4.8 and 4.9)

Use Pest, fixed dates (`Carbon::setTestNow`) and factories so results never depend on the real date.

### Feature tests (Table 4.1):
1. Register with valid information: account created, redirect to dashboard.
2. Register with an existing email: validation error, no duplicate.
3. Login with valid credentials: dashboard shown.
4. Login with an invalid password: authentication failure.
5. Add an employee with valid salary information: saved and listed.
6. Create a salary obligation: appears in upcoming payments.
7. Create an obligation with an invalid amount: validation error.
8. Record an on-time payment: Paid On Time, delay days 0.
9. Record a late payment: Paid Late, delay days calculated.
10. View upcoming reminders: obligations inside the window appear.
11. View overdue reminders: unpaid obligations after the due date appear.
12. Run a risk assessment: score, level and factors are shown.
13. View payment history: previous payments are listed.
14. View a report: selected records and totals are shown.

### Additional tests:
Ownership checks (user A vs user B), double payment rejected, next obligation created exactly once, `bills:process-daily` creates no duplicate reminders, and unit tests for `Money`, `ObligationStatusSync` and every row of the risk table.

---

## 13. Coding conventions

1. Follow Laravel conventions: singular models, plural tables, resource controllers, Form Requests for validation.
2. Keep controllers thin. Business rules live in `App\Services` classes (`ObligationStatusSync`, `PaymentRecorder`, `ReminderGenerator`, `RiskAssessmentService`).
3. Use Eloquent, not raw SQL. Never use float arithmetic on money.
4. Use constants or enums for status, risk level, type and frequency values.
5. Comments only where the reason is not obvious from the code.
6. Plain, clear Blade views with no front-end build complexity beyond what Breeze provides.

---

## 14. Screenshots for Chapter 4

After Phase 9, run `migrate:fresh --seed` and capture these screens:
1. Figure 4.2: Registration page
2. Figure 4.3: Login page
3. Figure 4.4: Dashboard
4. Figure 4.5: Employee list
5. Figure 4.6: Add employee form
6. Figure 4.7: Salary schedule form
7. Figure 4.8: Record payment form
8. Figure 4.9: Reminder panel
9. Figure 4.10: Risk assessment result, with the factors visible
10. Figure 4.11: Payment report

---

## 15. Starter prompt for Antigravity

Paste this into the agent after adding `IMPLEMENTATION.md` to the project folder:

> "Read IMPLEMENTATION.md fully and follow it exactly. We are building the Smart Bill Reminder system with Laravel, Breeze (Blade), Tailwind, Alpine.js and SQLite, on Windows. Start with Phase 0 only. Do not add anything the document does not list and do not change the locked decisions in Section 3. When Phase 0 is done, run the tests, tell me what you built and which files changed, and wait for my approval before starting Phase 1."

For each later phase, say:
> "Phase N approved. Start Phase N+1 from IMPLEMENTATION.md and follow the same rules."

---

## 16. Remaining differences from the report

The report text has been updated to match this guide (kobo storage, no partial status, the `payment_period` column, the 85-point scale and the employer-level indicators B and C). Two small points remain in the report’s figures, which cannot be edited as text:
1. **Fig. 3.22 (class diagram):** does not show `payment_period` on `SalaryObligation`. Redraw the class box to include it.
2. **Fig. 3.7 (Record Payment wireframe):** shows a Payment status field. In the software this is filled in automatically as Paid On Time or Paid Late, so show it as read-only.
