<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receptors', function (Blueprint $table) {
            $table->string('orders_base_url')->nullable()->after('password')->comment('آدرس پایه برای دریافت سفارشات (مثال: https://blanton.store/wp-json/wam/v1)');
            $table->string('orders_auth_token')->nullable()->after('orders_base_url')->comment('توکن احراز هویت برای API سفارشات');
        });
    }

    public function down(): void
    {
        Schema::table('receptors', function (Blueprint $table) {
            $table->dropColumn(['orders_base_url', 'orders_auth_token']);
        });
    }
};
