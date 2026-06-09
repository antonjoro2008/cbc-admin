<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_book_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('institution_id')->index();
            $table->foreignId('classroom_id')->nullable()->constrained('classrooms')->nullOnDelete();
            $table->string('period_type', 20);
            $table->date('period_start');
            $table->date('period_end')->nullable();
            $table->string('learning_area');
            $table->string('strand')->nullable();
            $table->text('activity_observed')->nullable();
            $table->unsignedTinyInteger('score_percent')->nullable();
            $table->string('cbe_level', 4)->nullable();
            $table->text('strengths')->nullable();
            $table->text('gaps_to_address')->nullable();
            $table->text('next_steps')->nullable();
            $table->text('teacher_notes')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'period_type', 'period_start'], 'abook_student_period_idx');
            $table->index(['teacher_id', 'period_start'], 'abook_teacher_period_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_book_entries');
    }
};
