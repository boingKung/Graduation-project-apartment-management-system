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
        Schema::create('meter_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('rooms')->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->enum('meter_type', ['water', 'electric'])->comment('ประเภทมิเตอร์');
            $table->integer('previous_value')->default(0)->comment('เลขมิเตอร์ครั้งก่อน');
            $table->integer('current_value')->comment('เลขมิเตอร์ที่จดใหม่');
            $table->integer('units_used')->comment('หน่วยที่ใช้จริง');
            $table->string('billing_month', 7)->comment('เดือนที่จด เช่น 2026-01');
            $table->date('reading_date')->comment('วันที่จดมิเตอร์');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meter_readings');
    }
};
