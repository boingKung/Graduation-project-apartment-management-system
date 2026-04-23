@extends('admin.layout')

@section('title', 'รายการแจ้งซ่อม')

@section('content')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <div class="container-fluid">

        {{-- Header & Filter --}}
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h3 class="text-primary fw-bold"><i class="fas fa-tools"></i> รายการแจ้งซ่อมทั้งหมด</h3>
                <p class="text-muted mb-0">คลิกปุ่มดำเนินการได้เลย — ไม่ต้องเปิดหน้าต่างเพิ่ม</p>
            </div>

            <div class="d-flex gap-2 flex-wrap">

                <div class="position-relative">
                    <input type="text" id="searchInput" class="form-control ps-5 rounded-pill border-secondary"
                        placeholder="ค้นหาห้อง, อาการ...">
                    <i class="fas fa-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                </div>

                <div class="btn-group shadow-sm">
                    <a href="{{ route('admin.maintenance.index') }}"
                        class="btn btn-outline-secondary {{ !request('status') ? 'active' : '' }}">ทั้งหมด</a>
                    <a href="{{ route('admin.maintenance.index', ['status' => 'pending']) }}"
                        class="btn btn-outline-warning {{ request('status') == 'pending' ? 'active' : '' }}">
                        รอดำเนินการ @if ($pendingCount > 0)
                            <span class="badge bg-danger rounded-pill">{{ $pendingCount }}</span>
                        @endif
                    </a>
                    <a href="{{ route('admin.maintenance.index', ['status' => 'processing']) }}"
                        class="btn btn-outline-info {{ request('status') == 'processing' ? 'active' : '' }}">กำลังซ่อม</a>
                    <a href="{{ route('admin.maintenance.index', ['status' => 'finished']) }}"
                        class="btn btn-outline-success {{ request('status') == 'finished' ? 'active' : '' }}">เสร็จสิ้น</a>
                </div>
            </div>
        </div>

        {{-- Summary Cards --}}
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            {{-- 🌟 แก้ไข: กำหนดความกว้าง-สูงให้เท่ากัน (50px) และใช้ Flex เพื่อให้อยู่ตรงกลาง --}}
                            <div class="bg-warning-subtle text-warning rounded-circle me-3 d-flex align-items-center justify-content-center shadow-sm" 
                                style="width: 50px; height: 50px;">
                                <i class="fas fa-clock fs-5"></i>
                            </div>
                            <div>
                                <div class="text-muted small">รอดำเนินการ</div>
                                <div class="h4 fw-bold mb-0">{{ $statusCounts['pending'] ?? 0 }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            {{-- 🌟 แก้ไข: กำหนดความกว้าง-สูงให้เท่ากัน (50px) และใช้ Flex เพื่อให้อยู่ตรงกลาง --}}
                            <div class="bg-info-subtle text-info rounded-circle me-3 d-flex align-items-center justify-content-center shadow-sm" 
                                style="width: 50px; height: 50px;">
                                <i class="fas fa-wrench fs-5"></i>
                            </div>
                            <div>
                                <div class="text-muted small">กำลังซ่อม</div>
                                <div class="h4 fw-bold mb-0">{{ $statusCounts['processing'] ?? 0 }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            {{-- 🌟 แก้ไข: กำหนดความกว้าง-สูงให้เท่ากัน (50px) และใช้ Flex เพื่อให้อยู่ตรงกลาง --}}
                            <div class="bg-success-subtle text-success rounded-circle me-3 d-flex align-items-center justify-content-center shadow-sm" 
                                style="width: 50px; height: 50px;">
                                <i class="fas fa-check-circle fs-5"></i>
                            </div>
                            <div>
                                <div class="text-muted small">เสร็จสิ้น</div>
                                <div class="h4 fw-bold mb-0">{{ $statusCounts['finished'] ?? 0 }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            {{-- 🌟 แก้ไข: กำหนดความกว้าง-สูงให้เท่ากัน (50px) และใช้ Flex เพื่อให้อยู่ตรงกลาง --}}
                            <div class="bg-secondary-subtle text-secondary rounded-circle me-3 d-flex align-items-center justify-content-center shadow-sm" 
                                style="width: 50px; height: 50px;">
                                <i class="fas fa-ban fs-5"></i>
                            </div>
                            <div>
                                <div class="text-muted small">ยกเลิก</div>
                                <div class="h4 fw-bold mb-0">{{ $statusCounts['cancelled'] ?? 0 }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== Workflow Guide ===== --}}
        <div class="alert alert-light border mb-4 py-2 px-3 d-flex align-items-center gap-3 small">
            <span class="text-muted">ขั้นตอน:</span>
            <span><span class="badge bg-warning text-dark">รอดำเนินการ</span></span>
            <i class="fas fa-arrow-right text-muted"></i>
            <span>คลิก <strong class="text-primary">รับงาน</strong> (ระบุช่าง)</span>
            <i class="fas fa-arrow-right text-muted"></i>
            <span><span class="badge bg-info text-dark">กำลังซ่อม</span></span>
            <i class="fas fa-arrow-right text-muted"></i>
            <span>คลิก <strong class="text-success">ปิดงาน</strong></span>
            <i class="fas fa-arrow-right text-muted"></i>
            <span><span class="badge bg-success">เสร็จสิ้น</span> ✓</span>
        </div>

        {{-- ===== Table ===== --}}
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-bottom-0 pt-3 pb-2 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold"><i class="fas fa-list text-primary me-2"></i>รายการทั้งหมด</h5>

                {{-- 🌟 ย้ายปุ่มแจ้งซ่อมใหม่มาไว้ขวาบนของตาราง --}}
                <button type="button" class="btn btn-warning text-dark fw-bold shadow-sm rounded-pill px-4"
                    data-bs-toggle="modal" data-bs-target="#adminAddMaintenanceModal">
                    <i class="fas fa-plus-circle me-1"></i> แจ้งซ่อมใหม่
                </button>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="maintenanceTable">
                        <thead class="bg-light">
                            <tr>
                                <th>ห้อง</th>
                                <th>อาการ / ปัญหา</th>
                                <th>สถานะ</th>
                                <th>ช่าง</th>
                                <th class="text-center" style="min-width: 200px;">ดำเนินการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($maintenances as $job)
                                @php
                                    $waitDays = (int) \Carbon\Carbon::parse($job->created_at)->diffInDays(now());
                                    $isUrgent = $job->status === 'pending' && $waitDays >= 3;
                                @endphp
                                <tr
                                    class="{{ $isUrgent ? 'border-start border-danger border-3' : ($job->status === 'pending' ? 'border-start border-warning border-3' : '') }}">

                                    {{-- Room + Date --}}
                                    <td class="ps-3">
                                        <span class="badge bg-primary fs-6">{{ $job->room->room_number }}</span>
                                        <div class="text-muted small">{{ $job->room->roomPrice->building->name ?? '' }}
                                        </div>
                                        <div class="text-muted" style="font-size: 0.7rem;">
                                            แจ้ง {{ \Carbon\Carbon::parse($job->created_at)->format('d/m/y H:i') }}
                                            @if ($job->status === 'pending' && $waitDays >= 1)
                                                &middot; <span
                                                    class="{{ $isUrgent ? 'text-danger fw-bold' : 'text-warning' }}">{{ $waitDays }}
                                                    วันแล้ว</span>
                                            @endif
                                        </div>
                                    </td>

                                    {{-- Problem --}}
                                    <td style="max-width: 280px;">
                                        <div class="fw-bold text-dark">{{ $job->title }}</div>
                                        @if ($job->details)
                                            <div class="text-muted small text-truncate" style="max-width: 250px;">
                                                {{ $job->details }}</div>
                                        @endif
                                    </td>

                                    {{-- Status Badge --}}
                                    <td>
                                        @switch($job->status)
                                            @case('pending')
                                                <span class="badge bg-warning text-dark"><i
                                                        class="fas fa-clock me-1"></i>รอดำเนินการ</span>
                                            @break

                                            @case('processing')
                                                <span class="badge bg-info text-dark"><i
                                                        class="fas fa-wrench me-1"></i>กำลังซ่อม</span>
                                            @break

                                            @case('finished')
                                                <span class="badge bg-success"><i
                                                        class="fas fa-check-circle me-1"></i>เสร็จสิ้น</span>
                                                @if ($job->finish_date)
                                                    <div class="text-muted" style="font-size: 0.7rem;">
                                                        {{ \Carbon\Carbon::parse($job->finish_date)->format('d/m/y') }}</div>
                                                @endif
                                            @break

                                            @default
                                                <span class="badge bg-secondary">ยกเลิก</span>
                                        @endswitch
                                    </td>

                                    {{-- Technician --}}
                                    <td>
                                        @if ($job->technician_name)
                                            <div class="fw-semibold small">{{ $job->technician_name }}</div>
                                            @if ($job->technician_time)
                                                <div class="text-muted" style="font-size: 0.7rem;">
                                                    นัด
                                                    {{ \Carbon\Carbon::parse($job->technician_time)->format('d/m H:i') }}
                                                </div>
                                            @endif
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>

                                    {{-- Actions --}}
                                    <td class="text-center">
                                        <div class="d-flex gap-1 justify-content-center align-items-center flex-wrap">
                                            @switch($job->status)
                                                @case('pending')
                                                    {{-- Main: รับงาน (pending → processing) --}}
                                                    <button class="btn btn-primary btn-sm fw-semibold"
                                                        data-id="{{ $job->id }}"
                                                        data-tech-name="{{ e($job->technician_name) }}"
                                                        data-tech-time="{{ $job->technician_time ?? '' }}"
                                                        onclick="acceptJob(this)">
                                                        <i class="fas fa-user-cog me-1"></i> รับงาน
                                                    </button>
                                                    <button class="btn btn-outline-secondary btn-sm" title="ยกเลิกงาน"
                                                        onclick="cancelJob({{ $job->id }})">
                                                        <i class="fas fa-ban"></i>
                                                    </button>
                                                    <button class="btn btn-outline-danger btn-sm" title="ลบ"
                                                        onclick="confirmDelete({{ $job->id }})">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                @break

                                                @case('processing')
                                                    {{-- Main: ปิดงาน (processing → finished) --}}
                                                    <button class="btn btn-success btn-sm fw-semibold"
                                                        data-id="{{ $job->id }}"
                                                        data-tech-name="{{ e($job->technician_name) }}"
                                                        data-tech-time="{{ $job->technician_time ?? '' }}"
                                                        data-details="{{ e($job->details) }}" onclick="finishJob(this)">
                                                        <i class="fas fa-check me-1"></i> ปิดงาน
                                                    </button>
                                                    <button class="btn btn-outline-secondary btn-sm" title="แก้ไขข้อมูลช่าง"
                                                        data-id="{{ $job->id }}"
                                                        data-tech-name="{{ e($job->technician_name) }}"
                                                        data-tech-time="{{ $job->technician_time ?? '' }}"
                                                        data-details="{{ e($job->details) }}" onclick="editJob(this)">
                                                        <i class="fas fa-pen"></i>
                                                    </button>
                                                @break

                                                @case('finished')
                                                    {{-- View only --}}
                                                    <button class="btn btn-outline-success btn-sm" title="ดูรายละเอียด"
                                                        data-title="{{ e($job->title) }}"
                                                        data-room="{{ $job->room->room_number ?? '-' }}"
                                                        data-building="{{ $job->room->roomPrice->building->name ?? '' }}"
                                                        data-tech-name="{{ e($job->technician_name) }}"
                                                        data-tech-time="{{ $job->technician_time ? \Carbon\Carbon::parse($job->technician_time)->format('d/m/Y H:i') : '-' }}"
                                                        data-details="{{ e($job->details) }}"
                                                        data-finish-date="{{ $job->finish_date ? \Carbon\Carbon::parse($job->finish_date)->format('d/m/Y H:i') : '-' }}"
                                                        data-created="{{ \Carbon\Carbon::parse($job->created_at)->format('d/m/Y H:i') }}"
                                                        onclick="viewJob(this)">
                                                        <i class="fas fa-eye me-1"></i> ดูข้อมูล
                                                    </button>
                                                @break

                                                @default
                                                    <span class="text-muted small">-</span>
                                            @endswitch
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">
                                            <i
                                                class="fas fa-tools fa-3x mb-3 text-secondary bg-light p-4 rounded-circle"></i><br>
                                            ยังไม่มีรายการแจ้งซ่อมในขณะนี้
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="p-3 border-top">
                        {{ $maintenances->withQueryString()->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Hidden forms for submission --}}
        <form id="quickActionForm" method="POST" style="display: none;">
            @csrf
            @method('PUT')
            <input type="hidden" name="status" id="qa_status">
            <input type="hidden" name="details" id="qa_details">
            <input type="hidden" name="technician_name" id="qa_technician_name">
            <input type="hidden" name="technician_time" id="qa_technician_time">
        </form>

        <form id="deleteForm" method="POST" style="display: none;">
            @csrf
        </form>


        {{-- ===== MODAL: แจ้งซ่อมใหม่ (แอดมินสร้างเอง) ===== --}}
        <div class="modal fade" id="adminAddMaintenanceModal" tabindex="-1" aria-hidden="true">
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

                            <div class="mb-4">
                                <label class="form-label fw-bold text-dark">เลือกห้องพัก <span
                                        class="text-danger">*</span></label>
                                <select name="room_id" class="form-select fw-semibold" required>
                                    <option value="">-- เลือกห้องพักที่ต้องการแจ้งซ่อม --</option>
                                    @foreach (\App\Models\Room::orderBy('room_number')->get() as $r)
                                        <option value="{{ $r->id }}">ห้อง {{ $r->room_number }}</option>
                                    @endforeach
                                </select>
                            </div>

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


        <script>
            const UPDATE_URL = "{{ route('admin.maintenance.update_status', ':id') }}";
            const DELETE_URL = "{{ route('admin.maintenance.delete', ':id') }}";

            // ========================================
            // 1) รับงาน (pending → processing)
            // ========================================
            function acceptJob(btn) {
            const id = btn.dataset.id;
            const techName = btn.dataset.techName || '';
            const techTime = btn.dataset.techTime || '';

            Swal.fire({
                title: '🔧 รับงานซ่อม',
                html: `
            <div class="text-start px-2">
                <div class="mb-3">
                    <label class="form-label fw-bold">ชื่อช่างที่รับงาน <span class="text-danger">*</span></label>
                    <input type="text" id="swal_tech_name" class="form-control" placeholder="เช่น ช่างเอก, ช่างสมชาย">
                </div>
                <div>
                    <label class="form-label fw-bold">วัน-เวลานัดซ่อม <small class="text-muted fw-normal">(ไม่บังคับ)</small></label>
                    <input type="datetime-local" id="swal_tech_time" class="form-control">
                </div>
            </div>
        `,
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-user-cog"></i> รับงาน',
                cancelButtonText: 'ยกเลิก',
                confirmButtonColor: '#0d6efd',
                focusConfirm: false,
                didOpen: () => {
                    // 🌟 เซ็ตค่าข้อมูลเดิมลงใน Input
                    document.getElementById('swal_tech_name').value = techName;
                    document.getElementById('swal_tech_time').value = formatDateTimeLocal(techTime);
                    document.getElementById('swal_tech_name').focus();
                },
                preConfirm: () => {
                    const name = document.getElementById('swal_tech_name').value.trim();
                    if (!name) {
                        Swal.showValidationMessage('กรุณาระบุชื่อช่าง');
                        return false;
                    }
                    return {
                        technician_name: name,
                        technician_time: document.getElementById('swal_tech_time').value || ''
                    };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    submitAction(id, 'processing', {
                        technician_name: result.value.technician_name,
                        technician_time: result.value.technician_time
                    });
                }
            });
        }

            // ========================================
            // 2) ปิดงาน (processing → finished)
            // 🌟 แก้ไข: ดึงข้อมูลเดิมมาโชว์ในช่องบันทึกสิ่งที่ซ่อม
            // ========================================
            function finishJob(btn) {
                const id = btn.dataset.id;
                const techName = btn.dataset.techName;
                const techTime = btn.dataset.techTime;
                const existingDetails = btn.dataset.details || ''; // 🌟 ดึงข้อมูล details เดิม

                Swal.fire({
                    title: '✅ ปิดงานซ่อม',
                    html: `
            <div class="text-start px-2">
                <p class="mb-3">ช่าง: <strong id="swal_finish_tech_display"></strong></p>
                <div>
                    <label class="form-label fw-bold">บันทึกสิ่งที่ซ่อม <small class="text-muted fw-normal">(ไม่บังคับ)</small></label>
                    <textarea id="swal_finish_details" class="form-control" rows="3" placeholder="เช่น เปลี่ยนก๊อกน้ำ, ซ่อมแอร์เรียบร้อย">${existingDetails}</textarea>
                </div>
            </div>
        `,
                    showCancelButton: true,
                    confirmButtonText: '<i class="fas fa-check"></i> ยืนยันปิดงาน',
                    cancelButtonText: 'ยกเลิก',
                    confirmButtonColor: '#198754',
                    didOpen: () => {
                        document.getElementById('swal_finish_tech_display').textContent = techName;
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        const details = document.getElementById('swal_finish_details').value;
                        submitAction(id, 'finished', {
                            technician_name: techName,
                            technician_time: techTime,
                            details: details // ส่งข้อมูลใหม่ทับข้อมูลเดิม
                        });
                    }
                });
            }

            // ========================================
            // 3) แก้ไขข้อมูลช่าง (processing only)
            // 🌟 แก้ไข: ดึงข้อมูลเดิมมาโชว์ตอนกดแก้ไข
            // ========================================
            function editJob(btn) {
                const id = btn.dataset.id;
                const techName = btn.dataset.techName || '';
                const techTime = btn.dataset.techTime || '';
                const details = btn.dataset.details || ''; // 🌟 ดึงข้อมูล details เดิม

                Swal.fire({
                    title: '✏️ แก้ไขข้อมูลงานซ่อม',
                    html: `
            <div class="text-start px-2">
                <div class="mb-3">
                    <label class="form-label fw-bold">ชื่อช่าง</label>
                    <input type="text" id="swal_edit_tech_name" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">วัน-เวลานัดซ่อม</label>
                    <input type="datetime-local" id="swal_edit_tech_time" class="form-control">
                </div>
                <div>
                    <label class="form-label fw-bold">รายละเอียดเพิ่มเติม</label>
                    <textarea id="swal_edit_details" class="form-control" rows="3"></textarea>
                </div>
            </div>
        `,
                    showCancelButton: true,
                    confirmButtonText: '<i class="fas fa-save"></i> บันทึก',
                    cancelButtonText: 'ยกเลิก',
                    confirmButtonColor: '#0d6efd',
                    didOpen: () => {
                        document.getElementById('swal_edit_tech_name').value = techName;
                        document.getElementById('swal_edit_tech_time').value = formatDateTimeLocal(techTime);
                        document.getElementById('swal_edit_details').value = details; // 🌟 เซ็ตค่ารายละเอียดเดิม
                    },
                    preConfirm: () => {
                        const name = document.getElementById('swal_edit_tech_name').value.trim();
                        if (!name) {
                            Swal.showValidationMessage('กรุณาระบุชื่อช่าง');
                            return false;
                        }
                        return {
                            technician_name: name,
                            technician_time: document.getElementById('swal_edit_tech_time').value || '',
                            details: document.getElementById('swal_edit_details').value || ''
                        };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        submitAction(id, 'processing', {
                            technician_name: result.value.technician_name,
                            technician_time: result.value.technician_time,
                            details: result.value.details // ส่งข้อมูลที่แก้ไขแล้วกลับไป
                        });
                    }
                });
            }

            // ========================================
            // 4) ยกเลิกงาน
            // ========================================
            function cancelJob(id) {
                Swal.fire({
                    title: 'ยกเลิกงานซ่อมนี้?',
                    text: 'รายการนี้จะถูกเปลี่ยนเป็นสถานะ "ยกเลิก"',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: '<i class="fas fa-ban"></i> ยืนยันยกเลิก',
                    cancelButtonText: 'ไม่ ปิดเมนูนี้',
                    confirmButtonColor: '#e84e40',
                }).then((result) => {
                    if (result.isConfirmed) {
                        submitAction(id, 'cancelled', {});
                    }
                });
            }

            // ========================================
            // 5) ดูรายละเอียด (finished) - Modern UI Design
            // ========================================
            function viewJob(btn) {
                const roomDisplay = btn.dataset.building ?
                    `${btn.dataset.room} <span class="text-muted fs-6 fw-normal">(${btn.dataset.building})</span>` :
                    btn.dataset.room;

                const title = btn.dataset.title || '-';
                const techName = btn.dataset.techName || '-';
                const techTime = btn.dataset.techTime || '-';
                const details = btn.dataset.details || '-';
                const created = btn.dataset.created || '-';
                const finishDate = btn.dataset.finishDate || '-';

                Swal.fire({
                    html: `
            <div class="text-start" style="font-family: inherit;">
                
                <div class="d-flex align-items-center mb-4 pb-3 border-bottom">
                    <div class="bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center me-3 shadow-sm" style="width: 55px; height: 55px;">
                        <i class="fas fa-clipboard-check fs-3"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold mb-1 text-dark">รายละเอียดงานซ่อม</h4>
                        <span class="badge bg-success bg-opacity-10 text-success border border-success rounded-pill px-3 py-1">
                            <i class="fas fa-check-circle me-1"></i> ปิดงานเรียบร้อย
                        </span>
                    </div>
                </div>

                <div class="mb-4">
                    <div class="p-3 bg-light rounded-4 border shadow-sm" style="border-left: 5px solid #0d6efd !important;">
                        <div class="text-primary fw-bold fs-5 mb-2">${title}</div>
                        <div class="text-secondary fw-semibold">
                            <i class="fas fa-door-closed me-2 text-primary"></i>ห้อง: <span class="text-dark fs-5 ms-1">${roomDisplay}</span>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-6">
                        <div class="p-3 border rounded-4 h-100 bg-white shadow-sm">
                            <div class="text-muted small mb-1"><i class="fas fa-user-hard-hat me-1 text-warning"></i> ช่างผู้รับผิดชอบ</div>
                            <div class="fw-bold text-dark fs-6">${techName}</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 border rounded-4 h-100 bg-white shadow-sm">
                            <div class="text-muted small mb-1"><i class="fas fa-calendar-check me-1 text-info"></i> เวลานัดหมาย</div>
                            <div class="fw-bold text-dark fs-6">${techTime}</div>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <div class="text-muted small mb-2 fw-semibold"><i class="fas fa-tools me-1"></i> บันทึกหลังการซ่อม</div>
                    <div class="p-3 bg-secondary bg-opacity-10 rounded-4 text-dark border border-light" style="min-height: 80px; max-height: 150px; overflow-y: auto; line-height: 1.6;">
                        ${details}
                    </div>
                </div>

                <div class="pt-3 border-top mt-2">
                    <div class="d-flex justify-content-between align-items-center px-2">
                        <div class="text-center">
                            <div class="text-muted small mb-1"><i class="fas fa-paper-plane me-1"></i> แจ้งเรื่องเมื่อ</div>
                            <div class="fw-semibold text-dark small bg-light px-2 py-1 rounded">${created}</div>
                        </div>
                        <div class="text-muted opacity-50">
                            <i class="fas fa-long-arrow-alt-right fs-3"></i>
                        </div>
                        <div class="text-center">
                            <div class="text-muted small mb-1"><i class="fas fa-flag-checkered me-1 text-success"></i> เสร็จสิ้นเมื่อ</div>
                            <div class="fw-bold text-success small bg-success bg-opacity-10 px-2 py-1 rounded">${finishDate}</div>
                        </div>
                    </div>
                </div>

            </div>
        `,
                    showCloseButton: true,
                    showConfirmButton: false,
                    width: 580,
                    padding: '2rem',
                    customClass: {
                        popup: 'rounded-4 shadow-lg border-0',
                        htmlContainer: 'm-0'
                    }
                });
            }

            // ========================================
            // Form Submission (ส่งเฉพาะ field ที่จำเป็น)
            // ========================================
            function submitAction(id, status, data) {
                const form = document.getElementById('quickActionForm');
                form.action = UPDATE_URL.replace(':id', id);

                document.getElementById('qa_status').value = status;
                document.getElementById('qa_status').disabled = false;

                const fields = {
                    'qa_details': 'details',
                    'qa_technician_name': 'technician_name',
                    'qa_technician_time': 'technician_time'
                };

                Object.entries(fields).forEach(([inputId, key]) => {
                    const input = document.getElementById(inputId);
                    if (key in data) {
                        input.value = data[key];
                        input.disabled = false;
                    } else {
                        input.disabled = true;
                    }
                });

                form.submit();
            }

            // ========================================
            // Delete
            // ========================================
            function confirmDelete(id) {
                Swal.fire({
                    title: 'ลบรายการนี้?',
                    text: 'ข้อมูลนี้จะไม่สามารถกู้คืนได้!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="fas fa-trash"></i> ลบเลย',
                    cancelButtonText: 'ยกเลิก',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = document.getElementById('deleteForm');
                        form.action = DELETE_URL.replace(':id', id);
                        form.submit();
                    }
                });
            }

            // ========================================
            // Real-time Search
            // ========================================
            document.getElementById('searchInput').addEventListener('keyup', function() {
                const value = this.value.toLowerCase();
                document.querySelectorAll('#maintenanceTable tbody tr').forEach(row => {
                    row.style.display = row.innerText.toLowerCase().includes(value) ? '' : 'none';
                });
            });

            // ========================================
            // Util
            // ========================================
            function formatDateTimeLocal(value) {
                if (!value) return '';
                return value.replace(' ', 'T').slice(0, 16);
            }

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

            // ========================================
            // Success Alert from Session
            // ========================================

            @if ($errors->any())
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: "{{ $errors->first() }}",
                });
            @endif
        </script>
    @endsection
