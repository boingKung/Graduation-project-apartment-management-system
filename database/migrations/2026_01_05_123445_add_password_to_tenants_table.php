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
            // เพิ่มคอลัมน์ password ต่อท้าย id_card
            $table->string('password')->after('id_card')->nullable()->comment('รหัสผ่านสำหรับ Login');
            
            // ปรับปรุง id_card ให้เก็บเลข 13 หลักปกติ (ไม่ Hash)
            // หมายเหตุ: ต้องติดตั้ง doctrine/dbal ก่อนหากต้องการเปลี่ยนคุณสมบัติคอลัมน์เดิม
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('password');
        });
    }
};
