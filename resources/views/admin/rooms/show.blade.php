@extends('admin.layout')

@section('title', 'จัดการข้อมูลห้องพัก')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h3 class="fw-bold text-dark mb-0">จัดการข้อมูลห้องพัก</h3>
                <p class="text-muted small">รายการเลขห้องและสถานะห้องพักทั้งหมด</p>
            </div>
            <button class="btn btn-primary px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#insertRoomModal">
                <i class="bi bi-plus-circle me-1"></i> เพิ่มห้องพักใหม่
            </button>
        </div>
    </div>

    {{-- ลบฟอร์มค้นหาด้านบนออก แล้วนำ Form มาครอบตารางแทน --}}
    <form method="GET" action="{{ route('admin.rooms.show') }}" id="filterTableForm">
        <div class="card border-0 shadow-sm">
            
            {{-- Header เล็กๆ พร้อมปุ่มล้างค่า --}}
            <div class="card-header bg-white border-bottom-0 pt-3 pb-2 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 text-dark">รายการห้องพัก</h5>
                <a href="{{ route('admin.rooms.show') }}" class="btn btn-light btn-sm border text-muted shadow-sm">
                    <i class="bi bi-eraser-fill"></i> ล้างการค้นหา
                </a>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-muted small">
                            <tr>
                                <th class="px-4 py-3" width="15%">หมายเลขห้อง</th>
                                <th width="20%">อาคาร</th>
                                <th width="10%">ประเภท</th>
                                <th width="10%">ชั้น</th>
                                <th width="10%">ราคา</th>
                                <th width="10%">สถานะ</th>
                                <th width="10%">หมายเหตุ</th>
                                <th class="text-center px-4" width="15%">จัดการ</th>
                            </tr>

                            {{--  แถวสำหรับ Inline Filter --}}
                            <tr class="bg-white border-bottom shadow-sm">
                                <td class="px-3 py-2">
                                    <input type="text" name="filter_room" class="form-control form-control-sm bg-light border-0 fw-bold" 
                                        placeholder="หมายเลขห้อง..." value="{{ request('filter_room') }}" 
                                        onkeydown="if(event.key === 'Enter') this.form.submit();">
                                </td>
                                <td class="py-2">
                                    <select name="filter_building" class="form-select form-select-sm bg-light border-0 text-muted" 
                                        onchange="this.form.submit()">
                                        <option value="">- ทุกอาคาร -</option>
                                        @foreach($buildings as $b)
                                            <option value="{{ $b->id }}" {{ request('filter_building') == $b->id ? 'selected' : '' }}>
                                                {{ $b->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="py-2">
                                    <select name="filter_room_type" class="form-select form-select-sm bg-light border-0 text-muted" 
                                        onchange="this.form.submit()">
                                        <option value="">- ทุกประเภท -</option>
                                        @foreach($room_types as $type)
                                            <option value="{{ $type->id }}" {{ request('filter_room_type') == $type->id ? 'selected' : '' }}>
                                                {{ $type->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="py-2">
                                    <input type="number" name="filter_floor" class="form-control form-control-sm bg-light border-0 text-muted" 
                                        placeholder="เลขชั้น..." value="{{ request('filter_floor') }}"  min="1"
                                        onkeydown="if(event.key === 'Enter') this.form.submit();"
                                        onchange="this.form.submit()">
                                </td>
                                <td class="py-2"></td>
                                <td class="py-2">
                                    <select name="filter_status" class="form-select form-select-sm bg-light border-0 fw-bold" 
                                        onchange="this.form.submit()">
                                        <option value="">- ทั้งหมด -</option>
                                        <option value="ว่าง" class="" {{ request('filter_status') == 'ว่าง' ? 'selected' : '' }}>ว่าง</option>
                                        <option value="มีผู้เช่า" class="" {{ request('filter_status') == 'มีผู้เช่า' ? 'selected' : '' }}>มีผู้เช่า</option>
                                        <option value="ซ่อมบำรุง" class="" {{ request('filter_status') == 'ซ่อมบำรุง' ? 'selected' : '' }}>ซ่อมบำรุง</option>
                                    </select>
                                </td>
                                <td class="py-2">
                                </td>
                                <td class="py-2 text-center">
                                    {{-- ปุ่ม Submit ซ่อนไว้สำหรับรองรับการกด Enter --}}
                                    <button type="submit" class="d-none">Search</button>
                                </td>
                            </tr>
                        </thead>
                        
                        <tbody>
                            @foreach($rooms as $item)
                            <tr>
                                <td class="px-4 fw-bold text-dark fs-5">
                                    {{ $item->room_number }}
                                    {{-- 🌟 แสดงไอคอนถ้ามีหมายเหตุ --}}
                                    @if($item->remark)
                                        <i class="bi bi-chat-text text-info ms-1" style="font-size: 1rem; cursor: pointer;" title="หมายเหตุ: {{ $item->remark }}"></i>
                                    @endif
                                </td>
                                <td>{{ $item->roomPrice->building->name }}</td>
                                <td>
                                    <div class="small fw-bold text-secondary">{{ $item->roomPrice->roomType->name }}</div>
                                </td>
                                <td>ชั้น {{ $item->roomPrice->floor_num }}</td>
                                <td> <div class="text-primary fw-bold small">{{ number_format($item->price, 0) }} ฿</div></td>
                                <td>
                                    {{-- การแสดง Badge สถานะแบบมีพื้นหลังโปร่งใส (Modern UX) --}}
                                    @if($item->status == 'ว่าง')
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success"><i class="bi bi-check-circle-fill me-1"></i> ว่าง</span>
                                    @elseif($item->status == 'มีผู้เช่า')
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger"><i class="bi bi-person-fill me-1"></i> มีผู้เช่า</span>
                                    @else
                                        <span class="badge bg-warning bg-opacity-10 text-warning border border-warning"><i class="bi bi-tools me-1"></i> ซ่อมบำรุง</span>
                                    @endif
                                </td>
                                <td>
                                    <i class="bi {{ $item->remark ? 'bi-check-circle-fill text-success fs-5' : 'bi-dash' }}"></i>
                                </td>
                                <td class="text-center px-4">
                                    {{-- เปลี่ยนปุ่มเป็น Outline Icon เพื่อลดความรกรุงรัง --}}
                                    <button type="button" class="btn btn-light border btn-sm text-warning shadow-sm me-1" title="แก้ไข"
                                            onclick="openEditModal({{ $item->id }}, '{{ $item->room_number }}', {{ $item->room_price_id }}, '{{ $item->status }}', {{ $item->price ?? 0 }}, '{{ $item->remark }}')">
                                        <i class="bi bi-pencil-square"></i> แก้ไข
                                    </button>
                                    {{-- เปลี่ยนฟอร์มปุ่มลบให้ไม่พัง Layout บรรทัด --}}
                                    {{-- <button type="button" class="btn btn-light border btn-sm text-danger shadow-sm px-2" title="ลบ" 
                                            onclick="confirmDelete('{{ route('admin.rooms.delete', $item->id) }}')">
                                        <i class="bi bi-trash"></i> ลบ
                                    </button> --}}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    
                    {{-- แสดง Empty State ถ้าไม่พบข้อมูล --}}
                    @if($rooms->isEmpty())
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-door-open fs-1"></i>
                            <p class="mt-2">ไม่พบข้อมูลห้องพักที่ค้นหา</p>
                        </div>
                    @endif

                    <div class="p-3 bg-white border-top">{{ $rooms->links() }}</div>
                </div>
            </div>
        </div>
    </form>
</div>

{{-- MODAL INSERT --}}
<div class="modal fade" id="insertRoomModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">เพิ่มห้องพักใหม่</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.rooms.insert') }}" method="POST" id="insertRoomForm">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">หมายเลขห้อง (Room Number)</label>
                        <input type="text" name="room_number" class="form-control" placeholder="เช่น 1101" maxlength="4" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">เลือกประเภท (อ้างอิงจากตึกและชั้น)</label>
                        <select name="room_price_id" class="form-select select2-with-image" style="width: 100%;" required>
                            <option value="">-- เลือกตึก/ประเภท/ราคา --</option>
                            @foreach($room_prices as $rp)
                                <option value="{{ $rp->id }}" data-image="{{ $rp->color_code ? asset('storage/' . $rp->color_code) : '' }}" >
                                    {{ $rp->building->name }} ชั้น {{ $rp->floor_num }} | {{ $rp->roomType->name }} 
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">ราคาห้องพักต่อเดือน (บาท) <span class="text-danger">*</span></label>
                        <input type="number" name="price" class="form-control text-primary fw-bold" placeholder="เช่น 3,500" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">สถานะเริ่มต้น</label>
                        <select name="status" class="form-select">
                            <option value="ว่าง">ว่าง</option>
                            <option value="มีผู้เช่า">มีผู้เช่า</option>
                            <option value="ซ่อมบำรุง">ซ่อมบำรุง</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted">หมายเหตุ <small class="fw-normal">(ถ้ามี)</small></label>
                        <textarea name="remark" class="form-control" rows="2" placeholder="ใส่ข้อความหมายเหตุเกี่ยวกับห้องนี้..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-primary px-4" onclick="confirmInsert()">บันทึกข้อมูล</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL EDIT --}}
<div class="modal fade" id="editRoomModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning text-black">
                <h5 class="modal-title fw-bold">แก้ไขข้อมูลห้องพัก</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editRoomForm" method="POST">
                @csrf @method('PUT')
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">หมายเลขห้อง</label>
                        <input type="text" name="room_number" id="edit_room_number" class="form-control" maxlength="4" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">ประเภท</label>
                        <select name="room_price_id" id="edit_room_price_id" class="form-select select2-with-image" style="width: 100%;" required>
                            @foreach($room_prices as $rp)
                                <option value="{{ $rp->id }}" data-image="{{ $rp->color_code ? asset('storage/' . $rp->color_code) : '' }}">
                                    
                                     {{ $rp->building->name }} ชั้น {{ $rp->floor_num }} | {{ $rp->roomType->name }} 
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">ราคาห้องพักต่อเดือน (บาท) <span class="text-danger">*</span></label>
                        <input type="number" name="price" id="edit_price" class="form-control text-primary fw-bold" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">สถานะ</label>
                        <select name="status" id="edit_status" class="form-select">
                            <option value="ว่าง">ว่าง</option>
                            <option value="มีผู้เช่า">มีผู้เช่า</option>
                            <option value="ซ่อมบำรุง">ซ่อมบำรุง</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted">หมายเหตุ <small class="fw-normal">(ถ้ามี)</small></label>
                        <textarea name="remark" id="edit_remark" class="form-control" rows="2" placeholder="ใส่ข้อความหมายเหตุเกี่ยวกับห้องนี้..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-warning px-4 fw-bold" onclick="confirmUpdate()" id="btnUpdateRoom">บันทึกการแก้ไข</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Detele ฟอร์มลบส่วนกลาง --}}
<form id="globalDeleteForm" method="POST" class="d-none">
    @csrf
</form>
@endsection

@push('scripts')
<script>
    function confirmInsert() {
        let form = document.getElementById('insertRoomForm'); // ต้องไปเติม id="insertRoomForm" ที่แท็ก form ของ modal insert ด้วย
        if (!form.reportValidity()) return; // เช็คว่ากรอกข้อมูลครบหรือไม่

        Swal.fire({
            title: 'กำลังบันทึกข้อมูล...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        form.submit();
    }

    function openEditModal(id, room_number, room_price_id, status ,price, remark) {
        document.getElementById('edit_room_number').value = room_number;
        document.getElementById('edit_room_price_id').value = room_price_id;
        document.getElementById('edit_status').value = status;
        document.getElementById('edit_price').value = price;
        document.getElementById('edit_remark').value = (remark === 'null' || !remark) ? '' : remark;
        $('#edit_room_price_id').val(room_price_id).trigger('change');
        let url = "{{ route('admin.rooms.update', ':id') }}";
        url = url.replace(':id', id);
        document.getElementById('editRoomForm').action = url;

        new bootstrap.Modal(document.getElementById('editRoomModal')).show();
    }

    function confirmUpdate() {
        Swal.fire({
            title: 'ยืนยันการแก้ไข?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            confirmButtonText: 'ตกลง, แก้ไขเลย!',
            cancelButtonText: 'ยกเลิก',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                const btn = document.getElementById('btnUpdateRoom');
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>';
                document.getElementById('editRoomForm').submit();
            }
        });
    }

   function confirmDelete(deleteUrl) {
        Swal.fire({
            title: 'ยืนยันการลบห้องพัก?',
            text: "ข้อมูลห้องพักและประวัติที่เกี่ยวข้องจะหายไป!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'ใช่, ลบเลย!',
            cancelButtonText: 'ยกเลิก',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                // เรียกใช้ Global Form
                let form = document.getElementById('globalDeleteForm');
                // กำหนด URL สำหรับการลบเข้าไปที่ action ของฟอร์ม
                Swal.fire({
                    title: 'กำลังลบห้องพัก...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                form.action = deleteUrl;
                // ส่งฟอร์ม
                form.submit();
            }
        });
    }

    // แสดงภาพ ใน option
    // 🌟 ฟังก์ชันสำหรับตกแต่ง Option ให้มีรูปภาพ
    function formatOptionWithImage(opt) {
        if (!opt.id) { return opt.text; } // ถ้าเป็น placeholder ไม่ต้องใส่รูป

        var imageUrl = $(opt.element).data('image');
        
        // ถ้ารูปมี ให้สร้างแท็กโดยใช้ display: flex เพื่อให้อยู่บรรทัดเดียวกันเสมอ
        if (imageUrl && imageUrl !== '') {
            var $opt = $(
                '<div style="display: flex; align-items: center;">' +
                    '<img src="' + imageUrl + '" style="width: 40px; height: 25px; object-fit: cover; border-radius: 4px; margin-right: 10px; flex-shrink: 0; box-shadow: 0 1px 3px rgba(0,0,0,0.2);" onerror="this.style.display=\'none\'" /> ' +
                    '<span style="line-height: 1.2;">' + opt.text + '</span>' +
                '</div>'
            );
            return $opt;
        } else {
            // กรณีไม่มีรูป ก็ใช้ display: flex จัดให้อยู่บรรทัดเดียวกันเช่นกัน
            var $optNoImg = $(
                '<div style="display: flex; align-items: center;">' +
                    '<div style="width: 40px; height: 25px; background:#e9ecef; border-radius: 4px; margin-right: 10px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-size: 10px; color:#adb5bd;">ไม่มีรูป</div>' +
                    '<span style="line-height: 1.2;">' + opt.text + '</span>' +
                '</div>'
            );
            return $optNoImg;
        }
    }

    $(document).ready(function() {
        // เช็คว่ามี jQuery และ Select2 ไหม ถ้าพังจะได้รู้ทันที
        if (typeof $.fn.select2 === 'undefined') {
            console.error('🚨 Select2 ไม่ทำงาน! คุณลืมใส่สคริปต์ jQuery หรือ Select2 ในหน้า layout.blade.php');
            return;
        }

        // เปิดใช้งาน Select2 เมื่อคลิกเปิด Modal Insert
        $('#insertRoomModal').on('shown.bs.modal', function () {
            $('#insertRoomModal .select2-with-image').select2({
                dropdownParent: $('#insertRoomModal'), 
                templateResult: formatOptionWithImage,
                templateSelection: formatOptionWithImage,
                width: '100%',
                theme: "bootstrap-5" // ลบออกได้ถ้าไม่ได้โหลด CSS bootstrap-5 theme มา
            });
        });

        // เปิดใช้งาน Select2 เมื่อคลิกเปิด Modal Edit
        $('#editRoomModal').on('shown.bs.modal', function () {
            $('#editRoomModal .select2-with-image').select2({
                dropdownParent: $('#editRoomModal'),
                templateResult: formatOptionWithImage,
                templateSelection: formatOptionWithImage,
                width: '100%',
                theme: "bootstrap-5"
            });
        });
    });
</script>
@endpush