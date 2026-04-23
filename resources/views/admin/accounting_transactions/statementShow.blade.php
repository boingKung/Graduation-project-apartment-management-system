@extends('admin.layout')
@section('title', 'รายงานสรุปการทำธุรกรรมประจำเดือน') {{-- กำหนด title สำหรับ Breadcrumb --}}
@section('content')
    <div class="container-fluid py-4 bg-white">
        <form method="GET" action="{{ route('admin.accounting_transactions.show') }}">
            {{-- Header Section: ใช้ค่าจาก Controller --}}
            <div class="text-center mb-4">
               <h2 class="fw-bold text-dark mb-1">
                    @if($displayDate === 'รายการธุรกรรมทั้งหมด')
                        <i class="bi bi-layers-half me-2 text-primary"></i>{{ $displayDate }}
                    @else
                        <i class="bi bi-calendar3 me-2 text-primary"></i>{{ $displayDate }}
                    @endif
                </h2>
                <p class="text-muted small mb-3">
                    <i class="bi bi-info-circle me-1"></i> ท่านสามารถเลือกช่วงวันที่หรือเดือนที่ต้องการตรวจสอบข้อมูลได้จากช่องด้านล่างนี้
                </p>
                <div class="d-flex justify-content-center align-items-center gap-2 mt-2">
                    <span>แสดงตั้งแต่วันที่</span>
                    <input type="date" name="date_start" class="form-control form-control-sm shadow-sm" style="width: 150px;"
                        value="{{ $startDate }}" onchange="this.form.submit()">
                    <span>ถึงวันที่</span>
                    <input type="date" name="date_end" class="form-control form-control-sm shadow-sm"
                        style="width: 150px;" value="{{ $endDate }}" onchange="this.form.submit()">
                    <a href="{{ route('admin.accounting_transactions.show') }}" class="btn btn-light">ล้างค่า</a>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="btn-group shadow-sm">
                    <a href="{{ route('admin.accounting_transactions.summary', request()->query()) }}"
                        class="btn btn-outline-dark btn-sm ">
                        <i class="bi bi-file-earmark-bar-graph me-1"></i> สรุปงบรับ-จ่าย
                    </a>
                    <a href="{{ route('admin.accounting_transactions.income', request()->query()) }}"
                        class="btn btn-outline-success btn-sm">
                        <i class="bi bi-graph-up-arrow me-1"></i> รายงานรายรับ
                    </a>
                    <a href="{{ route('admin.accounting_transactions.expense', request()->query()) }}"
                        class="btn btn-outline-danger btn-sm">
                        <i class="bi bi-graph-down-arrow me-1"></i> รายงานรายจ่าย
                    </a>
                </div>
                <div class="d-flex gap-2">
                    {{-- ส่ง type_id = 2 สำหรับรายจ่าย --}}
                    <a href="{{ route('admin.accounting_transactions.create', ['type_id' => 2]) }}"
                        class="btn btn-danger btn-sm px-3 shadow-sm">
                        เพิ่มรายจ่าย <i class="bi bi-dash-circle-fill ms-1"></i>
                    </a>
                    {{-- ส่ง type_id = 1 สำหรับรายรับ --}}
                    <a href="{{ route('admin.accounting_transactions.create', ['type_id' => 1]) }}"
                        class="btn btn-success btn-sm px-3 shadow-sm">
                        เพิ่มรายรับ <i class="bi bi-plus-circle-fill ms-1"></i>
                    </a>
                </div>
            </div>

            {{-- Table Section --}}
            <div class="card border-0 shadow-sm rounded-3">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light border-bottom">
                            <tr class="text-secondary small">
                                <th class="ps-4 py-3" width="12%">วันที่</th>
                                <th width="12%">หมวดหมู่</th>
                                <th width="18%">หัวข้อรายการธุรกรรม</th>
                                <th class="text-center" width="12%">อาคาร/ตึก</th>
                                <th class="text-center" width="7%">ห้อง</th>
                                <th class="text-end" width="12%">จำนวนเงิน</th>
                                <th class="ps-4" width="12%">ผู้บันทึก</th>
                                <th class="text-center" width="10%">สถานะ</th>
                                <th class="text-center" width="5%">จัดการ</th>
                            </tr>
                            {{-- Filter Row: ใส่ชื่อ name ให้ตรงกับ Controller เพื่อให้ค้นหาได้ --}}
                            <tr class="bg-white">
                                <td class="ps-4"></td>
                                <td>
                                    <select name="category_id" class="form-select form-select-sm border-0"
                                        onchange="this.form.submit()">
                                        <option value="">ทั้งหมด</option>
                                        {{-- วนลูปตามกลุ่มประเภท (รายรับ/รายจ่าย) --}}
                                        @foreach ($categories as $typeName => $cats)
                                            <optgroup label="-- หมวดหมู่{{ $typeName }} --">
                                                @foreach ($cats as $cat)
                                                    <option value="{{ $cat->id }}"
                                                        {{ $categoryId == $cat->id ? 'selected' : '' }}>
                                                        {{ $cat->name }}
                                                    </option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="text" name="search_detail"
                                        class="form-control form-control-sm border-0"
                                        onkeydown="if(event.key === 'Enter') this.form.submit();"
                                        placeholder="ค้นหารายละเอียด" value="{{ $searchDetail }}"></td>

                                
                                <td>
                                    <select name="search_building" class="form-select form-select-sm border-0 text-center" onchange="this.form.submit()">
                                        <option value="">ทุกอาคาร</option>
                                        @foreach ($buildings as $b)
                                            <option value="{{ $b->id }}" {{ $searchBuilding == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="text" name="search_room"
                                        class="form-control form-control-sm border-0" placeholder="เลขห้อง"
                                         onkeydown="if(event.key === 'Enter') this.form.submit();"
                                        value="{{ $searchRoom }}"></td>
                                
                                <td>
                                    <select name="type_id" class="form-select form-select-sm border-0"
                                        onchange="this.form.submit()">
                                        <option value="">รายรับ/รายจ่าย</option>
                                        <option value="1" {{ $typeId == 1 ? 'selected' : '' }}>รายรับ</option>
                                        <option value="2" {{ $typeId == 2 ? 'selected' : '' }}>รายจ่าย</option>
                                    </select>
                                </td>
                                {{-- เพิ่มช่องค้นหาผู้บันทึก --}}
                                <td class="ps-4">
                                    <input type="text" name="filter_admin"
                                        class="form-control form-control-sm border-0"
                                        placeholder="ชื่อผู้บันทึก..." value="{{ $filterAdmin }}"
                                        onchange="this.form.submit()">
                                </td>

                                {{-- เพิ่มช่องค้นหาสถานะ --}}
                                <td class="text-center">
                                    <select name="filter_status"
                                        class="form-select form-select-sm border-0 text-center"
                                        onchange="this.form.submit()">
                                        <option value="">ทุกสถานะ</option>
                                        <option value="active" {{ $filterStatus == 'active' ? 'selected' : '' }}>ปกติ
                                        </option>
                                        <option value="void" {{ $filterStatus == 'void' ? 'selected' : '' }}>ยกเลิก
                                        </option>
                                    </select>
                                </td>
                                <td></td>
                            </tr>
                        </thead>
                        <tbody class="">
                            @forelse($transactions as $t)
                                <tr class="border-bottom-0 {{ $t->status === 'void' ? 'table-danger' : '' }}"
                                    onclick="viewDetail({{ $t->id }})" style="cursor: pointer;"
                                    title="คลิกเพื่อดูรายละเอียด">
                                    <td class="ps-4 small text-muted">{{ $t->thai_entry_date }}</td>
                                    <td class="small text-muted">{{ $t->category->name }}</td>
                                    <td class="small">{{ $t->title }}</td>
                                    <td class="text-center fw-bold text-secondary">{{ $t->display_building }}</td>
                                    <td class="text-center fw-bold">{{ $t->display_room }}</td>
                                    
                                    <td class="text-end fw-bold">
                                        @if ($t->category->type_id == 1)
                                            {{-- รายรับ --}}
                                            <span class="text-success">{{ number_format($t->amount, 2) }}</span>
                                        @else
                                            {{-- รายจ่าย --}}
                                            <span class="text-danger">-{{ number_format($t->amount, 2) }}</span>
                                        @endif
                                    </td>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <small class="text-muted">
                                                {{ $t->admin->firstname ?? 'System' }}
                                                {{ $t->admin->lastname ?? '' }}
                                            </small>
                                        </div>
                                    </td>
                                    {{-- แสดงสถานะด้วย Badge --}}
                                    <td class="text-center">
                                        @if ($t->status === 'active')
                                            <span
                                                class="badge rounded-pill bg-success-subtle text-success border border-success px-2 small">ปกติ</span>
                                        @else
                                            <span
                                                class="badge rounded-pill bg-danger-subtle text-danger border border-danger px-2 small">ยกเลิก</span>
                                        @endif
                                    </td>

                                    <td class="text-center">
                                        @if ($t->status === 'active' && !$t->payment_id)
                                            {{-- ปุ่มยกเลิกเฉพาะรายการที่บันทึกเอง --}}
                                            <button type="button" class="btn btn-sm btn-light border text-danger"
                                                onclick="event.stopPropagation(); confirmVoidTransaction(
                                                    {{ $t->id }}, 
                                                    '{{ $t->title }}', 
                                                    '{{ number_format($t->amount, 2) }}', 
                                                    '{{ $t->category->name }}', 
                                                    {{ $t->category->type_id }}
                                                )"
                                                title="ยกเลิกรายการนี้">
                                                <i class="bi bi-x-circle"></i>
                                            </button>
                                        @elseif($t->payment_id)
                                            <i class="bi bi-lock-fill text-muted" title="รายการจากระบบบิล (ล็อกไว้)"></i>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-5 text-muted">ไม่พบข้อมูล</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-white border-0 py-3">
                    {{ $transactions->appends(request()->query())->links() }}
                </div>
            </div>
        </form>
    </div>
    <div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-light border-0 py-3">
                    <h5 class="modal-title fw-bold text-dark"><i class="bi bi-info-circle me-2"></i>รายละเอียดธุรกรรม</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4" id="modalContent">
                    {{-- เนื้อหาจะถูกเติมด้วย JavaScript --}}
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status"></div>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm px-4"
                        data-bs-dismiss="modal">ปิดหน้าต่าง</button>
                </div>
            </div>
        </div>
    </div>
    {{-- Form สำหรับ Void --}}
    <form id="void-transaction-form" action="" method="POST" style="display: none;">
        @csrf
        @method('PUT')
    </form>
@endsection
@push('scripts')
    <script>
        function viewDetail(id) {
            const modal = new bootstrap.Modal(document.getElementById('detailModal'));
            const content = document.getElementById('modalContent');

            // ล้างค่าเก่าและแสดง Loading
            content.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>';
            modal.show();

            // ดึงข้อมูลผ่าน AJAX
            fetch(`accounting_transactions/readDetail/${id}`)
                .then(response => response.json())
                .then(data => {
                    content.innerHTML = `
                <div class="mb-4 text-center">
                    <h4 class="fw-bold mb-1">${data.title}</h4>
                    <span class="badge ${data.type === 'รายรับ' ? 'bg-success' : 'bg-danger'} px-3 rounded-pill">${data.type}</span>
                </div>
                <div class="row g-3">
                    <div class="col-6"><label class="text-muted small d-block">หมวดหมู่</label><strong>${data.category}</strong></div>
                    <div class="col-6 text-end"><label class="text-muted small d-block">วันที่ทำรายการ</label><strong>${data.date}</strong></div>
                    
                    <div class="col-6 mt-3"><label class="text-muted small d-block">อาคาร/ตึก</label><strong>${data.building}</strong></div>
                    <div class="col-6 text-end mt-3"><label class="text-muted small d-block">ห้อง</label><strong>${data.room}</strong></div>
                    
                    <div class="col-12"><hr class="my-1 border-light"></div>
                    
                    <div class="col-12 text-center"><label class="text-muted small d-block">จำนวนเงิน</label><h2 class="fw-bold text-primary mb-0">${data.amount} ฿</h2></div>
                    
                    <div class="col-12 mt-4 bg-light p-3 rounded">
                        <label class="text-muted small d-block mb-1">รายละเอียดเพิ่มเติม</label>
                        <p class="mb-0 small text-dark">${data.description}</p>
                    </div>
                    
                    <div class="col-12 mt-3">
                        <div class="d-flex justify-content-between small">
                            <span class="text-muted">ผู้บันทึก: ${data.admin}</span>
                            <span class="text-muted fst-italic">${data.payment_ref}</span>
                        </div>
                    </div>
                </div>
            `;
                })
                .catch(error => {
                    content.innerHTML = '<div class="alert alert-danger">เกิดข้อผิดพลาดในการโหลดข้อมูล</div>';
                });
        }

        function confirmVoidTransaction(id, title, amount, category, typeId) {
            // กำหนดสีและสัญลักษณ์ตามประเภท
            const isIncome = (typeId == 1);
            const colorClass = isIncome ? 'text-success' : 'text-danger';
            const typeLabel = isIncome ? 'รายรับ' : 'รายจ่าย';

            Swal.fire({
                title: '<span class="fw-bold">ยืนยันการยกเลิกรายการ?</span>',
                html: `
            <div class="mt-3 mb-4">
                <div class="small text-muted mb-1">รายการ: ${title}</div>
                <div class="display-5 fw-bold ${colorClass}">${isIncome ? '+' : '-'}${amount} ฿</div>
                <div class="mt-2">
                    <span class="badge rounded-pill bg-secondary bg-opacity-10 text-secondary border px-3">
                        หมวดหมู่: ${category} (${typeLabel})
                    </span>
                </div>
            </div>
            <p class="text-muted small">เมื่อยกเลิกแล้ว ยอดเงินนี้จะถูกเปลี่ยนสถานะเป็น <b class="text-danger">ยกเลิก</b><br>และจะไม่ถูกนำไปคำนวณในงบสรุปประจำเดือน</p>
        `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'ยืนยัน ยกเลิกรายการ',
                cancelButtonText: 'กลับไป',
                reverseButtons: true,
                customClass: {
                    confirmButton: 'btn btn-danger px-4 mx-2',
                    cancelButton: 'btn btn-light border px-4 mx-2'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'กำลังดำเนินการ...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    const form = document.getElementById('void-transaction-form');
                    form.action = `/admin/accounting_transactions/void/${id}`; // ตรวจสอบ Route ให้ตรง
                    form.submit();
                }
            });
        }

    </script>
@endpush
