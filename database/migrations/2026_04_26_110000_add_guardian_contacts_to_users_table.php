<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'guardian_email')) {
                $table->string('guardian_email')->nullable();
            }
            if (!Schema::hasColumn('users', 'guardian_phone')) {
                $table->string('guardian_phone', 50)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'guardian_email')) {
                $table->dropColumn('guardian_email');
            }
            if (Schema::hasColumn('users', 'guardian_phone')) {
                $table->dropColumn('guardian_phone');
            }
        });
    }
};

