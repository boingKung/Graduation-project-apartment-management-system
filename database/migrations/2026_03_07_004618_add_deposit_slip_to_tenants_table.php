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
            $table->string('deposit_payment_method')->nullable()->after('deposit_amount')->comment('วิธีการชำระเงินมัดจำ โอน เงินสด');
            // 🌟 เพิ่มคอลัมน์ deposit_slip ชนิด String และอนุญาตให้เป็นค่าว่างได้
            $table->string('deposit_slip')->nullable()->after('deposit_payment_method')->comment('เก็บเส้นทางของสลิปเงินมัดจำ');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // ลบคอลัมน์ทิ้งหากมีการกดย้อนกลับ (Rollback)
            $table->dropColumn('deposit_payment_method');
            $table->dropColumn('deposit_slip');
        });
    }
};
