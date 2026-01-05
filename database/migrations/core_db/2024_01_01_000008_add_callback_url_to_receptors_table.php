<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('core_db')->table('receptors', function (Blueprint $table) {
            if (!Schema::connection('core_db')->hasColumn('receptors', 'callback_url')) {
                $table->string('callback_url')->nullable()->after('orders_auth_token')
                    ->comment('URL برای ارسال callback پس از ثبت موفق سفارش در سامانه ترابری');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('core_db')->table('receptors', function (Blueprint $table) {
            if (Schema::connection('core_db')->hasColumn('receptors', 'callback_url')) {
                $table->dropColumn('callback_url');
            }
        });
    }
};

