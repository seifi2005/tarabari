<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('orders_db')->create('order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shipment_id')->comment('محموله مرتبط');
            $table->string('source_item_id')->comment('آی دی آیتم در سیستم مبدا');
            $table->unsignedBigInteger('product_id')->comment('شناسه محصول');
            $table->unsignedBigInteger('variation_id')->default(0)->comment('شناسه تنوع محصول');
            $table->integer('quantity')->comment('تعداد');
            $table->string('sku')->nullable()->comment('کد SKU محصول');
            $table->timestamps();
            
            // Indexes
            $table->index('shipment_id');
            $table->index('source_item_id');
            $table->index('product_id');
            $table->index(['shipment_id', 'product_id']);
            
            // Foreign Key (در همان دیتابیس)
            $table->foreign('shipment_id')
                ->references('id')
                ->on('shipments')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('orders_db')->dropIfExists('order_items');
    }
};

