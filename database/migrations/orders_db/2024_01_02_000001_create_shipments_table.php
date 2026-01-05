<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('orders_db')->create('shipments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('receptor_id')->nullable()->comment('پذیرنده مرتبط - ارجاع به core_db.receptors');
            $table->string('system_order_id')->unique()->comment('آی دی منحصر به فرد در این سامانه');
            $table->string('source_order_id')->comment('شماره سفارش مبدا');
            $table->string('customer_first_name');
            $table->string('customer_last_name');
            $table->string('origin')->default('تهران');
            $table->string('destination_city');
            $table->text('address');
            $table->string('postcode', 20);
            $table->string('mobile', 15);
            $table->decimal('total_price', 15, 2)->default(0)->comment('قیمت کل محموله');
            $table->enum('status', ['pending', 'processing', 'completed', 'cancelled'])
                ->default('pending')
                ->comment('وضعیت محموله');
            $table->timestamps();
            
            // Indexes
            $table->index('receptor_id');
            $table->index('source_order_id');
            $table->index('status');
            $table->index('created_at');
            $table->index(['receptor_id', 'status']);
            $table->index(['receptor_id', 'source_order_id']);
        });
    }

    public function down(): void
    {
        Schema::connection('orders_db')->dropIfExists('shipments');
    }
};

