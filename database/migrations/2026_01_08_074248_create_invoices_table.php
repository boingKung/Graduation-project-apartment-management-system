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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('room_id')->constrained('rooms')->onDelete('cascade');
            $table->string('invoice_number')->unique()->comment('หมายเลขใบแจ้งหนี้');
            $table->string('billing_month', 7)->comment('เดือนที่เรียกเก็บ เช่น 2026-01');
            $table->decimal('total_amount', 10, 2)->default(0)->comment('ยอดรวมทั้งหมด');
            $table->string('status')->default('ค้างชำระ')->comment('ค้างชำระ, รอตรวจสอบ, จ่ายแล้ว, ยกเลิก');
            $table->date('due_date')->comment('วันครบกำหนดชำระ');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
