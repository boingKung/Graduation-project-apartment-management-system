@extends('admin.layout')

@section('title', 'จัดการหมวดหมู่ห้องพัก')

@section('content')
    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-12 d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="fw-bold text-dark mb-0">จัดการหมวดหมู่ห้องพัก</h3>
                    <p class="text-muted small">กำหนดหมวดหมู่ตามประเภทห้องและอาคาร</p>
                </div>
                <button class="btn btn-primary px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#insertPriceModal">
                    <i class="bi bi-plus-circle me-1"></i> เพิ่มหมวดหมู่
                </button>
            </div>
        </div>

        {{-- นำ Form มาครอบตารางแทน --}}
        <form method="GET" action="{{ route('admin.room_prices.show') }}" id="filterTableForm">
            <div class="card border-0 shadow-sm">

                {{-- Header เล็กๆ พร้อมปุ่มล้างค่า --}}
                <div
                    class="card-header bg-white border-bottom-0 pt-3 pb-2 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-dark">รายการหมวดหมู่ห้องพัก</h5>
                    <a href="{{ route('admin.room_prices.show') }}"
                        class="btn btn-light btn-sm border text-muted shadow-sm">
                        <i class="bi bi-eraser-fill"></i> ล้างการค้นหา
                    </a>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-muted small">
                                <tr>
                                    <th class="px-4 py-3" width="20%">รูปภาพ</th>
                                    <th width="25%">อาคาร</th>
                                    <th width="25%">ประเภทห้อง</th>
                                    <th width="15%">ชั้น</th>
                                    
                                    <th class="text-center px-4" width="15%">จัดการ</th>
                                </tr>

                                {{-- แถวสำหรับ Inline Filter --}}
                                <tr class="bg-white border-bottom shadow-sm">
                                    <td class="px-4 py-2"></td> {{-- รูปภาพ ไม่ได้กรอง ให้ปล่อยว่าง --}}
                                    <td class="py-2">
                                        <select name="filter_building"
                                            class="form-select form-select-sm bg-light border-0 fw-bold text-muted"
                                            onchange="this.form.submit()">
                                            <option value="">- ทุกอาคาร -</option>
                                            @foreach ($buildings as $b)
                                                <option value="{{ $b->id }}"
                                                    {{ request('filter_building') == $b->id ? 'selected' : '' }}>
                                                    {{ $b->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="py-2">
                                        <select name="filter_room_type"
                                            class="form-select form-select-sm bg-light border-0 fw-bold text-muted"
                                            onchange="this.form.submit()">
                                            <option value="">- ทุกประเภท -</option>
                                            @foreach ($room_types as $rt)
                                                <option value="{{ $rt->id }}"
                                                    {{ request('filter_room_type') == $rt->id ? 'selected' : '' }}>
                                                    {{ $rt->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="py-2">
                                        <input type="number" name="filter_floor"
                                            class="form-control form-control-sm bg-light border-0 text-muted"
                                            placeholder="เลขชั้น..." value="{{ request('filter_floor') }}" min="1"
                                            onkeydown="if(event.key === 'Enter') this.form.submit();"
                                            onchange="this.form.submit()">
                                    </td>
                                    <td class="py-2"></td> {{-- หมวดหมู่ไม่ได้กรอง ให้ปล่อยว่าง --}}
                                    <td class="py-2 text-center">
                                        {{-- ปุ่ม Submit ซ่อนไว้สำหรับรองรับการกด Enter --}}
                                        <button type="submit" class="d-none">Search</button>
                                    </td>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($room_prices as $item)
                                    <tr>
                                        <td class="px-4">
                                            @if (!empty($item->color_code))
                                                <img src="{{ asset('storage/' . $item->color_code) }}"
                                                    class="rounded shadow-sm"
                                                    style="width:60px;height:40px;object-fit:cover;"
                                                    onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                                <div class="bg-light rounded text-center text-muted"
                                                    style="width:60px;height:40px;line-height:40px;font-size:10px;display:none;">
                                                    ไม่มีรูป
                                                </div>
                                            @else
                                                <div class="bg-light rounded text-center text-muted"
                                                    style="width:60px;height:40px;line-height:40px;font-size:10px;">
                                                    ไม่มีรูป
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="fw-bold text-dark">{{ $item->building->name }}</div>
                                        </td>
                                        <td>
                                            <div class="text-secondary">{{ $item->roomType->name }}</div>
                                        </td>
                                        <td>ชั้น {{ $item->floor_num }}</td>
                                        
                                        <td class="text-center px-4">
                                            {{-- ปรับสไตล์ปุ่มจัดการให้ดูคลีนขึ้น --}}
                                            <button type="button"
                                                class="btn btn-light border btn-sm text-warning shadow-sm me-1"
                                                title="แก้ไข"
                                                onclick="openEditModal({{ $item->id }}, {{ $item->building_id }}, {{ $item->room_type_id }}, {{ $item->floor_num }})">
                                                <i class="bi bi-pencil-square"></i> แก้ไข
                                            </button>

                                            {{-- <button type="button"
                                                class="btn btn-light border btn-sm text-danger shadow-sm px-2"
                                                title="ลบ"
                                                onclick="confirmDelete('{{ route('admin.room_prices.delete', $item->id) }}')">
                                                <i class="bi bi-trash"></i> ลบ
                                            </button> --}}

                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        {{-- แสดง Empty State ถ้าไม่พบข้อมูล --}}
                        @if ($room_prices->isEmpty())
                            <div class="text-center py-5 text-muted">
                                <i class="bi bi-cash-stack fs-1"></i>
                                <p class="mt-2">ไม่พบข้อมูลหมวดหมู่ห้องพักที่ค้นหา</p>
                            </div>
                        @endif

                        <div class="p-3 bg-white border-top">{{ $room_prices->links() }}</div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    {{-- MODAL INSERT --}}
    <div class="modal fade" id="insertPriceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">กำหนดหมวดหมู่ห้องพักใหม่</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                {{-- สำคัญ: ต้องมี enctype="multipart/form-data" เพื่อส่งรูปภาพ --}}
                <form action="{{ route('admin.room_prices.insert') }}" method="POST" enctype="multipart/form-data"
                    id="insertRoomPriceForm">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">เลือกอาคาร</label>
                                <select name="building_id" class="form-select" required>
                                    <option value="">-- เลือกตึก --</option>
                                    @foreach ($buildings as $b)
                                        <option value="{{ $b->id }}">{{ $b->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">เลือกประเภทห้อง</label>
                                <select name="room_type_id" class="form-select" required>
                                    <option value="">-- เลือกประเภท --</option>
                                    @foreach ($room_types as $rt)
                                        <option value="{{ $rt->id }}">{{ $rt->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">ชั้นที่ (Floor)</label>
                                <input type="number" name="floor_num" min="1" class="form-control"
                                    placeholder="ระบุชั้น">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">อัปโหลดรูปภาพสีประจำประเภท</label>
                                <input type="file" name="color_code" class="form-control" accept="image/*" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="button" class="btn btn-primary px-4"
                            onclick="confirmInsert()">บันทึกข้อมูล</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    {{-- MODAL EDIT --}}
    <div class="modal fade" id="editPriceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold">แก้ไขหมวดหมู่ห้องพัก</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editPriceForm" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">เลือกอาคาร</label>
                                <select name="building_id" id="edit_building_id" class="form-select" required>
                                    @foreach ($buildings as $b)
                                        <option value="{{ $b->id }}">{{ $b->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">เลือกประเภทห้อง</label>
                                <select name="room_type_id" id="edit_room_type_id" class="form-select" required>
                                    @foreach ($room_types as $rt)
                                        <option value="{{ $rt->id }}">{{ $rt->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">ชั้นที่ (Floor)</label>
                                <input type="number" name="floor_num" id="edit_floor_num" min="1"
                                    max="5" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">เปลี่ยนรูป
                                    (เว้นว่างไว้หากไม่ต้องการเปลี่ยน)</label>
                                <input type="file" name="color_code" class="form-control" accept="image/*">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="button" class="btn btn-warning px-4 fw-bold" onclick="confirmUpdate()"
                            id="btnUpdate">บันทึกการแก้ไข</button>
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
            let form = document.getElementById(
            'insertRoomPriceForm'); // ต้องไปเติม id="insertRoomPriceForm" ที่แท็ก form ของ modal insert ด้วย
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

        function openEditModal(id, building_id, room_type_id, floor_num) {
            // 1. เติมข้อมูลลงในฟิลด์ต่างๆ
            document.getElementById('edit_building_id').value = building_id;
            document.getElementById('edit_room_type_id').value = room_type_id;
            document.getElementById('edit_floor_num').value = floor_num;

            // 2. ตั้งค่า Action URL ให้ตรงกับ ID
            let url = "{{ route('admin.room_prices.update', ':id') }}";
            url = url.replace(':id', id);
            document.getElementById('editPriceForm').action = url;

            // 3. เปิด Modal
            var editModal = new bootstrap.Modal(document.getElementById('editPriceModal'));
            editModal.show();
        }

        function confirmUpdate() {
            Swal.fire({
                title: 'ยืนยันการแก้ไข?',
                text: "ข้อมูลหมวดหมู่ห้องพักจะถูกเปลี่ยนแปลง",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#ffc107',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'ตกลง, แก้ไขเลย!',
                cancelButtonText: 'ยกเลิก',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    const btn = document.getElementById('btnUpdate');
                    btn.disabled = true;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> กำลังบันทึก...';
                    document.getElementById('editPriceForm').submit();
                }
            })
        }

        // SweetAlert ยืนยันการลบ
        function confirmDelete(deleteUrl) {
            Swal.fire({
                title: 'ยืนยันการลบข้อมูล?',
                text: "หากลบแล้วข้อมูลหมวดหมู่ประเภทห้องนี้จะไม่สามารถกู้คืนได้!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'ใช่, ลบเลย!',
                cancelButtonText: 'ยกเลิก',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    let form = document.getElementById('globalDeleteForm');
                    Swal.fire({
                        title: 'กำลังลบข้อมูล...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    form.action = deleteUrl;
                    // ส่งฟอร์ม
                    form.submit();
                }
            })
        }
    </script>
@endpush
