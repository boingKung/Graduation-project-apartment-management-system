@extends('admin.layout')

@section('title', 'ใบแจ้งหนี้')

@section('content')
    <div class="container-fluid">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4 bg-white">

                <div class="d-flex align-items-center justify-content-between mb-4 pb-2 border-bottom">
                    <h5 class="fw-bold mb-0 text-primary">
                        <i class="bi bi-journal-text me-2"></i>ระบบจัดการใบแจ้งหนี้
                    </h5>
                    <div class="badge bg-primary fs-6 px-4 py-2">
                        รอบเดือน: {{ $thai_billing_month }}
                    </div>
                </div>

                <div class="row g-4 align-items-start">

                    <div class="col-lg-4 border-end">
                        <label class="form-label fw-bold text-secondary mb-2">
                            <span class="badge bg-secondary me-1">1</span> เลือกรอบเดือน ค้นหาและสร้างบิล
                        </label>
                        <form method="GET" action="{{ route('admin.invoices.show') }}" class="vstack gap-2">
                            <input type="month" name="billing_month" class="form-control" value="{{ $billing_month }}"
                                onchange="this.form.submit()">
                            <a href="{{ route('admin.invoices.show') }}" class="btn btn-outline-secondary w-100 btn-sm">
                                <i class="bi bi-arrow-clockwise"></i> กลับไปเดือนปัจจุบัน
                            </a>
                        </form>
                    </div>

                    <div class="col-lg-4 border-end">
                        <label class="form-label fw-bold text-primary mb-2">
                            <span class="badge bg-primary me-1">2</span> ดำเนินการสร้างบิล
                        </label>
                        <div class="vstack gap-2">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light text-muted">วันที่ออกบิล</span>
                                <input type="date" id="input_issue_date" class="form-control"
                                    value="{{ old('issue_date', date('Y-m-d')) }}">
                            </div>

                            <form action="{{ route('admin.invoices.insertInvoicesAll') }}" method="POST"
                                id="createAllInvoicesForm">
                                @csrf
                                <input type="hidden" name="billing_month" value="{{ $billing_month }}">
                                <input type="hidden" name="issue_date" id="hidden_issue_date_all">
                                <button type="button" class="btn btn-primary w-100 fw-bold shadow-sm"
                                    onclick="confirmCreateAll()">
                                    <i class="bi bi-magic me-1"></i> เริ่มสร้างบิลทั้งหมด
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <label class="form-label fw-bold text-success mb-2">
                            <span class="badge bg-success me-1">3</span> แจ้งเตือนผู้เช่า
                        </label>
                        <div class="vstack gap-2">
                            <form action="{{ route('admin.invoice.sendInvoiceAll') }}" method="post"
                                id="sendAllInvoicesForm">
                                @csrf
                                <input type="hidden" name="billing_month" value="{{ $billing_month }}">
                                <button type="button" class="btn btn-success w-100 fw-bold shadow-sm"
                                    onclick="confirmSendAll()">
                                    <i class="bi bi-megaphone me-1"></i> ส่งบิลทุกห้อง
                                </button>
                            </form>
                            <small class="text-muted text-center">จะส่งเฉพาะบิลที่มีสถานะ "กรุณาส่งบิล" เท่านั้น</small>
                        </div>
                    </div>

                </div>

            </div>
        </div>
        <div>
            <a href="{{ route('admin.invoices.collectionReport', ['billing_month' => $billing_month]) }}"
                class="btn btn-info">รายงานการเก็บเงิน</a>
        </div>

        <div>
            <table class="table" border="1">
                <thead>
                    <tr>
                        <td>เลขห้อง</td>
                        <td>ค่าเช่า</td>
                        <td>วันที่ออกบิล</td>
                        <td>วันที่ครบชำระ</td>
                        <td>สถานะ</td>
                        <td>จัดการ</td>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rooms as $r)
                        <tr>
                            <td class="fw-bold">{{ $r->room_number }}</td>
                            {{-- แสดงค่าห้องจากตาราง room_prices --}}
                            <td>
                                @if ($r->invoice_total)
                                    {{-- กรณีสร้างบิลแล้ว: แสดงยอดรวมสุทธิ (ค่าห้อง + น้ำ + ไฟ + อื่นๆ) --}}
                                    <span class="fw-bold text-primary">
                                        {{ number_format($r->invoice_total, 2) }} บาท
                                    </span>
                                    <small class="d-block text-muted" style="font-size: 0.7rem;">(ยอดรวมสุทธิ)</small>
                                @else
                                    {{-- กรณียังไม่ได้สร้างบิล: แสดงราคาค่าห้องเริ่มต้น --}}
                                    <span class="text-muted">
                                        {{ number_format($r->roomPrice->price ?? 0, 2) }} บาท
                                    </span>
                                    <small class="d-block text-muted" style="font-size: 0.7rem;">(ราคาห้องพัก)</small>
                                @endif
                            </td>
                            {{-- วันที่ออกบิล --}}
                            <td>{{ $r->thai_issue_date ? $r->thai_issue_date : '-' }}</td>
                            {{-- วันที่ครบชำระ --}}
                            <td>{{ $r->thai_due_date ? $r->thai_due_date : '-' }}</td>
                            {{-- สถานะ --}}
                            <td>
                                {{-- สถานะมิเตอร์ --}}
                                <span class="badge bg-{{ $r->meter_color }} mb-1 d-block">
                                    <i class="bi bi-speedometer2 me-1"></i> {{ $r->meter_status }}
                                </span>
                                {{-- สถานะบิล --}}
                                @if ($r->invoice_status == 'กรุณาส่งบิล')
                                    <span class="badge bg-{{ $r->invoice_color }} d-block">
                                        <i class="bi bi-file-earmark-text me-1"></i> {{ $r->invoice_status }}
                                    </span>
                                @elseif ($r->invoice_status == 'ค้างชำระ')
                                    <span class="badge bg-danger d-block">
                                        <i class="bi bi-file-earmark-text me-1"></i> {{ $r->invoice_status }}
                                    </span>
                                @elseif ($r->invoice_status == 'ชำระบางส่วน')
                                    <span class="badge bg-warning d-block">
                                        <i class="bi bi-file-earmark-text me-1"></i> {{ $r->invoice_status }}
                                    </span>
                                @elseif ($r->invoice_status == 'ชำระแล้ว')
                                    <span class="badge bg-success d-block">
                                        <i class="bi bi-file-earmark-text me-1"></i> {{ $r->invoice_status }}
                                    </span>
                                @endif
                            </td>
                            {{-- ปุ่มจัดการ --}}
                            <td>
                                {{-- ปุ่มจัดการตามเงื่อนไข --}}
                                <div class="btn-group shadow-sm">
                                    @if (!$r->can_create_invoice)
                                        {{-- เปลี่ยนจากลิ้งก์หน้าใหม่ เป็นปุ่มเปิด Modal --}}
                                        <button type="button" class="btn btn-sm btn-outline-danger"
                                            onclick="openMeterModal('{{ $r->id }}', '{{ $r->room_number }}', '{{ $r->tenant_id }}', '{{ $r->prev_water ?? 0 }}', '{{ $r->prev_electric ?? 0 }}')">
                                            <i class="bi bi-pencil-fill"></i> จดมิเตอร์
                                        </button>
                                    @elseif(!$r->invoice_id)
                                        {{-- จดแล้วแต่ยังไม่มีบิล ให้สร้างบิล --}}
                                        <form action="{{ route('admin.invoice.insertInvoiceOne') }}" method="POST"
                                            id="InsertOneInvoice_{{ $r->id }}">
                                            @csrf
                                            <input type="hidden" name="tenant_id" value="{{ $r->tenant_id }}">
                                            <input type="hidden" name="room_id" value="{{ $r->id }}">
                                            <input type="hidden" name="billing_month" value="{{ $billing_month }}">
                                            <input type="hidden" name="issue_date" id="issue_date_{{ $r->id }}">
                                            {{-- ID สำหรับใส่ค่าจาก Modal --}}

                                            <button type="button" class="btn btn-sm btn-primary px-3 shadow-sm fw-bold"
                                                onclick="openCreateInvoiceModal('{{ $r->id }}', '{{ $r->room_number }}')">
                                                <i class="bi bi-plus-circle me-1"></i> สร้างบิล
                                            </button>
                                        </form>
                                    @else
                                        {{-- มีบิลแล้ว ให้ดูบิล --}}
                                        <a href="{{ route('admin.invoices.details', $r->invoice_id) }}"
                                            class="btn btn-sm btn-success">
                                            ดูบิล
                                        </a>
                                        {{-- ปุ่มส่งบิล (เช่น ส่งเข้า Line หรือ Email) --}}
                                        @if ($r->invoice_status == 'กรุณาส่งบิล')
                                            <form action="{{ route('admin.invoice.sendInvoiceOne') }}" method="post"
                                                id="sendInvoiceOne_{{ $r->id }}">
                                                @csrf
                                                <input type="hidden" name="invoice_id" value="{{ $r->invoice_id }}">
                                                <button type="button" class="btn btn-sm btn-outline-primary"
                                                    onclick="confirmSendInvoiceOne({{ $r->id }})">ส่งบิล</button>
                                            </form>
                                            <form action="{{ route('admin.invoices.deleteInvoiceOne', $r->invoice_id) }}"
                                                method="post" id="delete-form-{{ $r->invoice_id }}">
                                                @csrf
                                                {{-- ส่ง ID เข้าไปในฟังก์ชัน --}}
                                                <button type="button" class="btn btn-sm btn-outline-danger"
                                                    onclick="confirmDeleteInvoiceOne('{{ $r->invoice_id }}')">
                                                    ลบบิล
                                                </button>
                                            </form>
                                            @endif
                                    @endif
                                </div>
                            </td>
                            {{-- ??? <br> { ไม่ได้จดมิเตอร์ , ไม่ได้สร้างบิล , ไม่ได้ส่งบิล , ค้างชำระ , ชำระแล้ว}  --}}
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="modal fade" id="meterModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold">จดมิเตอร์ห้อง <span id="modalRoomNumber"></span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form action="{{ route('admin.invoice.insertInvoiceMeterReadingOne') }}" method="POST"
                    id="meterModalForm">
                    @csrf
                    <input type="hidden" name="room_id" id="modal_room_id">
                    <input type="hidden" name="tenant_id" id="modal_tenant_id">
                    <input type="hidden" name="room_number" id="modal_room_number_hidden">
                    <input type="hidden" name="billing_month" value="{{ $billing_month }}">

                    <div class="modal-body p-4">
                        <div class="mb-4">
                            <div class="mb-3">
                                <label for="" class="form-label">เลือกวันที่จดมิเตอร์</label>
                                <input type="date" class="form-control" name="reading_date" id="reading_date"
                                    value="{{ date('Y-m-d') }}" required>
                            </div>
                            <label class="fw-bold text-info mb-2"><i class="bi bi-droplet-fill"></i> มิเตอร์น้ำ</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <small class="text-muted">เลขครั้งก่อน (ยกมา)</small>
                                    <input type="number" name="water_prev" id="water_prev" class="form-control fw-bold"
                                        placeholder="ระบุเลขเริ่มต้น" oninput="calculateModalUsed()">
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">เลขครั้งนี้</small>
                                    <input type="number" name="water_current" id="water_current"
                                        class="form-control border-info fw-bold" required oninput="calculateModalUsed()">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="fw-bold text-danger mb-2"><i class="bi bi-lightning-charge-fill"></i>
                                มิเตอร์ไฟฟ้า</label>
                            <div class="row g-2 mt-2">
                                <div class="col-6">
                                    <small class="text-muted">เลขครั้งก่อน (ยกมา)</small>
                                    <input type="number" name="electric_prev" id="electric_prev"
                                        class="form-control fw-bold" placeholder="ระบุเลขเริ่มต้น" required
                                        oninput="calculateModalUsed()">
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">เลขครั้งนี้</small>
                                    <input type="number" name="electric_current" id="electric_current"
                                        class="form-control border-danger fw-bold" required
                                        oninput="calculateModalUsed()">
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-secondary mb-0 text-center py-2" id="usageSummary">
                            หน่วยที่ใช้: น้ำ 0 | ไฟ 0
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="button" class="btn btn-primary px-4"
                            onclick="checkAndSubmit()">บันทึกข้อมูล</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal fade" id="createInvoiceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white border-0 py-3">
                    <h6 class="modal-title fw-bold"><i class="bi bi-calendar-check me-2"></i>ระบุวันที่ออกบิล</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <p class="mb-3 small text-muted">ห้อง <span id="modal_room_display" class="fw-bold text-dark"></span> | รอบเดือน {{ $thai_billing_month }}</p>
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-secondary text-uppercase">วันที่ออกใบแจ้งหนี้</label>
                        <input type="date" id="modal_issue_date" class="form-control form-control-lg border-2 text-center" 
                            value="{{ date('Y-m-d') }}">
                    </div>
                    <input type="hidden" id="current_room_id"> {{-- สำหรับเก็บ ID ห้องพักชั่วคราว --}}
                    
                    <button type="button" class="btn btn-primary w-100 fw-bold py-2" onclick="confirmFromModal()">
                        ดำเนินการต่อ <i class="bi bi-arrow-right-short ms-1"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function openMeterModal(id, number, tenantId, prevWater, prevElectric) {
            document.getElementById('modal_room_id').value = id;
            document.getElementById('modal_tenant_id').value = tenantId;
            document.getElementById('modalRoomNumber').innerText = number;
            document.getElementById('modal_room_number_hidden').value = number;

            // จัดการมิเตอร์น้ำ
            const wPrevInput = document.getElementById('water_prev');
            wPrevInput.value = prevWater || '';
            if (!prevWater || prevWater == 0) {
                wPrevInput.readOnly = false;
                wPrevInput.classList.remove('bg-light');
                wPrevInput.classList.add('border-warning'); // ใส่ขอบสีส้มเตือนว่าต้องกรอกเอง
            } else {
                wPrevInput.readOnly = true;
                wPrevInput.classList.add('bg-light');
                wPrevInput.classList.remove('border-warning');
            }

            // จัดการมิเตอร์ไฟ
            const ePrevInput = document.getElementById('electric_prev');
            ePrevInput.value = prevElectric || '';
            if (!prevElectric || prevElectric == 0) {
                ePrevInput.readOnly = false;
                ePrevInput.classList.remove('bg-light');
                ePrevInput.classList.add('border-warning');
            } else {
                ePrevInput.readOnly = true;
                ePrevInput.classList.add('bg-light');
                ePrevInput.classList.remove('border-warning');
            }

            // รีเซ็ตค่าใหม่และคำนวณเบื้องต้น
            document.getElementById('water_current').value = '';
            document.getElementById('electric_current').value = '';
            calculateModalUsed();

            var myModal = new bootstrap.Modal(document.getElementById('meterModal'));
            myModal.show();
        }

        function calculateModalUsed() {
            const wPrev = parseFloat(document.getElementById('water_prev').value) || 0;
            const wCurr = parseFloat(document.getElementById('water_current').value) || 0;
            const ePrev = parseFloat(document.getElementById('electric_prev').value) || 0;
            const eCurr = parseFloat(document.getElementById('electric_current').value) || 0;

            const wUsed = wCurr - wPrev;
            const eUsed = eCurr - ePrev;

            let summaryHtml = `หน่วยที่ใช้: <span class="text-info">น้ำ ${wUsed >= 0 ? wUsed : 'เลขผิด'}</span> | 
                                    <span class="text-danger">ไฟ ${eUsed >= 0 ? eUsed : 'เลขผิด'}</span>`;

            document.getElementById('usageSummary').innerHTML = summaryHtml;
        }

        function checkAndSubmit() {
            // ดึงค่ามาคำนวณ
            const wUsed = (parseFloat(document.getElementById('water_current').value) || 0) - (parseFloat(document
                .getElementById('water_prev').value) || 0);
            const eUsed = (parseFloat(document.getElementById('electric_current').value) || 0) - (parseFloat(document
                .getElementById('electric_prev').value) || 0);

            // ถ้ามีค่าติดลบ ให้แจ้งเตือนและไม่ส่งฟอร์ม
            if (wUsed <= 0 || eUsed <= 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'กรอกเลขผิด!',
                    text: 'หน่วยที่ใช้ห้ามติดลบหรือเว้นว่าง กรุณาตรวจสอบเลขมิเตอร์ใหม่'
                });
                return;
            }

            // ถ้าผ่าน ให้ส่งฟอร์มทันที
            document.getElementById('meterModalForm').submit();
        }

        function confirmCreateAll() {
            const issueDate = document.getElementById('input_issue_date').value;

            if (!issueDate) {
                Swal.fire('กรุณาเลือกวันที่', 'โปรดระบุวันที่ออกใบแจ้งหนี้ก่อนดำเนินการ', 'warning');
                return;
            }

            // ส่งค่าวันที่ไปยังช่อง hidden ใน form
            document.getElementById('hidden_issue_date_all').value = issueDate;

            // แปลงรูปแบบวันที่โชว์ใน SweetAlert ให้ดูง่าย (Optional)
            const displayDate = new Date(issueDate).toLocaleDateString('th-TH', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });

            Swal.fire({
                title: 'ยืนยันสร้างบิลทั้งหมด?',
                html: `ระบบจะสร้างบิลรอบเดือน <b>{{ $thai_billing_month }}</b><br>โดยระบุวันที่ออกบิลเป็น: <b>${displayDate}</b>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#0d6efd',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'ตกลง, เริ่มสร้างบิล',
                cancelButtonText: 'ยกเลิก',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'กำลังสร้างบิล...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    document.getElementById('createAllInvoicesForm').submit();
                }
            });
        }

        function confirmSendInvoiceOne(id) {

            // 2. ค้นหาฟอร์มที่ต้องการส่ง
            const form = document.getElementById('sendInvoiceOne_' + id);

            Swal.fire({
                title: 'ยืนยันการส่งใบบิลค่าเช่า ?',
                text: "ส่งบิลให้กับผู้เช่า ",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                confirmButtonText: 'บันทึก',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        }

        function confirmSendAll() {
            Swal.fire({
                title: 'ยืนยันการส่งบิลทั้งหมด?',
                text: "ระบบจะเปลี่ยนสถานะบิลรอบเดือนนี้เป็น 'ค้างชำระ' เพื่อให้ผู้เช่ารับทราบ",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#198754', // สีเขียว Success
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'ตกลง, ส่งบิลเลย',
                cancelButtonText: 'ยกเลิก',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'กำลังดำเนินการ...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    document.getElementById('sendAllInvoicesForm').submit();
                }
            });
        }

        function confirmDeleteInvoiceOne(invoiceId) {
            Swal.fire({
                title: 'ยืนยันการลบบิล?',
                text: "รายการค่าใช้จ่ายและข้อมูลในบิลนี้จะถูกลบออกถาวร!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'ใช่, ลบเลย!',
                cancelButtonText: 'ยกเลิก',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    // แสดง Loading ระหว่างรอ
                    Swal.fire({
                        title: 'กำลังลบข้อมูล...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    // สั่ง Submit ฟอร์มที่มี ID ตรงกัน
                    document.getElementById('delete-form-' + invoiceId).submit();
                }
            });
        }

        function openCreateInvoiceModal(id, roomNumber) {
            document.getElementById('current_room_id').value = id;
            document.getElementById('modal_room_display').innerText = roomNumber;
            
            // ตั้งค่า Default Date เป็นวันนี้ หรือตามวันที่ในช่อง Input หลักด้านบน (ถ้ามี)
            const mainIssueDate = document.getElementById('input_issue_date').value;
            document.getElementById('modal_issue_date').value = mainIssueDate || "{{ date('Y-m-d') }}";

            var myModal = new bootstrap.Modal(document.getElementById('createInvoiceModal'));
            myModal.show();
        }

        // 2. ฟังก์ชันตรวจสอบและเรียก SweetAlert ยืนยัน (Confirm)
        function confirmFromModal() {
            const id = document.getElementById('current_room_id').value;
            const selectedDate = document.getElementById('modal_issue_date').value;
            const roomNumber = document.getElementById('modal_room_display').innerText;

            if (!selectedDate) {
                Swal.fire('กรุณาเลือกวันที่', 'โปรดระบุวันที่ต้องการออกใบแจ้งหนี้', 'warning');
                return;
            }

            // ปิด Modal ก่อนแสดงความยินยัน
            bootstrap.Modal.getInstance(document.getElementById('createInvoiceModal')).hide();

            // แปลงวันที่โชว์แบบไทย
            const dateThai = new Date(selectedDate).toLocaleDateString('th-TH', {
                year: 'numeric', month: 'long', day: 'numeric'
            });

            Swal.fire({
                title: 'ยืนยันสร้างใบแจ้งหนี้?',
                html: `ห้อง <b>${roomNumber}</b><br>วันที่ออกบิล: <span class="text-primary">${dateThai}</span>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#0d6efd',
                confirmButtonText: 'ตกลง, สร้างบิล',
                cancelButtonText: 'ยกเลิก',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    // ส่งค่าไปยังฟอร์มตัวจริง
                    const form = document.getElementById('InsertOneInvoice_' + id);
                    form.querySelector('input[name="issue_date"]').value = selectedDate;
                    
                    Swal.fire({ title: 'กำลังสร้างบิล...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); }});
                    form.submit();
                }
            });
        }

    </script>
@endpush
