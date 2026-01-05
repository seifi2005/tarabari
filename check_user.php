<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== بررسی کاربر ===\n\n";

// چک اتصال core_db
try {
    $pdo = \DB::connection('core_db')->getPdo();
    echo "✅ اتصال به core_db موفق\n";
} catch (Exception $e) {
    echo "❌ خطا در اتصال به core_db: " . $e->getMessage() . "\n";
    exit(1);
}

// چک وجود جدول users
try {
    $tableExists = \DB::connection('core_db')->getSchemaBuilder()->hasTable('users');
    if ($tableExists) {
        echo "✅ جدول users وجود دارد\n";
    } else {
        echo "❌ جدول users وجود ندارد - migration اجرا نشده!\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "❌ خطا در چک جدول: " . $e->getMessage() . "\n";
    exit(1);
}

// جستجوی کاربر
$mobile = '09300428006';
echo "\n=== جستجوی کاربر با موبایل: {$mobile} ===\n";

try {
    $user = \App\Models\User::where('mobile', $mobile)->first();
    
    if ($user) {
        echo "✅ کاربر پیدا شد!\n";
        echo "   ID: {$user->id}\n";
        echo "   Name: {$user->name}\n";
        echo "   Email: {$user->email}\n";
        echo "   Role: {$user->role}\n";
    } else {
        echo "❌ کاربر پیدا نشد!\n";
        echo "\n=== چک کردن در دیتابیس قدیمی ===\n";
        
        // چک دیتابیس قدیمی
        try {
            $oldDb = \DB::connection('mysql')->table('users')->where('mobile', $mobile)->first();
            if ($oldDb) {
                echo "⚠️  کاربر در دیتابیس قدیمی (dashboard) وجود دارد!\n";
                echo "   باید migration داده انجام شود.\n";
            }
        } catch (Exception $e) {
            echo "❌ دیتابیس قدیمی در دسترس نیست\n";
        }
    }
} catch (Exception $e) {
    echo "❌ خطا: " . $e->getMessage() . "\n";
}

echo "\n=== تعداد کاربران در core_db ===\n";
try {
    $count = \App\Models\User::count();
    echo "تعداد کاربران: {$count}\n";
} catch (Exception $e) {
    echo "❌ خطا: " . $e->getMessage() . "\n";
}

