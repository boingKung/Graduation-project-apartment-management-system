<?php
namespace App\Exports;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class CollectionReportExport implements FromView, ShouldAutoSize {
    protected $data;
    public function __construct($data) { $this->data = $data; }
    public function view(): View { 
        return view('admin.invoices.excel.collection_report', $this->data); 
    }
}