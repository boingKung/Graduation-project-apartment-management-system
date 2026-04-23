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
            // เพิ่มคอลัมน์ใหม่สำหรับเก็บข้อมูลสัญญาเช่า
            $table->integer('age')
                ->nullable()
                ->after('last_name')
                ->comment('อายุของผู้เช่า');

            $table->date('id_card_issue_date')
                ->nullable()
                ->after('id_card')
                ->comment('วันที่ออกบัตรประจำตัวประชาชน');

            $table->date('id_card_expiry_date')
                ->nullable()
                ->after('id_card_issue_date')
                ->comment('วันที่บัตรประจำตัวประชาชนหมดอายุ');

            $table->string('id_card_issue_place')
                ->nullable()
                ->after('id_card_expiry_date')
                ->comment('สถานที่ออกบัตร (เช่น อำเภอ/เขต)');

            $table->string('id_card_issue_province')
                ->nullable()
                ->after('id_card_issue_place')
                ->comment('จังหวัดที่ออกบัตร');

            $table->string('street')
                ->nullable()
                ->after('moo')
                ->comment('ชื่อถนน');

            $table->string('alley')
                ->nullable()
                ->after('street')
                ->comment('ตรอก หรือ ซอย');

            $table->string('workplace')
                ->nullable()
                ->after('phone')
                ->comment('สถานที่ทำงานปัจจุบันของผู้เช่า');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'age',
                'id_card_issue_date',
                'id_card_expiry_date',
                'id_card_issue_place',
                'id_card_issue_province',
                'street',
                'alley',
                'workplace'
            ]);
        });
    }
};