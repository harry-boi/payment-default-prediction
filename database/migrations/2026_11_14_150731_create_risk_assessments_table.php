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
        Schema::create('risk_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salary_obligation_id')->constrained('salary_obligations')->cascadeOnDelete();
            $table->unsignedTinyInteger('score'); // 0 to 85
            $table->string('risk_level'); // low, medium, high
            $table->decimal('late_rate', 5, 2); // percentage
            $table->unsignedInteger('overdue_count'); // count of employer's other overdue obligations
            $table->decimal('outstanding_ratio', 8, 2); // percentage
            $table->unsignedTinyInteger('due_proximity_points'); // 0, 5, 10, or 15
            $table->text('factors')->nullable(); // JSON: points for A, B, C, D, no_history flag, and plain sentences
            $table->timestamps();

            $table->index(['salary_obligation_id', 'created_at']);
            $table->index('risk_level');
            $table->index('score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('risk_assessments');
    }
};
