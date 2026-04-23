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
        Schema::table('meter_readings', function (Blueprint $table) {
            // 🌟 เพิ่ม ->nullable()->change() เพื่ออนุญาตให้ tenant_id เป็นค่าว่างได้
            $table->unsignedBigInteger('tenant_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meter_readings', function (Blueprint $table) {
            // คืนค่ากลับเป็นห้ามว่าง (เผื่อกรณีต้องการย้อนกลับ)
            $table->unsignedBigInteger('tenant_id')->nullable(false)->change();
        });
    }
};
