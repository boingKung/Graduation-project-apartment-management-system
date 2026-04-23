@extends('admin.layout')
@section('title', 'รับชำระเงินและจัดการบิลค้างชำระ')
@section('content')
    <div class="container-fluid py-4">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex align-items-center flex-wrap gap-2"> 
            <h4 class="fw-bold text-dark mb-0">รายการค้างชำระแยกตามห้อง</h4>
                @php
                    $headingMonth = request('filter_month') 
                        ? \Carbon\Carbon::parse(request('filter_month'))->locale('th')->isoFormat('MMMM YYYY') 
                        : 'ทั้งหมด (ทุกรอบเดือน)';
                @endphp
                <span class="badge bg-primary shadow-sm fs-6 px-3 py-2 rounded-pill">
                    <i class="bi bi-calendar-check me-1"></i> รอบเดือน: {{ $headingMonth }}
                </span>
            </div>
            <a href="{{ route('admin.payments.history') }}" class="btn btn-outline-secondary btn-sm shadow-sm fw-bold">
                <i class="bi bi-clock-history me-1"></i> ประวัติการรับเงิน
            </a>
        </div>

        {{-- แถบเครื่องมือค้นหา (Filter) --}}
        <div class="bg-white p-3 rounded-3 shadow-sm border mb-4">
            <form method="GET" action="{{ route('admin.payments.pendingInvoicesShow') }}" class="row g-3 align-items-center" id="searchFilterForm">

                <div class="col-auto">
                    <span class="fw-bold text-dark small"><i class="bi bi-funnel text-primary me-1"></i> กรองข้อมูล:</span>
                </div>

                {{-- ช่องค้นหาเลขห้อง --}}
                <div class="col-md-3">
                    <div class="input-group input-group-sm shadow-sm rounded">
                        <span class="input-group-text bg-white text-muted border-end-0"><i class="bi bi-door-closed"></i></span>
                        <input type="text" name="search_room" class="form-control border-start-0 ps-0"
                            placeholder="ระบุเลขห้อง..." value="{{ request('search_room') }}"
                            onchange="this.form.submit()">
                    </div>
                </div>

                {{-- Input เลือกรอบเดือน --}}
                <div class="col-md-3">
                    <div class="input-group input-group-sm shadow-sm rounded">
                        <span class="input-group-text bg-white text-muted border-end-0"><i class="bi bi-calendar-event"></i></span>
                        <input type="month" name="filter_month" class="form-control border-start-0 ps-0" 
                            value="{{ request('filter_month', date('Y-m')) }}" 
                            onchange="this.form.submit()">
                    </div>
                </div>

                {{-- ตัวกรองสถานะ --}}
                <div class="col-md-3">
                    <select name="filter_status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">-- ทุกสถานะ --</option>
                        <option value="ค้างชำระ" {{ request('filter_status') == 'ค้างชำระ' ? 'selected' : '' }}>ค้างชำระ</option>
                        <option value="ชำระบางส่วน" {{ request('filter_status') == 'ชำระบางส่วน' ? 'selected' : '' }}>ชำระบางส่วน</option>
                        <option value="รอตรวจสอบ" {{ request('filter_status') == 'รอตรวจสอบ' ? 'selected' : '' }}>รอตรวจสอบ (มีสลิป)</option>
                    </select>
                </div>

                {{-- ปุ่มล้างค่า --}}
                <div class="col-auto ms-auto">
                    <a href="{{ route('admin.payments.pendingInvoicesShow') }}"
                        class="btn btn-light btn-sm px-3 text-muted border shadow-sm fw-bold" title="ล้างการค้นหา">
                        <i class="bi bi-eraser-fill me-1"></i> ล้างค่า
                    </a>
                </div>

            </form>
        </div>

        {{-- วนลูปกลุ่มห้อง --}}
        @forelse ($pendingInvoices->groupBy('tenant.room.room_number') as $roomNumber => $invoices)
            <div class="card border-0 shadow-sm mb-4 rounded-3 overflow-hidden">
                {{-- ส่วนหัวห้องพัก --}}
                <div class="card-header bg-light border-bottom py-3 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <h5 class="mb-0 fw-bold text-dark me-2">เลขห้อง {{ $roomNumber }}</h5>
                        <span class="badge bg-warning text-dark small px-3 py-1 shadow-sm rounded-pill">รายละเอียดบิล</span>
                    </div>
                    <div class="text-end text-muted small d-none d-sm-block">
                        ผู้เช่า: <span class="fw-bold text-dark">{{ $invoices->first()->tenant->first_name ?? '-' }}</span>
                    </div>
                </div>

                <div class="card-body p-3">
                    @foreach ($invoices as $inv)
                        @php
                            $pendingPayment = $inv->payments->where('status', 'รอตรวจสอบ')->last();
                            
                            // 🌟 ตรวจสอบว่าเคยมีการชำระแบบ active ไปแล้วบ้างหรือไม่
                            $totalPaidActive = $inv->payments->where('status', 'active')->sum('amount_paid');
                            $isPartial = $totalPaidActive > 0 && $totalPaidActive < $inv->total_amount;
                            
                            // 🌟 ยอดคงเหลือ = ยอดเต็ม - ยอดที่ชำระผ่านแล้ว (active)
                            $remaining = max(0, $inv->total_amount - $totalPaidActive);
                            
                            $paidPct = $inv->total_amount > 0 ? min(100, round(($totalPaidActive / $inv->total_amount) * 100)) : 0;
                            $dueDate = \Carbon\Carbon::parse($inv->due_date);
                            $isOverdue = $dueDate->isPast();
                            $daysLeft = $dueDate->diffInDays(now(), false);
                        @endphp
                        
                        <div class="card mb-3 border border-light shadow-sm" style="background-color: #fcfcfc;">
                            <div class="card-body p-3">
                                <div class="row align-items-center">
                                    {{-- ฝั่งซ้าย: ข้อมูลบิล --}}
                                    <div class="col-md-7 col-lg-8">
                                        <div class="d-flex align-items-center mb-2 flex-wrap gap-2">
                                            <i class="bi bi-x-circle-fill text-danger fs-5"></i>
                                            <span class="badge bg-white text-primary border border-primary px-3 py-1 shadow-sm" style="font-size: 0.85rem;">
                                                <i class="bi bi-calendar-check me-1"></i>{{ \Carbon\Carbon::parse($inv->billing_month)->locale('th')->isoFormat('MMMM YYYY') }}
                                            </span>
                                            <h6 class="mb-0 fw-bold text-dark">
                                                ครบกำหนดชำระ : {{ $inv->thai_due_date }}
                                            </h6>
                                            
                                            @if ($isPartial)
                                                <span class="badge bg-info text-dark small px-2 py-1 shadow-sm">จ่ายแล้วบางส่วน</span>
                                            @endif
                                            
                                            @if($isOverdue)
                                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger small px-2 py-1">
                                                    เลยกำหนดมา {{ (int)$daysLeft }} วัน
                                                </span>
                                            @endif
                                        </div>
                                        
                                        <div class="ms-4 ps-2">
                                            @if ($isPartial)
                                                <div class="mb-1 text-muted small d-flex gap-3">
                                                    <span>ยอดเต็ม: {{ number_format($inv->total_amount, 2) }}</span>
                                                    <span>ชำระแล้ว: <span class="text-success fw-bold">{{ number_format($totalPaidActive, 2) }}</span></span>
                                                </div>
                                                <p class="mb-2 text-dark fw-bold">
                                                    ยอดคงเหลือที่ต้องจ่าย: <span class="text-danger fs-5">{{ number_format($remaining, 2) }}</span> บาท
                                                </p>
                                                
                                                {{-- Progress Bar แสดง % การจ่ายเงิน --}}
                                                <div class="d-flex align-items-center gap-2 mt-2" style="max-width: 300px;">
                                                    <div class="progress flex-grow-1" style="height: 6px;">
                                                        <div class="progress-bar bg-success" role="progressbar" style="width: {{ $paidPct }}%;" aria-valuenow="{{ $paidPct }}" aria-valuemin="0" aria-valuemax="100"></div>
                                                    </div>
                                                    <small class="text-muted fw-bold" style="font-size: 0.7rem;">{{ $paidPct }}%</small>
                                                </div>
                                            @else
                                                <p class="mb-0 text-muted">
                                                    ยอดค้างชำระ: <span class="fw-bold text-danger fs-5">{{ number_format($inv->total_amount, 2) }}</span> <span class="fw-bold text-danger">บาท</span>
                                                </p>
                                            @endif

                                            @if($pendingPayment)
                                                <div class="mt-3 mb-1">
                                                    <span class="badge bg-warning bg-gradient text-dark border border-warning shadow-sm py-2 px-3 fs-6">
                                                        <i class="bi bi-bell-fill text-danger me-1"></i> ผู้เช่าแจ้งชำระเงินแล้ว (รอตรวจสอบ)
                                                    </span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- ฝั่งขวา: ปุ่ม Action --}}
                                    <div class="col-md-5 col-lg-4 text-md-end mt-4 mt-md-0 d-flex justify-content-md-end gap-2">
                                        <a href="{{ route('admin.invoices.details', $inv->id) }}" class="btn btn-outline-secondary px-3 shadow-sm border-2" title="อ่านรายละเอียดบิล">
                                            <i class="bi bi-eye-fill"></i>
                                        </a>
                                        
                                        <button class="btn btn-success px-4 shadow-sm fw-bold border-2" onclick="openPaymentModal({{ json_encode($inv) }}, {{ json_encode($pendingPayment) }}, {{ $remaining }})">
                                            @if($pendingPayment)
                                                <i class="bi bi-search me-1"></i> ตรวจสอบสลิป
                                            @else
                                                <i class="bi bi-cash-coin me-1"></i> จ่ายค่าห้อง <i class="bi bi-caret-down-fill ms-1 small"></i>
                                            @endif
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="text-center py-5 bg-white rounded-3 shadow-sm border mt-4">
                <i class="bi bi-check-circle-fill text-success mb-3" style="font-size: 4rem;"></i>
                <h4 class="fw-bold text-success mb-2">ไม่มีรายการค้างชำระ</h4>
                <p class="text-muted mb-4">ยอดเยี่ยม! ผู้เช่าทุกห้องที่คุณค้นหาชำระเงินครบถ้วนแล้ว</p>
                @if(request('search_room') || request('filter_month'))
                    <a href="{{ route('admin.payments.pendingInvoicesShow') }}" class="btn btn-light border shadow-sm fw-bold">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> ดูหนี้ค้างชำระทั้งหมด
                    </a>
                @endif
            </div>
        @endforelse

    </div>

    {{-- MODAL PAYMENT --}}
    <div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('admin.payments.insert') }}" method="POST" enctype="multipart/form-data" id="paymentForm">
                @csrf
                <div class="modal-content border-0 shadow-lg rounded-3">
                    <div class="modal-header bg-success text-white py-3">
                        <h5 class="modal-title fw-bold mb-0"><i class="bi bi-cash-coin me-2"></i>รับชำระ — ห้อง <span id="room_label"></span></h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="bg-light px-4 py-3 border-bottom">
                        <div class="row text-center g-2" id="modal_summary">
                            <div class="col-4 border-end">
                                <small class="text-muted d-block" style="font-size:.8rem;">รอบเดือน</small>
                                <span class="fw-bold  text-dark" id="modal_billing_month">-</span>
                            </div>
                            <div class="col-4 border-end">
                                <small class="text-muted d-block" style="font-size:.8rem;">ยอดบิลเต็ม</small>
                                <span class="fw-bold  text-dark" id="modal_total_amount">-</span>
                            </div>
                            <div class="col-4">
                                <small class="text-muted d-block" style="font-size:.8rem;">ยอดที่ต้องจ่าย</small>
                                <span class="fw-bold text-danger" id="modal_remaining" style="font-size:1.1rem;">-</span>
                            </div>
                        </div>
                    </div>

                    <div class="modal-body px-4 py-4">
                        <input type="hidden" name="invoice_id" id="modal_invoice_id">
                        <input type="hidden" name="pending_payment_id" id="modal_pending_payment_id">

                        {{-- จำนวนเงิน --}}
                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark mb-2">ระบุยอดเงินที่รับชำระ (บาท)</label>
                            <input type="number" name="amount_paid" id="modal_amount_paid"
                                class="form-control form-control-lg text-success fw-bold text-center border-success border-2" 
                                step="0.01" min="0" oninput="validateAmount(this)" required
                                style="font-size:1.6rem; letter-spacing:1px; background-color: #f8fff9;">
                            <div class="d-flex justify-content-between mt-2">
                                <small class="text-muted fw-bold" id="modal_max_label">สูงสุด: -</small>
                                <a href="javascript:void(0)" class="text-success small text-decoration-none fw-bold" onclick="fillMax()">
                                    <i class="bi bi-arrow-down-circle"></i> กดเพื่อกรอกเต็มจำนวน
                                </a>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-6">
                                <label class="form-label fw-bold text-dark mb-1">วันที่ชำระ</label>
                                <input type="date" name="payment_date" id="modal_payment_date" class="form-control bg-light" value="{{ date('Y-m-d') }}" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-bold text-dark mb-1">ช่องทาง</label>
                                <select name="payment_method" id="modal_payment_method" class="form-select bg-light" required>
                                    <option value="โอนผ่านธนาคาร">โอนเงินเข้าบัญชี</option>
                                    <option value="เงินสด">จ่ายเงินสด</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold text-dark mb-1">แนบสลิป/หลักฐาน <span class="fw-normal text-muted">(ถ้ามี)</span></label>
                            <div class="input-group">
                                <input type="file" name="slip_image" id="modal_slip_input" class="form-control bg-light" accept="image/*" onchange="previewSlip(this)">
                                <button type="button" class="btn btn-outline-danger d-none" id="btn_clear_slip" onclick="clearSlipInput()" title="ยกเลิกการเลือกไฟล์">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        </div>

                        <div id="slip_preview_area" class="mb-4 text-center d-none p-3 bg-white border rounded shadow-sm">
                            <label class="form-label fw-bold text-success mb-2 d-block" id="slip_preview_label">
                                <i class="bi bi-image me-1"></i> หลักฐานสลิป
                            </label>
                            <div class="d-flex justify-content-center">
                                <a href="#" id="modal_slip_link" target="_blank" title="คลิกเพื่อดูรูปเต็ม">
                                    <img id="modal_slip_image" src="" class="img-fluid rounded border shadow-sm" style="max-height: 350px; object-fit: contain;">
                                </a>
                            </div>
                        </div>

                        <div class="mb-1">
                            <label class="form-label fw-bold text-dark mb-1">หมายเหตุ</label>
                            <input type="text" name="note" id="modal_note" class="form-control bg-light" placeholder="ระบุหมายเหตุ (ถ้ามี)">
                        </div>
                    </div>
                    
                    <div class="modal-footer border-0 pt-0 px-4 pb-4 justify-content-between">
                        <button type="button" class="btn btn-outline-danger shadow-sm d-none" id="btn_reject_payment" onclick="rejectPayment()">
                            <i class="bi bi-x-circle me-1"></i> ปฏิเสธสลิป
                        </button>
                        
                        <div class="ms-auto d-flex gap-2">
                            <button type="button" class="btn btn-light px-4 shadow-sm" data-bs-dismiss="modal">ยกเลิก</button>
                            <button type="button" class="btn btn-success fw-bold px-4 shadow-sm" onclick="confirmPayment()">
                                <i class="bi bi-check-circle me-1"></i> ยืนยันรับชำระ
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <form id="rejectPaymentForm" method="POST" class="d-none">
        @csrf
        <input type="hidden" name="note" id="reject_note_input">
    </form>
@endsection

@push('scripts')
    <script>
        let currentMaxDebt = 0;
        let originalSlipUrl = ''; 

        // 🌟 รับค่า remaining ที่คำนวณมาถูกต้องแล้วจาก Blade
        function openPaymentModal(invoice, pendingPayment, remainingDebt) {
            document.getElementById('paymentForm').reset();
            clearSlipInput(); 
            
            document.getElementById('modal_invoice_id').value = invoice.id;
            document.getElementById('room_label').innerText = invoice.tenant.room.room_number;

            currentMaxDebt = remainingDebt; // 🌟 ใช้ค่ายอดค้างจริงที่คำนวณแล้ว

            const monthStr = new Date(invoice.billing_month + '-01').toLocaleDateString('th-TH', { year: '2-digit', month: 'short' });
            document.getElementById('modal_billing_month').innerText = monthStr;
            document.getElementById('modal_total_amount').innerText = Number(invoice.total_amount).toLocaleString('th-TH', {minimumFractionDigits: 2}) + ' ฿';
            document.getElementById('modal_remaining').innerText = Number(currentMaxDebt).toLocaleString('th-TH', {minimumFractionDigits: 2}) + ' ฿';
            document.getElementById('modal_max_label').innerText = 'สูงสุด: ' + Number(currentMaxDebt).toLocaleString('th-TH', {minimumFractionDigits: 2}) + ' ฿';

            const amountInput = document.getElementById('modal_amount_paid');
            const dateInput = document.getElementById('modal_payment_date');
            const methodInput = document.getElementById('modal_payment_method');
            const noteInput = document.getElementById('modal_note');
            const idInput = document.getElementById('modal_pending_payment_id');
            const slipArea = document.getElementById('slip_preview_area');
            const slipImg = document.getElementById('modal_slip_image');
            const slipLink = document.getElementById('modal_slip_link');
            const slipLabel = document.getElementById('slip_preview_label');
            const rejectBtn = document.getElementById('btn_reject_payment');

            if (pendingPayment) {
                idInput.value = pendingPayment.id;
                amountInput.value = Number(pendingPayment.amount_paid).toFixed(2);
                if(pendingPayment.payment_date) {
                    let d = new Date(pendingPayment.payment_date);
                    let year = d.getFullYear();
                    let month = String(d.getMonth() + 1).padStart(2, '0');
                    let day = String(d.getDate()).padStart(2, '0');
                    dateInput.value = `${year}-${month}-${day}`;
                }
                methodInput.value = pendingPayment.payment_method;
                noteInput.value = pendingPayment.note || '';

                if (pendingPayment.slip_image) {
                    originalSlipUrl = '/storage/' + pendingPayment.slip_image;
                    slipImg.src = originalSlipUrl;
                    slipLink.href = originalSlipUrl;
                    slipLabel.innerHTML = '<i class="bi bi-image me-1"></i> สลิปที่ผู้เช่าแนบมา';
                    slipArea.classList.remove('d-none');
                } else {
                    originalSlipUrl = '';
                    slipArea.classList.add('d-none');
                }
                rejectBtn.classList.remove('d-none');
            } else {
                idInput.value = '';
                originalSlipUrl = '';
                amountInput.value = Number(currentMaxDebt).toFixed(2);
                dateInput.value = "{{ date('Y-m-d') }}";
                methodInput.value = "โอนผ่านธนาคาร";
                noteInput.value = '';
                slipArea.classList.add('d-none');
                rejectBtn.classList.add('d-none');
            }

            amountInput.max = currentMaxDebt;
            new bootstrap.Modal(document.getElementById('paymentModal')).show();
            setTimeout(() => amountInput.select(), 300);
        }

        function previewSlip(input) {
            const slipArea = document.getElementById('slip_preview_area');
            const slipImg = document.getElementById('modal_slip_image');
            const slipLink = document.getElementById('modal_slip_link');
            const btnClear = document.getElementById('btn_clear_slip');
            const slipLabel = document.getElementById('slip_preview_label');

            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    slipImg.src = e.target.result;
                    slipLink.href = e.target.result;
                    slipLabel.innerHTML = '<i class="bi bi-image text-primary me-1"></i> ตัวอย่างสลิปที่จะบันทึกใหม่';
                    slipArea.classList.remove('d-none');
                    btnClear.classList.remove('d-none');
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        function clearSlipInput() {
            document.getElementById('modal_slip_input').value = '';
            document.getElementById('btn_clear_slip').classList.add('d-none');
            
            const slipArea = document.getElementById('slip_preview_area');
            const slipImg = document.getElementById('modal_slip_image');
            const slipLink = document.getElementById('modal_slip_link');
            const slipLabel = document.getElementById('slip_preview_label');

            if (originalSlipUrl) {
                slipImg.src = originalSlipUrl;
                slipLink.href = originalSlipUrl;
                slipLabel.innerHTML = '<i class="bi bi-image text-success me-1"></i> สลิปที่ผู้เช่าแนบมา';
                slipArea.classList.remove('d-none');
            } else {
                slipArea.classList.add('d-none');
            }
        }

        function fillMax() {
            const amountInput = document.getElementById('modal_amount_paid');
            amountInput.value = Number(currentMaxDebt).toFixed(2);
        }

        function validateAmount(input) {
            const val = parseFloat(input.value);
            const max = parseFloat(input.max);
            if (val > max) {
                input.value = max.toFixed(2);
                Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 2000, timerProgressBar: true })
                    .fire({ icon: 'warning', title: 'ไม่สามารถรับชำระเกินยอดหนี้ได้' });
            }
        }

        function confirmPayment() {
            const room = document.getElementById('room_label').innerText;
            const amount = document.getElementById('modal_amount_paid').value;

            if (!amount || parseFloat(amount) <= 0) {
                Swal.fire({ icon: 'warning', title: 'กรุณาระบุยอดเงิน' });
                return;
            }

            Swal.fire({
                title: 'ยืนยันการรับชำระ?',
                html: `ห้อง <b>${room}</b><br>ยอดเงิน <b class="text-success fs-4">${parseFloat(amount).toLocaleString('th-TH', {minimumFractionDigits: 2})} บาท</b>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#198754',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="bi bi-check-circle me-1"></i> ยืนยันชำระเงิน',
                cancelButtonText: 'ยกเลิก',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({ title: 'กำลังบันทึก...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                    document.getElementById('paymentForm').submit();
                }
            });
        }

        function rejectPayment() {
            const paymentId = document.getElementById('modal_pending_payment_id').value;
            const room = document.getElementById('room_label').innerText;
            const noteValue = document.getElementById('modal_note').value;

            Swal.fire({
                title: 'ปฏิเสธและยกเลิกสลิปนี้?',
                html: `คุณต้องการยกเลิกข้อมูลการโอนเงินของห้อง <b>${room}</b> ใช่หรือไม่?<br><small class="text-danger">สถานะบิลจะกลับไปเป็นค้างชำระเหมือนเดิม<br>และข้อความแจ้งเตือนผู้เช่าผ่านไลน์</small><small class="text-danger"></small>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="bi bi-trash"></i> ยืนยันการปฏิเสธ',
                cancelButtonText: 'ปิดหน้าต่าง',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    let url = "{{ route('admin.payments.reject', ':id') }}";
                    url = url.replace(':id', paymentId);
                    
                    let form = document.getElementById('rejectPaymentForm');
                    form.action = url;
                    document.getElementById('reject_note_input').value = noteValue;
                    
                    Swal.fire({ title: 'กำลังยกเลิก...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                    form.submit();
                }
            });
        }
        
        window.addEventListener("beforeunload", function() {
            sessionStorage.setItem('scrollPosition', window.scrollY);
        });
        document.addEventListener("DOMContentLoaded", function() {
            let scrollpos = sessionStorage.getItem('scrollPosition');
            if (scrollpos) {
                window.scrollTo({ top: parseInt(scrollpos), behavior: "instant" });
                sessionStorage.removeItem('scrollPosition');
            }
        });
    </script>
@endpush