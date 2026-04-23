@extends('admin.layout')

@section('title', 'ตั้งค่ารายการค่าใช้จ่าย')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h3 class="fw-bold text-dark mb-0">ตั้งค่ารายการค่าใช้จ่าย</h3>
                <p class="text-muted small">กำหนดราคามาตรฐานสำหรับค่าเช่าห้อง ค่าน้ำ ค่าไฟ และค่าบริการอื่น ๆ</p>
            </div>
            <button class="btn btn-primary px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#insertExpenseModal">
                <i class="bi bi-plus-circle-fill me-1"></i> เพิ่มรายการใหม่
            </button>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="px-4" style="width: 80px;">ลำดับ</th>
                            <th>หมวดหมู่บัญชี (รายรับ) </th>
                            <th>ชื่อรายการ</th>
                            <th>ราคาต่อหน่วย</th>
                            <th>วันที่แก้ไขล่าสุด</th>
                            <th class="text-center px-4">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($expenses as $index => $item)
                        <tr>
                            <td class="px-4">{{ $index + 1 }}</td>
                            <td>
                                <span class="badge bg-info text-dark">
                                    {{ $item->category->name ?? 'ยังไม่ระบุ' }}
                                </span>
                            </td>
                            <td>
                                <div class="fw-bold text-dark">{{ $item->name }}</div>
                            </td>
                            <td>
                                <span class="text-primary fw-bold">{{ number_format($item->price, 2) }}</span> 
                                <span class="text-muted small">บาท</span>
                            </td>
                            <td class="small text-muted">
                                {{ $item->updated_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="text-center px-4">
                                {{-- ปุ่มแก้ไข --}}
                                <button class="btn btn-outline-warning btn-sm me-1" 
                                    onclick="openEditExpenseModal({{ json_encode($item) }})">
                                    <i class="bi bi-pencil-square"></i> แก้ไข
                                </button>

                                {{-- ปุ่มลบ --}}
                                {{-- <form action="{{ route('admin.tenant_expenses.delete', $item->id) }}" method="POST" class="d-inline" id="delete-expense-{{ $item->id }}">
                                    @csrf 
                                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="confirmDeleteExpense({{ $item->id }}, '{{ $item->name }}')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form> --}}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="p-3">{{ $expenses->links() }}</div>
            </div>
        </div>
    </div>
</div>

{{-- MODAL INSERT --}}
<div class="modal fade" id="insertExpenseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold">เพิ่มรายการค่าใช้จ่าย</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.tenant_expenses.insert') }}" method="POST" id="insertExpenseForm">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">หมวดหมู่บัญชี (รายรับ) *</label>
                        <select name="accounting_category_id" id="insert_accounting_category_id" class="form-select" required>
                            <option value="">-- เลือกหมวดหมู่รายรับ --</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">เลือกเพื่อให้ระบบลงบัญชีรายรับอัตโนมัติเมื่อผู้เช่าจ่ายเงิน</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">ชื่อรายการ *</label>
                        <input type="text" name="name" class="form-control" placeholder="เช่น ค่าน้ำ, ค่าไฟ, ค่าปรับ" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">ราคา (ต่อหน่วย หรือ ยอดรวม) *</label>
                        <div class="input-group">
                            <input type="number" name="price" class="form-control" step="0.01" placeholder="0.00" required>
                            <span class="input-group-text">บาท</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-primary px-4 shadow-sm" onclick="confirmInsert()">บันทึกข้อมูล</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL EDIT --}}
<div class="modal fade" id="editExpenseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning">
                <h5 class="modal-title fw-bold">แก้ไขรายการค่าใช้จ่าย</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editExpenseForm" method="POST">
                @csrf @method('PUT')
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">หมวดหมู่บัญชี (รายรับ) *</label>
                        <select name="accounting_category_id" id="edit_accounting_category_id" class="form-select" required>
                            <option value="">-- เลือกหมวดหมู่รายรับ --</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">ชื่อรายการ</label>
                        <input type="text" name="name" id="edit_expense_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">ราคา</label>
                        <div class="input-group">
                            <input type="number" name="price" id="edit_expense_price" class="form-control" step="0.01" required>
                            <span class="input-group-text">บาท</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" onclick="confirmUpdate()" id="btnUpdate" class="btn btn-warning px-4 fw-bold shadow-sm">บันทึกการแก้ไข</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function confirmInsert() {
        let form = document.getElementById('insertExpenseForm'); // ต้องเพิ่ม id ให้ฟอร์มด้วย
        if (!form.reportValidity()) return; // ดัก Required ก่อน

        Swal.fire({
            title: 'กำลังบันทึกข้อมูล...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });
        form.submit();
    }
    function openEditExpenseModal(item) {
        document.getElementById('edit_expense_name').value = item.name;
        document.getElementById('edit_expense_price').value = item.price;
        
        document.getElementById('edit_accounting_category_id').value = item.accounting_category_id || "";

        let url = "{{ route('admin.tenant_expenses.update', ':id') }}";
        url = url.replace(':id', item.id);
        document.getElementById('editExpenseForm').action = url;

        new bootstrap.Modal(document.getElementById('editExpenseModal')).show();
    }
    function confirmUpdate() {
        Swal.fire({
            title: 'ยืนยันการแก้ไขข้อมูล?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            confirmButtonText: 'บันทึก',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                const btn = document.getElementById('btnUpdate');
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>';
                document.getElementById('editExpenseForm').submit();
            }
        });
    }

    function confirmDeleteExpense(id, name) {
        Swal.fire({
            title: 'ยืนยันการลบรายการ?',
            text: "คุณกำลังจะลบรายการ '" + name + "' ข้อมูลที่เกี่ยวข้องในใบแจ้งหนี้เก่าอาจได้รับผลกระทบ!",
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
                document.getElementById('delete-expense-' + id).submit();
            }
        });
    }
</script>
@endpush