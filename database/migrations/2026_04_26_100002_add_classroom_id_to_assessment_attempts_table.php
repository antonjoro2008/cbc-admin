<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('assessment_attempts')) {
            return;
        }

        Schema::table('assessment_attempts', function (Blueprint $table) {
            if (!Schema::hasColumn('assessment_attempts', 'classroom_id')) {
                $table->foreignId('classroom_id')->nullable()->constrained('classrooms')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('assessment_attempts')) {
            return;
        }

        Schema::table('assessment_attempts', function (Blueprint $table) {
            if (Schema::hasColumn('assessment_attempts', 'classroom_id')) {
                $table->dropForeign(['classroom_id']);
                $table->dropColumn('classroom_id');
            }
        });
    }
};
