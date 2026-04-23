@extends('admin.layout')

@section('title', 'จัดการข้อมูลอาคาร')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12 mb-4 d-flex justify-content-between align-items-center">
            <div>
                <h3 class="fw-bold text-dark">จัดการข้อมูลอาคาร</h3>
                <p class="text-muted small">รายการตึกทั้งหมดใน อาทิตย์ อพาร์ทเม้นท์</p>
            </div>
            <button type="button" class="btn btn-primary px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#insertBuildingModal">
                <i class="bi bi-plus-circle me-1"></i> เพิ่มตึกใหม่
            </button>
        </div>
    </div>

    {{-- ส่วนแสดงตาราง --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="px-4 py-3" style="width: 100px;">ลำดับ</th>
                            <th class="py-3">ชื่ออาคาร</th>
                            <th class="py-3 text-end px-4">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($buildings as $index => $item)
                        <tr>
                            <td class="px-4 fw-bold text-muted">{{ $index + 1 }}</td>
                            <td>
                                <span class="fw-bold text-dark">{{ $item->name }}</span>
                            </td>
                            <td class="text-end px-4">
                                {{-- แก้ไขปุ่มให้เรียก Modal --}}
                                <button type="button" 
                                        class="btn btn-outline-warning btn-sm px-3 me-1" 
                                        onclick="openEditModal({{ $item->id }}, '{{ $item->name }}')">
                                    <i class="bi bi-pencil-square"></i> แก้ไข
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @if($buildings->isEmpty())
        <div class="card-body text-center py-5">
            <i class="bi bi-building text-light" style="font-size: 3rem;"></i>
            <p class="text-muted mt-2">ยังไม่มีข้อมูลอาคารในระบบ</p>
        </div>
        @endif
    </div>
</div>
{{-- 🌟 Modal เพิ่มอาคารใหม่ --}}
<div class="modal fade" id="insertBuildingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light border-bottom-0">
                <h5 class="modal-title fw-bold">เพิ่มอาคารใหม่</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="insertBuildingForm" action="{{ route('admin.building.insert') }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label class="form-label fw-bold text-dark">ชื่ออาคาร <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" placeholder="เช่น ตึก A, ตึก B" required>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="button" class="btn btn-primary py-2 px-4 fw-bold shadow-sm" id="btnSubmitInsert" onclick="confirmInsert()">
                            <i class="bi bi-save me-1"></i> บันทึกข้อมูล
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
{{-- การแก้ไข building --}}
<div class="modal fade" id="editBuildingModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="editModalLabel">แก้ไขข้อมูลอาคาร</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="editBuildingForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="mb-4">
                        <label for="name" class="form-label fw-bold">ชื่ออาคาร</label>
                        <input type="text" 
                               class="form-control" 
                               id="modal_building_name" 
                               name="name" 
                               required>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="button" class="btn btn-warning py-2 fw-bold shadow-sm" id="btnSubmit" onclick="confirmUpdate()">
                            <i class="bi bi-save me-1"></i> บันทึกการแก้ไข
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
    <script>
        // 🌟 ฟังก์ชันยืนยันเพิ่มตึกใหม่
        function confirmInsert() {
            let form = document.getElementById('insertBuildingForm');
            if (!form.reportValidity()) return;

            Swal.fire({
                title: 'กำลังบันทึกข้อมูล...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            const btn = document.getElementById('btnSubmitInsert');
            btn.disabled = true;
            form.submit();
        }
        // ฟังก์ชันเปิด Modal และใส่ข้อมูล
        function openEditModal(id, name) {
            // เซ็ตค่าชื่ออาคารใน Input
            document.getElementById('modal_building_name').value = name;
            
            // อัปเดต Action ของ Form ให้ตรงกับ ID ที่จะแก้ไข
            // สมมติชื่อ route คือ admin.building.update
            let updateUrl = "{{ route('admin.building.update', ':id') }}";
            updateUrl = updateUrl.replace(':id', id);
            document.getElementById('editBuildingForm').action = updateUrl;

            // สั่งเปิด Modal
            var editModal = new bootstrap.Modal(document.getElementById('editBuildingModal'));
            editModal.show();
        }

        // ฟังก์ชันยืนยันด้วย SweetAlert2
        function confirmUpdate() {
            Swal.fire({
                title: 'ยืนยันการแก้ไข?',
                text: "คุณต้องการบันทึกการเปลี่ยนแปลงข้อมูลตึกใช่หรือไม่",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'ใช่, บันทึกเลย!',
                cancelButtonText: 'ยกเลิก',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    const btn = document.getElementById('btnSubmit');
                    btn.disabled = true;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> กำลังบันทึก...';
                    
                    document.getElementById('editBuildingForm').submit();
                }
            })
        }
    </script>
@endpush