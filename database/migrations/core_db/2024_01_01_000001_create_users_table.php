<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('core_db')->create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('last_name')->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('mobile')->unique()->nullable();
            $table->string('national_code', 10)->unique()->nullable();
            $table->string('username')->unique()->nullable();
            $table->string('password');
            $table->enum('role', ['super_admin', 'operator', 'receptor'])->default('receptor');
            $table->unsignedBigInteger('receptor_id')->nullable()->comment('ارجاع به receptors');
            $table->rememberToken();
            $table->timestamps();
            
            // Indexes
            $table->index('role');
            $table->index('receptor_id');
            $table->index(['mobile', 'role']);
        });
    }

    public function down(): void
    {
        Schema::connection('core_db')->dropIfExists('users');
    }
};

