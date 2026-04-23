<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithColumnWidths; // 🌟 1. นำเข้าคลาสนี้

// 🌟 2. ใส่ implements WithColumnWidths
class SummaryReportExport implements FromView, WithColumnWidths 
{
    protected $data;

    public function __construct($data) { 
        $this->data = $data; 
    }

    public function view(): View { 
        return view('admin.accounting_transactions.excel.summary_excel', $this->data); 
    }

    // 🌟 3. เพิ่มฟังก์ชันนี้เพื่อกำหนดความกว้างแต่ละคอลัมน์ (หน่วยเป็นพิกเซลของ Excel)
    public function columnWidths(): array
    {
        return [
            'A' => 30, // คอลัมน์รายการ
            'B' => 45, // คอลัมน์รายละเอียด
            'C' => 20, // คอลัมน์รายรับ
            'D' => 20, // คอลัมน์รายจ่าย
        ];
    }
}