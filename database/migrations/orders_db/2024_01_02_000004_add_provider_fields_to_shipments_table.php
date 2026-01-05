<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('orders_db')->table('shipments', function (Blueprint $table) {
            $table->unsignedBigInteger('provider_id')->nullable()->after('receptor_id')->comment('ارائه‌دهنده - ارجاع به core_db.providers');
            $table->string('provider_tracking_number')->nullable()->after('provider_id')->comment('شماره پیگیری از provider');
            $table->string('provider_order_id')->nullable()->after('provider_tracking_number')->comment('شناسه سفارش در سیستم provider');
            $table->timestamp('sent_to_provider_at')->nullable()->after('provider_order_id')->comment('زمان ارسال به provider');
            $table->json('provider_response')->nullable()->after('sent_to_provider_at')->comment('پاسخ کامل API provider');
            
            // Indexes
            $table->index('provider_id');
            $table->index('provider_tracking_number');
            $table->index('sent_to_provider_at');
        });
    }

    public function down(): void
    {
        Schema::connection('orders_db')->table('shipments', function (Blueprint $table) {
            $table->dropIndex(['provider_id']);
            $table->dropIndex(['provider_tracking_number']);
            $table->dropIndex(['sent_to_provider_at']);
            
            $table->dropColumn([
                'provider_id',
                'provider_tracking_number',
                'provider_order_id',
                'sent_to_provider_at',
                'provider_response',
            ]);
        });
    }
};

