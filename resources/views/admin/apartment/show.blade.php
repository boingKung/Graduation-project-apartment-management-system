@extends('admin.layout')

@section('title', 'ข้อมูลอพาร์ทเม้นท์')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-10 mb-4">
            <h3 class="fw-bold text-dark">ตั้งค่าข้อมูลอพาร์ทเม้นท์</h3>
            <p class="text-muted small">จัดการข้อมูลเบื้องต้นของที่พักสำหรับแสดงผลในระบบใบแจ้งหนี้และหน้าผู้เช่า</p>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
                    <h5 class="mb-0 fw-bold text-primary">
                        <i class="bi bi-info-circle me-2"></i>รายละเอียดอพาร์ทเม้นท์
                    </h5>
                    {{-- ปุ่มแก้ไขที่เตรียมไว้สำหรับอนาคต --}}
                    <a href="{{ route('admin.apartment.edit', $apartment->id) }}" class="btn btn-warning btn-sm px-4 shadow-sm fw-bold">
                        <i class="bi bi-pencil-square me-1"></i> แก้ไขข้อมูล
                    </a>
                </div>
                <div class="card-body p-5">
                    {{-- แสดงข้อมูลตามโครงสร้างตาราง apartment --}}
                    <div class="row g-4">
                        <div class="col-md-12">
                            <label class="text-muted small mb-1">ชื่ออพาร์ทเม้นท์</label>
                            <p class="fw-bold fs-4 mb-0 text-dark border-bottom pb-2">
                                {{ $apartment->name ?? 'อาทิตย์ อพาร์ทเม้นท์' }}
                            </p>
                        </div>

                        <div class="col-md-4">
                            <label class="text-muted small mb-1">เลขที่</label>
                            <p class="mb-0 border-bottom pb-2">{{ $apartment->address_no ?? '199' }}</p>
                        </div>
                        <div class="col-md-4">
                            <label class="text-muted small mb-1">หมู่ที่</label>
                            <p class="mb-0 border-bottom pb-2">{{ $apartment->moo ?? '4' }}</p>
                        </div>
                        <div class="col-md-4">
                            <label class="text-muted small mb-1">รหัสไปรษณีย์</label>
                            <p class="mb-0 border-bottom pb-2">{{ $apartment->postal_code ?? '13170' }}</p>
                        </div>

                        <div class="col-md-4">
                            <label class="text-muted small mb-1">ตำบล/แขวง</label>
                            <p class="mb-0 border-bottom pb-2">{{ $apartment->sub_district ?? 'บ้านสร้าง' }}</p>
                        </div>
                        <div class="col-md-4">
                            <label class="text-muted small mb-1">อำเภอ/เขต</label>
                            <p class="mb-0 border-bottom pb-2">{{ $apartment->district ?? 'บางปะอิน' }}</p>
                        </div>
                        <div class="col-md-4">
                            <label class="text-muted small mb-1">จังหวัด</label>
                            <p class="mb-0 border-bottom pb-2">{{ $apartment->province ?? 'พระนครศรีอยุธยา' }}</p>
                        </div>

                        <div class="col-md-6">
                            <label class="text-muted small mb-1">เบอร์โทรศัพท์ติดต่อ</label>
                            <p class="mb-0 border-bottom pb-2 text-primary fw-bold fs-5">
                                <i class="bi bi-telephone-fill me-2 small"></i>{{ $apartment->phone ?? '092-969 4070' }}
                            </p>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="text-muted small mb-1">บันทึกข้อมูลระบบเมื่อ</label>
                            <p class="mb-0 border-bottom pb-2 text-muted small">
                                <i class="bi bi-clock-history me-2"></i>{{ $apartment->created_at ?? '-' }}
                            </p>
                        </div>

                        {{-- 🌟 ส่วนที่เพิ่มใหม่: ตั้งค่าระบบบิลและค่าปรับ --}}
                        <div class="col-12 mt-5">
                            <h6 class="fw-bold text-danger mb-3 border-bottom pb-2">
                                <i class="bi bi-receipt me-2"></i>ตั้งค่าระบบบิลและค่าปรับล่าช้า
                            </h6>
                            <div class="row g-4">
                                <div class="col-md-4">
                                    <label class="text-muted small mb-1">วันที่ครบกำหนดชำระ</label>
                                    <p class="mb-0 border-bottom pb-2 fw-bold text-dark">
                                        วันที่ <span class="text-primary fs-5">{{ $apartment->invoice_due_day ?? 5 }}</span> ของทุกเดือน
                                    </p>
                                </div>
                                <div class="col-md-4">
                                    <label class="text-muted small mb-1">ระยะเวลาคิดค่าปรับ</label>
                                    <p class="mb-0 border-bottom pb-2 fw-bold text-dark">
                                        <span class="text-danger fs-5">{{ $apartment->late_fee_grace_days ?? 15 }}</span> วัน
                                    </p>
                                </div>
                                <div class="col-md-4">
                                    <label class="text-muted small mb-1">จำนวนผู้พักอาศัยฟรีสูงสุด</label>
                                    <p class="mb-0 border-bottom pb-2 fw-bold text-dark">
                                        <span class="text-success fs-5">{{ $apartment->free_resident_limit ?? 2 }}</span> คน / ห้อง
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-light py-3 border-0 text-center">
                    <small class="text-muted italic">** ข้อมูลนี้เป็นข้อมูลส่วนกลางของระบบ ไม่สามารถลบได้ **</small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection