<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    //
    protected $fillable = [
        'invoice_id' , 'amount_paid' , 'payment_date', 'user_id',
        'payment_method' , 'slip_image', 'note' , 'status'
    ];
    protected $casts = [
        'payment_date' => 'date',
    ];
    /**
     * ประวัติการชำระเงินนี้ อ้างอิงถึงใบแจ้งหนี้ใบใดใบหนึ่ง 
     */
    public function invoice(){
        return $this->belongsTo(Invoice::class);
    }
    /**
     * ประวัติการชำระเงินก้อนนี้ ถูกบันทึกลงในระบบบัญชีรายการใด 
     */
    public function payments()
    {
        // เปลี่ยนจาก hasOne เป็น hasMany เพราะ 1 บิล อาจมีการแบ่งจ่ายหลายรอบ
        return $this->hasMany(Payment::class, 'invoice_id');
    }

    public function admin() {
        return $this->belongsTo(User::class, 'user_id');
    }

    // เพิ่มฟังก์ชันนี้เข้าไป เพื่อเชื่อมไปยังตารางบัญชี (สำคัญมากสำหรับแก้บัค)
    public function accounting_transactions()
    {
        return $this->hasMany(AccountingTransaction::class, 'payment_id');
    }
}
