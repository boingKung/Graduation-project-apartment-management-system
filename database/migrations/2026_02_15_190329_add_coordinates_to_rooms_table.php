<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('rooms', function (Blueprint $table) {
        // เก็บพิกัดแกน X, Y (ค่าเริ่มต้น 0)
        $table->integer('pos_x')->default(0)->after('status');
        $table->integer('pos_y')->default(0)->after('pos_x');

        // เก็บขนาดห้อง (เผื่ออนาคตอยากได้ห้องเล็ก/ใหญ่ไม่เท่ากัน)
        $table->integer('width')->default(100)->after('pos_y');
        $table->integer('height')->default(80)->after('width');
    });
}

public function down()
{
    Schema::table('rooms', function (Blueprint $table) {
        $table->dropColumn(['pos_x', 'pos_y', 'width', 'height']);
    });
}
};
