@extends('admin.layout')

@section('title', 'จัดการประเภทห้องพัก')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h3 class="fw-bold text-dark mb-0">จัดการประเภทห้องพัก</h3>
                <p class="text-muted small">แสดงรายการประเภทห้องทั้งหมดในระบบ</p>
            </div>
            <button type="button" class="btn btn-primary shadow-sm px-4" data-bs-toggle="modal" data-bs-target="#insertRoomTypeModal">
                <i class="bi bi-plus-circle me-1"></i> เพิ่มประเภทห้องใหม่
            </button>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="px-4 py-3" style="width: 100px;">ลำดับ</th>
                            <th class="py-3">ชื่อประเภทห้อง</th>
                            <th class="py-3 text-end px-4">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($room_types as $index => $item)
                        <tr>
                            <td class="px-4 fw-bold text-muted">{{ $index + 1 }}</td>
                            <td>
                                <span class="fw-bold text-dark">{{ $item->name }}</span>
                            </td>
                            <td class="text-end px-4">
                                <button type="button" 
                                        class="btn btn-outline-warning btn-sm px-3 me-1" 
                                        onclick="openEditModal({{ $item->id }}, '{{ $item->name }}')">
                                    <i class="bi bi-pencil-square"></i> แก้ไข
                                </button>

                                {{-- ปรับปุ่มลบให้เรียกใช้ฟังก์ชัน confirmDelete --}}
                                {{-- <form action="{{ route('admin.room_types.delete', $item->id) }}" method="POST" class="d-inline" id="delete-form-{{ $item->id }}">
                                    @csrf
                                    <button type="button" class="btn btn-outline-danger btn-sm px-3" onclick="confirmDelete({{ $item->id }})">
                                        <i class="bi bi-trash"></i> ลบ
                                    </button>
                                </form> --}}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @if($room_types->isEmpty())
        <div class="card-body text-center py-5">
            <i class="bi bi-inbox text-light" style="font-size: 3rem;"></i>
            <p class="text-muted mt-2">ยังไม่มีข้อมูลประเภทห้องในระบบ</p>
        </div>
        @endif
    </div>
</div>

{{-- Modal เพิ่มข้อมูล (Insert) --}}
<div class="modal fade" id="insertRoomTypeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">เพิ่มประเภทห้องพักใหม่</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="insertRoomTypeForm" action="{{ route('admin.room_types.insert') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="name" class="form-label fw-bold">ชื่อประเภทห้อง</label>
                        <input type="text" id="insert_name" name="name" class="form-control" placeholder="เช่น ห้องแอร์พร้อมเฟอร์นิเจอร์" required>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-primary px-4" id="btnSubmitInsert" onclick="confirmInsert()">
                        <i class="bi bi-plus-circle me-1"></i> บันทึกข้อมูล
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal แก้ไขข้อมูล (Edit) --}}
<div class="modal fade" id="editRoomTypeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold">แก้ไขประเภทห้องพัก</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editRoomTypeForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label fw-bold">ชื่อประเภทห้อง</label>
                        <input type="text" id="edit_name" name="name" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-warning px-4 fw-bold" id="btnSubmitUpdate" onclick="confirmUpdate()">
                        <i class="bi bi-save me-1"></i> บันทึกการแก้ไข
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script>
        // 1. ยืนยันการเพิ่มข้อมูล (Insert)
        function confirmInsert() {
            const name = document.getElementById('insert_name').value.trim();
            
            if (name === "") {
                Swal.fire({ icon: 'error', title: 'กรุณากรอกชื่อประเภทห้อง' });
                return;
            }

            Swal.fire({
                title: 'ยืนยันการเพิ่มข้อมูล?',
                html: `คุณต้องการเพิ่มประเภทห้อง <br><b class="text-primary">"${name}"</b> <br>เข้าสู่ระบบใช่หรือไม่?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#0d6efd',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'ยืนยันเพิ่มข้อมูล',
                cancelButtonText: 'ยกเลิก',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    const btn = document.getElementById('btnSubmitInsert');
                    btn.disabled = true;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> กำลังบันทึก...';
                    document.getElementById('insertRoomTypeForm').submit();
                }
            });
        }

        // 2. ยืนยันการแก้ไขข้อมูล (Update)
        function confirmUpdate() {
            const newName = document.getElementById('edit_name').value.trim();
            
            if (newName === "") {
                Swal.fire({ icon: 'error', title: 'ชื่อประเภทห้องห้ามปล่อยว่าง' });
                return;
            }

            Swal.fire({
                title: 'ยืนยันการแก้ไข?',
                html: `คุณต้องการเปลี่ยนชื่อประเภทห้องพักเป็น <br><b class="text-warning">"${newName}"</b> <br>ใช่หรือไม่?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#f0ad4e',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'ใช่, บันทึกการแก้ไข',
                cancelButtonText: 'ยกเลิก',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    const btn = document.getElementById('btnSubmitUpdate');
                    btn.disabled = true;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> กำลังบันทึก...';
                    document.getElementById('editRoomTypeForm').submit();
                }
            });
        }

        // 3. ยืนยันการลบข้อมูล (Delete)
        function confirmDelete(id) {
            Swal.fire({
                title: 'ยืนยันการลบข้อมูล?',
                text: "หากลบแล้ว ข้อมูลประเภทห้องนี้จะไม่สามารถกู้คืนได้ และอาจส่งผลต่อข้อมูลห้องพักที่ใช้งานอยู่!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'ใช่, ลบเลย!',
                cancelButtonText: 'ยกเลิก',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    // แสดงสถานะกำลังลบ
                    Swal.fire({
                        title: 'กำลังดำเนินการ...',
                        allowOutsideClick: false,
                        didOpen: () => { Swal.showLoading(); }
                    });
                    document.getElementById('delete-form-' + id).submit();
                }
            });
        }

        // ฟังก์ชันช่วยเปิด Modal แก้ไข (คงเดิม)
        function openEditModal(id, name) {
            document.getElementById('edit_name').value = name;
            let url = "{{ route('admin.room_types.update', ':id') }}";
            url = url.replace(':id', id);
            document.getElementById('editRoomTypeForm').action = url;
            var myModal = new bootstrap.Modal(document.getElementById('editRoomTypeModal'));
            myModal.show();
        }
    </script>
@endpush