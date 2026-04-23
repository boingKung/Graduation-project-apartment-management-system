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
    Schema::table('accounting_transactions', function (Blueprint $table) {
        // เพิ่มคอลัมน์ room_id และ building_id แบบ nullable (อนุญาตให้เป็นค่าว่างได้)
        $table->unsignedBigInteger('room_id')->nullable()->after('tenant_id');
        $table->unsignedBigInteger('building_id')->nullable()->after('room_id');
    });
}

public function down()
{
    Schema::table('accounting_transactions', function (Blueprint $table) {
        $table->dropColumn(['room_id', 'building_id']);
    });
}
};
