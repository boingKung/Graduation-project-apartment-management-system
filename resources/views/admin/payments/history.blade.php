@extends('admin.layout')
@section('title', 'ประวัติการรับเงิน')

@section('content')
    <div class="container-fluid py-4">
        <h4 class="fw-bold mb-3 text-primary"><i class="bi bi-clock-history me-2"></i>{{ $displayTitle }}</h4>

        {{-- ===== Summary Bar (มีกล่อง ปฏิเสธสลิป) ===== --}}
        <div class="row g-2 mb-3">
            <div class="col">
                <div class="card border-0 shadow-sm h-100 text-center py-2">
                    <div class="small text-muted">ทั้งหมด</div>
                    <div class="fs-5 fw-bold">{{ number_format($summary['total_count']) }} <small class="text-muted fw-normal">รายการ</small></div>
                    <div class="small text-primary fw-bold">{{ number_format($summary['total_amount'], 2) }} ฿</div>
                </div>
            </div>
            <div class="col">
                <a href="{{ route('admin.payments.history', array_merge(request()->except('filter_method'), ['filter_method' => 'เงินสด'])) }}" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100 text-center py-2 {{ $filterMethod == 'เงินสด' ? 'border-success border-2' : '' }}">
                        <div class="small text-muted"><i class="bi bi-cash text-success"></i> เงินสด</div>
                        <div class="fs-5 fw-bold text-success">{{ number_format($summary['cash_count']) }}</div>
                        <div class="small text-success">{{ number_format($summary['cash_amount'], 2) }} ฿</div>
                    </div>
                </a>
            </div>
            <div class="col">
                <a href="{{ route('admin.payments.history', array_merge(request()->except('filter_method'), ['filter_method' => 'โอนผ่านธนาคาร'])) }}" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100 text-center py-2 {{ $filterMethod == 'โอนผ่านธนาคาร' ? 'border-primary border-2' : '' }}">
                        <div class="small text-muted"><i class="bi bi-bank text-primary"></i> โอน</div>
                        <div class="fs-5 fw-bold text-primary">{{ number_format($summary['transfer_count']) }}</div>
                        <div class="small text-primary">{{ number_format($summary['transfer_amount'], 2) }} ฿</div>
                    </div>
                </a>
            </div>
            <div class="col">
                <a href="{{ route('admin.payments.history', array_merge(request()->except('filter_status'), ['filter_status' => 'void'])) }}" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100 text-center py-2 {{ $filterStatus == 'void' ? 'border-danger border-2' : '' }}">
                        <div class="small text-muted"><i class="bi bi-x-circle text-danger"></i> ยกเลิก</div>
                        <div class="fs-5 fw-bold text-danger">{{ number_format($summary['void_count']) }}</div>
                        <div class="small text-danger">{{ number_format($summary['void_amount'], 2) }} ฿</div>
                    </div>
                </a>
            </div>
            <div class="col">
                <a href="{{ route('admin.payments.history', array_merge(request()->except('filter_status'), ['filter_status' => 'ปฏิเสธสลิป'])) }}" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100 text-center py-2 {{ $filterStatus == 'ปฏิเสธสลิป' ? 'border-warning border-2' : '' }}">
                        <div class="small text-muted"><i class="bi bi-shield-x text-warning"></i> ปฏิเสธสลิป</div>
                        <div class="fs-5 fw-bold text-warning">{{ number_format($summary['reject_count']) }}</div>
                        <div class="small text-warning">{{ number_format($summary['reject_amount'], 2) }} ฿</div>
                    </div>
                </a>
            </div>
        </div>

        {{-- ===== Compact Filter Bar ===== --}}
        <form method="GET" action="{{ route('admin.payments.history') }}" id="filterForm">
            <div class="card border-0 shadow-sm rounded-3 overflow-hidden mb-4">
                <div class="card-header bg-white border-bottom-0 py-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-list-columns-reverse text-primary me-2"></i>รายการข้อมูลการรับเงิน</h6>
                    <a href="{{ route('admin.payments.history') }}" class="btn btn-light btn-sm text-muted border shadow-sm px-3" title="ล้างการค้นหา">
                        <i class="bi bi-eraser-fill me-1"></i> ล้างค่า
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-muted small">
                            <tr>
                                <th style="width: 14%;">วันที่ชำระ</th>
                                <th class="text-center" style="width: 8%;">ห้อง</th>
                                <th class="text-end" style="width: 11%;">ยอดเงิน</th>
                                <th class="text-center" style="width: 12%;">ช่องทาง</th>
                                <th class="text-center" style="width: 6%;"><i class="bi bi-image" title="สลิป"></i></th>
                                <th style="width: 18%;">ผู้ชำระเงิน</th>
                                <th style="width: 15%;">ผู้ทำรายการ</th>
                                <th class="text-center" style="width: 10%;">สถานะ</th>
                                <th class="text-center px-3" style="width: 8%;">จัดการ</th>
                            </tr>
                            {{-- แถวสำหรับ Inline Filter --}}
                            <tr class="bg-white border-bottom shadow-sm">
                                <td class="px-2 py-2">
                                    <input type="date" name="filter_date" class="form-control form-control-sm border-0 bg-light text-muted" value="{{ $filterDate }}" onchange="this.form.submit()">
                                </td>
                                <td>
                                    <input type="text" name="filter_room" class="form-control form-control-sm border-0 bg-light text-center fw-bold" placeholder="ห้อง" value="{{ $filterRoom }}" onkeydown="if(event.key === 'Enter') this.form.submit();">
                                </td>
                                <td>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light border-0 text-muted px-1">≥</span>
                                        <input type="number" name="filter_min_amount" class="form-control border-0 bg-light text-center" placeholder="ยอดเงิน" value="{{ $filterMinAmount }}" onkeydown="if(event.key === 'Enter') this.form.submit();">
                                    </div>
                                </td>
                                <td>
                                    <select name="filter_method" class="form-select form-select-sm border-0 bg-light text-center" onchange="this.form.submit()">
                                        <option value="">ทั้งหมด</option>
                                        <option value="เงินสด" class="text-success fw-bold" {{ $filterMethod == 'เงินสด' ? 'selected' : '' }}>เงินสด</option>
                                        <option value="โอนผ่านธนาคาร" class="text-primary fw-bold" {{ $filterMethod == 'โอนผ่านธนาคาร' ? 'selected' : '' }}>โอน</option>
                                    </select>
                                </td>
                                <td></td>
                                <td>
                                    <input type="text" name="filter_payer" class="form-control form-control-sm border-0 bg-light" placeholder="ผู้ชำระ..." value="{{ $filterPayer }}" onkeydown="if(event.key === 'Enter') this.form.submit();">
                                </td>
                                <td>
                                    <input type="text" name="filter_receiver" class="form-control form-control-sm border-0 bg-light" placeholder="ผู้รับ..." value="{{ $filterReceiver }}" onkeydown="if(event.key === 'Enter') this.form.submit();">
                                </td>
                                <td>
                                    <select name="filter_status" class="form-select form-select-sm border-0 bg-light text-center" onchange="this.form.submit()">
                                        <option value="">ทั้งหมด</option>
                                        <option value="active" class="text-success" {{ $filterStatus == 'active' ? 'selected' : '' }}>ปกติ</option>
                                        <option value="void" class="text-danger" {{ $filterStatus == 'void' ? 'selected' : '' }}>ยกเลิก</option>
                                        <option value="ปฏิเสธสลิป" class="text-warning" {{ $filterStatus == 'ปฏิเสธสลิป' ? 'selected' : '' }}>ปฏิเสธสลิป</option>
                                    </select>
                                </td>
                                <td class="text-center px-2">
                                    <button type="submit" class="d-none">Search</button>
                                </td>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($history as $pay)
                                {{-- 🌟 แยกสีบรรทัดตามสถานะอย่างชัดเจน --}}
                                @php
                                    $rowClass = '';
                                    if($pay->status === 'void') $rowClass = 'table-danger text-muted';
                                    elseif($pay->status === 'ปฏิเสธสลิป') $rowClass = 'table-warning text-muted';
                                @endphp

                                <tr onclick="viewPaymentDetail({{ $pay->id }})" style="cursor: pointer;" class="{{ $rowClass }}">

                                    <td class="px-3 small fw-bold">{{ $pay->thai_payment_date }}</td>

                                    <td class="text-center">
                                        <span class="fs-6 fw-bold text-dark">{{ $pay->display_room }}</span>
                                    </td>

                                    <td class="text-end fw-bold {{ in_array($pay->status, ['void', 'ปฏิเสธสลิป']) ? 'text-muted' : 'text-success' }}">
                                        {{ number_format($pay->amount_paid, 2) }}
                                    </td>

                                    <td class="text-center">
                                        @if ($pay->payment_method == 'เงินสด')
                                            <span class="badge bg-success rounded-pill px-3"><i class="bi bi-cash me-1"></i> เงินสด</span>
                                        @else
                                            <span class="badge bg-primary rounded-pill px-3"><i class="bi bi-bank me-1"></i> โอน</span>
                                        @endif
                                    </td>

                                    {{-- 🌟 จัดไอคอนให้อยู่ตรงกลางด้วย d-flex justify-content-center --}}
                                    <td>
                                        <div class="d-flex justify-content-center align-items-center w-100 h-100">
                                            {!! $pay->slip_image ? '<i class="bi bi-image-fill text-info fs-5"></i>' : '<i class="bi bi-dash text-muted fs-5"></i>' !!}
                                        </div>
                                    </td>

                                    <td>
                                        <small class="fw-bold text-dark">{{ $pay->display_tenant }}</small>
                                    </td>

                                    <td>
                                        <small class="text-muted">{{ $pay->admin->firstname ?? 'System' }} {{ $pay->admin->lastname ?? '' }}</small>
                                    </td>

                                    <td class="text-center">
                                        {{-- 🌟 แสดง Badge สถานะให้ชัดเจน --}}
                                        @if ($pay->status === 'active')
                                            <span class="badge rounded-pill bg-success-subtle text-success border border-success px-2"><i class="bi bi-check-circle-fill"></i> ปกติ</span>
                                        @elseif ($pay->status === 'ปฏิเสธสลิป')
                                            <span class="badge rounded-pill bg-warning text-dark border border-warning px-2"><i class="bi bi-shield-x"></i> ปฏิเสธสลิป</span>
                                        @else
                                            <span class="badge rounded-pill bg-danger-subtle text-danger border border-danger px-2"><i class="bi bi-x-circle-fill"></i> ยกเลิก</span>
                                        @endif
                                    </td>

                                    <td class="text-center px-2">
                                        <div class="d-flex justify-content-center gap-1">
                                            @if ($pay->status === 'active')
                                                <button type="button" class="btn btn-sm btn-light border text-warning" onclick="event.stopPropagation(); editPayment({{ json_encode($pay) }})" title="แก้ไขข้อมูล">
                                                    <i class="bi bi-pencil-square"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-light border text-danger" onclick="event.stopPropagation(); confirmVoid({{ $pay->id }}, '{{ $pay->display_room }}')" title="ยกเลิกรายการนี้">
                                                    <i class="bi bi-x-circle"></i>
                                                </button>
                                            @else
                                                <button type="button" class="btn btn-sm btn-light border text-muted disabled" title="ไม่สามารถแก้ไขได้"><i class="bi bi-pencil-square"></i></button>
                                                <button type="button" class="btn btn-sm btn-light border text-muted disabled" title="ถูกยกเลิก/ปฏิเสธไปแล้ว"><i class="bi bi-slash-circle"></i></button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-5 text-muted">
                                        <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                                        <h5 class="fw-bold">ไม่พบประวัติการชำระเงิน</h5>
                                        <p class="small">ลองเปลี่ยนเงื่อนไขการค้นหา หรือคลิกที่ปุ่มล้างค่า</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>

                        @if ($history->count() > 0)
                            <tfoot class="table-light">
                                <tr class="fw-bold">
                                    <td class="ps-4">รวมหน้านี้</td>
                                    <td class="text-center">{{ $history->count() }} รายการ</td>
                                    <td class="text-end text-success fs-6">
                                        {{ number_format($history->where('status', 'active')->sum('amount_paid'), 2) }} ฿
                                    </td>
                                    <td colspan="6"></td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
                <div class="card-footer bg-white border-0 py-3">{{ $history->appends(request()->query())->links() }}</div>
            </div>
        </form>
    </div>

    {{-- Modal รายละเอียดการชำระเงิน (Detail Modal) --}}
    <div class="modal fade" id="paymentDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-light border-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-info-circle me-2"></i>รายละเอียดการรับเงิน</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4" id="modalContent">
                </div>
            </div>
        </div>
    </div>

    {{-- Modal แก้ไขข้อมูล (Edit Modal) --}}
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form action="" method="POST" id="editForm" enctype="multipart/form-data" class="modal-content border-0 shadow">
                @csrf @method('PUT')
                <div class="modal-header bg-dark text-white border-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>แก้ไขข้อมูลการรับเงิน</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">ช่องทางการชำระเงิน</label>
                        <select name="payment_method" id="edit_method" class="form-select">
                            <option value="เงินสด">เงินสด</option>
                            <option value="โอนผ่านธนาคาร">โอนผ่านธนาคาร</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">หลักฐานการโอน (สลิป)</label>
                        
                        {{-- 🌟 จัดรูปใน Modal แก้ไขให้อยู่กึ่งกลาง --}}
                        <div id="edit_slip_preview_container" class="mb-3" style="display: none;">
                            <div class="d-flex justify-content-center mb-2">
                                <img id="edit_slip_img" src="" class="img-fluid rounded border shadow-sm" style="max-height: 250px; object-fit: contain;">
                            </div>
                            <div class="alert alert-warning py-2 small mb-0 text-center">
                                <i class="bi bi-exclamation-triangle me-2"></i><b>คำเตือน:</b> หากคุณเลือกไฟล์ใหม่ ระบบจะลบไฟล์เดิมทิ้งทันที
                            </div>
                        </div>

                        <input type="file" name="slip_image" class="form-control">
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-bold text-muted">หมายเหตุ</label>
                        <textarea name="note" id="edit_note" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-light border btn-sm px-4" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-primary btn-sm px-4" id="btnUpdateSubmit" onclick="confirmUpdate()">บันทึกการแก้ไข</button>
                </div>
            </form>
        </div>
    </div>

    <form id="void-form" action="" method="POST" style="display: none;">
        @csrf
        @method('PUT')
    </form>
@endsection

@push('scripts')
    <script>
        function viewPaymentDetail(id) {
            const modal = new bootstrap.Modal(document.getElementById('paymentDetailModal'));
            const content = document.getElementById('modalContent');

            content.innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border text-primary border-3" role="status" style="width: 3rem; height: 3rem;"></div>
                    <p class="mt-3 text-muted small fw-bold">กำลังดึงข้อมูลการชำระเงิน...</p>
                </div>
            `;
            modal.show();

            fetch(`/admin/payments/history/getPaymentDetail/${id}`)
                .then(response => response.json())
                .then(data => {

                    let breakdownRows = '';
                    data.breakdown.forEach(item => {
                        breakdownRows += `
                            <tr>
                                <td class="text-start text-dark py-2 px-3">${item.name}</td>
                                <td class="text-end py-2 text-muted">฿${Number(item.subtotal).toLocaleString('th-TH', {minimumFractionDigits: 2})}</td>
                                <td class="text-end py-2 fw-bold text-success">฿${Number(item.paid).toLocaleString('th-TH', {minimumFractionDigits: 2})}</td>
                                <td class="text-end py-2 px-3 fw-bold text-danger">฿${Number(item.remaining).toLocaleString('th-TH', {minimumFractionDigits: 2})}</td>
                            </tr>
                        `;
                    });

                    content.innerHTML = `
                        <div class="text-center mb-4 pb-3 border-bottom border-light">
                            <h6 class="text-muted text-uppercase small fw-bold mb-1">ยอดรับชำระครั้งนี้</h6>
                            <div class="display-5 fw-bolder text-success mb-3">฿${data.amount}</div>
                            <div class="d-flex justify-content-center gap-2 small">
                                <span class="badge bg-light text-dark border px-3 py-2"><i class="bi bi-calendar-event me-1 text-primary"></i> ${data.date} ${data.time}</span>
                                <span class="badge ${data.method === 'เงินสด' ? 'bg-success' : 'bg-primary'} px-3 py-2"><i class="bi ${data.method === 'เงินสด' ? 'bi-cash' : 'bi-bank'} me-1"></i> ${data.method}</span>
                            </div>
                        </div>

                        <div class="row g-3 mb-4 text-sm">
                            <div class="col-6">
                                <div class="text-muted" style="font-size: 0.75rem;">อ้างอิงบิลเลขที่</div>
                                <div class="fw-bold text-dark">${data.invoice_no}</div>
                            </div>
                            <div class="col-6">
                                <div class="text-muted" style="font-size: 0.75rem;">รอบเดือน</div>
                                <div class="fw-bold text-dark">${data.billing_month}</div>
                            </div>
                            <div class="col-6">
                                <div class="text-muted" style="font-size: 0.75rem;">ห้อง / ผู้เช่า</div>
                                <div class="fw-bold text-dark">ห้อง ${data.room} <span class="fw-normal text-muted">(${data.tenant})</span></div>
                            </div>
                            <div class="col-6">
                                <div class="text-muted" style="font-size: 0.75rem;">ผู้ทำรายการ (แอดมิน)</div>
                                <div class="fw-bold text-dark">${data.receiver}</div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <h6 class="fw-bold text-dark mb-2 fs-6"><i class="bi bi-list-columns-reverse me-2 text-primary"></i>รายละเอียดภาพรวมบิล</h6>
                            <div class="table-responsive rounded-3 border">
                                <table class="table table-sm table-borderless table-striped align-middle mb-0" style="font-size: 0.85rem;">
                                    <thead class="table-light text-muted border-bottom border-light">
                                        <tr>
                                            <th class="text-start py-2 px-3">รายการค่าใช้จ่าย</th>
                                            <th class="text-end py-2">ยอดเต็ม</th>
                                            <th class="text-end py-2">ชำระแล้ว</th>
                                            <th class="text-end py-2 px-3">ค้างชำระ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${breakdownRows}
                                    </tbody>
                                    <tfoot class="border-top table-light border-light">
                                        <tr class="fw-bold fs-6">
                                            <td class="text-start py-3 px-3 text-dark">รวมทั้งบิล</td>
                                            <td class="text-end py-3 text-muted">฿${data.invoice_total}</td>
                                            <td class="text-end py-3 text-success">฿${data.invoice_paid}</td>
                                            <td class="text-end py-3 px-3 text-danger">฿${data.invoice_remain}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <div class="bg-light p-3 rounded-3 border border-light mb-3">
                            <div class="text-muted small fw-bold mb-1"><i class="bi bi-chat-text me-1"></i> หมายเหตุ:</div>
                            <div class="text-dark small">${data.note}</div>
                        </div>

                        ${data.slip ? `
                                <div class="text-center mt-4 border-top pt-4">
                                    <div class="text-muted small fw-bold mb-2"><i class="bi bi-image me-1"></i> หลักฐานการโอนเงิน (สลิป)</div>
                                    <div class="d-flex justify-content-center">
                                        <img src="${data.slip}" class="img-fluid rounded-3 shadow-sm border" style="max-height: 400px; object-fit: contain;">
                                    </div>
                                </div>
                            ` : ''}

                        <div class="text-center mt-4 pt-2">
                            <button class="btn btn-secondary px-4 fw-bold shadow-sm" data-bs-dismiss="modal">ปิดหน้าต่าง</button>
                        </div>
                    `;
                })
                .catch(err => {
                    content.innerHTML = `
                        <div class="text-center py-5 text-danger">
                            <i class="bi bi-exclamation-triangle-fill fs-1 d-block mb-2"></i>
                            <h6 class="fw-bold">เกิดข้อผิดพลาด</h6>
                            <p class="small text-muted mb-0">ไม่สามารถโหลดข้อมูลรายละเอียดได้</p>
                        </div>`;
                });
        }

        function confirmUpdate() {
            Swal.fire({
                title: 'ยืนยันการแก้ไขข้อมูล?',
                text: "คุณต้องการบันทึกการเปลี่ยนแปลงข้อมูลการรับเงินนี้ใช่หรือไม่?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#0d6efd', 
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="bi bi-check-lg me-1"></i>ยืนยัน บันทึกข้อมูล',
                cancelButtonText: 'กลับไปตรวจสอบ',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'กำลังบันทึกข้อมูล...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    document.getElementById('editForm').submit();
                }
            });
        }

        function editPayment(pay) {
            const modal = new bootstrap.Modal(document.getElementById('editModal'));
            const form = document.getElementById('editForm');
            const previewContainer = document.getElementById('edit_slip_preview_container');
            const previewImg = document.getElementById('edit_slip_img');

            form.action = `/admin/payments/history/update/${pay.id}`;
            document.getElementById('edit_method').value = pay.payment_method;
            document.getElementById('edit_note').value = pay.note || '';

            if (pay.slip_image) {
                previewContainer.style.display = 'block';
                previewImg.src = `/storage/${pay.slip_image}`;
            } else {
                previewContainer.style.display = 'none';
                previewImg.src = '';
            }

            modal.show();
        }

        function confirmVoid(id, room) {
            Swal.fire({
                title: 'ยืนยันการยกเลิก?',
                text: `คุณต้องการยกเลิกรายการชำระเงินของห้อง ${room} ใช่หรือไม่? ยอดหนี้จะถูกตีกลับเป็นสถานะค้างชำระ`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="bi bi-check-lg me-1"></i>ยืนยัน ยกเลิกรายการ',
                cancelButtonText: 'กลับไป',
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

                    const form = document.getElementById('void-form');
                    form.action = `/admin/payments/history/void/${id}`;
                    form.submit();
                }
            });
        }
    </script>
@endpush