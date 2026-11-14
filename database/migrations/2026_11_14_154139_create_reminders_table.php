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
        Schema::create('reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('salary_obligation_id')->constrained('salary_obligations')->cascadeOnDelete();
            $table->string('type'); // upcoming, overdue
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->timestamps();

            // Enforce at most one reminder of each type per obligation (Section 5.5 & 10.3)
            $table->unique(['salary_obligation_id', 'type']);
            $table->index(['user_id', 'is_read']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reminders');
    }
};
