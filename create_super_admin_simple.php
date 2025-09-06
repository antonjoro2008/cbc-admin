<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// Bootstrap Laravel
$app = Application::configure(basePath: dirname(__FILE__))
    ->withRouting(
        web: __DIR__.'/routes/web.php',
        commands: __DIR__.'/bootstrap/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

try {
    echo "Starting to create super admin user...\n";
    
    // Check if institution exists
    $institutionExists = DB::table('institutions')->where('code', 'SYS')->exists();
    
    if (!$institutionExists) {
        echo "Creating default institution...\n";
        DB::table('institutions')->insert([
            'name' => 'System Institution',
            'code' => 'SYS',
            'type' => 'system',
            'address' => 'System Address',
            'phone' => '0000000000',
            'email' => 'system@example.com',
            'website' => 'https://example.com',
            'logo_path' => 'images/logo.png',
            'banner_path' => null,
            'primary_color' => '#705EBC',
            'secondary_color' => '#FB953B',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        echo "Institution created successfully.\n";
    } else {
        echo "Institution already exists.\n";
    }
    
    $institution = DB::table('institutions')->where('code', 'SYS')->first();
    echo "Institution: {$institution->name}\n";
    
    // Check if super admin user exists
    $userExists = DB::table('users')->where('email', 'antonjoro2008@gmail.com')->exists();
    
    if (!$userExists) {
        echo "Creating super admin user...\n";
        DB::table('users')->insert([
            'name' => 'Super Admin',
            'email' => 'antonjoro2008@gmail.com',
            'password' => Hash::make('22222222'),
            'user_type' => 'admin',
            'grade_level' => null,
            'institution_id' => $institution->id,
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        echo "Super Admin user created successfully.\n";
    } else {
        echo "Super Admin user already exists.\n";
    }
    
    $superAdmin = DB::table('users')->where('email', 'antonjoro2008@gmail.com')->first();
    echo "Super Admin: {$superAdmin->name}\n";
    
    // Check if wallet exists
    $walletExists = DB::table('wallets')->where('user_id', $superAdmin->id)->exists();
    
    if (!$walletExists) {
        echo "Creating wallet...\n";
        DB::table('wallets')->insert([
            'user_id' => $superAdmin->id,
            'balance' => 1000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        echo "Wallet created successfully.\n";
    } else {
        echo "Wallet already exists.\n";
    }
    
    $wallet = DB::table('wallets')->where('user_id', $superAdmin->id)->first();
    echo "Wallet balance: {$wallet->balance}\n";
    
    echo "\n=== SUPER ADMIN USER CREATED SUCCESSFULLY ===\n";
    echo "Email: antonjoro2008@gmail.com\n";
    echo "Password: 22222222\n";
    echo "Institution: {$institution->name}\n";
    echo "Wallet Balance: {$wallet->balance} tokens\n";
    echo "==========================================\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
