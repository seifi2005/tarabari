<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$db = \DB::connection('core_db');

$tables = [
    'users',
    'receptors',
    'otp_codes',
    'personal_access_tokens',
    'password_reset_tokens',
    'failed_jobs',
    'jobs',
];

echo "=== چک جداول در core_db ===\n\n";

$missing = [];

foreach ($tables as $table) {
    $exists = $db->getSchemaBuilder()->hasTable($table);
    if ($exists) {
        echo "✅ {$table}\n";
    } else {
        echo "❌ {$table} - وجود ندارد\n";
        $missing[] = $table;
    }
}

echo "\n";

if (empty($missing)) {
    echo "✅ همه جداول وجود دارند!\n";
} else {
    echo "⚠️  جداول مفقود: " . implode(', ', $missing) . "\n";
    
    if (in_array('jobs', $missing)) {
        echo "\n=== ساخت جدول jobs ===\n";
        try {
            $db->getSchemaBuilder()->create('jobs', function ($table) {
                $table->bigIncrements('id');
                $table->string('queue')->index();
                $table->longText('payload');
                $table->unsignedTinyInteger('attempts');
                $table->unsignedInteger('reserved_at')->nullable();
                $table->unsignedInteger('available_at');
                $table->unsignedInteger('created_at');
                $table->index(['queue', 'reserved_at']);
            });
            echo "✅ جدول jobs ساخته شد!\n";
        } catch (\Exception $e) {
            echo "❌ خطا: " . $e->getMessage() . "\n";
        }
    }
}

