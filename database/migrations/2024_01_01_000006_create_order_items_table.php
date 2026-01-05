<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('order_items')) {
            Schema::create('order_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('shipment_id')->constrained('shipments')->onDelete('cascade');
                $table->string('source_item_id')->comment('آی دی آیتم در سیستم مبدا');
                $table->string('name');
                $table->unsignedBigInteger('product_id');
                $table->unsignedBigInteger('variation_id')->default(0);
                $table->integer('quantity');
                $table->decimal('price', 15, 2);
                $table->decimal('subtotal', 15, 2);
                $table->decimal('total', 15, 2);
                $table->string('sku')->nullable();
                $table->timestamps();
                
                $table->index('shipment_id');
                $table->index('source_item_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
