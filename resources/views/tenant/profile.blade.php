@extends('tenant.layout')

@section('title', 'ข้อมูลส่วนตัว')

@section('content')

<div class="container-xl px-2 px-md-4 py-3">

    {{-- Header --}}
    <div class="mb-4 d-flex justify-content-between align-items-end flex-wrap gap-3 animate-fade-in">
        <div>
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle rounded-pill px-3 py-2 fw-semibold small shadow-sm pulse-hover">
                    <i class="fa-regular fa-id-badge me-1"></i> ข้อมูลส่วนตัว
                </span>
            </div>
            <h2 class="fw-bolder mb-2 display-6">โปรไฟล์ผู้เช่า</h2>
            <p class="text-muted mb-0">ตรวจสอบข้อมูลส่วนตัวและรายละเอียดการเช่าห้องพักของคุณ</p>
        </div>
        
        @if($tenant->rental_contract)
        <a href="{{ asset('storage/' . $tenant->rental_contract) }}" target="_blank" class="btn btn-outline-danger shadow-sm rounded-pill px-4 fw-bold btn-glow">
            <i class="fa-solid fa-file-pdf me-2"></i> เปิดดูสัญญาเช่าของคุณ
        </a>
        @endif
    </div>

    @php
        // 1. จัดฟอร์แมตเลขบัตร ปชช
        $idCard = $tenant->id_card ?? '';
        $formattedIdCard = (strlen($idCard) == 13) 
            ? substr($idCard, 0, 1) . '-' . substr($idCard, 1, 4) . '-' . substr($idCard, 5, 5) . '-' . substr($idCard, 10, 2) . '-' . substr($idCard, 12, 1) 
            : ($idCard ?: '-');

        // 2. จัดฟอร์แมตเบอร์โทร
        $phone = $tenant->phone ?? '';
        $formattedPhone = (strlen($phone) == 10)
            ? substr($phone, 0, 3) . '-' . substr($phone, 3, 3) . '-' . substr($phone, 6, 4)
            : ($phone ?: 'ไม่มีหมายเลขโทรศัพท์');

        // 3. จัดเรียงที่อยู่
        $addressParts = [];
        if($tenant->address_no) $addressParts[] = "เลขที่ " . $tenant->address_no;
        if($tenant->moo) $addressParts[] = "หมู่ " . $tenant->moo;
        if($tenant->alley) $addressParts[] = "ซอย " . $tenant->alley;
        if($tenant->street) $addressParts[] = "ถนน " . $tenant->street;
        if($tenant->sub_district) $addressParts[] = "ต." . $tenant->sub_district;
        if($tenant->district) $addressParts[] = "อ." . $tenant->district;
        if($tenant->province) $addressParts[] = "จ." . $tenant->province;
        if($tenant->postal_code) $addressParts[] = $tenant->postal_code;
        $fullAddress = count($addressParts) > 0 ? implode(' ', $addressParts) : 'ไม่ได้ระบุที่อยู่';
    @endphp

    <div class="row g-4">

        {{-- Left Column --}}
        <div class="col-12 col-lg-4 animate-fade-in delay-1">
            <div class="card shadow-sm border-0 text-center rounded-4 overflow-hidden h-100 hover-lift">
                <div class="bg-primary bg-opacity-10" style="height: 92px;"></div>
                <div class="card-body px-4 pb-4" style="margin-top: -46px;">
                    <div class="mb-3">
                        <div class="rounded-circle bg-body shadow-sm d-flex align-items-center justify-content-center mx-auto border border-3 border-body" style="width: 100px; height: 100px;">
                            <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center w-100 h-100">
                                <i class="fa-solid fa-user text-primary floating-icon" style="font-size: 3rem;"></i>
                            </div>
                        </div>
                    </div>
                    <h4 class="fw-bold mb-1 text-body">{{ $tenant->first_name }} {{ $tenant->last_name }}</h4>
                    <div class="text-muted mb-3">{{ $tenant->age ?? '-' }} ปี</div>
                    
                    <div class="mb-4">
                        <span class="badge bg-success px-3 py-2 rounded-pill shadow-sm pulse-badge">
                            <i class="fa-solid fa-circle-check me-1"></i> ผู้เช่าปัจจุบัน
                        </span>
                    </div>
                    <hr class="opacity-25">
                    <div class="bg-body-tertiary rounded-4 p-3 border hover-scale-box">
                        <div class="small text-muted mb-1 fw-bold">ห้องพักของคุณ</div>
                        <h2 class="fw-bolder text-primary mb-0"><i class="fa-solid fa-door-closed"></i> {{ $tenant->room->room_number ?? '-' }}</h2>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Column --}}
        <div class="col-12 col-lg-8">
            <div class="card shadow-sm border-0 rounded-4 mb-4 hover-lift animate-fade-in delay-2">
                <div class="card-header bg-transparent border-bottom-0 pt-4 px-4">
                    <h5 class="mb-0 fw-bold"><i class="fa-solid fa-address-card me-2 text-info"></i> รายละเอียดข้อมูลส่วนบุคคล</h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="p-3 bg-light rounded-3 border hover-inner-shadow transition-all">
                                <label class="text-muted small fw-bold d-block mb-1">เลขประจำตัวประชาชน</label>
                                <div class="fw-bold fs-6">{{ $formattedIdCard }}</div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-3 bg-light rounded-3 border hover-inner-shadow transition-all">
                                <label class="text-muted small fw-bold d-block mb-1">เบอร์โทรศัพท์</label>
                                <div class="fw-bold fs-6">{{ $formattedPhone }}</div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-3 bg-light rounded-3 border hover-inner-shadow transition-all">
                                <label class="text-muted small fw-bold d-block mb-1">วันที่ออกบัตร / หมดอายุ</label>
                                <div class="fw-bold small">{{ $tenant->thai_id_card_issue_date }} - <span class="text-danger">{{ $tenant->thai_id_card_expiry_date }}</span></div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-3 bg-light rounded-3 border hover-inner-shadow transition-all">
                                <label class="text-muted small fw-bold d-block mb-1">สถานที่ออกบัตร</label>
                                <div class="fw-bold small">{{ $tenant->id_card_issue_place ?? '-' }} จ.{{ $tenant->id_card_issue_province ?? '-' }}</div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="p-3 bg-light rounded-3 border hover-inner-shadow transition-all">
                                <label class="text-muted small fw-bold d-block mb-1">สถานที่ทำงาน</label>
                                <div class="fw-bold">{{ $tenant->workplace ?? 'ไม่ระบุข้อมูล' }}</div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="p-3 bg-light rounded-3 border hover-inner-shadow transition-all">
                                <label class="text-muted small fw-bold d-block mb-1">ที่อยู่ตามทะเบียนบ้าน</label>
                                <div class="fw-bold">{{ $fullAddress }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ข้อมูลการเช่า --}}
            <div class="card shadow-sm border-0 rounded-4 hover-lift animate-fade-in delay-3">
                <div class="card-header bg-transparent border-bottom-0 pt-4 px-4">
                    <h5 class="mb-0 fw-bold"><i class="fa-solid fa-file-contract me-2 text-success"></i> ข้อมูลสัญญาเช่า</h5>
                </div>
                <div class="card-body p-4">
                    <ul class="list-group list-group-flush mb-0 custom-list-hover">
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <span class="text-muted fw-semibold">ราคาเช่าห้องพัก (ต่อเดือน)</span>
                            <span class="fw-bolder text-success fs-5">฿{{ number_format($tenant->room->price ?? 0, 2) }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <span class="text-muted fw-semibold">เงินมัดจำแรกเข้า</span>
                            <span class="fw-bold text-primary fs-5">฿{{ number_format($tenant->deposit_amount ?? 0, 2) }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <span class="text-muted fw-semibold">วันที่เริ่มเช่า</span>
                            <span class="fw-bold">{{ $tenant->thai_start_date }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <span class="text-muted fw-semibold">วันที่สิ้นสุดสัญญา</span>
                            <span class="fw-bold text-danger">{{ $tenant->thai_end_date }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    {{-- Tips Section --}}
    <div class="card shadow-sm border-0 mt-4 rounded-4 overflow-hidden hover-lift animate-fade-in delay-3">
        <div class="card-body p-4 position-relative">
            <i class="fa-solid fa-lightbulb text-primary opacity-10 position-absolute" style="font-size: 8rem; right: -20px; bottom: -20px;"></i>
            
            <div class="d-flex align-items-start gap-3 position-relative z-1">
                <div class="flex-shrink-0 bg-body p-2 rounded-circle shadow-sm border border-secondary-subtle">
                    <i class="fa-solid fa-lightbulb text-warning floating-icon" style="font-size: 1.5rem; width: 1.5rem; text-align: center;"></i>
                </div>
                <div class="flex-grow-1 pt-1">
                    <h6 class="fw-bold mb-2 text-body">เคล็ดลับการใช้งานระบบสำหรับผู้เช่า</h6>
                    <ul class="mb-0 text-body-secondary small lh-lg" style="padding-left: 1.2rem;">
                        <li>ระบบจะสร้าง <strong>บิลค่าเช่า</strong> ใหม่ในทุกๆ สิ้นเดือน สามารถดูและแจ้งโอนได้ที่เมนู "บิลค่าเช่า"</li>
                        <li>หากพบสิ่งของชำรุดภายในห้องพัก สามารถส่งรูปภาพและ <strong>แจ้งซ่อม</strong> ผ่านระบบได้ทันที</li>
                        <li>กรุณาชำระค่าเช่าภายในวันที่กำหนด เพื่อหลีกเลี่ยงค่าปรับล่าช้า</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

</div>

<style>
    /* การตั้งค่าพื้นฐาน */
    .tracking-wide { letter-spacing: 0.5px; }
    .transition-all { transition: all 0.3s ease; }

    /* Hover Lift Effect สำหรับการ์ด */
    .hover-lift { transition: transform 0.3s ease, box-shadow 0.3s ease; }
    .hover-lift:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 20px rgba(0, 0, 0, 0.08) !important;
    }

    /* Inner Shadow ตอนเอาเมาส์ชี้กล่องข้อมูล */
    .hover-inner-shadow:hover {
        background-color: #ffffff !important;
        box-shadow: inset 0 0 0 1px #0d6efd, 0 4px 8px rgba(13, 110, 253, 0.1) !important;
        transform: scale(1.01);
    }

    /* Scale Box Effect สำหรับกล่องข้อมูลย่อย */
    .hover-scale-box { transition: all 0.3s ease; }
    .hover-scale-box:hover {
        transform: scale(1.03);
        background-color: #fff !important;
        border-color: #0d6efd !important;
        box-shadow: 0 4px 10px rgba(13, 110, 253, 0.1);
    }

    /* Hover List Effect สำหรับรายการสัญญา */
    .custom-list-hover .list-group-item {
        transition: all 0.25s ease;
        border-left: 3px solid transparent;
    }
    .custom-list-hover .list-group-item:hover {
        background-color: #f8f9fa;
        border-left: 3px solid #198754; /* สีเขียว */
        padding-left: 1rem !important;
    }

    /* Button Glow Effect สำหรับปุ่ม PDF */
    .btn-glow { transition: all 0.3s ease; }
    .btn-glow:hover {
        box-shadow: 0 0 15px rgba(220, 53, 69, 0.4) !important;
        transform: translateY(-2px);
    }

    /* Floating Icon Animation */
    @keyframes float {
        0% { transform: translateY(0px); }
        50% { transform: translateY(-8px); }
        100% { transform: translateY(0px); }
    }
    .floating-icon {
        animation: float 3s ease-in-out infinite;
    }

    /* Pulse Badge Animation */
    @keyframes soft-pulse {
        0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(25, 135, 84, 0.4); }
        70% { transform: scale(1.05); box-shadow: 0 0 0 8px rgba(25, 135, 84, 0); }
        100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(25, 135, 84, 0); }
    }
    .pulse-badge {
        animation: soft-pulse 2s infinite;
        display: inline-block;
    }

    /* Fade In Up Animations ตอนโหลดหน้า */
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-fade-in {
        opacity: 0;
        animation: fadeInUp 0.6s ease-out forwards;
    }
    .delay-1 { animation-delay: 0.1s; }
    .delay-2 { animation-delay: 0.2s; }
    .delay-3 { animation-delay: 0.3s; }
</style>

@endsection