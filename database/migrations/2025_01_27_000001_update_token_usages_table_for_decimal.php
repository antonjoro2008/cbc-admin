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
        Schema::table('token_usages', function (Blueprint $table) {
            // Change tokens_used column to decimal with 2 decimal places
            $table->decimal('tokens_used', 10, 2)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('token_usages', function (Blueprint $table) {
            // Revert tokens_used column to integer
            $table->integer('tokens_used')->change();
        });
    }
};
