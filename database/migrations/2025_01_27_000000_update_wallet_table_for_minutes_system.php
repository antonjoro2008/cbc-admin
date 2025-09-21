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
        Schema::table('wallets', function (Blueprint $table) {
            // Change balance column to decimal with 2 decimal places
            $table->decimal('balance', 10, 2)->change();
            
            // Add available_minutes column if it doesn't exist
            if (!Schema::hasColumn('wallets', 'available_minutes')) {
                $table->decimal('available_minutes', 10, 2)->default(0.00);
            } else {
                // If it exists, change it to decimal with 2 decimal places
                $table->decimal('available_minutes', 10, 2)->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            // Revert balance column to integer
            $table->integer('balance')->change();
            
            // Remove available_minutes column
            $table->dropColumn('available_minutes');
        });
    }
};
