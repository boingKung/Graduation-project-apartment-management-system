@extends('admin.layout')

@section('title', 'แก้ไขข้อมูลอพาร์ทเม้นท์')

@section('content')
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold text-primary"><i class="bi bi-pencil-square me-2"></i>แก้ไขข้อมูลอพาร์ทเม้นท์
                        </h5>
                    </div>
                    <div class="card-body p-5">
                        <form action="{{ route('admin.apartment.update', $apartment->id) }}" method="POST"
                            id="editApartmentForm">
                            @csrf
                            @method('PUT')

                            <div class="row g-3">
                                <div class="col-md-12 mb-3">
                                    <label class="form-label fw-bold">ชื่ออพาร์ทเม้นท์</label>
                                    <input type="text" name="name" class="form-control"
                                        value="{{ old('name', $apartment->name) }}" required>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">เลขที่</label>
                                    <input type="text" name="address_no" class="form-control"
                                        value="{{ old('address_no', $apartment->address_no) }}">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">หมู่ที่</label>
                                    <input type="text" name="moo" class="form-control"
                                        value="{{ old('moo', $apartment->moo) }}">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">รหัสไปรษณีย์</label>
                                    <input type="text" name="postal_code" class="form-control"
                                        value="{{ old('postal_code', $apartment->postal_code) }}">
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">ตำบล/แขวง</label>
                                    <input type="text" name="sub_district" class="form-control"
                                        value="{{ old('sub_district', $apartment->sub_district) }}">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">อำเภอ/เขต</label>
                                    <input type="text" name="district" class="form-control"
                                        value="{{ old('district', $apartment->district) }}">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">จังหวัด</label>
                                    <input type="text" name="province" class="form-control"
                                        value="{{ old('province', $apartment->province) }}">
                                </div>

                                <div class="col-md-6 mb-4">
                                    <label class="form-label fw-bold text-primary">เบอร์โทรศัพท์ติดต่อ</label>
                                    <input type="text" name="phone" class="form-control "
                                        value="{{ old('phone', $apartment->phone) }}">
                                </div>

                                {{-- 🌟 ส่วนที่เพิ่มใหม่: ฟอร์มตั้งค่าบิลและค่าปรับ --}}
                                <div class="col-12 mt-2">
                                    <h6 class="fw-bold text-danger mb-3 border-bottom pb-2">
                                        <i class="bi bi-receipt me-2"></i>ตั้งค่าระบบบิลและค่าปรับล่าช้า
                                    </h6>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">วันที่ครบกำหนดชำระ <span
                                            class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light">วันที่</span>
                                        <input type="number" name="invoice_due_day"
                                            class="form-control text-center fw-bold text-primary"
                                            value="{{ old('invoice_due_day', $apartment->invoice_due_day ?? 5) }}"
                                            min="1" max="30" required>
                                        <span class="input-group-text bg-light">ของเดือน</span>
                                    </div>
                                    <small class="text-muted">ใส่ตัวเลข 1 - 30</small>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">ระยะเวลาคิดค่าปรับ <span
                                            class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" name="late_fee_grace_days"
                                            class="form-control text-center fw-bold text-danger"
                                            value="{{ old('late_fee_grace_days', $apartment->late_fee_grace_days ?? 15) }}"
                                            min="0" required>
                                        <span class="input-group-text bg-light">วัน</span>
                                    </div>
                                    <small class="text-muted">จำนวนวันที่โดนปรับ</small>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">จำกัดผู้พักอาศัยฟรี <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" name="free_resident_limit"
                                            class="form-control text-center fw-bold text-success"
                                            value="{{ old('free_resident_limit', $apartment->free_resident_limit ?? 2) }}"
                                            min="1" required>
                                        <span class="input-group-text bg-light">คน/ห้อง</span>
                                    </div>
                                    <small class="text-muted">จำนวนคนสูงสุดที่อยู่ได้โดยไม่คิดเงินเพิ่ม</small>
                                </div>
                            </div>

                            <hr>
                            <div class="d-flex justify-content-end gap-2">
                                <a href="{{ route('admin.apartment.show') }}" class="btn btn-light px-4">ยกเลิก</a>
                                <button type="button" id="btnSubmitUpdate" class="btn btn-primary px-5"
                                    onclick="confirmUpdate()">บันทึกการเปลี่ยนแปลง</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script>
        function confirmUpdate() {
            Swal.fire({
                title: 'ยืนยันการแก้ไข?',
                text: "คุณต้องการบันทึกการเปลี่ยนแปลงข้อมูลอพาร์ทเม้นท์ใช่หรือไม่",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'ใช่, บันทึกเลย!',
                cancelButtonText: 'ยกเลิก',
                reverseButtons: true // ให้ปุ่มยกเลิกอยู่ซ้าย ปุ่มยืนยันอยู่ขวา
            }).then((result) => {
                if (result.isConfirmed) {
                    const btn = document.getElementById('btnSubmitUpdate');
                    btn.disabled = true;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> กำลังบันทึก...';
                    // ถ้ากดยืนยัน ให้ส่งฟอร์ม
                    document.getElementById('editApartmentForm').submit();
                }
            })
        }
    </script>
@endpush
