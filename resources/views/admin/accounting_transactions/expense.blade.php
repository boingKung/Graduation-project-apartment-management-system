@extends('admin.layout')
@section('content')
<div class="container py-4 bg-white shadow-sm rounded">
    {{-- แถบควบคุม (Navigation & Filters) --}}
    <div class="card border-0 shadow-sm mb-4 d-print-none">
        <div class="card-body p-3">
            <div class="row g-3 align-items-center">
                <div class="col-xl-5">
                    <div class="btn-group w-100 shadow-sm">
                        <a href="{{ route('admin.accounting_transactions.summary', request()->query()) }}" 
                           class="btn {{ request()->routeIs('admin.accounting_transactions.summary') ? 'btn-dark' : 'btn-outline-dark' }} btn-sm px-3">
                            <i class="bi bi-file-earmark-bar-graph me-1"></i> สรุปงบรับ-จ่าย
                        </a>
                        <a href="{{ route('admin.accounting_transactions.income', request()->query()) }}" 
                            class="btn btn-outline-success btn-sm px-3">
                            <i class="bi bi-graph-up-arrow me-1"></i> รายงานรายรับ
                        </a>
                        <a href="{{ route('admin.accounting_transactions.expense', request()->query()) }}" 
                            class="btn {{ request()->routeIs('admin.accounting_transactions.expense') ? 'btn-danger' : 'btn-outline-danger' }} btn-sm px-3">
                            <i class="bi bi-graph-down-arrow me-1"></i> รายงานรายจ่าย
                        </a>
                    </div>
                </div>
                <div class="col-xl-7">
                    <form method="GET" class="row g-2 justify-content-end align-items-center">
                        <div class="col-auto"><label class="small fw-bold text-muted">ตั้งแต่วันที่</label></div>
                        <div class="col-auto"><input type="date" name="date_start" class="form-control form-control-sm" value="{{ $startDate }}" onchange="this.form.submit()"></div>
                        <div class="col-auto"><label class="small fw-bold text-muted">ถึงวันที่</label></div>
                        <div class="col-auto"><input type="date" name="date_end" class="form-control form-control-sm" value="{{ $endDate }}" onchange="this.form.submit()"></div>
                        <div class="col-auto d-flex gap-1">
                            <a href="{{ route('admin.accounting_transactions.expense') }}" class="btn btn-light btn-sm border" title="ล้างค่า"><i class="bi bi-arrow-clockwise"></i></a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="text-center mb-4">
        <h3 class="fw-bold text-danger">รายจ่าย {{ $thai_startDate }} - {{ $thai_endDate }}</h3>
    </div>
     <div class="d-flex justify-content-end mb-3">
        {{-- 🌟 ปุ่ม Excel สีเขียว --}}
            <a href="{{ route('admin.accounting_transactions.exportExpenseExcel', request()->query()) }}" 
            class="btn btn-outline-success btn-sm me-3">
                <i class="bi bi-file-earmark-excel-fill me-1"></i> โหลด Excel
            </a>
         <a href="{{ route('admin.accounting_transactions.printExpensePdf', request()->query()) }}" 
         class="btn btn-outline-danger btn-sm px-3" target="_blank">
             <i class="bi bi-file-earmark-pdf-fill me-1"></i> โหลด PDF รายจ่าย
         </a>
     </div>
    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle border-dark shadow-sm">
            <thead class="bg-light text-center  border-dark fw-bold text-uppercase small">
                <tr>
                    <th width="20%">รายการ</th>
                    <th width="35%">รายละเอียด</th>
                    <th width="20%">จำนวนเงิน (บาท)</th>
                    <th width="25%">ยอดรวมหมวด (บาท)</th>
                </tr>
            </thead>
            <tbody>
                @php $grandTotalExpense = 0; @endphp

                @forelse($expenseByGroup as $catName => $transactions)
                    @php 
                        $catTotal = $transactions->sum('amount');
                        $grandTotalExpense += $catTotal;
                        $rowCount = $transactions->count();
                    @endphp
                    
                    @foreach($transactions as $index => $t)
                    <tr>
                        @if($index === 0)
                            {{-- แสดงชื่อหมวดหมู่เฉพาะบรรทัดแรกของกลุ่ม --}}
                            <td rowspan="{{ $rowCount }}" class="ps-3 bg-light text-center fw-bold pt-3">
                                {{ $catName }}
                            </td>
                        @endif
                        <td class="ps-3 small">
                            <div class="text-dark">{{ $t->title }}</div>
                            @if($t->description) 
                                <div class=" mt-1">- {{ $t->description }}</div> 
                            @endif
                        </td>
                        <td class="text-end pe-3 text-dark">{{ number_format($t->amount, 2) }}</td>
                        @if($index === 0)
                            {{-- แสดงยอดรวมหมวดแบบ Rowspan ด้านขวาสุดเพื่อให้สมดุล --}}
                            <td rowspan="{{ $rowCount }}" ></td>
                        @endif
                    </tr>
                    @endforeach

                    {{--  แถวสรุปย่อยของหมวดหมู่เพื่อเน้นย้ำความชัดเจน --}}
                    <tr class="bg-light table-sm">
                        <td colspan="2" class=" pe-3 small fw-bold ">รวม {{ $catName }}</td>
                        <td></td>
                        <td class="text-end pe-3 fw-bold border-start-0">{{ number_format($catTotal, 2) }}</td>
                    </tr>

                    {{--  แถวว่างเว้นบรรทัด (Spacer) ระหว่างหมวดหมู่ --}}
                    <tr><td colspan="4" class="py-2 bg-white shadow-none"></td></tr>

                @empty
                    <tr><td colspan="4" class="text-center py-5 text-muted">ไม่พบข้อมูลรายจ่ายในเดือนนี้</td></tr>
                @endforelse
            </tbody>
            <tfoot class="table-dark">
                <tr class="border-top border-3 border-dark">
                    <th colspan="3" class="text-center py-3 fs-5">รวมรายจ่ายทั้งสิ้น (เงินสด เงินโอน)</th>
                    <th class="text-end pe-3 fs-4 text-warning">{{ number_format($grandTotalExpense, 2) }}</th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<style>
    @media print { 
        .d-print-none { display: none !important; } 
        .container { max-width: none; width: 100%; border:0; padding:0; }
        .table { border: 2px solid #000 !important; width: 100% !important; }
        .table td, .table th { border: 1px solid #000 !important; }
        /* ซ่อนแถวว่างตอนพิมพ์ถ้าต้องการความกระชับ */
        .bg-white { background-color: transparent !important; }
    }
</style>
@endsection