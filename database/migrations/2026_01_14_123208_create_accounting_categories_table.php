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
        Schema::create('accounting_categories', function (Blueprint $table) {
            $table->id();
            // เชื่อมกับประเภท (รายรับ/รายจ่าย)
            $table->foreignId('type_id')->constrained('accounting_types')->onDelete('cascade')->comment('เชื่อมตาราง type รายรับ รายจ่าย');
            // ชื่อหมวดหมู่: เช่น ค่าเช่าห้อง (รายรับ), ค่าอุปกรณ์ (รายจ่าย), ค่าแรงช่าง (รายจ่าย)
            $table->string('name')->comment('ชื่อหมวดหมู่ ค่าเช่าห้อง (รายรับ) ค่าอุปกรณ์ (รายจ่าย)');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounting_categories');
    }
};
