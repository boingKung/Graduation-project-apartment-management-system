@extends('admin.layout')

@section('title', 'จัดการข้อมูลผู้ดูแลระบบ')

@section('content')
<style>
    .admin-user-modal .modal-content {
        border-radius: 1rem;
        overflow: hidden;
    }

    .admin-user-modal .modal-header {
        border-bottom: 0;
        padding: 1rem 1.25rem;
    }

    .admin-user-modal .modal-body {
        background: #f8fafc;
    }

    .admin-user-modal .form-section {
        background: #ffffff;
        border: 1px solid #eef2f7;
        border-radius: 0.85rem;
        padding: 1rem;
    }

    .admin-user-modal .section-title {
        font-size: 0.92rem;
        font-weight: 700;
        color: #334155;
        margin-bottom: 0.85rem;
    }

    .admin-user-modal .form-label {
        font-size: 0.85rem;
        margin-bottom: 0.35rem;
    }

    .admin-user-modal .form-control,
    .admin-user-modal .form-select {
        border-radius: 0.6rem;
        min-height: 42px;
    }

    .admin-user-modal .password-input-wrapper {
        position: relative;
    }

    .admin-user-modal .password-toggle-btn {
        position: absolute;
        right: 0.55rem;
        top: 50%;
        transform: translateY(-50%);
        border: 0;
        background: transparent;
        color: #6b7280;
        padding: 0.25rem;
    }
</style>
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h3 class="fw-bold text-dark mb-0">จัดการข้อมูลผู้ดูแลระบบ</h3>
                <p class="text-muted small">จัดการรายชื่อผู้ใช้งานระบบ กำหนดตำแหน่ง และสถานะการใช้งาน</p>
            </div>
            {{-- เฉพาะผู้บริหารเท่านั้นที่เพิ่มแอดมินใหม่ได้ --}}
            @if(Auth::guard('admin')->user()->role == 'ผู้บริหาร')
            <button class="btn btn-primary px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#insertAdminModal">
                <i class="bi bi-person-plus-fill me-1"></i> เพิ่มผู้ดูแลใหม่
            </button>
            @endif
        </div>
    </div>
    {{--  Form ครอบตารางเพื่อทำ Inline Search --}}
    <form method="GET" action="{{ route('admin.users_manage.show') }}" id="filterTableForm">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom-0 pt-3 pb-2 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 text-dark">รายชื่อผู้ใช้งานระบบ</h5>
                <a href="{{ route('admin.users_manage.show') }}" class="btn btn-light btn-sm border text-muted shadow-sm">
                    <i class="bi bi-eraser-fill"></i> ล้างการค้นหา
                </a>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-muted small">
                            <tr>
                                <th class="px-4 py-3" width="20%">บัญชีผู้ใช้งาน</th>
                                <th width="30%">ชื่อ-นามสกุล</th>
                                <th width="15%">ตำแหน่ง</th>
                                <th width="15%">สถานะ</th>
                                <th class="text-center px-4" width="20%">จัดการ</th>
                            </tr>

                            {{--  แถวสำหรับ Inline Filter --}}
                            <tr class="bg-white border-bottom shadow-sm">
                                <td class="px-3 py-2">
                                    <input type="text" name="filter_username" class="form-control form-control-sm bg-light border-0" 
                                        placeholder="บัญชีผู้ใช้งาน..." value="{{ request('filter_username') }}" 
                                        onkeydown="if(event.key === 'Enter') this.form.submit();">
                                </td>
                                <td class="py-2">
                                    <input type="text" name="filter_name" class="form-control form-control-sm bg-light border-0" 
                                        placeholder="ชื่อ-นามสกุล..." value="{{ request('filter_name') }}" 
                                        onkeydown="if(event.key === 'Enter') this.form.submit();">
                                </td>
                                <td class="py-2">
                                    <select name="filter_role" class="form-select form-select-sm bg-light border-0 text-muted" onchange="this.form.submit()">
                                        <option value="">- ทุกตำแหน่ง -</option>
                                        <option value="ผู้บริหาร" {{ request('filter_role') == 'ผู้บริหาร' ? 'selected' : '' }}>ผู้บริหาร</option>
                                        <option value="พนักงาน" {{ request('filter_role') == 'พนักงาน' ? 'selected' : '' }}>พนักงาน</option>
                                    </select>
                                </td>
                                <td class="py-2">
                                    <select name="filter_status" class="form-select form-select-sm bg-light border-0 text-muted" onchange="this.form.submit()">
                                        <option value="">- ทุกสถานะ -</option>
                                        <option value="ใช้งาน" class="text-success" {{ request('filter_status') == 'ใช้งาน' ? 'selected' : '' }}>ใช้งาน</option>
                                        <option value="ระงับใช้งาน" class="text-danger" {{ request('filter_status') == 'ระงับใช้งาน' ? 'selected' : '' }}>ระงับใช้งาน</option>
                                    </select>
                                </td>
                                <td class="py-2 text-center">
                                    <button type="submit" class="d-none">Search</button>
                                </td>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($admins as $admin)
                            <tr>
                                <td class="px-4 fw-bold text-dark">{{ $admin->username }}</td>
                                <td>{{ $admin->firstname }} {{ $admin->lastname }}</td>
                                <td>
                                    <span class="badge {{ $admin->role == 'ผู้บริหาร' ? 'bg-info text-dark' : 'bg-primary' }} rounded-pill px-3">
                                        {{ $admin->role }}
                                    </span>
                                </td>
                                <td>
                                    @if($admin->status == 'ใช้งาน')
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success"><i class="bi bi-check-circle-fill me-1"></i> ใช้งาน</span>
                                    @else
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger"><i class="bi bi-x-circle-fill me-1"></i> ระงับใช้งาน</span>
                                    @endif
                                </td>
                                <td class="text-center px-4">
                                    <button type="button" class="btn btn-light border btn-sm text-warning shadow-sm me-1" title="แก้ไข"
                                        onclick="openEditAdminModal({{ json_encode($admin) }})">
                                        <i class="bi bi-pencil-square"></i> แก้ไข
                                    </button>
                                    
                                    @if(Auth::guard('admin')->id() !== $admin->id)
                                        {{--  ปุ่มลบส่ง URL ไปให้ JS ทำงาน --}}
                                        {{-- <button type="button" class="btn btn-light border btn-sm text-danger shadow-sm px-2" title="ลบ" 
                                                onclick="confirmDeleteAdmin('{{ route('admin.users_manage.delete', $admin->id) }}')">
                                            <i class="bi bi-trash"></i> ลบ
                                        </button> --}}
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>

                    @if($admins->isEmpty())
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-person-x fs-1"></i>
                            <p class="mt-2">ไม่พบข้อมูลผู้ดูแลระบบ</p>
                        </div>
                    @endif

                    <div class="p-3 bg-white border-top">{{ $admins->links() }}</div>
                </div>
            </div>
        </div>
    </form>
</div>

{{-- MODAL INSERT ADMIN --}}
<div class="modal fade" id="insertAdminModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg admin-user-modal">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white">
                <div>
                    <h5 class="modal-title fw-bold mb-0">เพิ่มผู้ดูแลใหม่</h5>
                    <small class="text-white-50">กรอกข้อมูลบัญชีสำหรับเข้าใช้งานระบบ</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.users_manage.insert') }}" method="POST" id="insertAdminForm">
                @csrf
                <div class="modal-body p-4">
                    <div class="form-section mb-3">
                        <div class="section-title">ข้อมูลบัญชี</div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">บัญชีผู้ใช้งาน</label>
                                <input type="text" name="username" autocomplete="off" class="form-control" placeholder="ชื่อสำหรับเข้าสู่ระบบ" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">ตำแหน่ง</label>
                                <select name="role" class="form-select">
                                    <option value="พนักงาน">พนักงาน</option>
                                    <option value="ผู้บริหาร">ผู้บริหาร</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">รหัสผ่าน</label>
                                <div class="password-input-wrapper">
                                    <input type="password" name="password" id="addPassword" autocomplete="new-password" class="form-control" placeholder="รหัสผ่านอย่างน้อย 6 ตัวอักษร" required minlength="6" style="padding-right: 45px;">
                                    <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('addPassword')" data-toggle="addPassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="addPasswordConfirm" class="form-label text-secondary">ยืนยันรหัสผ่านอีกครั้ง</label>
                                <div class="password-input-wrapper">
                                    <input type="password" name="password_confirmation" id="addPasswordConfirm" autocomplete="new-password"
                                           class="form-control" placeholder="ยืนยันรหัสผ่าน" required style="padding-right: 45px;">
                                    <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('addPasswordConfirm')" data-toggle="addPasswordConfirm">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="section-title">ข้อมูลส่วนตัว</div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">ชื่อจริง</label>
                                <input type="text" name="firstname" autocomplete="off" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">นามสกุล</label>
                                <input type="text" name="lastname" autocomplete="off" class="form-control" required>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-white border-0 px-4 pb-4">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-primary px-4" onclick="confirmInsert()">
                        <i class="bi bi-check2-circle me-1"></i> บันทึกข้อมูล
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL EDIT ADMIN --}}
<div class="modal fade" id="editAdminModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg admin-user-modal">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-warning">
                <div>
                    <h5 class="modal-title fw-bold mb-0">แก้ไขข้อมูลผู้ดูแล</h5>
                    <small class="text-dark text-opacity-75">ปรับข้อมูลสิทธิ์และสถานะของบัญชีผู้ใช้งาน</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editAdminForm" method="POST">
                @csrf @method('PUT')
                <div class="modal-body p-4">
                    <div class="form-section mb-3">
                        <div class="section-title">ข้อมูลผู้ใช้งาน</div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">ชื่อจริง</label>
                                <input type="text" name="firstname" id="edit_firstname" autocomplete="off" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">นามสกุล</label>
                                <input type="text" name="lastname" id="edit_lastname" autocomplete="off" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">ชื่อผู้ใช้งาน (username)</label>
                                <input type="text" name="username" id="edit_username" autocomplete="off" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">ตำแหน่ง</label>
                                <select name="role" id="edit_role" class="form-select">
                                    <option value="พนักงาน">พนักงาน</option>
                                    <option value="ผู้บริหาร">ผู้บริหาร</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">สถานะ</label>
                                <select name="status" id="edit_status" class="form-select">
                                    <option value="ใช้งาน">ใช้งาน</option>
                                    <option value="ระงับใช้งาน">ระงับใช้งาน</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="section-title">เปลี่ยนรหัสผ่าน (ถ้าต้องการ)</div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">รหัสผ่านใหม่</label>
                                <div class="password-input-wrapper">
                                    <input type="password" name="password" id="edit_password" autocomplete="new-password" class="form-control" placeholder="เว้นว่างหากไม่เปลี่ยน" style="padding-right: 45px;">
                                    <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('edit_password')" data-toggle="edit_password">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_password_confirm" class="form-label text-secondary">ยืนยันรหัสผ่านอีกครั้ง</label>
                                <div class="password-input-wrapper">
                                    <input type="password" name="password_confirmation" id="edit_password_confirm" autocomplete="new-password" class="form-control" placeholder="ยืนยันรหัสผ่าน" style="padding-right: 45px;">
                                    <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('edit_password_confirm')" data-toggle="edit_password_confirm">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="alert alert-info py-2 small mb-0">
                                    <i class="bi bi-info-circle me-1"></i> เว้นว่างรหัสผ่านหากไม่ต้องการเปลี่ยน
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-white border-0 px-4 pb-4">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-warning px-4 fw-bold" onclick="confirmUpdate()" id="btnUpdateUser">
                        <i class="bi bi-save me-1"></i> บันทึกการแก้ไข
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ฟอร์มสำหรับการลบ (Global Form) --}}
<form id="globalDeleteForm" method="POST" class="d-none">
    @csrf
</form>
@endsection

@push('scripts')
<script>
    function togglePasswordVisibility(inputId) {
        const input = document.getElementById(inputId);
        if (!input) return;

        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';

        const toggleBtn = document.querySelector(`[data-toggle="${inputId}"] i`);
        if (!toggleBtn) return;

        toggleBtn.classList.remove('fa-eye', 'fa-eye-slash');
        toggleBtn.classList.add(isPassword ? 'fa-eye-slash' : 'fa-eye');
    }

    // 1. ฟังก์ชันก่อนการ Insert (ตรวจสอบความถูกต้อง + Loading)
    function confirmInsert() {
        let form = document.getElementById('insertAdminForm');
        if (!form.reportValidity()) return; // เช็คว่ากรอกครบไหม ถ้าไม่ครบเบราว์เซอร์จะเตือนเอง

        Swal.fire({
            title: 'กำลังบันทึกข้อมูล...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });
        form.submit();
    }

    // 2. ฟังก์ชันเปิด Modal แก้ไข
    function openEditAdminModal(admin) {
        document.getElementById('edit_username').value = admin.username;
        document.getElementById('edit_firstname').value = admin.firstname;
        document.getElementById('edit_lastname').value = admin.lastname;
        document.getElementById('edit_role').value = admin.role;
        document.getElementById('edit_status').value = admin.status;

        let url = "{{ route('admin.users_manage.update', ':id') }}";
        url = url.replace(':id', admin.id);
        document.getElementById('editAdminForm').action = url;

        new bootstrap.Modal(document.getElementById('editAdminModal')).show();
    }

    // 3. ฟังก์ชันยืนยันการแก้ไข (ตรวจสอบความถูกต้อง + ยืนยัน + Loading)
    function confirmUpdate() {
        let form = document.getElementById('editAdminForm');
        if (!form.reportValidity()) return; // เช็ค HTML5 Required

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
                Swal.fire({
                    title: 'กำลังบันทึก...',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });
                form.submit();
            }
        });
    }

    // 4. ฟังก์ชันยืนยันการลบ (Global Form + Loading)
    function confirmDeleteAdmin(deleteUrl) {
        Swal.fire({
            title: 'ยืนยันการลบผู้ใช้งาน?',
            text: "ข้อมูลผู้ใช้งานจะถูกลบออกจากระบบถาวร!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'ตกลง, ลบเลย!',
            cancelButtonText: 'ยกเลิก',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'กำลังลบข้อมูล...',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });
                
                let form = document.getElementById('globalDeleteForm');
                form.action = deleteUrl;
                form.submit();
            }
        });
    }
</script>
@endpush