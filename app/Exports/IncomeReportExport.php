<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithColumnWidths; // เพิ่มเพื่อกำหนดความกว้างเอง

class IncomeReportExport implements FromView, WithColumnWidths
{
    protected $data;

    public function __construct($data) 
    { 
        $this->data = $data; 
    }

    public function view(): View 
    { 
        return view('admin.accounting_transactions.excel.income_excel', $this->data); 
    }

    // กำหนดความกว้างคอลัมน์ของรายงานรายรับ
    public function columnWidths(): array
    {
        return [
            'A' => 45, // รายการ (คอลัมน์นี้ข้อความจะยาวหน่อย)
            'B' => 25, // รายย่อย (บาท)
            'C' => 25, // ยอดรวมสุทธิ (บาท)
        ];
    }
}