<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('core_db')->create('receptors', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('company_name');
            $table->string('mobile')->unique();
            $table->string('allowed_ip')->nullable()->comment('IP مجاز برای دسترسی');
            $table->string('username')->unique();
            $table->string('password');
            $table->string('orders_base_url')->nullable()->comment('آدرس پایه API سفارشات');
            $table->string('orders_auth_token')->nullable()->comment('توکن احراز هویت API');
            $table->timestamps();
            
            // Indexes
            $table->index('mobile');
            $table->index('username');
        });
    }

    public function down(): void
    {
        Schema::connection('core_db')->dropIfExists('receptors');
    }
};

