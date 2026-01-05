<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('core_db')->create('receptor_provider', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('receptor_id')->comment('پذیرنده - ارجاع به receptors');
            $table->unsignedBigInteger('provider_id')->comment('ارائه‌دهنده - ارجاع به providers');
            $table->timestamps();
            
            // Indexes
            $table->index('receptor_id');
            $table->index('provider_id');
            $table->unique(['receptor_id', 'provider_id'], 'receptor_provider_unique');
        });
    }

    public function down(): void
    {
        Schema::connection('core_db')->dropIfExists('receptor_provider');
    }
};

