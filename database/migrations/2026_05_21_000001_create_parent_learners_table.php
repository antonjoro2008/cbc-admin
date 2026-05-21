<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('parent_learners')) {
            Schema::create('parent_learners', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('student_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('name');
                $table->string('grade_level', 45)->nullable();
                $table->timestamps();

                $table->index(['user_id', 'student_user_id']);
            });

            return;
        }

        if (! Schema::hasColumn('parent_learners', 'student_user_id')) {
            Schema::table('parent_learners', function (Blueprint $table) {
                $table->foreignId('student_user_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('parent_learners') && Schema::hasColumn('parent_learners', 'student_user_id')) {
            Schema::table('parent_learners', function (Blueprint $table) {
                $table->dropConstrainedForeignId('student_user_id');
            });
        }
    }
};
