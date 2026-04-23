<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantExpense extends Model
{
    //
    protected $fillable = ['name', 'price','accounting_category_id'];
    public function category()
    {
        return $this->belongsTo(AccountingCategory::class, 'accounting_category_id');
    }

    public function invoiceDetails(){
        return $this->hasMany(InvoiceDetail::class, 'tenant_expense_id');
    }
}
