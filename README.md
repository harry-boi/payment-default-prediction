# Smart Bill Reminder of Payment Default Prediction System for Income Payers

**HND Final Year Project**  
**Author:** Effiong Dominic Eyoh  
**Institution:** Akwa Ibom State Polytechnic, Ikot Osurua  
**Department:** Computer Science  
**Academic Session:** 2025/2026  

---

## 1. Project Overview

The **Smart Bill Reminder of Payment Default Prediction System for Income Payers** is a financial intelligence and automated reminder platform designed to help employers and income payers systematically organize recurring employee salary obligations, track payment histories, calculate forward-looking default risks, and eliminate late payment penalties.

### Key Architectural Highlights
- **Deterministic 85-Point Risk Assessment Engine (Algorithm 3.8.7):** Calculates payment default risk across four weighted components (Employee late payment history, employer-wide overdue volume, outstanding overdue balance ratio, and due proximity) without external black-box models.
- **Zero-Float Currency Architecture:** Stored exclusively in integer **Kobo** (`unsignedBigInteger`) to eliminate IEEE 754 floating-point rounding errors.
- **Automatic Status Synchronization & Daily Reminders:** Automatically transitions bills between `upcoming` and `overdue` states, generating non-duplicate upcoming (7-day window) and overdue notifications.
- **Resilient Fallback Execution:** In addition to the daily cron scheduler (`php artisan bills:process-daily`), every user visit to the dashboard automatically evaluates status synchronizations, risk assessments, and reminder checks on the fly.

---

## 2. Technology Stack

- **Framework:** Laravel 12 / 13 (PHP 8.3+)
- **Database:** SQLite
- **Authentication:** Laravel Breeze (Blade stack)
- **Frontend:** Tailwind CSS, Alpine.js, Vite
- **Testing Suite:** Pest PHP & PHPUnit

---

## 3. Installation & Local Setup

### Prerequisites
- PHP 8.3+ with `pdo_sqlite`, `sqlite3`, `mbstring`, `openssl`, `tokenizer`, `xml`, and `ctype` extensions enabled.
- Composer 2.8+
- Node.js 20+ & npm

### Setup Steps

1. **Clone repository & install dependencies:**
   ```bash
   git clone <repository-url>
   cd payment_default_prediction
   composer install
   npm install
   ```

2. **Environment configuration:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   Ensure `.env` has:
   ```dotenv
   DB_CONNECTION=sqlite
   APP_TIMEZONE=Africa/Lagos
   ```

3. **Database Migration & Demo Seeding:**
   ```bash
   php artisan migrate:fresh --seed
   ```

4. **Build Frontend Assets:**
   ```bash
   npm run build
   ```

5. **Start Application Server:**
   ```bash
   php artisan serve
   ```
   Access the system at [http://localhost:8000](http://localhost:8000).

---

## 4. Demo Credentials

The database seeder automatically initializes a complete demonstration environment with 6 realistic employees, 30 salary obligations across various payment cycles, payment histories, active alerts, and multi-tier risk ratings (Low, Medium, High).

- **URL:** [http://localhost:8000/login](http://localhost:8000/login)
- **Email:** `demo@example.com`
- **Password:** `password`
- **Income Payer / User:** Effiong Dominic Eyoh

---

## 5. Automated Daily Processing Command

The daily processing job executes daily at `06:00` (configured in `routes/console.php`). It can also be triggered manually with an optional simulation date:

```bash
# Process for today
php artisan bills:process-daily

# Simulate processing for a specific date
php artisan bills:process-daily --date=2026-11-20
```

---

## 6. Verification Matrix (Report Table 4.1)

All 14 test cases specified in **Table 4.1** of Chapter 4 have been implemented and verified using Pest PHP.

| Test # | Test Case / Description | Pest Test Suite File | Test Method / Name | Status |
|---|---|---|---|---|
| **1** | Register with valid information: account created, redirect to dashboard | `tests/Feature/Auth/RegistrationTest.php` | `new users can register` | **PASSED** |
| **2** | Register with an existing email: validation error, no duplicate account | `tests/Feature/Auth/RegistrationTest.php` | `test 2: register with an existing email validation error and no duplicate` | **PASSED** |
| **3** | Login with valid credentials: authenticated and dashboard shown | `tests/Feature/Auth/AuthenticationTest.php` | `users can authenticate using the login screen` | **PASSED** |
| **4** | Login with an invalid password: authentication failure | `tests/Feature/Auth/AuthenticationTest.php` | `users can not authenticate with invalid password` | **PASSED** |
| **5** | Add an employee with valid salary information: saved and listed | `tests/Feature/EmployeeTest.php` | `test 5: add an employee with valid salary information saved and listed` | **PASSED** |
| **6** | Create a salary obligation: appears in upcoming payments | `tests/Feature/SalaryObligationTest.php` | `test 6: create a salary obligation appears in upcoming payments` | **PASSED** |
| **7** | Create an obligation with an invalid amount: validation error | `tests/Feature/SalaryObligationTest.php` | `test 7: create an obligation with an invalid amount validation error` | **PASSED** |
| **8** | Record an on-time payment: Paid On Time, delay days 0 | `tests/Feature/PaymentTest.php` | `test 8: record an on-time payment sets Paid On Time and delay days 0` | **PASSED** |
| **9** | Record a late payment: Paid Late, delay days calculated | `tests/Feature/PaymentTest.php` | `test 9: record a late payment sets Paid Late and calculates delay days` | **PASSED** |
| **10** | View upcoming reminders: obligations inside window appear | `tests/Feature/ReminderTest.php` | `test 10: view upcoming reminders obligations inside window appear` | **PASSED** |
| **11** | View overdue reminders: unpaid obligations after due date appear | `tests/Feature/ReminderTest.php` | `test 11: view overdue reminders unpaid obligations after due date appear` | **PASSED** |
| **12** | Run a risk assessment: score, level and factor sentences are shown | `tests/Feature/RiskAssessmentTest.php` | `test 12: run a risk assessment score level and factors are shown` | **PASSED** |
| **13** | View payment history: previous payments are listed with delay days | `tests/Feature/PaymentTest.php` | `test 13: view payment history previous payments are listed` | **PASSED** |
| **14** | View a report: selected records and totals are shown | `tests/Feature/ReportTest.php` | `test 14: view a report selected records and totals are shown` | **PASSED** |

### Running the Tests
```bash
php artisan test --compact
```
*Current test suite status: 98 tests passed, 369 assertions, 0 failures.*

---

## 7. Report Screenshots Guide (Chapter 4)

After seeding the demo database (`php artisan migrate:fresh --seed`), capture screenshots corresponding to Chapter 4 figures:

1. **Figure 4.2:** Registration page (`/register`)
2. **Figure 4.3:** Login page (`/login`)
3. **Figure 4.4:** Dashboard (`/dashboard`) showing metrics, overdue alert panel, upcoming bills, and quick actions
4. **Figure 4.5:** Employee list (`/employees`) with 6 seeded employees, job roles, salary badges, and action menus
5. **Figure 4.6:** Add employee form (`/employees/create`) with salary input and first obligation scheduling toggle
6. **Figure 4.7:** Salary schedule form (`/salary-obligations/create`)
7. **Figure 4.8:** Record payment form (`/payments/create?obligation_id=...`) showing read-only payment status calculation and confirmation modal
8. **Figure 4.9:** Reminders & Alerts panel (`/reminders`) showing unread notifications, overdue alerts, and mark-as-read buttons
9. **Figure 4.10:** Risk assessment results (`/risk-assessments`) showing scores, badges, and expandable factor breakdowns
10. **Figure 4.11:** Payment report (`/reports`) showing filters, summary metrics, print view, and bottom aggregates
