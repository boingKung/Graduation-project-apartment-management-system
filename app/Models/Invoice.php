<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    // ระบุให้ส่งค่าเหล่านี้ไปใน JSON ด้วย สร้าง ฟิล เสมือน ไว้ใช้ในการคำนวณ
    protected $appends = ['total_paid', 'remaining_balance'];

    //
    protected $fillable = [
        'tenant_id',
        'room_id',
        'user_id',
        'invoice_number', 
        'billing_month',
        'issue_date',
        'total_amount',
        'status',
        'due_date'
    ];

    // ดึงรายการย่อยของใบแจ้งหนี้
    public function details() {
        return $this->hasMany(InvoiceDetail::class, 'invoice_id');
    }

    public function tenant() {
        return $this->belongsTo(Tenant::class);
    }

    public function payments() {
        return $this->hasMany(Payment::class, 'invoice_id');
    }
    // เชื่อมหา Admin ที่เป็นคนออกบิล
    public function admin() {
        return $this->belongsTo(User::class, 'user_id');
    }
    // คำนวณยอดที่ชำระแล้วทั้งหมด
    public function getTotalPaidAttribute() {
        // รวมยอดเงินที่จ่ายจริงจากตาราง payments
        return $this->payments()->where('status', 'active')->sum('amount_paid');
    }

    // คำนวณยอดคงเหลือที่ต้องจ่ายเพิ่ม
    public function getRemainingBalanceAttribute() {
        // ยอดเต็มในบิล - ยอดที่จ่ายมาแล้ว
        return max(0, $this->total_amount - $this->total_paid);
    }
    public function room() {
        return $this->belongsTo(Room::class, 'room_id');
    }
}
