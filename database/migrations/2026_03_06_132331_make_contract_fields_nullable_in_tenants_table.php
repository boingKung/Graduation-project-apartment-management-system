<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // 🌟 เปลี่ยนให้ฟิลด์เกี่ยวกับสัญญาเช่า สามารถเป็นค่าว่างได้ (ตอนที่สถานะคือ รออนุมัติ)
            $table->date('start_date')->nullable()->change();
            $table->date('end_date')->nullable()->change();
            
            // เผื่อช่องเก็บไฟล์สัญญาไว้ด้วยเลยครับ เพราะผู้เช่าใหม่ยังไม่มีไฟล์สัญญา
            if (Schema::hasColumn('tenants', 'rental_contract')) {
                $table->string('rental_contract')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->date('start_date')->nullable(false)->change();
            $table->date('end_date')->nullable(false)->change();
            if (Schema::hasColumn('tenants', 'rental_contract')) {
                $table->string('rental_contract')->nullable(false)->change();
            }
        });
    }
};
