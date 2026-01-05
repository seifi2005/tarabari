<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('orders_db')->create('order_item_pricing', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_item_id')->comment('آیتم سفارش مرتبط');
            $table->string('item_name')->comment('نام محصول در زمان خرید');
            $table->decimal('unit_price', 15, 2)->comment('قیمت واحد');
            $table->integer('quantity')->comment('تعداد (کپی برای محاسبات سریع)');
            $table->decimal('subtotal', 15, 2)->comment('جمع قبل از تخفیف و مالیات');
            $table->decimal('discount', 15, 2)->default(0)->comment('مقدار تخفیف');
            $table->decimal('tax', 15, 2)->default(0)->comment('مالیات');
            $table->decimal('total', 15, 2)->comment('قیمت نهایی');
            $table->string('currency', 10)->default('IRR')->comment('واحد پول');
            $table->timestamps();
            
            // Indexes
            $table->index('order_item_id');
            $table->index('created_at');
            $table->index('currency');
            
            // Foreign Key
            $table->foreign('order_item_id')
                ->references('id')
                ->on('order_items')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('orders_db')->dropIfExists('order_item_pricing');
    }
};

