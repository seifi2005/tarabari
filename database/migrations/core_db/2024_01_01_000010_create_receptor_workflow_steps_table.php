<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('core_db')->create('receptor_workflow_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained('receptor_workflows')->onDelete('cascade');
            $table->integer('order')->comment('ترتیب اجرای مرحله');
            $table->string('name')->comment('نام مرحله');
            $table->timestamps();
            
            // Indexes
            $table->index('workflow_id');
            $table->index(['workflow_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::connection('core_db')->dropIfExists('receptor_workflow_steps');
    }
};

