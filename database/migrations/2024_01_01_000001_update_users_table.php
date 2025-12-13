<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('last_name')->nullable()->after('name');
            $table->string('mobile')->unique()->nullable()->after('email');
            $table->string('national_code')->nullable()->after('mobile');
            $table->string('username')->unique()->nullable()->after('national_code');
            $table->enum('role', ['super_admin', 'operator', 'receptor'])->default('receptor')->after('username');
            $table->foreignId('receptor_id')->nullable()->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['last_name', 'mobile', 'national_code', 'username', 'role', 'receptor_id']);
        });
    }
};

