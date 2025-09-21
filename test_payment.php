<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing payment lookup...\n";

$payment = App\Models\Payment::where('reference', 'CBC00000022-254726498973')->first();

if ($payment) {
    echo "Payment found:\n";
    echo "ID: " . $payment->id . "\n";
    echo "Status: " . $payment->status . "\n";
    echo "Reference: " . $payment->reference . "\n";
    echo "Amount: " . $payment->amount . "\n";
    echo "Tokens: " . $payment->tokens . "\n";
    echo "User ID: " . $payment->user_id . "\n";
} else {
    echo "Payment not found\n";
    
    // Let's check what payments exist
    echo "\nAll payments in database:\n";
    $payments = App\Models\Payment::all();
    foreach ($payments as $p) {
        echo "ID: {$p->id}, Reference: {$p->reference}, Status: {$p->status}\n";
    }
}
