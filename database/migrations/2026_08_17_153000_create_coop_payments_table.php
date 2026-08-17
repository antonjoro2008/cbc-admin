<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coop_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('message_reference')->nullable()->unique();
            $table->string('transaction_id')->nullable()->unique();
            $table->string('payment_ref')->nullable()->index();
            $table->string('phone_number')->nullable();
            $table->string('account_number')->nullable();
            $table->string('event_type')->nullable();
            $table->string('narration')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('currency', 8)->default('KES');
            $table->string('status')->default('pending');
            $table->string('source')->default('stk');
            $table->json('stk_response')->nullable();
            $table->json('callback_payload')->nullable();
            $table->json('ipn_payload')->nullable();
            $table->timestamp('transaction_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coop_payments');
    }
};
