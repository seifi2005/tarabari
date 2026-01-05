<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('shipments')) {
            Schema::create('shipments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('receptor_id')->nullable()->constrained('receptors')->onDelete('set null')->comment('پذیرنده مرتبط');
                $table->string('system_order_id')->unique()->comment('آی دی منحصر به فرد در این سامانه');
                $table->string('source_order_id')->comment('شماره سفارش مبدا');
                $table->string('customer_first_name');
                $table->string('customer_last_name');
                $table->string('origin')->default('تهران');
                $table->string('destination_city');
                $table->text('address');
                $table->string('postcode');
                $table->string('mobile');
                $table->decimal('total_price', 15, 2)->default(0);
                $table->string('status')->default('pending')->comment('pending, processing, completed, cancelled');
                $table->timestamps();
                
                $table->index('source_order_id');
                $table->index('receptor_id');
            });
        } else {
            // اگر جدول وجود دارد، فقط فیلدهای جدید را اضافه می‌کنیم
            Schema::table('shipments', function (Blueprint $table) {
                // بررسی و اضافه کردن receptor_id اگر وجود ندارد
                if (!Schema::hasColumn('shipments', 'receptor_id')) {
                    $table->foreignId('receptor_id')->nullable()->after('id')->constrained('receptors')->onDelete('set null')->comment('پذیرنده مرتبط');
                }
                
                // بررسی و اضافه کردن system_order_id اگر وجود ندارد
                if (!Schema::hasColumn('shipments', 'system_order_id')) {
                    $table->string('system_order_id')->unique()->nullable()->after('receptor_id')->comment('آی دی منحصر به فرد در این سامانه');
                }
            });
            
            // اضافه کردن index ها با raw SQL (اگر وجود نداشته باشند)
            $this->addIndexIfNotExists('shipments', 'source_order_id', 'shipments_source_order_id_index');
            
            if (Schema::hasColumn('shipments', 'receptor_id')) {
                $this->addIndexIfNotExists('shipments', 'receptor_id', 'shipments_receptor_id_index');
            }
        }
    }
    
    /**
     * اضافه کردن index اگر وجود نداشته باشد
     */
    private function addIndexIfNotExists(string $table, string $column, string $indexName): void
    {
        $indexExists = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
        
        if (empty($indexExists)) {
            DB::statement("CREATE INDEX `{$indexName}` ON `{$table}` (`{$column}`)");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
