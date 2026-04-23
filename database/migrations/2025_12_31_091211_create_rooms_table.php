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
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('room_number')->unique()->comment('หมายเลขห้อง');
            $table->foreignId('room_price_id')->constrained('room_prices')->onDelete('cascade')->comment('รหัสราคาห้อง');
            $table->string('status')->default('ว่าง')->comment('สถานะห้อง');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
