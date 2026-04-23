@extends('admin.layout')

@section('content')
    <style>
        /* 🌟 ป้องกัน FOUC และทำ Fade-in Animation */
        .page-content-wrapper {
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.4s ease-in-out, visibility 0.4s;
        }

        .page-content-wrapper.content-loaded {
            opacity: 1;
            visibility: visible;
        }

        /* 🌟 Staggered Fade-in สำหรับ Floor Section */
        .floor-section {
            opacity: 0;
            transform: translateY(15px);
            transition: opacity 0.5s ease-out, transform 0.5s ease-out;
        }

        .floor-section.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .hover-card:hover { transform: translateY(-5px); background-color: #f8f9fa; }
        
        /* Styling for Room Card */
        .room-card {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            transition: all 0.25s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        .room-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08) !important;
        }

        .room-number {
            font-size: 1.25rem;
            font-weight: 800;
            padding: 8px 0;
            text-align: center;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            letter-spacing: 0.5px;
        }

        .btn-room-info {
            position: absolute;
            top: 8px; right: 8px;
            background: none; border: none;
            color: rgba(0,0,0,0.3); font-size: 0.9rem;
            z-index: 5; transition: color 0.2s; padding: 0;
        }
        .btn-room-info:hover { color: #0d6efd; }

        .room-body { padding: 12px 10px; text-align: center; flex-grow: 1; }
        .room-footer { padding: 10px; background: rgba(0,0,0,0.02); border-top: 1px solid rgba(0,0,0,0.05); }
        .btn-action { font-size: 0.85rem; padding: 5px 0; border-radius: 6px; font-weight: 600; }
        .badge-status { font-weight: 600; padding: 6px 10px; font-size: 0.7rem; }

        /* 🌟 Theme: ว่าง (สีเทา) */
        .status-ว่าง .room-number { background-color: #f1f5f9; color: #475569; }
        .status-ว่าง .badge-status { background-color: #f1f5f9; color: #64748b; border: 1px solid #cbd5e1; }
        .status-ว่าง { border-color: #e2e8f0; }

        /* 🌟 Theme: มีผู้เช่า (สีเขียว) */
        .status-มีผู้เช่า .room-number { background-color: #ecfdf5; color: #065f46; }
        .status-มีผู้เช่า .badge-status { background-color: #d1fae5; color: #047857; border: 1px solid #a7f3d0; }
        .status-มีผู้เช่า { border-color: #a7f3d0; }
        .status-มีผู้เช่า .btn-room-info { color: #059669; }

        /* 🌟 Theme: ค้างชำระ (สีแดง) */
        .status-ค้างชำระ .room-number { background-color: #fef2f2; color: #991b1b; }
        .status-ค้างชำระ .badge-status { background-color: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
        .status-ค้างชำระ { border-color: #fecaca; box-shadow: 0 4px 6px rgba(220, 38, 38, 0.08); }
        .status-ค้างชำระ .btn-room-info { color: #dc2626; }

        .status-ซ่อมบำรุง .badge-status { background-color: #f2fad1; color: #000504; border: 1px solid #ffffff; }
    </style>

    <div class="container-fluid">
        {{-- 🌟 ครอบเนื้อหาทั้งหมดด้วย Wrapper เพื่อซ่อนไว้ก่อนแล้วค่อย Fade in 🌟 --}}
        <div class="page-content-wrapper" id="mainContentArea">

            {{-- Header: ชื่อตึก + ปุ่มย้อนกลับ + Filter --}}
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="d-flex align-items-center">
                    <a href="{{ route('admin.rooms.system') }}" class="btn btn-outline-secondary me-3 rounded-circle shadow-sm"
                        style="width: 45px; height: 45px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <div>
                        <h2 class="text-primary mb-0 fw-bold">{{ $currentBuilding->name }}</h2>
                        <small class="text-muted">กำลังแสดงผังห้องแยกตามชั้น</small>
                    </div>
                </div>

                <form action="{{ route('admin.rooms.system') }}" method="GET" class="d-flex gap-2">
                    <input type="hidden" name="building_id" value="{{ $currentBuilding->id }}">
                    <select name="status" class="form-select form-select-sm shadow-sm" style="width: 150px;"
                        onchange="this.form.submit()">
                        <option value="">สถานะทั้งหมด</option>
                        <option value="ว่าง" {{ request('status') == 'ว่าง' ? 'selected' : '' }}>ว่าง</option>
                        <option value="มีผู้เช่า" {{ request('status') == 'มีผู้เช่า' ? 'selected' : '' }}>มีผู้เช่า</option>
                        <option value="ซ่อมบำรุง" {{ request('status') == 'ซ่อมบำรุง' ? 'selected' : '' }}>ซ่อมบำรุง</option>
                    </select>
                    <input type="text" name="search" class="form-control form-select-sm shadow-sm" style="width: 150px;"
                        placeholder="เลขห้อง..." value="{{ request('search') }}">
                    <button type="submit" class="btn btn-primary btn-sm shadow-sm"><i class="fas fa-search"></i></button>
                </form>
            </div>

            {{-- Loop แยกชั้น --}}
            @forelse($roomsByFloor as $floorNum => $rooms)
                <div class="floor-section mb-5">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-dark text-white px-3 py-1 rounded me-3 fw-bold shadow-sm">
                            FL. {{ $floorNum }}
                        </div>
                        <div class="border-bottom flex-grow-1"></div>
                    </div>

                    <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-6 g-3">
                        @foreach ($rooms as $room)
                            <div class="col">
                                {{-- เรียกใช้ ui_status จาก Controller --}}
                                <div class="room-card shadow-sm status-{{ $room->ui_status }} position-relative">

                                    <button class="btn-room-info"
                                        onclick="showRoomInfo('{{ $room->room_number }}', '{{ $room->type_name }}', '{{ number_format($room->price) }}', '{{ $room->id }}' , '{{  e($room->remark) }}') ">
                                        <i class="fas fa-info-circle"></i>
                                    </button>

                                    <div class="room-number">{{ $room->room_number }}</div>

                                    <div class="room-body d-flex flex-column justify-content-center">
                                        {{-- ป้าย Badge สถานะ --}}
                                        <div>
                                            <span class="badge rounded-pill badge-status mb-2">
                                                @if ($room->ui_status == 'ว่าง')
                                                    <i class="bi bi-door-open-fill me-1"></i> ห้องว่าง
                                                @elseif ($room->ui_status == 'ซ่อมบำรุง')
                                                    <i class="bi bi-tools me-1"></i> ซ่อมบำรุง
                                                @elseif ($room->ui_status == 'ค้างชำระ')
                                                    <i class="bi bi-exclamation-triangle-fill me-1"></i> ค้างชำระ
                                                @else
                                                    <i class="bi bi-person-fill me-1"></i> มีผู้เช่า
                                                @endif
                                            </span>
                                        </div>

                                        {{-- ชื่อผู้เช่า หรือ ราคาห้อง --}}
                                        <div class="fw-bold text-dark text-truncate mb-1" style="max-width: 100%; font-size: 0.9rem;">
                                            @if ($room->current_tenant)
                                                {{ $room->current_tenant->first_name }} {{ $room->current_tenant->last_name }}
                                            @else
                                                <span class="text-muted fw-normal">{{ number_format($room->price) }} บ./เดือน</span>
                                            @endif
                                        </div>

                                        {{-- ข้อมูลเสริม (ยอดค้าง / สัญญา) --}}
                                        <div class="extra-info mt-auto">
                                            @if ($room->ui_status == 'ค้างชำระ')
                                                <div class="text-danger fw-bold small bg-white rounded px-1 py-1 d-inline-block border border-danger border-opacity-25 w-100">
                                                    ค้าง ฿{{ number_format($room->unpaid_amount) }}
                                                </div>
                                            @elseif ($room->ui_status == 'มีผู้เช่า' && $room->days_left !== null)
                                                @php 
                                                    $dayColorClass = $room->days_left <= 15 ? 'text-danger' : ($room->days_left <= 30 ? 'text-warning text-dark' : 'text-success');
                                                @endphp
                                                <div class="small fw-semibold {{ $dayColorClass }} d-flex flex-column align-items-center">
                                                    <span><i class="far fa-calendar-x me-1"></i>หมด: {{ $room->display_end_date }}</span>
                                                    <span style="font-size: 0.75rem; opacity: 0.85;">
                                                        @if($room->days_left < 0)
                                                            (เลยกำหนดมา {{ abs($room->days_left) }} วัน)
                                                        @else
                                                            (เหลืออีก {{ $room->days_left }} วัน)
                                                        @endif
                                                    </span>
                                                </div>
                                            @else
                                                <div class="small text-muted text-truncate text-center">{{ $room->remark ? Str::limit($room->remark, 15, '...') : $room->type_name }}</div>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="room-footer">
                                        @if ($room->status == 'ว่าง')
                                            <button type="button" class="btn btn-sm btn-success w-100 btn-action shadow-sm"
                                                onclick="openRegistrationTypeModal('{{ $room->id }}', '{{ $room->room_number }}')">
                                                <i class="fas fa-plus-circle"></i> จอง
                                            </button>
                                        @elseif ($room->current_tenant)
                                            <button class="btn btn-sm btn-primary w-100 btn-action shadow-sm"
                                                onclick="showTenant('{{ $room->room_number }}', '{{ $room->current_tenant->first_name }} {{ $room->current_tenant->last_name }}', '{{ $room->current_tenant->phone }}', '{{ \Carbon\Carbon::parse($room->current_tenant->start_date)->locale('th')->isoFormat('D MMM YY') }}', '{{ $room->current_tenant->id }}')">
                                                <i class="fas fa-user-circle"></i> ข้อมูลผู้เช่า
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-search fa-3x mb-3 text-secondary"></i>
                    <h4>ไม่พบข้อมูลห้องพัก</h4>
                </div>
            @endforelse

        </div> {{-- ปิด page-content-wrapper --}}

        {{-- ========================================================= --}}
        {{-- MODALS (ย้ายมาไว้ข้างนอก Wrapper เพื่อความเป็นระเบียบ)      --}}
        {{-- ========================================================= --}}

        {{-- 1. Modal แสดงข้อมูลผู้เช่า (Tenant Info) --}}
        <div class="modal fade" id="tenantModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title"><i class="fas fa-user"></i> ข้อมูลผู้เช่าห้อง <span id="t-room"
                                class="fw-bold"></span></h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center py-4">
                        <div class="avatar-circle mb-3 bg-light text-primary mx-auto d-flex align-items-center justify-content-center"
                            style="width: 80px; height: 80px; border-radius: 50%; font-size: 30px;">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <h4 id="t-name" class="fw-bold mb-1"></h4>
                        <p class="text-muted mb-3"><i class="fas fa-phone-alt"></i> <span id="t-phone"></span></p>

                        <div class="bg-light p-3 rounded mb-3 text-start">
                            <small class="text-muted d-block">วันที่เริ่มสัญญา:</small>
                            <span id="t-date" class="fw-bold text-dark"></span>
                        </div>

                        {{-- ปุ่ม Action เพิ่มเติม --}}
                        <div class="d-grid gap-2">
                            <a href="#" id="t-link-edit" class="btn btn-outline-primary">
                                <i class="fas fa-edit"></i> ดูข้อมูลเต็ม / แก้ไข / แจ้งย้ายออก
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. Modal แสดงสเปคห้อง (Room Specs) --}}
        <div class="modal fade" id="roomInfoModal" tabindex="-1">
            <div class="modal-dialog modal-sm modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-secondary text-white py-2">
                        <h6 class="modal-title"><i class="fas fa-info-circle"></i> รายละเอียดห้อง <span
                                id="r-room"></span>
                        </h6>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between px-0">
                                <span class="text-muted">ประเภท:</span>
                                <span id="r-type" class="fw-bold"></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0">
                                <span class="text-muted">ราคา:</span>
                                <span class="fw-bold text-success"><span id="r-price"></span> บ.</span>
                            </li>
                        </ul>
                        <div class="mb-4">
                            <label class="fw-bold text-dark small mb-2"><i class="bi bi-journal-text text-primary me-1"></i> หมายเหตุห้องพัก</label>
                            <div id="r-remark" class="p-3 bg-light rounded text-dark small lh-lg border" style="min-height: 40px;"></div>
                        </div>
                        <hr class="my-3">
                        <div class="d-grid">
                            <a href="#" id="btn-room-history" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-history"></i> ดูประวัติห้องพักทั้งหมด
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 3. Modal เลือกประเภทการลงทะเบียน (เพิ่มเอง / ดึงออนไลน์) --}}
        <div class="modal fade" id="registrationTypeModal" tabindex="-1">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4">
                    <div class="modal-header border-0 pb-0 px-4 pt-4">
                        <h5 class="modal-title fw-bold">เลือกลงทะเบียนเข้าพักห้อง <span id="reg_type_room_number" class="text-primary"></span></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            {{-- ตัวเลือก: เพิ่มเองด้วยมือ --}}
                            <div class="col-md-6">
                                <a href="#" id="btn_manual_create" class="text-decoration-none">
                                    <div class="card h-100 border-2 border-primary shadow-sm hover-card" style="transition: 0.2s;">
                                        <div class="card-body text-center py-5">
                                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                                <i class="fas fa-user-edit fs-1"></i>
                                            </div>
                                            <h5 class="fw-bold text-dark">เพิ่มผู้เช่าด้วยตัวเอง</h5>
                                            <p class="text-muted small mb-0">แอดมินพิมพ์กรอกข้อมูลผู้เช่า<br>และอัปโหลดสัญญาเช่าเองทั้งหมด</p>
                                        </div>
                                    </div>
                                </a>
                            </div>

                            {{-- ตัวเลือก: ดึงจากคนที่จองออนไลน์ --}}
                            <div class="col-md-6">
                                <div style="cursor: pointer;" onclick="openSelectOnlineModal()" class="h-100">
                                    <div class="card h-100 border-2 border-success shadow-sm hover-card" style="transition: 0.2s;">
                                        <div class="card-body text-center py-5">
                                            <div class="bg-success bg-opacity-10 text-success rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                                <i class="fas fa-globe fs-1"></i>
                                            </div>
                                            <h5 class="fw-bold text-dark">ดึงจากรายชื่อจองออนไลน์</h5>
                                            <p class="text-muted small mb-0">ดึงข้อมูลจากผู้เช่าที่ลงทะเบียนผ่านเว็บไซต์<br>เพื่อตรวจสอบและอนุมัติเข้าอยู่</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 4. Modal สำหรับเลือกคนจองออนไลน์ --}}
        <div class="modal fade" id="selectRegistrationModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title"><i class="fas fa-list-ul me-2"></i>เลือกคนที่จองออนไลน์ (ห้อง <span id="reg_room_number"></span>)</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-0">
                        <div class="list-group list-group-flush" id="registrationListBody">
                            <div class="text-center py-4"><div class="spinner-border text-success"></div></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        // 🌟 แก้ปัญหา FOUC (หน้าจอกระตุกตอนโหลด)
        document.addEventListener("DOMContentLoaded", function() {
            // 1. แสดง Content Wrapper หลัก
            const mainContent = document.getElementById('mainContentArea');
            if (mainContent) {
                setTimeout(() => {
                    mainContent.classList.add('content-loaded');
                }, 50); 
            }

            // 2. ทำ Staggered Fade-in ให้แต่ละชั้น (Floor Section) ค่อยๆ โผล่มา
            const floorSections = document.querySelectorAll('.floor-section');
            floorSections.forEach((section, index) => {
                setTimeout(() => {
                    section.classList.add('visible');
                }, 100 + (index * 100)); // ไล่เวลา Fade in ทีละชั้น
            });
        });

        // ตัวแปรโกลบอลสำหรับเก็บ ID ห้องชั่วคราว
        let currentSelectedRoomId = null;

        // ฟังก์ชันเปิด Modal ให้เลือกว่าจะเพิ่มมือ หรือดึงออนไลน์
        function openRegistrationTypeModal(roomId, roomNumber) {
            currentSelectedRoomId = roomId;
            
            document.getElementById('reg_type_room_number').innerText = roomNumber;
            document.getElementById('reg_room_number').innerText = roomNumber;

            let createUrl = "{{ route('admin.tenants.create') }}?room_id=" + roomId;
            document.getElementById('btn_manual_create').href = createUrl;

            new bootstrap.Modal(document.getElementById('registrationTypeModal')).show();
        }

        // ฟังก์ชันเปิด Modal รายชื่อจองออนไลน์
        function openSelectOnlineModal() {
            bootstrap.Modal.getInstance(document.getElementById('registrationTypeModal')).hide();
            
            let listBody = document.getElementById('registrationListBody');
            listBody.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-success"></div></div>';
            
            new bootstrap.Modal(document.getElementById('selectRegistrationModal')).show();

            fetch(`{{ url('admin/api/pending-registrations') }}`)
                .then(res => res.json())
                .then(data => {
                    if(data.length === 0) {
                        listBody.innerHTML = '<div class="text-center py-4 text-muted"><i class="fas fa-box-open fs-2 mb-2 d-block"></i>ไม่มีรายการจองออนไลน์ที่รออนุมัติ</div>';
                        return;
                    }
                    
                    let html = '';
                    data.forEach(reg => {
                        let reviewUrl = `{{ url('admin/tenants/review-registration') }}/${reg.id}?room_id=${currentSelectedRoomId}`;
                        html += `
                            <a href="${reviewUrl}" class="list-group-item list-group-item-action py-3 d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1 fw-bold">${reg.first_name} ${reg.last_name}</h6>
                                    <small class="text-muted"><i class="fas fa-phone-alt"></i> ${reg.phone}</small>
                                </div>
                                <span class="badge bg-success rounded-pill px-3 py-2 shadow-sm">เลือกคนนี้ <i class="fas fa-arrow-right ms-1"></i></span>
                            </a>
                        `;
                    });
                    listBody.innerHTML = html;
                });
        }

        // เปิด Modal แสดงข้อมูลผู้เช่าย่อๆ
        function showTenant(room, name, phone, date, id) {
            document.getElementById('t-room').innerText = room;
            document.getElementById('t-name').innerText = name;
            document.getElementById('t-phone').innerText = phone;
            document.getElementById('t-date').innerText = date;

            document.getElementById('t-link-edit').href = "{{ route('admin.tenants.show') }}?filter_room=" +
                encodeURIComponent(room) + "&filter_phone=" + encodeURIComponent(phone);
            new bootstrap.Modal(document.getElementById('tenantModal')).show();
        }

        // เปิด Modal แสดงข้อมูลห้อง
        function showRoomInfo(room, type, price, id , remark) {
            document.getElementById('r-room').innerText = room;
            document.getElementById('r-type').innerText = type;
            document.getElementById('r-price').innerText = price;

            const remarkDisplay = (remark && remark !== 'null') ? remark : '<span class="opacity-50">ไม่มีหมายเหตุสำหรับห้องนี้</span>';
            document.getElementById('r-remark').innerHTML = remarkDisplay;

            let historyUrl = "{{ route('admin.rooms.history', ':id') }}";
            historyUrl = historyUrl.replace(':id', id);

            document.getElementById('btn-room-history').href = historyUrl;

            new bootstrap.Modal(document.getElementById('roomInfoModal')).show();
        }
    </script>
@endpush