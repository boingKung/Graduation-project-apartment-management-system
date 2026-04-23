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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->onDelete('cascade')->comment('เชื่อมตาราง invoices');
            $table->decimal('amount_paid',10,2)->comment('จำนวนจ่ายจริง กรณีจ่ายไม่เต็ม หรือมีส่วนลด');
            $table->date('payment_date')->comment('วันที่ได้รับชำระเงินจริง');
                // ช่องทางการจ่าย (เงินสด, โอนผ่านธนาคาร)
            $table->string('payment_method')->comment('ช่องทางการจ่าย (เงินสด, โอนผ่านธนาคาร)'); 
            // เก็บพาธรูปภาพสลิปโอนเงิน (ถ้ามี)
            $table->string('slip_image')->nullable()->comment('เก็บพาธรูปภาพสลิปโอนเงิน (ถ้ามี)'); 
            // หมายเหตุเพิ่มเติมจาก Admin
            $table->text('note')->nullable()->comment('หมายเหตุเพิ่มเติมจาก Admin');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
