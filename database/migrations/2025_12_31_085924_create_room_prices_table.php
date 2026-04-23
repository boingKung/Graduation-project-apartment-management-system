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
        Schema::create('room_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->constrained('buildings')->onDelete('cascade')->comment('รหัสอาคาร');
            $table->foreignId('room_type_id')->constrained('room_types')->onDelete('cascade')->comment('รหัสประเภทห้อง');
            $table->integer('floor_num')->comment('ชั้นที่ตั้งอยู่');
            $table->decimal('price', 10, 2)->comment('ราคาห้อง');
            $table->string('color_code')->nullable()->comment('เก็บรูปสีประเภทห้อง path');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_prices');
    }
};
