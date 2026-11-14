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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salary_obligation_id')->constrained('salary_obligations')->cascadeOnDelete();
            $table->unsignedBigInteger('amount_paid'); // in kobo
            $table->date('paid_at');
            $table->string('status'); // paid_on_time, paid_late
            $table->unsignedInteger('delay_days')->default(0);
            $table->string('reference')->nullable();
            $table->timestamps();

            $table->index('paid_at');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
