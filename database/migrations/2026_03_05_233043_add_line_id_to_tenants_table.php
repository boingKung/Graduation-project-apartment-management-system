<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('tenants', function (Blueprint $table) {
            // เก็บ User ID ของ LINE สำหรับใช้ระบุตัวตนและส่ง Notification
            $table->string('line_id')->nullable()->unique()->after('id_card')->comment('LINE User ID สำหรับเชื่อมต่อระบบ');

            // เก็บ URL รูปภาพโปรไฟล์จาก LINE (กรณีต้องการแสดงผลล่าสุด)
            $table->string('line_avatar')->nullable()->after('line_id')->comment('URL รูปโปรไฟล์จากบัญชี LINE');
        });
    }

    public function down()
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['line_id', 'line_avatar']);
        });
    }
};
