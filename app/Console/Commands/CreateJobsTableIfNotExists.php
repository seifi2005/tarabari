<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateJobsTableIfNotExists extends Command
{
    protected $signature = 'db:create-jobs-table';

    protected $description = 'Create jobs table if it does not exist';

    public function handle()
    {
        $connection = DB::connection('core_db');
        
        if (Schema::connection('core_db')->hasTable('jobs')) {
            $this->info('✅ جدول jobs قبلاً وجود دارد');
            return 0;
        }

        $this->info('در حال ساخت جدول jobs...');

        try {
            $connection->statement("
                CREATE TABLE `jobs` (
                    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
                    `queue` varchar(255) NOT NULL,
                    `payload` longtext NOT NULL,
                    `attempts` tinyint unsigned NOT NULL,
                    `reserved_at` int unsigned DEFAULT NULL,
                    `available_at` int unsigned NOT NULL,
                    `created_at` int unsigned NOT NULL,
                    PRIMARY KEY (`id`),
                    KEY `jobs_queue_index` (`queue`),
                    KEY `jobs_queue_reserved_at_index` (`queue`, `reserved_at`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            $this->info('✅ جدول jobs با موفقیت ساخته شد!');
            return 0;
        } catch (\Exception $e) {
            $this->error('❌ خطا: ' . $e->getMessage());
            return 1;
        }
    }
}
