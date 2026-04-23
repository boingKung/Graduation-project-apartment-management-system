<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountingCategory extends Model
{
    //
    protected $fillable = ['type_id','name'];

    /**
     * หมวดหมู่ย่อยนี้ ขึ้นตรงกับประเภทบัญชีใดบัญชีหนึ่ง
     */
    public function type(){
        return $this->belongsTo(AccountingType::class, 'type_id');
    }

    /**
     * หนึ่งหมวดหมู่ มีรายการธุรกรรมเกิดขึ้นได้หลายครั้ง 
     */
    public function transactions(){
        return $this->hasMany(AccountingTransaction::class, 'category_id');
    }

    public function tenant_expense()
    {
        // เปลี่ยนจาก hasOne เป็น hasMany เพราะ 1 บิล อาจมีการแบ่งจ่ายหลายรอบ
        return $this->hasOne(TenantExpense::class, 'accounting_category_id');
    }

}
