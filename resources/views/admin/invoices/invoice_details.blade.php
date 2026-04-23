@extends('admin.layout')
@section('title', 'อ่านใบแจ้งหนี้')
@section('content')
<div class="container py-4">
    <div class="card shadow-sm border-0 ">
        <div class="card-body p-5">
            <div class="row mb-4">
                <div class="col-sm-6">
                    <h3 class="fw-bold text-primary">{{ $apartment->name ?? 'ชื่ออพาร์ทเม้นท์' }} </h3>
                    <p class="mb-0">
                        {{ $apartment->address_no }} 
                        หมู่ {{ $apartment->moo }} 
                        ต.{{ $apartment->sub_district }} 
                        อ.{{ $apartment->district }} 
                        จ.{{ $apartment->province }} 
                        {{ $apartment->postal_code }}
                    </p>
                    <p class="mb-0">โทร: {{ $apartment->phone ?? '-' }}</p>
                </div>
                <div class="col-sm-6 text-end">
                    <h2 class="text-uppercase fw-bold">ใบเสร็จรับเงิน</h2>
                    <div class="mt-3">
                        <p class="mb-0"><strong>เลขที่:</strong> {{ $invoice->invoice_number }}</p>
                        <p class="mb-0">
                            <strong>ประจำเดือน:</strong> 
                            {{ $invoice->thai_billing_month }} 
                        </p>
                    </div>
                </div>
            </div>

            <hr>

            <div class="row mb-4">
                <div class="col-sm-6">
                    <h6 class="text-muted">ส่งถึง (ผู้เช่า):</h6>
                    <h5 class="fw-bold">{{ $invoice->tenant->first_name }} {{ $invoice->tenant->last_name }}</h5>
                    <p class="mb-0">                        
                        <strong>ที่อยู่ </strong> {{ $invoice->tenant->address_no }} 
                        หมู่ {{ $invoice->tenant->moo }} 
                        ต.{{ $invoice->tenant->sub_district }} 
                        อ.{{ $invoice->tenant->district }} 
                        จ.{{ $invoice->tenant->province }} 
                        {{ $invoice->tenant->postal_code }}
                    </p>
                    <p class="mb-0">ห้อง: {{ $invoice->tenant->room->room_number }} </p>
                    <p class="mb-0">เบอร์โทร: {{ $invoice->tenant->phone }}</p>
                </div>
                <div class="col-sm-6 text-end">
                    <h6 class="text-muted">วันที่ออกบิล:</h6>
                    <p>{{ $invoice->thai_issue_date }}</p>
                    <h6 class="text-muted">วันที่จดมิเตอร์ </h6>
                    <p class="text-danger fw-bold">{{  $invoice->thai_reading_date }} </p>
                    <h6 class="text-muted">กำหนดชำระ (Due Date):</h6>
                    <p class="text-danger fw-bold">{{ $invoice->thai_due_date }}</p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-light text-center">
                        <tr>
                            <th>ลำดับ</th>
                            <th>รายการ</th>
                            <th>จดครั้งก่อน</th>
                            <th>จดครั้งหลัง</th>
                            <th>จำนวนหน่วย</th>
                            <th>ราคา/หน่วย</th>
                            <th>จำนวนเงิน</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoice->details as $index => $item)
                        <tr>
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td>{{ $item->name }}</td>
                            <td class="text-center">{{ $item->previous_unit ?? '-' }}</td>
                            <td class="text-center">{{ $item->current_unit ?? '-' }}</td>
                            <td class="text-center">{{ $item->quantity }}</td>
                            <td class="text-end">{{ number_format($item->price_per_unit, 2) }}</td>
                            <td class="text-end">{{ number_format($item->subtotal, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-center fw-bold">{{ $invoice->total_amount_thai }}</td>
                            <td colspan="3" class="text-center fw-bold">ยอดรวมสุทธิ</td>
                            <td class="text-end fw-bold text-primary" style="font-size: 1.2rem;">
                                {{ number_format($invoice->total_amount, 2) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="mt-5 pt-4 border-top"> {{-- เพิ่มเส้นแบ่งและระยะห่างด้านบน --}}
                <div class="row align-items-center">
                    {{-- ส่วนหมายเหตุ: ปรับให้ดูซอฟต์ลง --}}
                    <div class="col-sm-7 mb-3 mb-sm-0">
                        <div class="p-3 rounded-3 bg-light border-0">
                            <h6 class="fw-bold mb-1 text-dark"><i class="bi bi-info-circle me-1"></i> หมายเหตุ:</h6>
                            <p class="mb-0 text-muted small">
                                กรุณาชำระเงินภายในวันที่กำหนดเพื่อหลีกเลี่ยงค่าปรับตามระเบียบของหอพัก <br>
                                หากชำระเงินแล้ว โปรดเก็บหลักฐานการโอนเงินไว้เพื่อตรวจสอบ
                            </p>
                        </div>
                    </div>
            
                    {{-- ส่วนปุ่ม Action: จัดกลุ่มและเพิ่ม Icon ให้ครบ --}}
                    <div class="col-sm-5 text-end no-print">
                        <div class="d-flex flex-wrap justify-content-end gap-2">
                            {{-- ปุ่มกลับ --}}
                            <a href="{{ url()->previous() }}" class="btn btn-outline-secondary px-3 shadow-sm">
                                <i class="bi bi-arrow-left"></i> กลับ
                            </a>
                            @if ($invoice->status !== 'ชำระแล้ว')
                            {{-- ปุ่มแก้ไข --}}
                            <a href="{{ route('admin.invoices.editDetails' , $invoice->id ) }}" class="btn btn-warning px-3 shadow-sm fw-bold">
                                <i class="bi bi-pencil-square me-1"></i> แก้ไขรายการ
                            </a>
                            @endif
                            {{-- ปุ่มพิมพ์ --}}
                            <a href="{{ route('admin.invoices.print_invoice_details',$invoice->id) }}" target="_blank"
                                class="btn btn-success px-4 shadow-sm fw-bold">
                                <i class="bi bi-printer me-1"></i> พิมพ์ใบเสร็จรับเงิน
                            </a>
                            {{-- <button onclick="window.print();" class="btn btn-success px-4 shadow-sm fw-bold">
                                <i class="bi bi-printer me-1"></i> พิมพ์ใบแจ้งหนี้
                            </button> --}}
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
    /* เพิ่มการจัดการตอนพิมพ์ให้เนียนขึ้น */
    /* @media print {
        .no-print { display: none !important; }
        .border-top { border-top: 1px solid #dee2e6 !important; }
        body { background-color: white !important; }
    } */
</style>
@endsection