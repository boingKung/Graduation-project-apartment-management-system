<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountingType extends Model
{
    //
    public $timestamos = false; // ไม่ได้ใช้ timestamps
    protected $fillable = ['name'];

    // หนึ่งประเภทบัญชี (เช่น รายรับ) มีได้หลายหมวดหมู่ (เช่น ค่าเช่า, เงินมัดจำ)
    public function categories(){
        return $this->hasMany(AccountingCategory::class, 'type_id');
    }
}
