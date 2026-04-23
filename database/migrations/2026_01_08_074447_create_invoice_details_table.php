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
        Schema::create('invoice_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->onDelete('cascade');
            $table->foreignId('tenant_expense_id')->nullable()->constrained('tenant_expenses');
            $table->foreignId('meter_reading_id')->nullable()->constrained('meter_readings');
            
            $table->string('name')->comment('ชื่อรายการ ณ ตอนที่ออกบิล');
            $table->integer('previous_unit')->nullable()->comment('หน่วยก่อนหน้า');
            $table->integer('current_unit')->nullable()->comment('หน่วยปัจจุบัน');
            $table->integer('quantity')->comment('จำนวน');
            $table->decimal('price_per_unit', 10, 2)->comment('ราคาต่อหน่วย');
            $table->decimal('subtotal', 10, 2)->comment('ยอดรวม');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_details');
    }
};
