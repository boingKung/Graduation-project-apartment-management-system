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
        Schema::create('apartment', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('ชื่ออพาร์ทเม้นท์');
            $table->string('address_no')->nullable()->comment('เลขที่');
            $table->string('moo', 3)->nullable()->comment('หมู่ที่');
            $table->string('sub_district')->nullable()->comment('ตำบล/แขวง');
            $table->string('district')->nullable()->comment('อำเภอ/เขต');
            $table->string('province')->nullable()->comment('จังหวัด');
            $table->string('postal_code', 5)->nullable()->comment('รหัสไปรษณีย์');
            $table->string('phone', length: 10)->nullable()->comment('เบอร์โทรศัพท์');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apartment');
    }
};
