<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class MigrateUsersFromOldDatabase extends Command
{
    protected $signature = 'migrate:users-from-old-db';

    protected $description = 'Migrate users and receptors from old database (dashboard) to new database (panel_core)';

    public function handle()
    {
        $this->info('=== شروع Migration داده‌ها ===');
        $this->newLine();

        // چک اتصال به دیتابیس قدیمی
        try {
            $oldDb = DB::connection('old_db');
            $oldDb->getPdo();
            $this->info('✅ اتصال به دیتابیس قدیمی موفق');
        } catch (\Exception $e) {
            $this->error('❌ خطا در اتصال به دیتابیس قدیمی: ' . $e->getMessage());
            $this->warn('💡 لطفاً در .env متغیر OLD_DB_DATABASE=dashboard را اضافه کنید');
            return 1;
        }

        // چک اتصال به دیتابیس جدید
        try {
            $newDb = DB::connection('core_db');
            $newDb->getPdo();
            $this->info('✅ اتصال به دیتابیس جدید موفق');
        } catch (\Exception $e) {
            $this->error('❌ خطا در اتصال به دیتابیس جدید: ' . $e->getMessage());
            return 1;
        }

        $this->newLine();

        // Migration کاربران
        $this->migrateUsers($oldDb, $newDb);

        // Migration پذیرنده‌ها
        $this->migrateReceptors($oldDb, $newDb);

        $this->newLine();
        $this->info('✅ Migration کامل شد!');
        
        return 0;
    }

    private function migrateUsers($oldDb, $newDb)
    {
        $this->info('=== Migration کاربران ===');

        try {
            // چک وجود جدول در دیتابیس قدیمی
            if (!$oldDb->getSchemaBuilder()->hasTable('users')) {
                $this->warn('⚠️  جدول users در دیتابیس قدیمی وجود ندارد');
                return;
            }

            $oldUsers = $oldDb->table('users')->get();
            $count = $oldUsers->count();

            if ($count === 0) {
                $this->info('   هیچ کاربری در دیتابیس قدیمی یافت نشد');
                return;
            }

            $this->info("   پیدا شد: {$count} کاربر");

            $migrated = 0;
            $skipped = 0;

            foreach ($oldUsers as $oldUser) {
                // چک کردن وجود کاربر در دیتابیس جدید
                $exists = $newDb->table('users')
                    ->where('email', $oldUser->email)
                    ->orWhere('mobile', $oldUser->mobile ?? '')
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                // Insert به دیتابیس جدید
                $newDb->table('users')->insert([
                    'id' => $oldUser->id,
                    'name' => $oldUser->name ?? '',
                    'last_name' => $oldUser->last_name ?? null,
                    'email' => $oldUser->email,
                    'email_verified_at' => $oldUser->email_verified_at ?? null,
                    'mobile' => $oldUser->mobile ?? null,
                    'national_code' => $oldUser->national_code ?? null,
                    'username' => $oldUser->username ?? null,
                    'password' => $oldUser->password,
                    'role' => $oldUser->role ?? 'receptor',
                    'receptor_id' => $oldUser->receptor_id ?? null,
                    'remember_token' => $oldUser->remember_token ?? null,
                    'created_at' => $oldUser->created_at ?? now(),
                    'updated_at' => $oldUser->updated_at ?? now(),
                ]);

                $migrated++;
                $this->line("   ✅ کاربر منتقل شد: {$oldUser->email}");
            }

            $this->info("   ✅ {$migrated} کاربر منتقل شد");
            if ($skipped > 0) {
                $this->warn("   ⏭️  {$skipped} کاربر رد شد (قبلاً وجود داشت)");
            }

        } catch (\Exception $e) {
            $this->error('   ❌ خطا: ' . $e->getMessage());
        }

        $this->newLine();
    }

    private function migrateReceptors($oldDb, $newDb)
    {
        $this->info('=== Migration پذیرنده‌ها ===');

        try {
            // چک وجود جدول در دیتابیس قدیمی
            if (!$oldDb->getSchemaBuilder()->hasTable('receptors')) {
                $this->warn('⚠️  جدول receptors در دیتابیس قدیمی وجود ندارد');
                return;
            }

            $oldReceptors = $oldDb->table('receptors')->get();
            $count = $oldReceptors->count();

            if ($count === 0) {
                $this->info('   هیچ پذیرنده‌ای در دیتابیس قدیمی یافت نشد');
                return;
            }

            $this->info("   پیدا شد: {$count} پذیرنده");

            $migrated = 0;
            $skipped = 0;

            foreach ($oldReceptors as $oldReceptor) {
                // چک کردن وجود پذیرنده در دیتابیس جدید
                $exists = $newDb->table('receptors')
                    ->where('mobile', $oldReceptor->mobile)
                    ->orWhere('username', $oldReceptor->username)
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                // Insert به دیتابیس جدید
                $newDb->table('receptors')->insert([
                    'id' => $oldReceptor->id,
                    'first_name' => $oldReceptor->first_name ?? '',
                    'last_name' => $oldReceptor->last_name ?? '',
                    'company_name' => $oldReceptor->company_name ?? '',
                    'mobile' => $oldReceptor->mobile,
                    'allowed_ip' => $oldReceptor->allowed_ip ?? null,
                    'username' => $oldReceptor->username,
                    'password' => $oldReceptor->password,
                    'orders_base_url' => $oldReceptor->orders_base_url ?? null,
                    'orders_auth_token' => $oldReceptor->orders_auth_token ?? null,
                    'created_at' => $oldReceptor->created_at ?? now(),
                    'updated_at' => $oldReceptor->updated_at ?? now(),
                ]);

                $migrated++;
                $this->line("   ✅ پذیرنده منتقل شد: {$oldReceptor->company_name} ({$oldReceptor->mobile})");
            }

            $this->info("   ✅ {$migrated} پذیرنده منتقل شد");
            if ($skipped > 0) {
                $this->warn("   ⏭️  {$skipped} پذیرنده رد شد (قبلاً وجود داشت)");
            }

        } catch (\Exception $e) {
            $this->error('   ❌ خطا: ' . $e->getMessage());
        }

        $this->newLine();
    }
}
