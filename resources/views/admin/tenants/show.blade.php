@extends('admin.layout')

@section('title', 'จัดการข้อมูลผู้เช่า')

@section('content')

    {{-- *************************************************************** --}}
    {{-- ส่วน CSS เฉพาะหน้า: ปรับแต่งตารางและ Modal ให้สวยงาม --}}
    {{-- *************************************************************** --}}
    <style>
        /* ปรับแต่งช่อง Auto Complete ของ jquery.Thailand */
        .tt-menu {
            background-color: #ffffff;
            border: 1px solid #ced4da;
            border-radius: 0.375rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            margin-top: 5px;
            padding: 5px 0;
            width: 100%;
            max-height: 200px;
            overflow-y: auto;
        }

        .tt-suggestion {
            padding: 8px 15px;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .tt-suggestion:hover {
            background-color: #f1f5f9;
            color: var(--primary-color);
        }
    </style>

    <div class="container-fluid py-4">

        {{-- Header ส่วนหัวหน้าจอ --}}
        <div class="row mb-4">
            <div class="col-12 d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="fw-bold text-dark mb-0">จัดการข้อมูลผู้เช่า</h3>
                    <p class="text-muted small">บันทึกประวัติ, สัญญาเช่า และตรวจสอบรายชื่อผู้พักอาศัย</p>
                </div>
                {{-- <button class="btn btn-primary px-4 shadow-sm rounded-pill" data-bs-toggle="modal"
                    data-bs-target="#insertTenantModal">
                    <i class="bi bi-person-plus-fill me-1"></i> เพิ่มผู้เช่าใหม่
                </button> --}}
            </div>
        </div>

        {{-- 🌟 1. Summary Cards (เพิ่มใหม่) --}}
        <div class="row g-3 mb-4">
            {{-- Card 1: ผู้เช่าปัจจุบัน --}}
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm rounded-4 border-start border-primary border-4 h-100">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 bg-primary bg-opacity-10 text-primary rounded-circle d-flex justify-content-center align-items-center" style="width: 50px; height: 50px;">
                                <i class="bi bi-door-open-fill fs-4"></i>
                            </div>
                            <div class="ms-3">
                                <p class="text-muted small mb-0 fw-bold">เช่าอยู่ปัจจุบัน</p>
                                <h4 class="fw-bolder mb-0 text-dark">{{ $statActive }} <span class="fs-6 text-muted fw-normal">ห้อง</span></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Card 2: จำนวนผู้พักอาศัยรวม --}}
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm rounded-4 border-start border-info border-4 h-100">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 bg-info bg-opacity-10 text-info rounded-circle d-flex justify-content-center align-items-center" style="width: 50px; height: 50px;">
                                <i class="bi bi-people-fill fs-4"></i>
                            </div>
                            <div class="ms-3">
                                <p class="text-muted small mb-0 fw-bold">ผู้อยู่อาศัยรวม</p>
                                <h4 class="fw-bolder mb-0 text-dark">{{ $statResidents }} <span class="fs-6 text-muted fw-normal">คน</span></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Card 3: ผูก LINE แล้ว --}}
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm rounded-4 border-start border-success border-4 h-100">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 bg-success bg-opacity-10 text-success rounded-circle d-flex justify-content-center align-items-center" style="width: 50px; height: 50px;">
                                <i class="bi bi-chat-dots-fill fs-4"></i>
                            </div>
                            <div class="ms-3">
                                <p class="text-muted small mb-0 fw-bold">ผูก LINE แจ้งเตือนแล้ว</p>
                                <h4 class="fw-bolder mb-0 text-dark">{{ $statLine }} <span class="fs-6 text-muted fw-normal">บัญชี</span></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Card 4: สิ้นสุดสัญญา --}}
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm rounded-4 border-start border-secondary border-4 h-100">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 bg-secondary bg-opacity-10 text-secondary rounded-circle d-flex justify-content-center align-items-center" style="width: 50px; height: 50px;">
                                <i class="bi bi-archive-fill fs-4"></i>
                            </div>
                            <div class="ms-3">
                                <p class="text-muted small mb-0 fw-bold">ประวัติผู้เช่าเก่า</p>
                                <h4 class="fw-bolder mb-0 text-dark">{{ $statTerminated }} <span class="fs-6 text-muted fw-normal">รายการ</span></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- *************************************************************** --}}
        {{-- ส่วน Filter: ค้นหาข้อมูล --}}
        {{-- *************************************************************** --}}
        {{-- ส่วน Filter: ค้นหาข้อมูล --}}
        <form method="GET" action="{{ route('admin.tenants.show') }}" id="filterTableForm">
            <input type="hidden" name="sort_by" id="sort_by" value="{{ request('sort_by', 'room_number') }}">
            <input type="hidden" name="sort_dir" id="sort_dir" value="{{ request('sort_dir', 'asc') }}">

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom-0 pt-3 pb-2 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-table text-primary me-2"></i>รายชื่อผู้เช่าและข้อมูลสัญญา</h6>
                    <a href="{{ route('admin.tenants.show') }}" class="btn btn-light btn-sm text-muted border shadow-sm px-3" title="ล้างการค้นหาและตัวกรองทั้งหมด">
                        <i class="bi bi-eraser-fill me-1"></i> ล้างค่า
                    </a>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="tenantTable">
                            <thead class="bg-light text-muted small">
                                <tr>
                                    {{-- 🌟 2. ทำให้คอลัมน์ ห้อง สามารถกดเรียงลำดับได้ --}}
                                    <th class="text-center text-primary hover-opacity" style="width: 8%; cursor: pointer; user-select: none;" onclick="toggleSort('room_number')">
                                        ห้อง
                                        @if (request('sort_by', 'room_number') == 'room_number')
                                            <i class="bi bi-sort-{{ request('sort_dir', 'asc') == 'asc' ? 'up' : 'down' }} fw-bold ms-1"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up text-muted opacity-50 ms-1"></i>
                                        @endif
                                    </th>

                                    <th class="text-center" style="width: 10%;">ประเภทตึก</th>
                                    <th style="width: 17%;">ชื่อ-นามสกุล</th>
                                    <th style="width: 10%;">เบอร์โทรศัพท์</th>
                                    <th class="text-center" style="width: 7%;">จำนวนคน</th>

                                    {{-- วันที่เริ่มเช่า --}}
                                    <th style="width: 11%; cursor: pointer; user-select: none;"
                                        onclick="toggleSort('start_date')" class="text-primary hover-opacity">
                                        วันที่เริ่มเช่า
                                        @if (request('sort_by') == 'start_date')
                                            <i class="bi bi-sort-{{ request('sort_dir') == 'asc' ? 'up' : 'down' }} fw-bold ms-1"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up text-muted opacity-50 ms-1"></i>
                                        @endif
                                    </th>

                                    {{-- วันที่สิ้นสุดสัญญา --}}
                                    <th style="width: 12%; cursor: pointer; user-select: none;"
                                        onclick="toggleSort('end_date')" class="text-primary hover-opacity">
                                        วันที่สิ้นสุดสัญญา
                                        @if (request('sort_by') == 'end_date')
                                            <i class="bi bi-sort-{{ request('sort_dir') == 'asc' ? 'up' : 'down' }} fw-bold ms-1"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up text-muted opacity-50 ms-1"></i>
                                        @endif
                                    </th>

                                    <th class="text-center" style="width: 9%;">ที่จอดรถ</th>
                                    <th class="text-center" style="width: 8%;">สถานะ</th>
                                    <th class="text-center px-2" style="width: 8%;">จัดการ</th>
                                </tr>

                                {{-- แถวสำหรับ Inline Filter --}}
                                <tr class="bg-white border-bottom shadow-sm">
                                    <td class="px-2 py-2">
                                        <input type="text" name="filter_room" class="form-control form-control-sm bg-light border-0 text-center" placeholder="ค้นหาห้อง" value="{{ request('filter_room') }}" onkeydown="if(event.key === 'Enter') this.form.submit();">
                                    </td>
                                    <td class="px-2 py-2">
                                        <select name="filter_building" class="form-select form-select-sm bg-light border-0 text-center text-muted" onchange="this.form.submit()">
                                            <option value="">ทุกตึก</option>
                                            @foreach ($buildings as $b)
                                                <option value="{{ $b->id }}" {{ request('filter_building') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="py-2 px-1">
                                        <input type="text" name="filter_name" class="form-control form-control-sm bg-light border-0" placeholder="ชื่อ-นามสกุล" value="{{ request('filter_name') }}" onkeydown="if(event.key === 'Enter') this.form.submit();">
                                    </td>
                                    <td class="py-2 px-1">
                                        <input type="text" name="filter_phone" class="form-control form-control-sm bg-light border-0" placeholder="เบอร์โทร" value="{{ request('filter_phone') }}" onkeydown="if(event.key === 'Enter') this.form.submit();">
                                    </td>
                                    <td class="py-2 px-1">
                                        <input type="number" name="filter_resident_count" class="form-control form-control-sm bg-light border-0 text-center" placeholder="คน" min="1" value="{{ request('filter_resident_count') }}" onkeydown="if(event.key === 'Enter') this.form.submit();">
                                    </td>
                                    <td class="py-2 px-1">
                                        <input type="date" name="filter_start_date" style="min-width: 110px;" class="form-control form-control-sm bg-light border-0 text-muted px-1" value="{{ request('filter_start_date') }}" onchange="this.form.submit()">
                                    </td>
                                    <td class="py-2 px-1">
                                        <input type="date" name="filter_end_date" style="min-width: 110px;" class="form-control form-control-sm bg-light border-0 text-muted px-1" value="{{ request('filter_end_date') }}" onchange="this.form.submit()">
                                    </td>
                                    <td class="py-2 text-center px-1">
                                        <select name="filter_parking" class="form-select form-select-sm bg-light border-0 fw-bold text-center px-1" onchange="this.form.submit()">
                                            <option value="">ทั้งหมด</option>
                                            <option value="1" class="text-success" {{ request('filter_parking') === '1' ? 'selected' : '' }}>มี</option>
                                            <option value="0" class="text-muted" {{ request('filter_parking') === '0' ? 'selected' : '' }}>ไม่มี</option>
                                        </select>
                                    </td>
                                    <td class="py-2 px-1">
                                        <select name="filter_status" class="form-select form-select-sm bg-light border-0 fw-bold text-center  px-1" onchange="this.form.submit()">
                                            <option value="">ทั้งหมด</option>
                                            <option value="กำลังใช้งาน" class="text-success" {{ request('filter_status') == 'กำลังใช้งาน' ? 'selected' : '' }}>ใช้งาน</option>
                                            <option value="สิ้นสุดสัญญา" class="text-secondary" {{ request('filter_status') == 'สิ้นสุดสัญญา' ? 'selected' : '' }}>สิ้นสุด</option>
                                        </select>
                                    </td>
                                    <td class="py-2 text-center px-2">
                                        <button type="submit" class="d-none">Search</button>
                                    </td>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($tenants as $item)
                                    @php
                                        $today = \Carbon\Carbon::now()->startOfDay(); 
                                        $daysLeft = $item->end_date ? (int) $today->diffInDays($item->end_date, false) : null;
                                        $isExpiringSoon = $daysLeft !== null && $daysLeft <= 30 && $daysLeft > 0 && $item->status === 'กำลังใช้งาน';
                                        $isExpired = $daysLeft !== null && $daysLeft <= 0 && $item->status === 'กำลังใช้งาน';
                                        $isTerminated = $item->status === 'สิ้นสุดสัญญา';

                                        $rowBg = '';
                                        if ($isTerminated) {
                                            $rowBg = 'table-secondary text-muted';
                                        } elseif ($isExpired) {
                                            $rowBg = 'table-danger';
                                        } elseif ($isExpiringSoon) {
                                            $rowBg = 'table-warning';
                                        }
                                    @endphp
                                    <tr class="{{ $rowBg }}">

                                        {{-- ห้อง --}}
                                        <td class="text-center px-3">
                                            <div class="fs-5 fw-bold {{ $isTerminated ? 'text-secondary' : 'text-primary' }} mb-0">
                                                {{ $item->room->room_number ?? 'ไม่ระบุ' }}
                                            </div>
                                        </td>

                                        {{-- ตึก --}}
                                        <td class="text-center small fw-bold {{ $isTerminated ? 'text-secondary' : 'text-dark' }}">
                                            {{ $item->room->roomPrice->building->name ?? '-' }}
                                        </td>

                                        {{-- 🌟 3. ไอคอน LINE (เพิ่มใหม่) ท้ายชื่อ --}}
                                        <td class="fw-semibold">
                                            {{ $item->first_name }} {{ $item->last_name }}
                                            @if (!empty($item->line_id))
                                                <span class="badge bg-success ms-1 px-2 py-1" title="ผู้เช่าเชื่อมต่อบัญชี LINE แล้ว" style="font-size: 0.65rem;">
                                                    <i class="bi bi-chat-dots-fill"></i> LINE
                                                </span>
                                            @endif
                                        </td>
                                        
                                        <td>{{ $item->phone }}</td>
                                        <td class="text-center fw-bold">{{ $item->resident_count }} <span class="small fw-normal">คน</span></td>
                                        <td class="small">{{ $item->thai_start_date }}</td>

                                        {{-- วันที่สิ้นสุดสัญญา --}}
                                        <td class="small">
                                            @if ($item->end_date)
                                                <div class="fw-semibold">{{ $item->thai_end_date }}</div>
                                                @if ($item->status === 'กำลังใช้งาน')
                                                    @if ($isExpired)
                                                        <span class="badge bg-danger mt-1">หมดสัญญาแล้ว!</span>
                                                    @elseif($daysLeft !== null && $daysLeft <= 30)
                                                        <span class="badge bg-warning text-dark mt-1">เหลืออีก {{ $daysLeft }} วัน</span>
                                                    @endif
                                                @endif
                                            @else
                                                <span>- ไม่ระบุ -</span>
                                            @endif
                                        </td>

                                        <td class="text-center">
                                            <i class="bi {{ $item->has_parking ? 'bi-check-circle-fill text-success fs-5' : 'bi-dash' }} {{ $isTerminated ? 'opacity-50' : '' }}"></i>
                                        </td>

                                        <td>
                                            @if ($item->status === 'กำลังใช้งาน')
                                                <span class="badge bg-success bg-opacity-10 text-success border border-success px-3 py-2 rounded-pill">กำลังใช้งาน</span>
                                            @else
                                                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary px-3 py-2 rounded-pill">สิ้นสุดสัญญา</span>
                                            @endif
                                        </td>

                                        <td class="text-center px-2">
                                            <div class="d-flex gap-1 justify-content-center">
                                                <a href="{{ route('admin.tenants.detail', $item->id) }}" class="btn btn-info btn-sm px-2" title="ดูรายละเอียด">
                                                    <i class="bi bi-eye-fill"></i>
                                                </a>

                                                @if ($item->status == 'กำลังใช้งาน')
                                                    <button type="button" class="btn btn-warning btn-sm px-2" title="แก้ไข" onclick='openEditModal(@json($item))'>
                                                        <i class="bi bi-pencil-square"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-dark btn-sm px-2" title="ย้ายออก" onclick="TerminateForm({{ json_encode($item) }})">
                                                        <i class="bi bi-door-closed"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        @if ($tenants->isEmpty())
                            <div class="text-center py-5 text-muted">
                                <i class="bi bi-search fs-1"></i>
                                <p class="mt-2">ไม่พบข้อมูลที่ค้นหา</p>
                            </div>
                        @endif
                        <div class="p-3 bg-white border-top">{{ $tenants->links() }}</div>
                    </div>
                </div>
            </div>
        </form>
    </div>
    </div>
    </form>
    </div>

    {{-- *************************************************************** --}}
    {{-- MODAL: แก้ไขข้อมูลผู้เช่า (EDIT) --}}
    {{-- *************************************************************** --}}
    <div class="modal fade" id="editTenantModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title fw-bold text-dark"><i class="bi bi-pencil-square me-2"></i>แก้ไขข้อมูลผู้เช่า</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <form id="editTenantForm" method="POST" enctype="multipart/form-data">
                    @csrf @method('PUT')
                    <div class="modal-body p-4 bg-light bg-opacity-25">

                        {{-- Section 1: ข้อมูลส่วนตัว --}}
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-body">
                                <h6 class="fw-bold text-warning mb-3 border-bottom pb-2">1. ข้อมูลส่วนบุคคล</h6>
                                <div class="row g-3">
                                    <div class="col-md-5">
                                        <label class="form-label fw-bold">ชื่อ <span class="text-danger">*</span></label>
                                        <input type="text" name="first_name" id="edit_first_name" class="form-control" required>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label fw-bold">นามสกุล <span class="text-danger">*</span></label>
                                        <input type="text" name="last_name" id="edit_last_name" class="form-control" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-bold">อายุ <span class="text-danger">*</span></label>
                                        <input type="number" name="age" id="edit_age" class="form-control" min="1" required>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">เลขบัตรประชาชน <span class="text-danger">*</span></label>
                                        <input type="text" name="id_card" id="edit_id_card" class="form-control input-idcard" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">เบอร์โทรศัพท์ <span class="text-danger">*</span></label>
                                        <input type="text" name="phone" id="edit_phone" class="form-control input-phone" required>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">ออกบัตรเมื่อวันที่</label>
                                        <input type="date" name="id_card_issue_date" id="edit_id_card_issue_date" class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">บัตรหมดอายุวันที่</label>
                                        <input type="date" name="id_card_expiry_date" id="edit_id_card_expiry_date" class="form-control">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">สถานที่ออกบัตร (ณ)</label>
                                        <input type="text" name="id_card_issue_place" id="edit_id_card_issue_place" class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">จังหวัดที่ออกบัตร</label>
                                        <select name="id_card_issue_province" id="edit_id_card_issue_province" class="form-select">
                                            <option value="">กำลังโหลด...</option>
                                        </select>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label fw-bold">สถานที่ทำงาน</label>
                                        <input type="text" name="workplace" id="edit_workplace" class="form-control">
                                    </div>

                                    <div class="col-md-6 mt-4">
                                        <label class="form-label fw-bold">จำนวนผู้อยู่อาศัย <span class="text-danger">*</span></label>
                                        <input type="number" name="resident_count" id="edit_resident_count" class="form-control" min="1" required>
                                    </div>
                                    <div class="col-md-6 mt-4">
                                        <label class="form-label fw-bold text-muted">รหัสผ่านระบบ <small>(เว้นว่างถ้าไม่เปลี่ยน)</small></label>
                                        <input type="text" name="password" class="form-control bg-light" placeholder="ระบุเพื่อเปลี่ยนรหัสผ่านใหม่">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Section 2: ที่อยู่ (Cascading Select) --}}
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-body">
                                <h6 class="fw-bold text-warning mb-3 border-bottom pb-2">2. ที่อยู่ตามทะเบียนบ้าน</h6>
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">เลขที่ <span class="text-danger">*</span></label>
                                        <input type="text" name="address_no" id="edit_address_no" class="form-control" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">หมู่ที่ <span class="text-danger">*</span></label>
                                        <input type="text" name="moo" id="edit_moo" class="form-control" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">ตรอก/ซอย</label>
                                        <input type="text" name="alley" id="edit_alley" class="form-control">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">ถนน</label>
                                        <input type="text" name="street" id="edit_street" class="form-control">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">จังหวัด <span class="text-danger">*</span></label>
                                        <select name="province" id="edit_province" class="form-select" required>
                                            <option value="">กำลังโหลด...</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">อำเภอ/เขต <span class="text-danger">*</span></label>
                                        <select name="district" id="edit_district" class="form-select" disabled required>
                                            <option value="">-- เลือกจังหวัดก่อน --</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">ตำบล/แขวง <span class="text-danger">*</span></label>
                                        <select name="sub_district" id="edit_sub_district" class="form-select" disabled required>
                                            <option value="">-- เลือกอำเภอก่อน --</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">รหัสไปรษณีย์ <span class="text-danger">*</span></label>
                                        <input type="text" name="postal_code" id="edit_postal_code" class="form-control bg-light" readonly required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Section 3: สัญญาและไฟล์แนบ --}}
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <h6 class="fw-bold text-warning mb-3 border-bottom pb-2">3. สัญญาและค่าใช้จ่าย</h6>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">วันที่เริ่มเช่า <span class="text-danger">*</span></label>
                                        <input type="date" name="start_date" id="edit_start_date" class="form-control" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">วันที่สิ้นสุด (ถ้ามี)</label>
                                        <input type="date" name="end_date" id="edit_end_date" class="form-control">
                                    </div>

                                    {{-- ส่วนจัดการเงินมัดจำ --}}
                                    <div class="col-12 mt-4">
                                        <h6 class="fw-bold text-dark mb-2"><i class="bi bi-cash-coin me-2 text-warning"></i>การชำระเงินมัดจำ</h6>
                                        <div id="deposit_warning_msg" class="alert alert-info small py-2 d-none">
                                            <i class="bi bi-info-circle-fill me-1"></i> รายการมัดจำเดิมสมบูรณ์แล้ว ไม่สามารถแก้ไขได้
                                        </div>
                                    </div>

                                    <div class="col-md-3 deposit-field">
                                            <label class="form-label fw-bold">วันที่รับเงินมัดจำ <span class="text-danger">*</span></label>
                                            <input type="date" name="deposit_date" id="edit_deposit_date" class="form-control" required>
                                        </div>

                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">เงินมัดจำ (บาท) <span class="text-danger">*</span></label>
                                        <input type="number" name="deposit_amount" id="edit_deposit_amount" class="form-control fw-bold text-success" min="1" required>
                                    </div>
                                    <div class="col-md-3 deposit-field">
                                        <label class="form-label fw-bold">ช่องทางชำระมัดจำ <span class="text-danger">*</span></label>
                                        <select name="deposit_payment_method" id="edit_deposit_payment_method" class="form-select">
                                            <option value="เงินสด">เงินสด</option>
                                            <option value="โอนผ่านธนาคาร">โอนผ่านธนาคาร</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 deposit-field">
                                        <label class="form-label fw-bold">แนบสลิปมัดจำ (ถ้ามี)</label>
                                        <input type="file" name="deposit_slip" id="edit_deposit_slip" class="form-control" accept="image/*">
                                    </div>

                                    {{-- Service Card (ที่จอดรถ) --}}
                                    <div class="col-12 pt-3">
                                        <label class="w-100" style="cursor: pointer;" for="edit_has_parking">
                                            <div class="card border-2 shadow-sm" id="editParkingCardUI" style="transition: all 0.2s ease;">
                                                <div class="card-body d-flex align-items-center p-3">
                                                    <div class="bg-warning bg-opacity-10 text-warning rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0" style="width: 50px; height: 50px;">
                                                        <i class="bi bi-car-front-fill fs-4"></i>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <h6 class="fw-bold mb-1 text-dark">บริการที่จอดรถส่วนตัว</h6>
                                                        <div class="text-muted small">เพิ่มสิทธิการจอดรถ (ระบบจะเพิ่มค่าบริการในบิลรายเดือน)</div>
                                                    </div>
                                                    <div class="form-check form-switch pe-2 mb-0 ms-3 flex-shrink-0">
                                                        <input class="form-check-input m-0" type="checkbox" name="has_parking" value="1" id="edit_has_parking" style="transform: scale(1.6); cursor: pointer;"
                                                            onchange="
                                                            let card = document.getElementById('editParkingCardUI');
                                                            if(this.checked){
                                                                card.classList.add('border-warning', 'bg-warning', 'bg-opacity-10');
                                                            } else {
                                                                card.classList.remove('border-warning', 'bg-warning', 'bg-opacity-10');
                                                            }
                                                        ">
                                                    </div>
                                                </div>
                                            </div>
                                        </label>
                                    </div>

                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer border-0 bg-white">
                        <span class="text-muted small me-auto"><i class="bi bi-info-circle me-1"></i> ระบบจะสร้างไฟล์ PDF สัญญาเช่าใหม่อัตโนมัติเมื่อกดบันทึก</span>
                        <button type="button" class="btn btn-light px-4 rounded-3" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="button" onclick="confirmUpdate()" class="btn btn-warning px-4 rounded-3 shadow-sm fw-bold">
                            <i class="bi bi-save me-1"></i> บันทึกการแก้ไข
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- *************************************************************** --}}
    {{-- MODAL: แจ้งย้ายออก (TERMINATE) --}}
    {{-- *************************************************************** --}}
    <div class="modal fade" id="terminateTenantModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-door-open me-2"></i>แจ้งย้ายออก / สิ้นสุดสัญญา</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="terminateForm" method="POST">
                    @csrf @method('PUT')
                    <div class="modal-body p-4">
                        <div class="alert alert-warning small">
                            <i class="bi bi-exclamation-circle-fill me-1"></i> เมื่อยืนยัน
                            สถานะห้องจะว่างทันทีและผู้เช่าเดิมจะเข้าสู่ระบบไม่ได้
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">ห้องพัก</label>
                            <input type="text" id="term_room_number" class="form-control bg-light" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">วันที่ย้ายออก</label>
                            <input type="date" name="end_date" id="term_end_date" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">คืนเงินมัดจำ (บาท)</label>
                            <input type="number" name="refund_amount" id="term_refund_amount" class="form-control"
                                min="0" step="0.01">
                            <small class="text-muted d-block mt-1">* กรอก 0 หากไม่คืนหรือริบมัดจำ</small>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="button" onclick="confirmTerminate()" class="btn btn-dark px-4">ยืนยัน</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- delete ฟอร์มลบส่วนกลาง (Global Delete Form) --}}
    <form id="globalDeleteForm" method="POST" class="d-none">
        @csrf
    </form>

    {{-- *************************************************************** --}}
    {{-- MODAL: ดูสัญญาเช่า (CONTRACT PREVIEW) --}}
    {{-- *************************************************************** --}}
    {{-- <div class="modal fade" id="contractPreviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white py-2">
                    <h6 class="modal-title fw-bold mb-0"><i class="bi bi-file-earmark-text me-2"></i>สัญญาเช่า</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center p-0" id="contractPreviewBody"
                    style="min-height: 500px; background: #f8f9fa;">
                    <div class="d-flex justify-content-center align-items-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                    </div>
                </div>
                <div class="modal-footer border-0 py-2">
                    <a href="#" id="contractOpenNewTab" class="btn btn-outline-primary btn-sm" target="_blank">
                        <i class="bi bi-box-arrow-up-right me-1"></i>เปิดในแท็บใหม่
                    </a>
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">ปิด</button>
                </div>
            </div>
        </div>
    </div> --}}

@endsection

{{-- *************************************************************** --}}
{{-- ส่วน Scripts: จัดการ Logic หน้าบ้าน --}}
{{-- *************************************************************** --}}
@push('scripts')
    {{-- 1. Libraries (ไม่ใช้ jquery.Thailand.js แล้ว — ใช้ cascading select แทน) --}}
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/cleave.js@1.6.0/dist/cleave.min.js"></script>

    <script>
        // ********************************************
        // Global: Thai Address Data + Helper Functions
        // ********************************************
        let thaiDB = null;

        async function loadThaiDB() {
            if (thaiDB) return thaiDB;
            try {
                const res = await fetch(
                    '{{ asset('thai_address.json') }}'
                );
                thaiDB = await res.json();
                return thaiDB;
            } catch (e) {
                console.error('โหลดข้อมูลที่อยู่ไม่สำเร็จ:', e);
                return [];
            }
        }
        // ฟังก์ชันจัดการการจัดเรียง (Sort) เมื่อคลิกที่หัวตาราง
        function toggleSort(column) {
            let sortByKey = document.getElementById('sort_by').value;
            let sortDirKey = document.getElementById('sort_dir').value;

            // ถ้ากดซ้ำคอลัมน์เดิม ให้สลับ asc / desc
            if (sortByKey === column) {
                document.getElementById('sort_dir').value = (sortDirKey === 'asc') ? 'desc' : 'asc';
            } else {
                // ถ้ากดคอลัมน์ใหม่ ให้เริ่มด้วย asc ก่อน
                document.getElementById('sort_by').value = column;
                document.getElementById('sort_dir').value = 'asc';
            }

            // สั่งให้ฟอร์มทำงานเพื่อโหลดข้อมูลใหม่
            document.getElementById('filterTableForm').submit();
        }

        function setupCascading(prefix) {
            const prov = $('#' + prefix + '_province');
            const dist = $('#' + prefix + '_district');
            const sub = $('#' + prefix + '_sub_district');
            const zip = $('#' + prefix + '_postal_code');

            // เมื่อเปลี่ยน จังหวัด
            prov.on('change', function() {
                const val = $(this).val();
                
                // ล้างค่าเก่าทิ้ง และล็อคช่องไว้ก่อน
                dist.empty().append('<option value="">-- เลือกอำเภอ --</option>').prop('disabled', true);
                sub.empty().append('<option value="">-- เลือกตำบล --</option>').prop('disabled', true);
                zip.val('');

                if (!val) return;

                // ดึงข้อมูลอำเภอใหม่ และปลดล็อคช่อง
                const amphoes = [...new Set(thaiDB.filter(r => r.province === val).map(r => r.amphoe))].sort();
                amphoes.forEach(a => dist.append(new Option(a, a)));
                dist.prop('disabled', false);
            });

            // เมื่อเปลี่ยน อำเภอ
            dist.on('change', function() {
                const provVal = prov.val();
                const val = $(this).val();
                
                // ล้างค่าตำบลเก่าทิ้ง และล็อคช่องไว้ก่อน
                sub.empty().append('<option value="">-- เลือกตำบล --</option>').prop('disabled', true);
                zip.val('');

                if (!val) return;

                // ดึงข้อมูลตำบลใหม่ และปลดล็อคช่อง
                const districts = [...new Set(thaiDB.filter(r => r.province === provVal && r.amphoe === val).map(r => r.district))].sort();
                districts.forEach(d => sub.append(new Option(d, d)));
                sub.prop('disabled', false);
            });

            // เมื่อเปลี่ยน ตำบล
            sub.on('change', function() {
                const provVal = prov.val();
                const distVal = dist.val();
                const val = $(this).val();
                
                zip.val('');
                if (!val) return;

                // ค้นหารหัสไปรษณีย์และเติมให้อัตโนมัติ
                const match = thaiDB.find(r => r.province === provVal && r.amphoe === distVal && r.district === val);
                if (match) zip.val(match.zipcode);
            });
        }

        async function populateEditAddress(province, district, subDistrict, postalCode) {
            const db = await loadThaiDB();
            if (!db || !db.length) return;

            const prov = $('#edit_province');
            const dist = $('#edit_district');
            const sub = $('#edit_sub_district');
            const zip = $('#edit_postal_code');

            // 1. เซ็ตค่า จังหวัด
            prov.val(province || '');
            
            // 2. โหลดรายการ อำเภอ และเซ็ตค่า
            dist.empty().append('<option value="">-- เลือกอำเภอ --</option>');
            if (province) {
                const amphoes = [...new Set(db.filter(r => r.province === province).map(r => r.amphoe))].sort();
                amphoes.forEach(a => dist.append(new Option(a, a)));
                dist.prop('disabled', false);
                dist.val(district || '');
            } else {
                dist.prop('disabled', true);
            }

            // 3. โหลดรายการ ตำบล และเซ็ตค่า
            sub.empty().append('<option value="">-- เลือกตำบล --</option>');
            if (province && district) {
                const districts = [...new Set(db.filter(r => r.province === province && r.amphoe === district).map(r => r.district))].sort();
                districts.forEach(d => sub.append(new Option(d, d)));
                sub.prop('disabled', false);
                sub.val(subDistrict || '');
            } else {
                sub.prop('disabled', true);
            }

            // 4. เซ็ตค่า รหัสไปรษณีย์
            zip.val(postalCode || '');
        }
        
        function previewContract(url, ext) {
            const body = document.getElementById('contractPreviewBody');
            const link = document.getElementById('contractOpenNewTab');
            link.href = url;

            if (ext === 'pdf') {
                body.innerHTML = '<iframe src="' + url + '" style="width:100%; height:600px; border:none;"></iframe>';
            } else if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)) {
                body.innerHTML = '<img src="' + url + '" class="img-fluid p-3" style="max-height:600px;" alt="สัญญาเช่า">';
            } else {
                body.innerHTML =
                    '<div class="py-5 text-muted"><i class="bi bi-file-earmark fs-1"></i><p class="mt-2">ไม่สามารถแสดงตัวอย่างได้ กรุณาเปิดในแท็บใหม่</p></div>';
            }

            new bootstrap.Modal(document.getElementById('contractPreviewModal')).show();
        }

        $(document).ready(function() {

            // ********************************************
            // 2. Cascading Address Selects
            // ********************************************
            loadThaiDB().then(db => {
                if (!db || !db.length) return;
                const provinces = [...new Set(db.map(r => r.province))].sort();

                ['add', 'edit'].forEach(prefix => {
                    const sel = document.getElementById(prefix + '_province');
                    if(sel){
                        sel.innerHTML = '<option value="">-- เลือกจังหวัด --</option>';
                        provinces.forEach(p => sel.add(new Option(p, p)));
                    }
                });

                // 🌟 โหลดจังหวัดสำหรับช่อง จังหวัดที่ออกบัตร ใน Modal Edit
                const editIssueProv = document.getElementById('edit_id_card_issue_province');
                if (editIssueProv) {
                    editIssueProv.innerHTML = '<option value="">-- เลือกจังหวัด --</option>';
                    provinces.forEach(p => editIssueProv.add(new Option(p, p)));
                }

                setupCascading('add');
                setupCascading('edit');
            });

            // ********************************************
            // 3. Setup Input Masks (Cleave.js)
            // ********************************************

            // ********************************************
            // 3.0 Expand/Collapse Detail Row
            // ********************************************
            document.querySelectorAll('.toggle-detail').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    const targetId = this.dataset.target;
                    const row = document.getElementById(targetId);
                    const icon = this.querySelector('i');
                    if (row.classList.contains('d-none')) {
                        row.classList.remove('d-none');
                        icon.classList.remove('bi-chevron-right');
                        icon.classList.add('bi-chevron-down');
                    } else {
                        row.classList.add('d-none');
                        icon.classList.remove('bi-chevron-down');
                        icon.classList.add('bi-chevron-right');
                    }
                });
            });

            function applyMasks() {
                // Mask เลขบัตรประชาชน: 1-2345-67890-12-3
                document.querySelectorAll('.input-idcard').forEach(function(el) {
                    new Cleave(el, {
                        blocks: [1, 4, 5, 2, 1],
                        delimiter: '-',
                        numericOnly: true
                    });
                });

                // Mask เบอร์โทร: 081-234-5678
                document.querySelectorAll('.input-phone').forEach(function(el) {
                    new Cleave(el, {
                        blocks: [3, 3, 4],
                        delimiter: '-',
                        numericOnly: true
                    });
                });
            }
            applyMasks(); // เรียกทำงานทันที

            // ********************************************
            // 4. Clean Data Before Submit (ลบขีดออก)
            // ********************************************

            // Insert Form
            $('#addTenantForm').on('submit', function() {
                let id = $(this).find('input[name="id_card"]');
                let ph = $(this).find('input[name="phone"]');
                id.val(id.val().replace(/-/g, ''));
                ph.val(ph.val().replace(/-/g, ''));
            });

            // Edit Form (ถูกเรียกผ่าน confirmUpdate อีกที)
        });

        // ********************************************
        // 5. Functions จัดการ Modal
        // ********************************************

        // 🌟 1. ฟังก์ชันจัดรูปแบบวันที่: แก้บัควันถอยหลัง 1 วัน (Timezone Fix)
        function normalizeDateForInput(dateValue) {
            if (!dateValue) return '';

            // กรณีส่งมาเป็นสตริง (เช่น "2026-04-01 00:00:00" หรือ ISO "2026-04-01T00:00:00.000Z")
            if (typeof dateValue === 'string') {
                // ตัดเอาแค่ 10 หลักแรก (YYYY-MM-DD) โดยไม่ผ่าน Date Object
                return dateValue.substring(0, 10);
            }

            // กรณีเป็น Object ของ Laravel { date: "2026-04-01 ..." }
            if (dateValue && dateValue.date) {
                return dateValue.date.substring(0, 10);
            }

            return '';
        }

        function openEditModal(tenant) {
            // 1. เติมข้อมูลพื้นฐาน และ ข้อมูลที่เพิ่มใหม่
            $('#edit_first_name').val(tenant.first_name);
            $('#edit_last_name').val(tenant.last_name);
            $('#edit_age').val(tenant.age); // 🌟 ดึงอายุ
            $('#edit_id_card').val(tenant.id_card); 
            $('#edit_phone').val(tenant.phone);
            $('#edit_resident_count').val(tenant.resident_count);

            // 🌟 ดึงข้อมูลบัตรประชาชนและสถานที่ทำงาน
            $('#edit_id_card_issue_date').val(normalizeDateForInput(tenant.id_card_issue_date));
            $('#edit_id_card_expiry_date').val(normalizeDateForInput(tenant.id_card_expiry_date));
            $('#edit_id_card_issue_place').val(tenant.id_card_issue_place);
            $('#edit_id_card_issue_province').val(tenant.id_card_issue_province);
            $('#edit_workplace').val(tenant.workplace);

            // 2. เติมที่อยู่ (cascading selects)
            $('#edit_address_no').val(tenant.address_no || '');
            $('#edit_moo').val(tenant.moo || '');
            $('#edit_alley').val(tenant.alley || ''); // 🌟 ดึงตรอก/ซอย
            $('#edit_street').val(tenant.street || ''); // 🌟 ดึงถนน
            populateEditAddress(tenant.province, tenant.district, tenant.sub_district, tenant.postal_code);

            // 3. เติมสัญญา
            $('#edit_deposit_amount').val(tenant.deposit_amount || 0);
            $('#edit_deposit_payment_method').val(tenant.deposit_payment_method || 'เงินสด');
            
            let formatStartDate = tenant.start_date ? tenant.start_date.substring(0, 10) : '';
            let formatEndDate = tenant.end_date ? tenant.end_date.substring(0, 10) : '';

            $('#edit_start_date').val(formatStartDate);
            $('#edit_end_date').val(formatEndDate);
            $('#edit_deposit_date').val('');

            // 4. จัดการ UI ของ "ที่จอดรถ"
            let parkingCheckbox = document.getElementById('edit_has_parking');
            let parkingCard = document.getElementById('editParkingCardUI');
            if (tenant.has_parking == 1 || tenant.has_parking === true) {
                parkingCheckbox.checked = true;
                parkingCard.classList.add('border-warning', 'bg-warning', 'bg-opacity-10');
            } else {
                parkingCheckbox.checked = false;
                parkingCard.classList.remove('border-warning', 'bg-warning', 'bg-opacity-10');
            }

            // 5. ตั้ง Action URL
            let url = "{{ route('admin.tenants.update', ':id') }}";
            url = url.replace(':id', tenant.id);
            $('#editTenantForm').attr('action', url);

            // 6. ตรวจสอบสถานะการชำระเงินมัดจำ
            // เรียกเช็คสถานะมัดจำจาก Server
            fetch(`{{ url('admin/tenants/check-deposit-status') }}/${tenant.id}`)
                .then(res => res.json())
                .then(res => {
                    let dateInput = $('#edit_deposit_date');
                    let amountInput = $('#edit_deposit_amount');
                    let methodInput = $('#edit_deposit_payment_method');
                    let slipInput = $('#edit_deposit_slip');
                    let warningMsg = $('#deposit_warning_msg');

                    if (res.has_active_deposit) {
                        // ✅ ใช้ค่าที่แก้บัคแล้ว (res.deposit_date)
                        dateInput.val(normalizeDateForInput(res.deposit_date));
                        
                        dateInput.prop('readonly', true).addClass('bg-light');
                        amountInput.prop('readonly', true).addClass('bg-light');
                        methodInput.prop('disabled', true);
                        slipInput.prop('disabled', true);
                        warningMsg.removeClass('d-none');
                    } else {
                        // ✅ กรณีไม่มีมัดจำเดิม ใช้วันที่เริ่มเช่าเป็นค่าเริ่มต้น (Normalized)
                        dateInput.val(normalizeDateForInput(tenant.start_date));
                        
                        dateInput.prop('readonly', false).removeClass('bg-light');
                        amountInput.prop('readonly', false).removeClass('bg-light');
                        methodInput.prop('disabled', false);
                        slipInput.prop('disabled', false);
                        warningMsg.addClass('d-none');
                    }

                    // สุดท้ายค่อยเปิด Modal
                    new bootstrap.Modal(document.getElementById('editTenantModal')).show();
                });
        }

        function confirmUpdate() {
            Swal.fire({
                title: 'ยืนยันการแก้ไข?',
                text: 'กรุณาตรวจสอบความถูกต้องของข้อมูล',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#ffc107',
                confirmButtonText: '<i class="bi bi-save text-dark"></i> <span class="text-dark">บันทึกข้อมูล</span>',
                cancelButtonText: 'ยกเลิก',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {

                    let form = $('#editTenantForm');
                    let id = form.find('input[name="id_card"]');
                    let ph = form.find('input[name="phone"]');
                    let startDate = form.find('input[name="start_date"]');

                    //  แก้บัคแก้ไขเลขบัตร/เบอร์ไม่ได้: ลบเครื่องหมายขีด (-) หรือตัวอักษรแปลกปลอมออกให้หมดก่อนส่ง
                    id.val(id.val().replace(/\D/g, ''));
                    ph.val(ph.val().replace(/\D/g, ''));

                    if (!startDate.val()) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'กรุณาระบุวันเริ่มสัญญา',
                            text: 'ยังไม่ได้กำหนดวันเริ่มสัญญาสำหรับผู้เช่า',
                            confirmButtonText: 'ตกลง'
                        });
                        return;
                    }

                    Swal.fire({
                        title: 'กำลังบันทึกข้อมูล...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // ปลดล็อก element ก่อนส่งฟอร์ม เพื่อให้ Backend รับค่าไปประมวลผลได้
                    $('#edit_deposit_payment_method').prop('disabled', false);

                    form.submit();
                }
            });
        }

        function TerminateForm(tenant) {
            $('#term_room_number').val(tenant.room.room_number);
            $('#term_refund_amount').val(tenant.deposit_amount);

            // 1. แก้ปัญหาวันที่คลาดเคลื่อนโดยการใช้ Date Object ของ JS จัดการ Timezone
            let targetDate;
            if (tenant.end_date) {
                // โหลดค่าวันที่มาแปลงเป็น Timezone ท้องถิ่น (ไทย) ก่อน
                let dateObj = new Date(tenant.end_date);
                let yyyy = dateObj.getFullYear();
                let mm = String(dateObj.getMonth() + 1).padStart(2, '0');
                let dd = String(dateObj.getDate()).padStart(2, '0');
                targetDate = `${yyyy}-${mm}-${dd}`;
            } else {
                let today = new Date();
                let yyyy = today.getFullYear();
                let mm = String(today.getMonth() + 1).padStart(2, '0');
                let dd = String(today.getDate()).padStart(2, '0');
                targetDate = `${yyyy}-${mm}-${dd}`;
            }

            $('#term_end_date').val(targetDate);

            let url = "{{ route('admin.tenants.updateStatusTenant', ':id') }}";
            url = url.replace(':id', tenant.id);
            $('#terminateForm').attr('action', url);

            new bootstrap.Modal(document.getElementById('terminateTenantModal')).show();
        }

        function confirmTerminate() {
            // ดึงค่าจาก Modal
            let roomNo = $('#term_room_number').val();
            let endDate = $('#term_end_date').val();
            let refund = $('#term_refund_amount').val();

            // แปลงวันที่ (YYYY-MM-DD) เป็นภาษาไทยแบบเต็ม (เช่น 4 มีนาคม 2569)
            let dateParts = endDate.split('-');
            let year = parseInt(dateParts[0]) + 543; // แปลงเป็น พ.ศ.
            let monthIdx = parseInt(dateParts[1]) - 1; // ลบ 1 เพราะ Array เริ่มที่ 0
            let day = parseInt(dateParts[2]);

            const thaiMonths = [
                "มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน",
                "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"
            ];

            let displayDate = `${day} ${thaiMonths[monthIdx]} ${year}`;

            // จัดรูปแบบเงิน
            let displayRefund = Number(refund).toLocaleString('th-TH', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });

            // แสดง SweetAlert พร้อมวันที่ภาษาไทย
            Swal.fire({
                title: 'ยืนยันการย้ายออก?',
                html: `
                    <div class="text-start bg-light p-3 rounded border mb-3 fs-6">
                        <div class="mb-2"><b>หมายเลขห้อง:</b> <span class="text-primary">${roomNo}</span></div>
                        <div class="mb-2"><b>วันที่สิ้นสุดสัญญา:</b> <span class="text-dark">${displayDate}</span></div>
                        <div><b>ยอดคืนเงินมัดจำ:</b> <span class="text-success">฿${displayRefund}</span></div>
                    </div>
                    <div class="text-danger small"><i class="bi bi-exclamation-triangle-fill me-1"></i>ผู้เช่าจะถูกเปลี่ยนสถานะเป็น 'สิ้นสุดสัญญา' ทันที</div>
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#343a40',
                cancelButtonColor: '#d33',
                confirmButtonText: 'ยืนยันการย้ายออก',
                cancelButtonText: 'ยกเลิก',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    // แสดง Loading ระหว่างทำงาน (แบบที่คุณต้องการ)
                    Swal.fire({
                        title: 'กำลังดำเนินการ...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // Submit ฟอร์มแบบปกติ
                    document.getElementById('terminateForm').submit();
                }
            });
        }

        function confirmDelete(id) {
            Swal.fire({
                title: 'ลบข้อมูลถาวร?',
                text: "ข้อมูลนี้จะกู้คืนไม่ได้!",
                icon: 'error',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'ลบเลย',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#delete-form-' + id).submit();
                }
            });
        }

        // ********************************************
        // 6. Alert Handling (Backend Errors)
        // ********************************************
        // เช็คว่ามี Error จากการ Validation หรือไม่
        // @if ($errors->any())
        //     Swal.fire({
        //         icon: 'error',
        //         title: 'เกิดข้อผิดพลาด (Error 400)',
        //         html: `
    //             <div class="text-start text-danger">
    //                 <ul class="mb-0">
    //                     @foreach ($errors->all() as $error)
    //                         <li>{{ $error }}</li>
    //                     @endforeach
    //                 </ul>
    //             </div>
    //             <hr>
    //             <small class="text-muted">โปรดตรวจสอบเงื่อนไข เช่น จำนวนผู้อยู่อาศัยเกินกำหนด</small>
    //         `,
        //         confirmButtonText: 'ตกลง'
        //     });
        // @endif
    </script>
@endpush
