<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountingTransaction extends Model
{
    //
    protected $fillable = [
        'category_id' , 'payment_id' , 'tenant_id', 'user_id',
        'room_id', 'building_id',
        'title' , 'amount' , 'entry_date' , 'description' ,'status'
    ];
    // กำหนดว่า entry_date คือวันที่ เพื่อให้ Carbon จัดการได้ง่ายขึ้น
    protected $casts = [
        'entry_date' => 'date',
    ];

    /**
     * รายการธุรกรรมนี้ อยู่ในหมวดหมู่ใด
     */
    public function category(){
        return $this->belongsTo(AccountingCategory::class , 'category_id');
    }

    /**
     * เชื่อมโยงกับประวัติการชำระเงิน (ถ้ามี) 
     */
    public function payment(){
        return $this->belongsTo(Payment::class , 'payment_id');
    }

    /**
     * เชื่อมโยงกับผู้เช่ากรณี เงินมัดจำ
     */
    public function tenant(){
        return $this->belongsTo(Tenant::class , 'tenant_id');
    }
    public function admin() {
        // เชื่อม user_id ไปยังตาราง users เพื่อดูผู้บันทึก
        return $this->belongsTo(User::class, 'user_id');
    }
    // 🌟 เพิ่มความสัมพันธ์ไปยัง Room
    public function room(){
        return $this->belongsTo(Room::class, 'room_id');
    }

    // 🌟 เพิ่มความสัมพันธ์ไปยัง Building (สำหรับค่าใช้จ่ายส่วนกลางของตึก)
    public function building(){
        return $this->belongsTo(Building::class, 'building_id');
    }
}
