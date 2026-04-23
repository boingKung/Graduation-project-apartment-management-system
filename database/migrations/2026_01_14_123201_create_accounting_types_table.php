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
        Schema::create('accounting_types', function (Blueprint $table) {
            $table->id();
            // ชื่อประเภท: 'รายรับ' หรือ 'รายจ่าย'
            $table->string('name')->comment('ชื่อประเภท: รายรับ รายจ่าย');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounting_types');
    }
};
