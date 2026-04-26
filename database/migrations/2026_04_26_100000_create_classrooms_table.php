<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classrooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('grade_level', 50);
            $table->timestamps();

            $table->index(['institution_id', 'grade_level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classrooms');
    }
};
