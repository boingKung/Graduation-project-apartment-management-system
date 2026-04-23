@extends('admin.layout')

@section('content')
    <div class="container-fluid py-4">

        {{-- 🌟 Header: ปุ่มย้อนกลับ & หัวข้อ --}}
        <div class="d-flex flex-wrap align-items-center justify-content-between mb-4 gap-3">
            <div class="d-flex align-items-center">
                <a href="{{ route('admin.rooms.system', ['building_id' => $room->building_id]) }}"
                    class="btn btn-white border shadow-sm rounded-circle me-3 d-flex justify-content-center align-items-center"
                    style="width: 40px; height: 40px;">
                    <i class="fas fa-arrow-left text-secondary"></i>
                </a>
                <div>
                    <h4 class="mb-0 fw-bold text-dark">
                        <i class="fas fa-door-closed text-primary me-2"></i>ประวัติห้องพัก: <span
                            class="text-primary">{{ $room->room_number }}</span>
                    </h4>
                </div>
            </div>

            <div>
                {{-- Badge สถานะห้อง --}}
                @if ($room->status == 'ว่าง')
                    <span
                        class="badge bg-success bg-opacity-10 text-success border border-success px-3 py-2 fs-6 rounded-pill"><i
                            class="bi bi-door-open-fill me-1"></i> ห้องว่าง พร้อมปล่อยเช่า</span>
                @elseif($room->status == 'มีผู้เช่า')
                    <span
                        class="badge bg-primary bg-opacity-10 text-primary border border-primary px-3 py-2 fs-6 rounded-pill"><i
                            class="bi bi-person-fill me-1"></i> มีผู้เช่าใช้งานอยู่</span>
                @else
                    <span
                        class="badge bg-warning bg-opacity-10 text-dark border border-warning px-3 py-2 fs-6 rounded-pill"><i
                            class="bi bi-tools me-1"></i> {{ $room->status }}</span>
                @endif
            </div>
        </div>

        <div class="row g-4">
            {{-- 🌟 Left Column: ข้อมูลผู้เช่าปัจจุบัน & ข้อมูลห้อง --}}
            <div class="col-xl-3 col-lg-4">

                {{-- Card 1: ผู้เช่าปัจจุบัน --}}
                <div class="card shadow-sm border-0 rounded-4 mb-4">
                    <div class="card-header bg-white border-bottom-0 pt-4 pb-0 text-center">
                        <h6 class="fw-bold text-secondary text-uppercase small tracking-wide"><i
                                class="fas fa-user-check text-success me-1"></i> ผู้เช่าปัจจุบัน</h6>
                    </div>
                    <div class="card-body text-center pt-3 pb-4">
                        @if ($currentTenant)
                            <h5 class="fw-bold mb-1 text-dark">{{ $currentTenant->first_name }}
                                {{ $currentTenant->last_name }}</h5>
                            <p class="text-muted small mb-3"><i class="fas fa-phone-alt"></i> {{ $currentTenant->phone }}
                            </p>

                            <div class="bg-light rounded-3 p-3 text-start mb-3 border text-center">
                                <small class="d-block text-muted mb-1"><i class="far fa-calendar-alt me-1"></i>
                                    วันที่เริ่มสัญญา</small>
                                <span class="fw-bold text-dark d-block">{{ $currentTenant->formatted_start_date }}</span>
                            </div>

                            <div class="d-grid">
                                <a href="{{ route('admin.tenants.detail', $currentTenant->id) }}"
                                    class="btn btn-primary fw-bold shadow-sm">
                                    <i class="bi bi-person-lines-fill me-1"></i> ดูข้อมูลผู้เช่า
                                </a>
                            </div>
                        @else
                            <div class="py-4">
                                <div class="bg-light rounded-circle mx-auto d-flex align-items-center justify-content-center mb-3"
                                    style="width:80px; height:80px;">
                                    <i class="fas fa-door-open fa-2x text-muted opacity-50"></i>
                                </div>
                                <h6 class="text-muted fw-bold">ขณะนี้ห้องว่าง</h6>
                                <p class="small text-muted mb-0">ยังไม่มีผู้เช่าเข้าพัก</p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Card 2: สรุปข้อมูลห้อง (Modern UI) --}}
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-body p-4">
                        <h6 class="fw-bold text-secondary text-uppercase small tracking-wide mb-3">
                            <i class="bi bi-info-circle-fill me-1"></i> ข้อมูลห้องพัก
                        </h6>

                        <div
                            class="bg-success bg-opacity-10 border border-success border-opacity-25 rounded-4 p-3 mb-4 d-flex justify-content-between align-items-center">
                            <div>
                                <span class="d-block text-success small fw-bold mb-1">ราคาเช่ารายเดือน</span>
                                <h4 class="fw-bold text-success mb-0 lh-1">{{ number_format($room->price) }} <span
                                        class="fs-6 fw-normal">฿</span></h4>
                            </div>
                            <div class="bg-white rounded-circle d-flex justify-content-center align-items-center shadow-sm"
                                style="width: 45px; height: 45px;">
                                <i class="bi bi-cash-stack text-success fs-4"></i>
                            </div>
                        </div>

                        <ul class="list-group list-group-flush mb-0">
                            <li
                                class="list-group-item d-flex justify-content-between align-items-center px-0 py-3 border-bottom border-light-subtle bg-transparent">
                                <span class="text-muted d-flex align-items-center small fw-semibold">
                                    <div class="bg-light text-secondary rounded d-flex justify-content-center align-items-center me-3"
                                        style="width: 32px; height: 32px;">
                                        <i class="bi bi-tag-fill"></i>
                                    </div>
                                    ประเภทห้อง
                                </span>
                                <span class="fw-bold text-dark">{{ $room->roomPrice->roomType->name ?? '-' }}</span>
                            </li>

                            <li
                                class="list-group-item d-flex justify-content-between align-items-center px-0 py-3 border-bottom border-light-subtle bg-transparent">
                                <span class="text-muted d-flex align-items-center small fw-semibold">
                                    <div class="bg-light text-secondary rounded d-flex justify-content-center align-items-center me-3"
                                        style="width: 32px; height: 32px;">
                                        <i class="bi bi-building"></i>
                                    </div>
                                    ที่ตั้งอาคาร
                                </span>
                                <span
                                    class="fw-bold text-dark">{{ $room->roomPrice->building->name ?? 'ไม่ระบุตึก' }}</span>
                            </li>

                            <li
                                class="list-group-item d-flex justify-content-between align-items-center px-0 py-3 border-0 bg-transparent">
                                <span class="text-muted d-flex align-items-center small fw-semibold">
                                    <div class="bg-light text-secondary rounded d-flex justify-content-center align-items-center me-3"
                                        style="width: 32px; height: 32px;">
                                        <i class="bi bi-layers"></i>
                                    </div>
                                    ชั้น
                                </span>
                                <span class="fw-bold text-dark">{{ $room->roomPrice->floor_num ?? '-' }}</span>
                            </li>
                            <li class="list-group-item px-0 py-3 border-0 bg-transparent">
                                <span class="text-muted d-flex align-items-center small fw-semibold mb-2">
                                    <div class="bg-light text-secondary rounded d-flex justify-content-center align-items-center me-3" style="width: 32px; height: 32px;">
                                        <i class="bi bi-journal-text"></i>
                                    </div>
                                    หมายเหตุห้องพัก
                                </span>
                                <div class="bg-light p-3 rounded-3 text-dark small lh-lg border border-light-subtle" style="min-height: 80px;">
                                    @if($room->remark)
                                        {{ e($room->remark) }}
                                    @else
                                        <span class="text-muted opacity-50 fst-italic">ไม่มีหมายเหตุเพิ่มเติมสำหรับห้องนี้</span>
                                    @endif
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            {{-- 🌟 Right Column: ประวัติ & การซ่อม --}}
            <div class="col-xl-9 col-lg-8">

                {{-- Tabs --}}
                <div class="card shadow-sm border-0 rounded-4 mb-4">
                    <div class="card-header bg-white border-bottom-0 pt-3 pb-0 px-4">
                        <ul class="nav nav-tabs border-bottom-0 custom-tabs" id="historyTab" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link active fw-bold px-4 py-3" id="tenants-tab" data-bs-toggle="tab"
                                    data-bs-target="#tenants-history" type="button">
                                    <i class="fas fa-list-ul me-1"></i> ประวัติการเช่าทั้งหมด <span
                                        class="badge bg-primary ms-1 rounded-pill">{{ $allTenants->total() }}</span>
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link fw-bold px-4 py-3" id="repair-tab" data-bs-toggle="tab"
                                    data-bs-target="#repair-history" type="button">
                                    <i class="fas fa-tools me-1"></i> ประวัติแจ้งซ่อม <span
                                        class="badge bg-secondary ms-1 rounded-pill">{{ $maintenanceLogs->total() }}</span>
                                </button>
                            </li>
                        </ul>
                    </div>

                    <div class="card-body p-0 border-top">
                        <div class="tab-content">

                            {{-- ============================== --}}
                            {{-- Tab 1: ประวัติการเช่าทั้งหมด --}}
                            {{-- ============================== --}}
                            <div class="tab-pane fade show active p-4" id="tenants-history">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <h6 class="fw-bold text-dark mb-0">ข้อมูลผู้เช่าตั้งแต่อดีตถึงปัจจุบัน</h6>
                                    </div>
                                </div>

                                <div class="table-responsive rounded border mb-3">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light text-secondary small">
                                            <tr>
                                                <th class="ps-3 py-3">ชื่อ-นามสกุล</th>
                                                <th>เบอร์ติดต่อ</th>
                                                <th>วันที่เข้าอยู่</th>
                                                <th>วันที่ย้ายออก</th>
                                                <th>สถานะ</th>
                                                <th class="text-center pe-3">จัดการ</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($allTenants as $t)
                                                <tr
                                                    class="{{ $t->status == 'กำลังใช้งาน' ? 'bg-primary bg-opacity-10 border-primary' : '' }}">
                                                    <td class="ps-3 py-3">
                                                        <div class="fw-bold text-dark">{{ $t->first_name }}
                                                            {{ $t->last_name }}</div>
                                                    </td>
                                                    <td>{{ $t->phone }}</td>
                                                    <td>{{ $t->formatted_start_date }}</td>
                                                    <td>
                                                        @if ($t->status == 'กำลังใช้งาน')
                                                            <span class="text-muted">-</span>
                                                        @else
                                                            {{ $t->formatted_end_date }}
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if ($t->status == 'กำลังใช้งาน')
                                                            <span class="badge bg-primary text-white px-2 py-1"><i
                                                                    class="fas fa-star me-1 small"></i> ปัจจุบัน</span>
                                                        @else
                                                            <span
                                                                class="badge bg-secondary px-2 py-1 text-white">ย้ายออกแล้ว</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-center pe-3">
                                                        <a href="{{ route('admin.tenants.detail', $t->id) }}"
                                                            class="btn btn-sm {{ $t->status == 'กำลังใช้งาน' ? 'btn-primary' : 'btn-outline-secondary' }} rounded-pill px-3">
                                                            <i class="bi bi-search me-1"></i> ดูข้อมูล
                                                        </a>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6" class="text-center py-5 text-muted">
                                                        <div class="mb-2"><i
                                                                class="fas fa-folder-open fa-2x opacity-50"></i></div>
                                                        ยังไม่มีประวัติการเข้าพักของห้องนี้
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                {{-- 🌟 แสดง Pagination สำหรับผู้เช่า --}}
                                <div class="d-flex justify-content-end">
                                    {{ $allTenants->appends(request()->except('tenant_page'))->links('pagination::bootstrap-5') }}
                                </div>
                            </div>

                            {{-- ============================== --}}
                            {{-- Tab 2: ประวัติการซ่อมบำรุง --}}
                            {{-- ============================== --}}
                            <div class="tab-pane fade p-4" id="repair-history">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <h6 class="fw-bold text-dark mb-0">รายการซ่อมบำรุง</h6>
                                    </div>
                                    <button class="btn btn-warning text-dark fw-bold btn-sm shadow-sm"
                                        data-bs-toggle="modal" data-bs-target="#addMaintenanceModal">
                                        <i class="fas fa-plus-circle me-1"></i> แจ้งซ่อมใหม่
                                    </button>
                                </div>

                                <div class="table-responsive rounded border mb-3">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light text-secondary small">
                                            <tr>
                                                <th class="ps-3 py-3">วันที่แจ้ง</th>
                                                <th>หัวข้อ / อาการเสีย</th>
                                                <th>สถานะ</th>
                                                <th>นายช่าง</th>
                                                <th>เวลานัดช่าง</th>
                                                <th class="pe-3">วันเสร็จสิ้น</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($maintenanceLogs as $log)
                                                <tr>
                                                    <td class="ps-3 py-3 fw-semibold text-dark">
                                                        {{ $log->formatted_repair_date }}</td>
                                                    <td>
                                                        <div class="fw-bold text-dark">{{ $log->title }}</div>
                                                        <div class="small text-muted text-truncate"
                                                            style="max-width: 200px;">{{ $log->details ?? '-' }}</div>
                                                    </td>
                                                    <td>
                                                        @if ($log->status == 'pending')
                                                            <span class="badge bg-warning text-dark px-2 py-1"><i
                                                                    class="bi bi-hourglass-split"></i> รอดำเนินการ</span>
                                                        @elseif($log->status == 'processing')
                                                            <span class="badge bg-info text-dark px-2 py-1"><i
                                                                    class="bi bi-tools"></i> กำลังซ่อม</span>
                                                        @elseif($log->status == 'finished')
                                                            <span class="badge bg-success px-2 py-1"><i
                                                                    class="bi bi-check-circle"></i> เสร็จสิ้น</span>
                                                        @else
                                                            <span class="badge bg-secondary px-2 py-1">ยกเลิก</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ $log->technician_name ?? '-' }}</td>
                                                    <td>
                                                        <span
                                                            class="small {{ $log->technician_time ? 'text-primary fw-semibold' : 'text-muted' }}">
                                                            {{ $log->formatted_tech_time }}
                                                        </span>
                                                    </td>
                                                    <td class="pe-3">
                                                        <span
                                                            class="small {{ $log->finish_date ? 'text-success fw-bold' : 'text-muted' }}">
                                                            {{ $log->formatted_finish_date }}
                                                        </span>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6" class="text-center py-5 text-muted">
                                                        <div class="mb-2"><i class="fas fa-tools fa-2x opacity-50"></i>
                                                        </div>
                                                        ไม่เคยมีประวัติการแจ้งซ่อม
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                {{-- 🌟 แสดง Pagination สำหรับประวัติซ่อม --}}
                                <div class="d-flex justify-content-end">
                                    {{ $maintenanceLogs->appends(request()->except('repair_page'))->links('pagination::bootstrap-5') }}
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- Modal บันทึกแจ้งซ่อม... (เหมือนเดิม ไม่ได้แก้ไข) --}}
    {{-- ===== MODAL: แจ้งซ่อมใหม่ (แอดมินสร้างเอง) ===== --}}
    <div class="modal fade" id="addMaintenanceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-bottom bg-warning bg-opacity-25 px-4 py-3">
                    <h5 class="modal-title fw-bold text-dark">
                        <i class="fa-solid fa-wrench text-warning me-2"></i> สร้างรายการแจ้งซ่อมใหม่
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('admin.maintenance.insert') }}" method="POST">
                    @csrf
                    <div class="modal-body px-4 py-4">
                        <div class="alert alert-light border small text-muted mb-4">
                            <i class="bi bi-info-circle-fill text-primary me-1"></i>
                            รายการนี้จะถูกสร้างสำหรับห้อง <strong class="text-dark">{{ $room->room_number }}</strong>
                            @if ($currentTenant)
                                (ผู้เช่า: {{ $currentTenant->first_name }})
                            @else
                                (ห้องว่าง)
                            @endif
                        </div>

                        <input type="hidden" name="room_id" value="{{ $room->id }}">

                        <div class="mb-3">
                            <label class="form-label fw-bold text-dark">หัวข้อแจ้งซ่อม <span
                                    class="text-danger">*</span></label>
                            <input type="text" id="maintenanceTitle" name="title" class="form-control"
                                placeholder="เช่น แอร์ไม่เย็น, ก๊อกน้ำรั่ว" required>
                        </div>

                        {{-- 🌟 เพิ่มปุ่มเลือกปัญหาที่พบบ่อย (Quick Fill) --}}
                        <div class="mb-4">
                            <label class="form-label fw-semibold text-muted small">เลือกปัญหาที่พบบ่อย</label>
                            <div class="d-flex flex-wrap gap-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill quick-btn"
                                    data-value="แอร์ไม่เย็น">
                                    <i class="fa-solid fa-snowflake me-1"></i>แอร์ไม่เย็น
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill quick-btn"
                                    data-value="น้ำไม่ไหล">
                                    <i class="fa-solid fa-faucet me-1"></i>น้ำไม่ไหล
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill quick-btn"
                                    data-value="ไฟฟ้าเสีย">
                                    <i class="fa-solid fa-bolt me-1"></i>ไฟฟ้าเสีย
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill quick-btn"
                                    data-value="ประตูล็อคไม่ได้">
                                    <i class="fa-solid fa-lock me-1"></i>ประตูล็อคไม่ได้
                                </button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold text-dark">รายละเอียดเพิ่มเติม <small
                                    class="text-muted fw-normal">(ไม่บังคับ)</small></label>
                            <textarea name="details" class="form-control" rows="3" placeholder="ระบุอาการเสีย หรือข้อมูลเพิ่มเติม..."></textarea>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark">ชื่อช่าง <small
                                        class="text-muted fw-normal">(ไม่บังคับ)</small></label>
                                <input type="text" name="technician_name" class="form-control"
                                    placeholder="ระบุชื่อช่าง">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark">เวลานัดซ่อม <small
                                        class="text-muted fw-normal">(ไม่บังคับ)</small></label>
                                <input type="datetime-local" name="technician_time" class="form-control">
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label fw-bold text-dark">สถานะเริ่มต้น <span
                                    class="text-danger">*</span></label>
                            <select name="status" class="form-select fw-semibold" required>
                                <option value="pending" class="text-warning">รอดำเนินการ (รับเรื่องไว้)</option>
                                <option value="processing" class="text-info">กำลังซ่อม (นัดช่างแล้ว)</option>
                                <option value="finished" class="text-success">เสร็จสิ้น (ซ่อมเสร็จแล้ว)</option>
                            </select>
                        </div>

                    </div>
                    <div class="modal-footer border-top bg-light px-4 py-3">
                        <button type="button" class="btn btn-secondary px-4 rounded-pill"
                            data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-warning text-dark fw-bold px-4 shadow-sm rounded-pill">
                            <i class="fa-solid fa-save me-1"></i> บันทึกข้อมูล
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @push('styles')
        <style>
            .tracking-wide {
                letter-spacing: 0.5px;
            }

            .custom-tabs .nav-link {
                color: #6c757d;
                border: none;
                border-bottom: 3px solid transparent;
                transition: all 0.3s ease;
            }

            .custom-tabs .nav-link:hover {
                color: #0d6efd;
                background-color: transparent;
            }

            .custom-tabs .nav-link.active {
                color: #0d6efd;
                background-color: transparent;
                border-bottom: 3px solid #0d6efd;
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            // 🌟 Script สำหรับจำหน้าแท็บ (Tab) เวลาเปลี่ยนหน้า Paginator 🌟
            document.addEventListener("DOMContentLoaded", function() {
                // โหลดแท็บเดิมที่เคยเปิดไว้
                let activeTab = sessionStorage.getItem('roomHistoryActiveTab');
                if (activeTab) {
                    let targetEl = document.querySelector(activeTab);
                    if (targetEl) {
                        let tab = new bootstrap.Tab(targetEl);
                        tab.show();
                    }
                }

                // บันทึกแท็บเวลาคลิกเปลี่ยน
                let tabLinks = document.querySelectorAll('button[data-bs-toggle="tab"]');
                tabLinks.forEach(function(link) {
                    link.addEventListener('shown.bs.tab', function(e) {
                        sessionStorage.setItem('roomHistoryActiveTab', '#' + e.target.id);
                    });
                });
            });

            // 🌟 ปัญหาที่พบบ่อย (Quick Fill) สำหรับ Modal แจ้งซ่อมใหม่
            document.addEventListener('DOMContentLoaded', function() {
                document.querySelectorAll('.quick-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        // เซ็ตค่าลงใน Input Title
                        document.getElementById('maintenanceTitle').value = this.dataset.value;

                        // เปลี่ยนสไตล์ปุ่มทั้งหมดให้เป็นเส้นขอบ (outline) 
                        document.querySelectorAll('.quick-btn').forEach(b => {
                            b.classList.remove('btn-primary', 'text-white');
                            b.classList.add('btn-outline-secondary');
                        });

                        // เปลี่ยนสไตล์ปุ่มที่ถูกคลิกให้เป็นสีทึบ
                        this.classList.remove('btn-outline-secondary');
                        this.classList.add('btn-primary', 'text-white');
                    });
                });
            });
        </script>
    @endpush
@endsection
