<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('salary_obligations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('amount'); // stored in kobo (₦1 = 100 kobo)
            $table->date('due_date');
            $table->string('frequency'); // weekly, biweekly, monthly
            $table->string('status')->default('upcoming'); // upcoming, overdue, paid
            $table->text('notes')->nullable();
            $table->string('payment_period')->nullable(); // e.g. "October 2026"
            $table->timestamps();

            // Indexes per Section 5.3
            $table->index(['employee_id', 'status']);
            $table->index('due_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salary_obligations');
    }
};
