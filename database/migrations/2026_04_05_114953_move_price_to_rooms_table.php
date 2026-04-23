<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // 1. เพิ่มคอลัมน์ price ลงในตาราง rooms
        Schema::table('rooms', function (Blueprint $table) {
            $table->decimal('price', 10, 2)->default(0)->after('status')->comment('ราคาห้องพัก');
        });

        // 2. คัดลอกข้อมูลราคาเดิมจาก room_prices มาใส่ rooms 
        // (อ้างอิงจาก relationship roomPrice ของคุณ ซึ่ง Laravel จะมองหา room_price_id ตามค่าเริ่มต้น)
        DB::statement('
            UPDATE rooms 
            JOIN room_prices ON rooms.room_price_id = room_prices.id 
            SET rooms.price = room_prices.price
        ');

        // 3. ลบคอลัมน์ price ออกจาก room_prices
        Schema::table('room_prices', function (Blueprint $table) {
            $table->dropColumn('price');
        });
    }

    public function down()
    {
        // กรณีต้องการ Rollback กลับไปเป็นเหมือนเดิม
        Schema::table('room_prices', function (Blueprint $table) {
            $table->decimal('price', 10, 2)->default(0)->after('floor_num');
        });

        DB::statement('
            UPDATE room_prices rp 
            JOIN rooms r ON r.room_price_id = rp.id 
            SET rp.price = r.price
        ');

        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn('price');
        });
    }
};