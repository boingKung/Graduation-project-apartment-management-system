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
            // 🌟 เพิ่ม ->nullable()->change() เพื่ออนุญาตให้เป็นค่าว่างได้
            $table->unsignedBigInteger('room_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // คืนค่ากลับเป็นห้ามว่าง (เผื่อกรณีต้องการย้อนกลับคำสั่ง)
            $table->unsignedBigInteger('room_id')->nullable(false)->change();
        });
    }
};
