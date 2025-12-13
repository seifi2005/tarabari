<?php

namespace Database\Seeders;

use App\Models\Receptor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ReceptorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ایجاد Receptor نمونه
        Receptor::updateOrCreate(
            ['username' => 'receptor1'],
            [
                'first_name' => 'رضا',
                'last_name' => 'محمدی',
                'company_name' => 'شرکت نمونه',
                'mobile' => '09999999999',
                'allowed_ip' => null, // null یعنی همه IP ها مجاز هستند
                'password' => Hash::make('receptor123'),
            ]
        );

        // ایجاد Receptor دوم با IP محدود
        Receptor::updateOrCreate(
            ['username' => 'receptor2'],
            [
                'first_name' => 'سارا',
                'last_name' => 'احمدی',
                'company_name' => 'شرکت دوم',
                'mobile' => '09888888888',
                'allowed_ip' => '127.0.0.1', // فقط از localhost
                'password' => Hash::make('receptor123'),
            ]
        );

        $this->command->info('Receptors created successfully!');
        $this->command->info('Receptor 1: receptor1 / receptor123');
        $this->command->info('Receptor 2: receptor2 / receptor123 (IP: 127.0.0.1)');
    }
}
