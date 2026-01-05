<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('core_db')->create('receptor_workflow_step_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('step_id')->constrained('receptor_workflow_steps')->onDelete('cascade');
            $table->string('action_type')->comment('نوع Action: notify_receptor, send_sms_to_customer, send_sms_to_admin');
            $table->json('config')->nullable()->comment('تنظیمات Action به صورت JSON');
            $table->integer('order')->comment('ترتیب اجرای Action در همان Step');
            $table->timestamps();
            
            // Indexes
            $table->index('step_id');
            $table->index(['step_id', 'order']);
            $table->index('action_type');
        });
    }

    public function down(): void
    {
        Schema::connection('core_db')->dropIfExists('receptor_workflow_step_actions');
    }
};

