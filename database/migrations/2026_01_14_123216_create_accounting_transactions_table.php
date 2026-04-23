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
        Schema::create('accounting_transactions', function (Blueprint $table) {
            $table->id();
            // เชื่อมโยงกับหมวดหมู่
            $table->foreignId('category_id')->constrained('accounting_categories')->onDelete('cascade')->comment('เชื่อมตาราง หมวดหมู่');
            
            // เชื่อมโยงกับประวัติการจ่ายเงิน (ถ้าเป็นรายรับจากใบแจ้งหนี้ให้ใส่ ID นี้ไว้)
            // ตั้งเป็น nullable เพื่อรองรับรายการที่ Admin แอดเองโดยไม่เกี่ยวกับใบแจ้งหนี้
            $table->foreignId('payment_id')->nullable()->constrained('payments')->onDelete('set null')->comment('เชื่อมโยงกับประวัติการจ่ายเงิน ค่าเช่า');
            // เชื่อมกับผู้เช่า (Nullable เพราะบางรายการ เช่น ซื้อไม้กวาด ไม่เกี่ยวกับผู้เช่า)
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->onDelete('set null')->comment('เชื่อมตารางผู้เช่า กรณี ค่ามัดจำ');
            // หัวข้อรายการธุรกรรม
            $table->string('title')->comment('หัวข้อรายการธุรกรรม'); 
            
            // จำนวนเงินสุทธิของรายการนั้นๆ
            $table->decimal('amount', 10, 2)->comment('จำนวนเงินสุทธิของรายการนั้นๆ'); 
            
            // วันที่ลงบัญชี (ใช้สำหรับกรองข้อมูลรายวัน/เดือน)
            $table->date('entry_date')->comment('วันที่ลงบัญชี (ใช้สำหรับกรองข้อมูลรายวัน/เดือน)'); 
            
            // รายละเอียดเพิ่มเติม
            $table->text('description')->nullable()->comment('รายละเอียดเพิ่มเติม');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounting_transactions');
    }
};
