<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
        {
            Schema::table('tenants', function (Blueprint $table) {
                // 1. ลบสถานะ Unique ออก (ชื่อ Index ปกติคือ tenants_id_card_unique)
                $table->dropUnique(['id_card']); 
                
                // 2. กำหนดให้ id_card เป็น string ธรรมดา (ไม่บังคับ unique ในระดับ Database แล้ว)
                $table->string('id_card')->change(); 
            });
        }

        public function down(): void
        {
            Schema::table('tenants', function (Blueprint $table) {
                // กรณีจะย้อนกลับ ให้สั่งเพิ่ม unique กลับคืนไป
                $table->unique('id_card');
            });
        }
};
