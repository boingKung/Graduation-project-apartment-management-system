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
        Schema::create('tenant_expenses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('ชื่อรายการ เช่น ค่าห้อง, ค่าน้ำ, ค่าไฟ');
            $table->decimal('price', 10, 2)->comment('ราคาต่อหน่วย หรือราคาเหมาจ่าย');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_expenses');
    }
};
