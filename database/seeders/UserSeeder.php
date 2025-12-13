<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ایجاد Super Admin
        User::create([
            'name' => 'مدیر',
            'last_name' => 'کل سیستم',
            'email' => 'superadmin@dashboard.local',
            'national_code' => '1234567890',
            'mobile' => '09123456789',
            'username' => 'superadmin',
            'password' => Hash::make('superadmin123'),
            'role' => 'super_admin',
        ]);

        // ایجاد اپراتورها
        User::create([
            'name' => 'علی',
            'last_name' => 'احمدی',
            'email' => 'operator1@dashboard.local',
            'national_code' => '1111111111',
            'mobile' => '09111111111',
            'username' => 'operator1',
            'password' => Hash::make('operator123'),
            'role' => 'operator',
        ]);

        User::create([
            'name' => 'محمد',
            'last_name' => 'رضایی',
            'email' => 'operator2@dashboard.local',
            'national_code' => '2222222222',
            'mobile' => '09222222222',
            'username' => 'operator2',
            'password' => Hash::make('operator123'),
            'role' => 'operator',
        ]);

        User::create([
            'name' => 'فاطمه',
            'last_name' => 'کریمی',
            'email' => 'operator3@dashboard.local',
            'national_code' => '3333333333',
            'mobile' => '09333333333',
            'username' => 'operator3',
            'password' => Hash::make('operator123'),
            'role' => 'operator',
        ]);

        $this->command->info('Users created successfully!');
        $this->command->info('Super Admin: superadmin / superadmin123');
        $this->command->info('Operators: operator1, operator2, operator3 / operator123');
    }
}
