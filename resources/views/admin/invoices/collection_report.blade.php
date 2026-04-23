@extends('admin.layout')
@section('title', 'รายงานเก็บเงินประจำเดือน')
@section('content')
    <div class="container-fluid py-4">
        {{-- ส่วนตัวกรอง (Checkbox) --}}
        <div class="card border-0 shadow-sm mb-4 d-print-none">
            <div class="card-body p-4 bg-white">
                <form method="GET" action="{{ route('admin.invoices.collectionReport') }}">
                    <div class="row g-4">
                        <div class="col-md-3 border-end">
                            <label class="small fw-bold text-muted mb-2">รอบเดือน</label>
                            <input type="month" name="billing_month" class="form-control" value="{{ $billing_month }}"
                                onchange="this.form.submit()">
                        </div>
                        <div class="col-md-3 border-end">
                            <label class="small fw-bold text-muted mb-2">สถานะห้อง</label>
                            <div class="d-flex gap-3 mt-1">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="status_filter[]" value="ว่าง"
                                        id="st_vacant" {{ in_array('ว่าง', $status_filter) ? 'checked' : '' }}
                                        onchange="this.form.submit()">
                                    <label class="form-check-label" for="st_vacant">ว่าง</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="status_filter[]" value="มีผู้เช่า"
                                        id="st_occupied" {{ in_array('มีผู้เช่า', $status_filter) ? 'checked' : '' }}
                                        onchange="this.form.submit()">
                                    <label class="form-check-label" for="st_occupied">เช่า</label>
                                </div>
                                 <div class="form-check ms-3 ps-3 ">
                                    <input class="form-check-input" type="checkbox" name="show_payment_status" value="1"
                                        id="show_payment_status" {{ $show_payment_status ? 'checked' : '' }}
                                        onchange="this.form.submit()">
                                    <label class="form-check-label small fw-bold text-primary"
                                        for="show_payment_status">สถานะการชำระ</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold text-muted mb-2">แสดงรายการคอลัมน์</label>
                            <div class="d-flex flex-wrap gap-3">
                                @foreach ($allExpenseSettings as $expense)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="show_columns[]"
                                            value="{{ $expense->name }}" id="col_{{ $loop->index }}"
                                            {{ in_array($expense->name, $show_columns) ? 'checked' : '' }}
                                            onchange="this.form.submit()">
                                        <label class="form-check-label small"
                                            for="col_{{ $loop->index }}">{{ $expense->name }}</label>
                                    </div>
                                @endforeach
                                
                            </div>
                        </div>
                        <div class="col-12 text-end border-top pt-3">
                            <a href="{{ route('admin.invoices.collectionReport') }}" 
                               class="btn btn-light border rounded-pill px-4 shadow-sm text-muted">
                                <i class="bi bi-eraser-fill me-1"></i> ล้างค่า
                            </a>
                            {{-- 🌟 ปุ่มใหม่: โหลด Excel (สีเขียว) --}}
                            <a href="{{ route('admin.invoices.export_collection_report_excel', request()->query()) }}" 
                            class="btn btn-success px-4 rounded-pill shadow-sm">
                                <i class="bi bi-file-earmark-excel-fill me-2"></i> โหลด Excel
                            </a>
                            <a href="{{ route('admin.invoices.print_collection_report', request()->query()) }}" 
                            class="btn btn-danger px-4 rounded-pill shadow-sm" 
                            target="_blank">
                                <i class="bi bi-file-pdf me-2"></i>โหลด PDF รายงาน
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="text-center mb-4 pt-3">
            <h3 class="fw-bold text-dark mb-1">
                รายงานเก็บเงินประจำเดือน 
                <span class="text-primary">{{ $thai_month }}</span>  
                อาทิตย์ อพาร์ทเม้นท์
            </h3>
        </div>
        
        <div class="table-responsive bg-white rounded shadow-sm">
            <table class="table table-bordered table-hover align-middle mb-0 text-center border-dark" style="font-size: 0.9rem;">
                <thead class="table-dark">
                    <tr>
                        <th>ลำดับ</th>
                        <th>ห้อง</th>
                        <th>ว่าง</th>
                        <th>เช่า</th>
                        <th>ชื่อ-นามสกุล</th>
                        @foreach ($show_columns as $colName)
                            <th>{{ $colName }}</th>
                        @endforeach
                        <th>รวมเงิน</th>
                        <th>วันที่รับเงิน</th>
                        @if($show_payment_status)
                            <th>สถานะการชำระ</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rooms as $index => $room)
                        @php
                            $rowTotal = $room->expense_details->only($show_columns)->sum();
                        @endphp
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td class="fw-bold">{{ $room->room_number }}</td>
                            
                            {{--  นับ เช่า/ว่าง อิงจากบิลที่มีในเดือนนั้น --}}
                            <td>{{ !$room->report_is_occupied ? '1' : '-' }}</td>
                            <td class="fw-bold">{{ $room->report_is_occupied ? '1' : '-' }}</td>
                            
                            <td class="text-start ps-2">
                                @if ($room->report_tenant_name !== '-')
                                    {{ $room->report_tenant_name }}
                                @else
                                    <span class="text-muted italic small">-</span>
                                @endif
                            </td>

                            @foreach ($show_columns as $colName)
                                <td class="text-end">{{ isset($room->expense_details[$colName]) ? number_format($room->expense_details[$colName], 2) : '-' }}</td>
                            @endforeach
                            
                            <td class="fw-bold text-primary text-end">{{ $rowTotal > 0 ? number_format($rowTotal, 2) : '-' }}</td>
                            <td class="small">{{ $room->payment_date_display }}</td>
                            
                            {{--  แสดงสถานะตามบิล --}}
                            @if($show_payment_status)
                                <td class="{{ $room->payment_status !== '-' ? 'fw-bold' : 'text-muted' }}">{{ $room->payment_status }}</td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-light fw-bold border-dark">
                    <tr>
                        <td colspan="2">รวม</td>
                        {{--  นับรวม เช่า/ว่าง แบบอิงจากบิล --}}
                        <td>{{ $rooms->where('report_is_occupied', false)->count() ?: '-' }}</td>
                        <td>{{ $rooms->where('report_is_occupied', true)->count() ?: '-' }}</td>
                        <td>{{ $rooms->where('report_is_occupied', true)->count() }} ราย</td>
                        
                        @foreach ($show_columns as $colName)
                            <td class="text-end">{{ number_format($rooms->sum(fn($r) => $r->expense_details[$colName] ?? 0), 2) }}</td>
                        @endforeach
                        
                        <td class="text-primary text-end">
                            @php
                                $grandTotal = $rooms->sum(function ($r) use ($show_columns) {
                                    return $r->expense_details->only($show_columns)->sum();
                                });
                            @endphp
                            {{ number_format($grandTotal, 2) }}
                        </td>
                        <td></td>
                        @if($show_payment_status)
                            <td></td>
                        @endif
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
@endsection