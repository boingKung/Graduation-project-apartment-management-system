<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithColumnWidths;

class ExpenseReportExport implements FromView, WithColumnWidths
{
    protected $data;

    public function __construct($data) 
    { 
        $this->data = $data; 
    }

    public function view(): View 
    { 
        return view('admin.accounting_transactions.excel.expense_excel', $this->data); 
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20, // หมวดหมู่
            'B' => 50, // รายละเอียด (กว้างหน่อยเพราะต้องใส่ทั้ง Title และ Description)
            'C' => 20, // รายย่อย (บาท)
            'D' => 25, // รวมหมวด (บาท)
        ];
    }
}