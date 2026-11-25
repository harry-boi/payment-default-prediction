<?php

namespace App\Models;

use App\Enums\ReminderType;
use Database\Factories\ReminderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Reminder extends Model
{
    /** @use HasFactory<ReminderFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'salary_obligation_id',
        'type',
        'message',
        'is_read',
        'emailed_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ReminderType::class,
            'is_read' => 'boolean',
            'emailed_at' => 'datetime',
        ];
    }

    /**
     * The user (income payer) who received this reminder.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The salary obligation associated with this reminder.
     */
    public function salaryObligation(): BelongsTo
    {
        return $this->belongsTo(SalaryObligation::class);
    }

    /**
     * The employee associated with this reminder.
     */
    public function employee(): HasOneThrough
    {
        return $this->hasOneThrough(
            Employee::class,
            SalaryObligation::class,
            'id', // Foreign key on salary_obligations table...
            'id', // Foreign key on employees table...
            'salary_obligation_id', // Local key on reminders table...
            'employee_id' // Local key on salary_obligations table...
        );
    }

    /**
     * Mark this reminder as read.
     */
    public function markAsRead(): bool
    {
        return $this->update(['is_read' => true]);
    }

    /**
     * Mark this reminder as delivered by email.
     */
    public function markAsEmailed(): bool
    {
        return $this->update(['emailed_at' => now()]);
    }

    /**
     * Check if reminder is for upcoming payment.
     */
    public function isUpcoming(): bool
    {
        return $this->type === ReminderType::Upcoming;
    }

    /**
     * Check if reminder is for overdue payment.
     */
    public function isOverdue(): bool
    {
        return $this->type === ReminderType::Overdue;
    }
}
