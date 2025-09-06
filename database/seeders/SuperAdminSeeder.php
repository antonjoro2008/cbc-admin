<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Institution;
use App\Models\Wallet;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default institution
        $institution = Institution::firstOrCreate(
            ['code' => 'SYS'],
            [
                'name' => 'System Institution',
                'type' => 'system',
                'address' => 'System Address',
                'phone' => '0000000000',
                'email' => 'system@example.com',
                'website' => 'https://example.com',
                'logo_path' => null,
                'banner_path' => null,
                'primary_color' => '#705EBC',
                'secondary_color' => '#FB953B',
                'is_active' => true,
            ]
        );

        // Create super admin user
        $superAdmin = User::firstOrCreate(
            ['email' => 'antonjoro2008@gmail.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('22222222'),
                'user_type' => 'admin',
                'grade_level' => null,
                'institution_id' => $institution->id,
                'email_verified_at' => now(),
            ]
        );

        // Create wallet for super admin if it doesn't exist
        Wallet::firstOrCreate(
            ['user_id' => $superAdmin->id],
            [
                'balance' => 1000, // Give some initial tokens
            ]
        );

        $this->command->info('Super Admin user created successfully!');
        $this->command->info('Email: antonjoro2008@gmail.com');
        $this->command->info('Password: 22222222');
    }
}
