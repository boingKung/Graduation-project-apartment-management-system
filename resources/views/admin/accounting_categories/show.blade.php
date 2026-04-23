@extends('admin.layout')
@section('title', 'จัดการหมวดหมู่บัญชี')

@section('content')
    <div class="container-fluid py-4">
        <div class="row mb-4 align-items-center">
            <div class="col-md-6">
                <h3 class="fw-bold text-dark mb-0">จัดการหมวดหมู่บัญชี</h3>
                <p class="text-muted small">กำหนดหมวดหมู่รายรับและรายจ่ายสำหรับระบบบัญชี</p>
            </div>
            <div class="col-md-6 text-md-end">
                <button class="btn btn-primary shadow-sm px-4" data-bs-toggle="modal" data-bs-target="#insertCategoryModal">
                    <i class="bi bi-plus-circle me-1"></i> เพิ่มหมวดหมู่ใหม่
                </button>
            </div>
        </div>

        <div class="row g-4">
            {{-- ตารางรายรับ (ID: 1) --}}
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm overflow-hidden">
                    <div class="card-header bg-success bg-opacity-10 border-0 py-3">
                        <h5 class="mb-0 text-success fw-bold"><i class="bi bi-plus-square-fill me-2"></i>หมวดหมู่รายรับ</h5>
                    </div>
                    @include('admin.accounting_categories._category_table', [
                        'categories' => $income_categories,
                        'type' => 'income',
                    ])
                </div>
            </div>

            {{-- ตารางรายจ่าย (ID: 2) --}}
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm overflow-hidden">
                    <div class="card-header bg-danger bg-opacity-10 border-0 py-3">
                        <h5 class="mb-0 text-danger fw-bold"><i class="bi bi-dash-square-fill me-2"></i>หมวดหมู่รายจ่าย</h5>
                    </div>
                    @include('admin.accounting_categories._category_table', [
                        'categories' => $expense_categories,
                        'type' => 'expense',
                    ])
                </div>
            </div>
        </div>
    </div>
    {{-- Modal เพิ่มหมวดหมู่ใหม่ (Insert) --}}
    <div class="modal fade" id="insertCategoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle me-2"></i>เพิ่มหมวดหมู่บัญชีใหม่</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form action="{{ route('admin.accounting_category.insert') }}" method="POST" id="insertCategoryForm">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-bold">ชื่อหมวดหมู่</label>
                            <input type="text" name="name" class="form-control"
                                placeholder="เช่น ค่าเช่าห้อง, ค่าซ่อมแซม" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">ประเภทบัญชี</label>
                            <select name="type_id" class="form-select" required>
                                <option value="">-- เลือกประเภท --</option>
                                <option value="1">รายรับ (Income)</option>
                                <option value="2">รายจ่าย (Expense)</option>
                            </select>
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

    {{-- Modal แก้ไขหมวดหมู่ (Update) --}}
    <div class="modal fade" id="editCategoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>แก้ไขหมวดหมู่บัญชี</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editCategoryForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-bold">ชื่อหมวดหมู่</label>
                            <input type="text" id="edit_name" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">ประเภทบัญชี</label>
                            <select id="edit_type_id" name="type_id" class="form-select" required>
                                <option value="1">รายรับ (Income)</option>
                                <option value="2">รายจ่าย (Expense)</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="button" class="btn btn-warning px-4 fw-bold" onclick="confirmUpdate()">
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
        function confirmInsert() {
        let form = document.getElementById('insertCategoryForm'); // ต้องไปเติม id="insertCategoryForm" ที่แท็ก form ของ modal insert ด้วย
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
        function openEditModal(id, name, type_id) {
            // กำหนดค่าในช่อง Input
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_type_id').value = type_id;

            // กำหนด Action URL
            let url = "{{ route('admin.accounting_category.update', ':id') }}";
            url = url.replace(':id', id);
            document.getElementById('editCategoryForm').action = url;

            // แสดง Modal
            var myModal = new bootstrap.Modal(document.getElementById('editCategoryModal'));
            myModal.show();
        }
        // ฟังก์ชันยืนยันการแก้ไข (Confirm Update)
        function confirmUpdate() {
            Swal.fire({
                title: 'ยืนยันการแก้ไขข้อมูล?',
                text: "ข้อมูลหมวดหมู่บัญชีจะถูกอัปเดตทันที",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#ffc107', // สีเหลือง Warning
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'ตกลง, บันทึกเลย',
                cancelButtonText: 'ยกเลิก',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    // แสดง Loading ระหว่างส่งข้อมูล
                    Swal.fire({
                        title: 'กำลังบันทึก...',
                        allowOutsideClick: false,
                        didOpen: () => { Swal.showLoading(); }
                    });
                    document.getElementById('editCategoryForm').submit();
                }
            });
        }
    </script>
@endpush
