<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('core_db')->create('receptor_workflows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receptor_id')->unique()->constrained('receptors')->onDelete('cascade');
            $table->boolean('is_active')->default(false)->comment('وضعیت فعال/غیرفعال بودن Workflow');
            $table->timestamps();
            
            // Indexes
            $table->index('receptor_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::connection('core_db')->dropIfExists('receptor_workflows');
    }
};

