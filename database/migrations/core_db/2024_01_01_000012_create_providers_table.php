<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('core_db')->create('providers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('نام فارسی: دکا، تیپاکس، ماهکس، پست');
            $table->string('code')->unique()->comment('کد یکتا: deka, tipax, mahex, post');
            $table->string('api_base_url')->nullable()->comment('آدرس پایه API');
            $table->string('api_username')->nullable()->comment('نام کاربری API');
            $table->string('api_password')->nullable()->comment('رمز عبور API (encrypted)');
            $table->string('api_key')->nullable()->comment('کلید API (اگر نیاز باشد)');
            $table->boolean('is_active')->default(true)->comment('فعال/غیرفعال');
            $table->json('config')->nullable()->comment('تنظیمات اضافی (serviceID, contractID, etc.)');
            $table->timestamps();
            
            // Indexes
            $table->index('code');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::connection('core_db')->dropIfExists('providers');
    }
};

