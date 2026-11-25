<?php

use App\Enums\ObligationStatus;
use App\Enums\ReminderType;
use App\Models\Employee;
use App\Models\Reminder;
use App\Models\SalaryObligation;
use App\Models\User;
use App\Services\ReminderEmailService;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;

function configureResendForEmailTest(): void
{
    config()->set('services.resend.key', 're_test_key');
    config()->set('services.resend.from', 'alerts@edgloe.com');
    config()->set('services.resend.from_name', 'Smart Bill Reminder');
}

function makeReminderForEmailTest(User $user, ReminderType $type, ?string $emailedAt = null): Reminder
{
    $employee = Employee::factory()->create([
        'user_id' => $user->id,
        'name' => 'Bassey Okon',
    ]);

    $obligation = SalaryObligation::factory()->create([
        'employee_id' => $employee->id,
        'amount' => 4000000,
        'due_date' => $type === ReminderType::Overdue ? now()->subDays(2)->toDateString() : now()->addDays(2)->toDateString(),
        'status' => $type === ReminderType::Overdue ? ObligationStatus::Overdue : ObligationStatus::Upcoming,
    ]);

    return Reminder::factory()->create([
        'user_id' => $user->id,
        'salary_obligation_id' => $obligation->id,
        'type' => $type,
        'emailed_at' => $emailedAt,
    ]);
}

test('email summary sends a digest through resend and stamps the reminders', function () {
    configureResendForEmailTest();
    Http::fake(['api.resend.com/*' => Http::response(['id' => 'email_123'], 200)]);

    $user = User::factory()->create(['email' => 'owner@example.com']);
    $reminder = makeReminderForEmailTest($user, ReminderType::Overdue);

    $sentCount = app(ReminderEmailService::class)->sendSummaryForUser($user);

    expect($sentCount)->toBe(1)
        ->and($reminder->fresh()->emailed_at)->not->toBeNull();

    Http::assertSent(function (ClientRequest $request) {
        return $request->url() === 'https://api.resend.com/emails'
            && $request['to'] === ['owner@example.com']
            && str_contains($request['subject'], '1 overdue')
            && str_contains($request['text'], 'Bassey Okon');
    });
});

test('daily digest path emails only reminders that have never been emailed', function () {
    configureResendForEmailTest();
    Http::fake(['api.resend.com/*' => Http::response(['id' => 'email_124'], 200)]);

    $user = User::factory()->create();
    $alreadyEmailed = makeReminderForEmailTest($user, ReminderType::Upcoming, now()->subDay()->toDateTimeString());
    $fresh = makeReminderForEmailTest($user, ReminderType::Upcoming);

    $digestsSent = app(ReminderEmailService::class)->sendAllNewDigests();

    expect($digestsSent)->toBe(1)
        ->and($fresh->fresh()->emailed_at)->not->toBeNull()
        ->and($alreadyEmailed->fresh()->emailed_at->toDateTimeString())->toBe(now()->subDay()->toDateTimeString());

    Http::assertSentCount(1);
});

test('email summary route reports a clear error when email alerts are not configured', function () {
    config()->set('services.resend.key', null);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('reminders.email-summary'))
        ->assertSessionHas('error');
});

test('email summary route emails the signed in user when configured', function () {
    configureResendForEmailTest();
    Http::fake(['api.resend.com/*' => Http::response(['id' => 'email_125'], 200)]);

    $user = User::factory()->create();
    makeReminderForEmailTest($user, ReminderType::Upcoming);

    $this->actingAs($user)
        ->post(route('reminders.email-summary'))
        ->assertSessionHas('success');

    Http::assertSentCount(1);
});
