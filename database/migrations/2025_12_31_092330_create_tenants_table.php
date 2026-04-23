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
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            // username
            $table->foreignId('room_id')->constrained('rooms')->onDelete('cascade')->comment('รหัสห้อง');
            // password
            $table->string('id_card')->unique()->comment('เป็นรหัสผ่าน หมายเลขบัตรประชาชน สามารถมาแก้ไขได้');
            // data
            $table->string('first_name')->comment('ชื่อผู้เช่า');
            $table->string('last_name')->comment('นามสกุลผู้เช่า');
            $table->string('address_no')->nullable()->comment('เลขที่');
            $table->string('moo', 3)->nullable()->comment('หมู่ที่');
            $table->string('sub_district')->nullable()->comment('ตำบล/แขวง');
            $table->string('district')->nullable()->comment('อำเภอ/เขต');
            $table->string('province')->nullable()->comment('จังหวัด');
            $table->string('postal_code', 5)->nullable()->comment('รหัสไปรษณีย์');
            $table->string('phone', 10)->nullable()->comment('เบอร์โทรศัพท์');
            // วันที่เช่า
            $table->date('start_date')->comment('วันที่เริ่มเช่า');
            $table->date('end_date')->nullable()->comment('วันที่สิ้นสุดการเช่า');
            // condition เงื่อนไข เก็บค่าเช่าเพิ่มเติม
            $table->boolean('has_parking')->default(false)->comment('สถานะการใช้ที่จอดรถ: true=ใช้, false=ไม่ใช้');
            $table->integer('resident_count')->default(1)->comment('จำนวนผู้อยู่อาศัย');
            // ไฟล์เอกสารสัญญาเช่า
            $table->string('rental_contract')->comment('ไฟล์สัญญาเช่า path');
            $table->string('status')->default('กำลังใช้งาน')->comment('สถานะผู้เช่า กำลังใช้งาน/สิ้นสุดสัญญา');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
