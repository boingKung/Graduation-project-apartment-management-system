@extends('admin.layout')

@section('title', 'รายละเอียดผู้เช่า')

@section('content')
    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-12 d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="fw-bold text-dark mb-0">ข้อมูลผู้เช่าห้อง {{ $tenant->room->room_number }}</h3>
                    <p class="text-muted small">รายละเอียดสัญญาและข้อมูลการติดต่อ</p>
                    <a href="{{ route('admin.tenants.show') }}" class="btn btn-light btn-sm mb-2 shadow-sm border"><i
                            class="bi bi-arrow-left"></i> กลับหน้ารายการ</a>
                </div>
                <div>
                    @if ($tenant->status === 'กำลังใช้งาน')
                        <span class="badge bg-success fs-6 px-4 py-2 rounded-pill"><i
                                class="bi bi-check-circle-fill me-1"></i> กำลังใช้งาน</span>
                    @else
                        <span class="badge bg-secondary fs-6 px-4 py-2 rounded-pill"><i class="bi bi-lock-fill me-1"></i>
                            สิ้นสุดสัญญาแล้ว</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- 🌟 1. ส่วนข้อมูลผู้เช่า (ปรับปรุงใหม่ เพิ่มฟิลด์ครบถ้วน) --}}
        <div class="row g-4 mb-4">
            {{-- ฝั่งซ้าย: ข้อมูลส่วนบุคคล และ ที่อยู่ --}}
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                        <h5 class="fw-bold text-primary"><i class="bi bi-person-lines-fill me-2"></i>ข้อมูลส่วนบุคคล และ ที่อยู่</h5>
                    </div>
                    <div class="card-body">
                        
                        {{-- 1.1 ข้อมูลส่วนตัว --}}
                        <h6 class="fw-bold text-dark mb-3 border-bottom pb-2">ข้อมูลเบื้องต้น</h6>
                        <div class="row g-4 mb-4">
                            <div class="col-md-5">
                                <div class="text-muted small mb-1">ชื่อ-นามสกุล</div>
                                <div class="fw-bold fs-5 text-dark">{{ $tenant->first_name }} {{ $tenant->last_name }}</div>
                            </div>
                            <div class="col-md-2">
                                <div class="text-muted small mb-1">อายุ</div>
                                <div class="fw-bold text-dark">{{ $tenant->age ?? '-' }} ปี</div>
                            </div>
                            <div class="col-md-5">
                                <div class="text-muted small mb-1">เบอร์โทรศัพท์</div>
                                <div class="fw-bold"><i class="bi bi-telephone-fill text-success me-1"></i>
                                    {{ $tenant->phone ?? '-' }}</div>
                            </div>
                            <div class="col-12">
                                <div class="text-muted small mb-1">สถานที่ทำงาน</div>
                                <div class="fw-bold text-dark">{{ $tenant->workplace ?? '-' }}</div>
                            </div>
                        </div>

                        {{-- 1.2 ข้อมูลบัตรประชาชน --}}
                        <h6 class="fw-bold text-dark mb-3 border-bottom pb-2 mt-4">เอกสารประจำตัว</h6>
                        <div class="row g-4 mb-4 bg-light p-1 rounded mx-0">
                            <div class="col-md-6">
                                <div class="text-muted small mb-1">เลขบัตรประชาชน</div>
                                <div class="fw-bold text-primary">{{ $tenant->id_card }}</div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted small mb-1">สถานที่ออกบัตร</div>
                                <div class="fw-bold text-dark">{{ $tenant->id_card_issue_place ?? '-' }} {{ $tenant->id_card_issue_province ? 'จ.'.$tenant->id_card_issue_province : '' }}</div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted small mb-1">วันที่ออกบัตร</div>
                                <div class="fw-bold text-dark">{{ $tenant->thai_id_card_issue_date }}</div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted small mb-1">บัตรหมดอายุวันที่</div>
                                <div class="fw-bold text-danger">{{ $tenant->thai_id_card_expiry_date }}</div>
                            </div>
                        </div>

                        {{-- 1.3 ที่อยู่ตามทะเบียนบ้าน --}}
                        <h6 class="fw-bold text-dark mb-3 border-bottom pb-2 mt-4">ที่อยู่ตามทะเบียนบ้าน</h6>
                        <div class="fw-bold bg-white p-3 rounded border border-light shadow-sm">
                            @php
                                $addrParts = array_filter([
                                    $tenant->address_no ? 'เลขที่ ' . $tenant->address_no : null,
                                    $tenant->moo ? 'หมู่ ' . $tenant->moo : null,
                                    $tenant->alley ? 'ตรอก/ซอย ' . $tenant->alley : null,
                                    $tenant->street ? 'ถนน ' . $tenant->street : null,
                                    $tenant->sub_district ? 'ต.' . $tenant->sub_district : null,
                                    $tenant->district ? 'อ.' . $tenant->district : null,
                                    $tenant->province ? 'จ.' . $tenant->province : null,
                                    $tenant->postal_code,
                                ]);
                            @endphp
                            <i class="bi bi-geo-alt-fill text-danger me-2 fs-5"></i>
                            {{ $addrParts ? implode(' ', $addrParts) : 'ไม่มีข้อมูลที่อยู่' }}
                        </div>

                    </div>
                </div>
            </div>

            {{-- ฝั่งขวา: ข้อมูลสัญญา --}}
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                        <h5 class="fw-bold text-dark"><i class="bi bi-file-text-fill text-warning me-2"></i>ข้อมูลสัญญาเช่า
                        </h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush mb-4">
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted">จำนวนผู้อยู่อาศัยในห้อง</span>
                                <span class="fw-bold">{{ $tenant->resident_count }} คน</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted">วันที่เริ่มเช่า</span>
                                <span class="fw-bold text-dark">{{ $tenant->thai_start_date }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted">วันที่สิ้นสุดสัญญา</span>
                                <span
                                    class="fw-bold {{ $tenant->end_date && now()->diffInDays($tenant->end_date, false) <= 30 ? 'text-danger' : 'text-dark' }}">
                                    {{ $tenant->thai_end_date }}
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted">เงินมัดจำ</span>
                                <span
                                    class="fw-bold text-success fs-5">฿{{ number_format($tenant->deposit_amount, 0) }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted">ใช้บริการที่จอดรถ</span>
                                <span class="fw-bold">
                                    @if ($tenant->has_parking)
                                        <span class="badge bg-warning text-dark rounded-pill px-3"><i class="bi bi-car-front-fill me-1"></i> มีรถส่วนตัว</span>
                                    @else
                                        <span class="badge bg-secondary rounded-pill px-3">ไม่มีรถ</span>
                                    @endif
                                </span>
                            </li>
                        </ul>
                        
                        <h6 class="fw-bold text-muted mb-2">เอกสารสัญญาเช่า</h6>
                        @if ($tenant->rental_contract)
                            @php
                                $ext = strtolower(pathinfo($tenant->rental_contract, PATHINFO_EXTENSION));
                            @endphp

                            @if ($ext === 'pdf')
                                <a href="{{ asset('storage/' . $tenant->rental_contract) }}" target="_blank"
                                    class="btn btn-outline-danger w-100 fw-bold ">
                                    <i class="bi bi-file-pdf-fill me-1"></i> เปิดดูไฟล์สัญญา PDF
                                </a>
                            @else
                                <a href="{{ asset('storage/' . $tenant->rental_contract) }}" target="_blank"
                                    class="btn btn-outline-primary w-100 fw-bold ">
                                    <i class="bi bi-image-fill me-1"></i> เปิดดูรูปภาพสัญญา
                                </a>
                            @endif
                        @else
                            <div class="alert alert-light border text-center text-muted p-3">
                                <i class="bi bi-exclamation-circle mb-2 fs-4 d-block"></i>
                                ไม่มีการแนบไฟล์สัญญา
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- ตารางที่ 1: บิลค้างชำระ (Pending Invoices) --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold text-danger mb-0"><i class="bi bi-receipt text-danger me-2"></i>รายการบิลที่รอชำระ</h5>
                {{-- ช่องค้นหาตารางบิล --}}
                <div class="input-group input-group-sm w-25 shadow-sm">
                    <span class="input-group-text bg-white text-muted border-end-0">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" id="searchInvoice" class="form-control border-start-0 ps-0"
                        placeholder="พิมพ์ค้นหารอบเดือน, บิล...">
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="invoiceTable">
                        <thead class="table-light text-muted small">
                            <tr>
                                <th class="ps-4">รอบเดือน</th>
                                <th>เลขที่บิล</th>
                                <th class="text-end">ยอดเรียกเก็บ (฿)</th>
                                <th class="text-end">ค้างชำระ (฿)</th>
                                <th class="text-center">สถานะ</th>
                                <th class="text-center">วันครบกำหนด</th>
                                <th class="text-center">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pendingInvoices as $inv)
                                <tr>
                                    <td class="ps-4 fw-bold">{{ $inv->thai_billing_month }}</td>
                                    <td class="text-muted">{{ $inv->invoice_number }}</td>
                                    <td class="text-end">{{ number_format($inv->total_amount, 2) }}</td>
                                    <td class="text-end text-danger fw-bold">
                                        {{ number_format($inv->remaining_balance, 2) }}</td>
                                    <td class="text-center">
                                        @if ($inv->status === 'ส่งบิลแล้ว')
                                            <span class="badge bg-warning text-dark rounded-pill">ส่งบิลแล้ว</span>
                                        @elseif($inv->status === 'ชำระบางส่วน')
                                            <span class="badge bg-info text-dark rounded-pill">ชำระบางส่วน</span>
                                        @else
                                            <span class="badge bg-danger rounded-pill">ค้างชำระ</span>
                                        @endif
                                    </td>
                                    <td class="text-center small">{{ $inv->thai_due_date }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('admin.invoices.details', $inv->id) }}"
                                            class="btn btn-sm btn-light border text-primary">
                                            <i class="bi bi-eye"></i> ดูบิล
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-success fw-bold">
                                        <i class="bi bi-check-circle-fill fs-4 d-block mb-1"></i> ไม่มีรายการค้างชำระ
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($pendingInvoices->hasPages())
                    <div class="card-footer bg-white border-0 pt-3 pb-2">
                        {{ $pendingInvoices->appends(request()->query())->links() }}
                    </div>
                @endif
            </div>
        </div>

        {{-- ตารางที่ 2: ประวัติการรับชำระเงิน (Payments) --}}
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold text-success mb-0"><i class="bi bi-wallet2 text-success me-2"></i>ประวัติการรับเงิน
                </h5>
                {{-- ช่องค้นหาตารางประวัติ --}}
                <div class="input-group input-group-sm w-25 shadow-sm">
                    <span class="input-group-text bg-white text-muted border-end-0">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" id="searchPayment" class="form-control border-start-0 ps-0"
                        placeholder="พิมพ์ค้นหารายการ, วันที่...">
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="paymentTable">
                        <thead class="table-light text-muted small">
                            <tr>
                                <th class="ps-4">วันที่ชำระ</th>
                                <th>รายการ</th>
                                <th class="text-end">จำนวนเงิน (฿)</th>
                                <th class="text-center">ช่องทาง</th>
                                <th>ผู้บันทึก</th>
                                <th class="text-center">สถานะ</th>
                                <th class="text-center">หลักฐาน</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($payments as $pay)
                                <tr class="{{ $pay->status === 'void' ? 'table-danger text-muted' : '' }}">
                                    <td class="ps-4 fw-bold">{{ $pay->thai_payment_date }}</td>
                                    <td>
                                        {{ $pay->display_title }}
                                        @if ($pay->invoice)
                                            <div class="small text-muted">อ้างอิง: {{ $pay->invoice->invoice_number }}
                                            </div>
                                        @endif
                                    </td>
                                    <td
                                        class="text-end fw-bold {{ $pay->status === 'void' ? 'text-danger' : 'text-success' }}">
                                        {{ number_format($pay->amount_paid, 2) }}
                                    </td>
                                    <td class="text-center small">{{ $pay->payment_method }}</td>
                                    <td class="small">{{ $pay->admin->firstname ?? 'System' }}</td>
                                    <td class="text-center">
                                        @if ($pay->status === 'active')
                                            <span class="badge bg-success-subtle text-success border border-success rounded-pill px-2"><i class="bi bi-check-circle-fill"></i> ปกติ</span>
                                        @elseif ($pay->status === 'รอตรวจสอบ')
                                            <span class="badge bg-info-subtle text-info border border-info rounded-pill px-2"><i class="bi bi-clock-history"></i> รอตรวจสอบ</span>
                                        @elseif (in_array($pay->status, ['ปฏิเสธสลิป', 'ปฏิเสธสลีป']))
                                            <span class="badge bg-warning text-dark border border-warning rounded-pill px-2"><i class="bi bi-shield-x"></i> ปฏิเสธสลิป</span>
                                        @elseif ($pay->status === 'void')
                                            <span class="badge bg-danger-subtle text-danger border border-danger rounded-pill px-2"><i class="bi bi-x-circle-fill"></i> ยกเลิก</span>
                                        @else
                                            <span class="badge bg-secondary rounded-pill px-2">{{ $pay->status }}</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ($pay->slip_image)
                                            <a href="{{ asset('storage/' . $pay->slip_image) }}" target="_blank"
                                                class="text-primary" title="ดูสลิป">
                                                <i class="bi bi-image-fill fs-5"></i>
                                            </a>
                                        @else
                                            <span class="text-muted"><i class="bi bi-dash"></i></span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        <i class="bi bi-inbox fs-4 d-block mb-1"></i> ยังไม่มีประวัติการชำระเงิน
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($payments->hasPages())
                    <div class="card-footer bg-white border-0 pt-3 pb-2">
                        {{ $payments->appends(request()->query())->links() }}
                    </div>
                @endif
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        // สคริปต์สำหรับระบบค้นหาตารางบิลค้างชำระ (Live Search)
        document.getElementById('searchInvoice').addEventListener('keyup', function() {
            let filter = this.value.toLowerCase();
            let rows = document.querySelectorAll('#invoiceTable tbody tr');

            rows.forEach(row => {
                if (!row.querySelector('td[colspan]')) {
                    let text = row.innerText.toLowerCase();
                    row.style.display = text.includes(filter) ? '' : 'none';
                }
            });
        });

        // สคริปต์สำหรับระบบค้นหาตารางประวัติการรับเงิน (Live Search)
        document.getElementById('searchPayment').addEventListener('keyup', function() {
            let filter = this.value.toLowerCase();
            let rows = document.querySelectorAll('#paymentTable tbody tr');

            rows.forEach(row => {
                if (!row.querySelector('td[colspan]')) {
                    let text = row.innerText.toLowerCase();
                    row.style.display = text.includes(filter) ? '' : 'none';
                }
            });
        });
    </script>
@endpush