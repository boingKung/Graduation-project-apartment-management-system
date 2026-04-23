@extends('admin.layout')
@section('title', 'ยืนยันอนุมัติผู้เช่า')

@section('content')

    <div class="container-xl py-4">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card shadow-sm border-0 rounded-4">
                    {{-- 🌟 เปลี่ยนธีมเป็น bg-success --}}
                    <div class="card-header bg-success bg-gradient text-white border-0 p-4 rounded-top-4">
                        <div class="d-flex justify-content-between align-items-start">

                            <div class="d-flex align-items-center">
                                @php
                                    $backUrl = $selectedRoom && isset($selectedRoom->roomPrice->building_id)
                                        ? route('admin.rooms.system', ['building_id' => $selectedRoom->roomPrice->building_id])
                                        : route('admin.rooms.system');
                                @endphp

                                <a href="{{ $backUrl }}"
                                    class="btn btn-light text-success rounded-circle me-3 shadow-sm d-flex align-items-center justify-content-center"
                                    style="width: 45px; height: 45px; transition: 0.2s;" title="กลับไปผังห้อง">
                                    <i class="bi bi-arrow-left fs-5"></i>
                                </a>

                                <div>
                                    <h4 class="fw-bold mb-1">
                                        <i class="bi bi-check-circle-fill me-2"></i>ยืนยันอนุมัติผู้เช่า (จากออนไลน์)
                                    </h4>
                                    <p class="text-white-50 small mb-0">ตรวจสอบความถูกต้องของข้อมูล (ระบบจะสร้างสัญญา PDF ให้อัตโนมัติ)
                                    </p>
                                </div>
                            </div>

                            @if ($selectedRoom)
                                <div class="text-end">
                                    <div class="badge bg-white text-success px-3 py-2 fs-6 shadow-sm rounded-pill">
                                        <i class="bi bi-door-closed-fill me-1"></i> ห้อง {{ $selectedRoom->room_number }}
                                        <span class="fw-normal ms-1">({{ number_format($selectedRoom->price ?? 0, 0) }} บ./เดือน)</span>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="card-body p-4 bg-light bg-opacity-25">
                        
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form action="{{ route('admin.tenants.approve', $tenant->id) }}" method="POST" enctype="multipart/form-data" id="approveTenantForm" novalidate>
                            @csrf
                            <input type="hidden" name="room_id" value="{{ $selectedRoom->id }}">

                            {{-- Section 1: ข้อมูลห้องพัก (แบบเดียวกับ Create แต่ฟิกซ์ค่าไว้) --}}
                            <div class="card border-0 shadow-sm mb-4">
                                <div class="card-body">
                                    <h6 class="fw-bold text-success mb-3 border-bottom pb-2">1. ข้อมูลการเช่า</h6>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">ห้องพัก (เลือกแล้ว)</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control bg-light"
                                                    value="ห้อง {{ $selectedRoom->room_number }}" readonly>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">จำนวนผู้อยู่อาศัย <span class="text-danger">*</span></label>
                                            <input type="number" name="resident_count" class="form-control"
                                                value="{{ old('resident_count', $tenant->resident_count) }}" min="1" required>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Section 2: ข้อมูลส่วนตัว --}}
                            <div class="card border-0 shadow-sm mb-4">
                                <div class="card-body">
                                    <h6 class="fw-bold text-success mb-3 border-bottom pb-2">2. ข้อมูลส่วนบุคคล</h6>
                                    <div class="row g-3">
                                        <div class="col-md-5">
                                            <label class="form-label fw-bold">ชื่อ <span class="text-danger">*</span></label>
                                            <input type="text" name="first_name" class="form-control"
                                                value="{{ old('first_name', $tenant->first_name) }}" required>
                                        </div>
                                        <div class="col-md-5">
                                            <label class="form-label fw-bold">นามสกุล <span class="text-danger">*</span></label>
                                            <input type="text" name="last_name" class="form-control"
                                                value="{{ old('last_name', $tenant->last_name) }}" required>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label fw-bold">อายุ <span class="text-danger">*</span></label>
                                            <input type="number" name="age" class="form-control"
                                                value="{{ old('age', $tenant->age) }}" min="1" required>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">เลขบัตรประชาชน <span class="text-danger">*</span></label>
                                            <input type="text" name="id_card" class="form-control input-idcard"
                                                value="{{ old('id_card', $tenant->id_card) }}" placeholder="x-xxxx-xxxxx-xx-x">
                                            @error('id_card')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">เบอร์โทรศัพท์ <span class="text-danger">*</span></label>
                                            <input type="text" name="phone" class="form-control input-phone"
                                                value="{{ old('phone', $tenant->phone) }}" placeholder="xxx-xxx-xxxx" required>
                                            @error('phone')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">ออกบัตรเมื่อวันที่</label>
                                            <input type="date" name="id_card_issue_date" class="form-control"
                                                value="{{ old('id_card_issue_date', $tenant->id_card_issue_date ? \Carbon\Carbon::parse($tenant->id_card_issue_date)->format('Y-m-d') : '') }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">บัตรหมดอายุวันที่</label>
                                            <input type="date" name="id_card_expiry_date" class="form-control"
                                                value="{{ old('id_card_expiry_date', $tenant->id_card_expiry_date ? \Carbon\Carbon::parse($tenant->id_card_expiry_date)->format('Y-m-d') : '') }}">
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">สถานที่ออกบัตร (ณ)</label>
                                            <input type="text" name="id_card_issue_place" class="form-control"
                                                value="{{ old('id_card_issue_place', $tenant->id_card_issue_place) }}"
                                                placeholder="เช่น ที่ว่าการอำเภอเมือง...">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">จังหวัดที่ออกบัตร</label>
                                            <input type="hidden" id="old_issue_prov" value="{{ old('id_card_issue_province', $tenant->id_card_issue_province) }}">
                                            <select name="id_card_issue_province" id="id_card_issue_province" class="form-select">
                                                <option value="">กำลังโหลด...</option>
                                            </select>
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label fw-bold">สถานที่ทำงาน</label>
                                            <input type="text" name="workplace" class="form-control"
                                                value="{{ old('workplace', $tenant->workplace) }}">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Section 3: ที่อยู่ตามทะเบียนบ้าน --}}
                            <div class="card border-0 shadow-sm mb-4">
                                <div class="card-body">
                                    <h6 class="fw-bold text-success mb-3 border-bottom pb-2">3. ที่อยู่ตามทะเบียนบ้าน</h6>
                                    <div class="row g-3">
                                        <div class="col-md-3">
                                            <label class="form-label fw-bold">เลขที่ <span class="text-danger">*</span></label>
                                            <input type="text" name="address_no" class="form-control"
                                                value="{{ old('address_no', $tenant->address_no) }}" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-bold">หมู่ที่ <span class="text-danger">*</span></label>
                                            <input type="text" name="moo" class="form-control"
                                                value="{{ old('moo', $tenant->moo) }}" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-bold">ตรอก/ซอย</label>
                                            <input type="text" name="alley" class="form-control"
                                                value="{{ old('alley', $tenant->alley) }}">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-bold">ถนน</label>
                                            <input type="text" name="street" class="form-control"
                                                value="{{ old('street', $tenant->street) }}">
                                        </div>

                                        <input type="hidden" id="old_prov" value="{{ old('province', $tenant->province) }}">
                                        <input type="hidden" id="old_dist" value="{{ old('district', $tenant->district) }}">
                                        <input type="hidden" id="old_sub" value="{{ old('sub_district', $tenant->sub_district) }}">

                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">จังหวัด <span class="text-danger">*</span></label>
                                            <select name="province" id="add_province" class="form-select" required>
                                                <option value="">กำลังโหลด...</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">อำเภอ/เขต <span class="text-danger">*</span></label>
                                            <select name="district" id="add_district" class="form-select" disabled required>
                                                <option value="">-- เลือกจังหวัดก่อน --</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">ตำบล/แขวง <span class="text-danger">*</span></label>
                                            <select name="sub_district" id="add_sub_district" class="form-select" disabled required>
                                                <option value="">-- เลือกอำเภอก่อน --</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">รหัสไปรษณีย์ <span class="text-danger">*</span></label>
                                            <input type="text" name="postal_code" id="add_postal_code"
                                                class="form-control bg-light" readonly
                                                placeholder="เลือกตำบลเพื่อเติมอัตโนมัติ" value="{{ old('postal_code', $tenant->postal_code) }}" required>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- 🌟 Section 4: สัญญาและค่าใช้จ่าย (แก้ไขสลิปตามหน้า Create) --}}
                            <div class="card border-0 shadow-sm mb-4">
                                <div class="card-body">
                                    <h6 class="fw-bold text-success mb-3 border-bottom pb-2">4. สัญญาและค่าใช้จ่าย <span class="text-danger">ระบบสร้างไฟล์ PDF อัตโนมัติ</span> </h6>
                                    <div class="row g-3">
                                        <div class="col-md-3">
                                            <label class="form-label fw-bold">วันที่เริ่มเช่า <span class="text-danger">*</span></label>
                                            <input type="date" name="start_date" class="form-control"
                                                value="{{ old('start_date', date('Y-m-d')) }}" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-bold">วันที่สิ้นสุด (ถ้ามี)</label>
                                            <input type="date" name="end_date" class="form-control"
                                                value="{{ old('end_date') }}">
                                        </div>

                                        <div class="col-12 mt-4">
                                            <h6 class="fw-bold text-dark mb-2"><i class="bi bi-cash-coin me-2 text-success"></i>การชำระเงินมัดจำ (แก้ไขได้)</h6>
                                        </div>

                                        <div class="col-md-3">
                                            <label class="form-label fw-bold">วันที่รับเงินมัดจำ <span class="text-danger">*</span></label>
                                            <input type="date" name="deposit_date" class="form-control" 
                                                value="{{ old('deposit_date', date('Y-m-d')) }}" required>
                                            <div class="form-text small">วันที่เงินเข้าบัญชีหรือรับเงินสด</div>
                                        </div>
                                        
                                        {{-- 🌟 แก้ไขให้เปลี่ยนตัวเลข, เปลี่ยนวิธีชำระ, และเปลี่ยนรูปได้ --}}
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold">เงินมัดจำ (บาท) <span class="text-danger">*</span></label>
                                            <input type="number" name="deposit_amount" id="deposit_amount"
                                                class="form-control text-success fw-bold"
                                                value="{{ old('deposit_amount', $tenant->deposit_amount) }}" min="1" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold">ช่องทางชำระมัดจำ <span class="text-danger">*</span></label>
                                            <select name="deposit_payment_method" class="form-select" required>
                                                <option value="เงินสด" {{ old('deposit_payment_method', $tenant->deposit_payment_method) == 'เงินสด' ? 'selected' : '' }}>เงินสด</option>
                                                <option value="โอนผ่านธนาคาร" {{ old('deposit_payment_method', $tenant->deposit_payment_method) == 'โอนผ่านธนาคาร' ? 'selected' : '' }}>โอนผ่านธนาคาร</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold">แนบสลิปมัดจำ (ถ้ามี)</label>
                                            <input type="file" name="deposit_slip" id="slipInput" class="form-control" accept="image/*" onchange="previewSlip(event)">
                                        </div>

                                        {{-- 🌟 ส่วนแสดงตัวอย่างรูปสลิป --}}
                                        <div class="col-12 mt-3">
                                            <div class="bg-white border rounded shadow-sm p-3 text-center" style="min-height: 150px; width: 100%;">
                                                <div id="slipPreviewContainer" class="d-flex flex-column align-items-center justify-content-center h-100 text-muted">
                                                    @if ($tenant->deposit_slip)
                                                        <a href="/storage/{{ $tenant->deposit_slip }}" target="_blank" title="คลิกเพื่อดูรูปเต็ม">
                                                            <img src="/storage/{{ $tenant->deposit_slip }}" id="slipImage" 
                                                                 class="img-fluid rounded border" 
                                                                 style="max-height: 250px; object-fit: contain; transition: transform .2s;" 
                                                                 onmouseover="this.style.transform='scale(1.02)'" 
                                                                 onmouseout="this.style.transform='scale(1)'">
                                                        </a>
                                                        <div class="small text-muted mt-2"><i class="bi bi-zoom-in me-1"></i>คลิกที่รูปเพื่อดูขนาดเต็ม</div>
                                                    @else
                                                        <i id="slipIcon" class="bi bi-image fs-1 d-block mb-2 text-black-50"></i>
                                                        <img id="slipImage" class="img-fluid rounded border d-none" style="max-height: 250px; object-fit: contain;">
                                                        <span id="slipText" class="small text-danger fw-bold">ยังไม่มีรูปสลิป หรือเลือกจ่ายเงินสด</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        {{-- บริการที่จอดรถ (Card UI) --}}
                                        <div class="col-12 pt-4 border-top mt-4">
                                            @php
                                                $isParkingChecked = old('has_parking', $tenant->has_parking) ? 'checked' : '';
                                                $parkingCardClass = old('has_parking', $tenant->has_parking)
                                                    ? 'border-success bg-success bg-opacity-10'
                                                    : 'bg-white';
                                            @endphp
                                            <label class="w-100" style="cursor: pointer;" for="parkingCheck">
                                                <div class="card border-2 shadow-sm {{ $parkingCardClass }}" id="parkingCardUI"
                                                    style="transition: all 0.2s ease;">
                                                    <div class="card-body d-flex align-items-center p-3">
                                                        <div class="bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0"
                                                            style="width: 50px; height: 50px;">
                                                            <i class="bi bi-car-front-fill fs-4"></i>
                                                        </div>
                                                        <div class="flex-grow-1">
                                                            <h6 class="fw-bold mb-1 text-dark">บริการที่จอดรถส่วนตัว</h6>
                                                            <div class="text-muted small">ผู้เช่าต้องการสิทธิการจอดรถ (ระบบจะเพิ่มค่าบริการในบิลรายเดือน)</div>
                                                        </div>
                                                        <div class="form-check form-switch pe-2 mb-0 ms-3 flex-shrink-0">
                                                            <input class="form-check-input m-0" type="checkbox"
                                                                name="has_parking" value="1" id="parkingCheck"
                                                                {{ $isParkingChecked }}
                                                                style="transform: scale(1.6); cursor: pointer;"
                                                                onchange="
                                                                    let card = document.getElementById('parkingCardUI');
                                                                    if(this.checked){
                                                                        card.classList.add('border-success', 'bg-success', 'bg-opacity-10');
                                                                        card.classList.remove('bg-white');
                                                                    } else {
                                                                        card.classList.remove('border-success', 'bg-success', 'bg-opacity-10');
                                                                        card.classList.add('bg-white');
                                                                    }
                                                                ">
                                                        </div>
                                                    </div>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Buttons --}}
                            <div class="d-flex gap-2 justify-content-end mt-4">
                                <a href="{{ $backUrl }}" class="btn btn-light border rounded-3 px-4">
                                    <i class="bi bi-x-circle me-1"></i> ยกเลิก
                                </a>
                                <button type="submit" class="btn btn-success rounded-3 px-5 shadow-sm fw-bold">
                                    <i class="bi bi-save me-1"></i> ยืนยันอนุมัติ และบันทึกสัญญา
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    {{-- โหลดไลบรารีที่จำเป็น (Cleave.js) --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cleave.js/1.6.0/cleave.min.js"></script>

    <script>
        // 🌟 ฟังก์ชันสำหรับ Preview รูปสลิปก่อนอัปโหลด
        function previewSlip(event) {
            const reader = new FileReader();
            const imgElement = document.getElementById('slipImage');
            
            // ซ่อนข้อความหรือรูปเก่าที่ไม่มีประโยชน์
            const iconElement = document.getElementById('slipIcon');
            const textElement = document.getElementById('slipText');
            if(iconElement) iconElement.classList.add('d-none');
            if(textElement) textElement.classList.add('d-none');

            reader.onload = function() {
                imgElement.src = reader.result;
                imgElement.classList.remove('d-none'); // แสดงรูปใหม่
                
                // ถ้ารูปเก่ามี <a> หุ้มอยู่ ให้เปลี่ยน href เป็นลิงก์ใหม่ด้วย (ให้กดดูรูปใหญ่ได้)
                const aTag = imgElement.closest('a');
                if(aTag) aTag.href = reader.result;
            }
            if(event.target.files[0]) {
                reader.readAsDataURL(event.target.files[0]);
            }
        }

        // ********************************************
        // Global: Thai Address API (jquery.Thailand.js)
        // ********************************************
        let thaiDB = null;

        async function loadThaiDB() {
            if (thaiDB) return thaiDB;
            try {
                const res = await fetch('{{ asset('thai_address.json') }}');
                thaiDB = await res.json();
                return thaiDB;
            } catch (e) {
                console.error('โหลดข้อมูลที่อยู่ไม่สำเร็จ:', e);
                return [];
            }
        }

        document.addEventListener('DOMContentLoaded', async function() {
            // 1. Setup Input Masks
            new Cleave('.input-idcard', { blocks: [1, 4, 5, 2, 1], delimiter: '-', numericOnly: true });
            new Cleave('.input-phone', { blocks: [3, 3, 4], delimiter: '-', numericOnly: true });

            // 2. ลบขีดก่อน Submit
            document.getElementById('approveTenantForm').addEventListener('submit', function(e) {
                let idInput = document.querySelector('.input-idcard');
                let phoneInput = document.querySelector('.input-phone');
                idInput.value = idInput.value.replace(/\D/g, '');
                phoneInput.value = phoneInput.value.replace(/\D/g, '');
            });

            // 3. โหลดและตั้งค่าที่อยู่
            const db = await loadThaiDB();
            if (!db || !db.length) return;

            const provEl = document.getElementById('add_province');
            const distEl = document.getElementById('add_district');
            const subEl = document.getElementById('add_sub_district');
            const zipEl = document.getElementById('add_postal_code');
            const issueProvEl = document.getElementById('id_card_issue_province');

            let oldProv = document.getElementById('old_prov').value;
            let oldDist = document.getElementById('old_dist').value;
            let oldSub = document.getElementById('old_sub').value;
            let oldIssueProv = document.getElementById('old_issue_prov').value;

            const provinces = [...new Set(db.map(r => r.province))].sort();
            
            // โหลดจังหวัดที่อยู่ปัจจุบัน
            provEl.innerHTML = '<option value="">-- เลือกจังหวัด --</option>';
            provinces.forEach(p => {
                let selected = (p === oldProv) ? 'selected' : '';
                provEl.insertAdjacentHTML('beforeend', `<option value="${p}" ${selected}>${p}</option>`);
            });

            // โหลดจังหวัดที่ออกบัตรประชาชน
            if (issueProvEl) {
                issueProvEl.innerHTML = '<option value="">-- เลือกจังหวัด --</option>';
                provinces.forEach(p => {
                    let selected = (p === oldIssueProv) ? 'selected' : '';
                    issueProvEl.insertAdjacentHTML('beforeend', `<option value="${p}" ${selected}>${p}</option>`);
                });
            }

            function updateDistrict(provValue, defaultDist = '') {
                distEl.innerHTML = '<option value="">-- เลือกอำเภอ --</option>';
                subEl.innerHTML = '<option value="">-- เลือกตำบล --</option>';
                if (!provValue) {
                    distEl.disabled = true; subEl.disabled = true; return;
                }
                const amphoes = [...new Set(db.filter(r => r.province === provValue).map(r => r.amphoe))].sort();
                amphoes.forEach(a => {
                    let selected = (a === defaultDist) ? 'selected' : '';
                    distEl.insertAdjacentHTML('beforeend', `<option value="${a}" ${selected}>${a}</option>`);
                });
                distEl.disabled = false;
            }

            function updateSubDistrict(provValue, distValue, defaultSub = '') {
                subEl.innerHTML = '<option value="">-- เลือกตำบล --</option>';
                if (!distValue) {
                    subEl.disabled = true; return;
                }
                const districts = [...new Set(db.filter(r => r.province === provValue && r.amphoe === distValue).map(r => r.district))].sort();
                districts.forEach(d => {
                    let selected = (d === defaultSub) ? 'selected' : '';
                    subEl.insertAdjacentHTML('beforeend', `<option value="${d}" ${selected}>${d}</option>`);
                });
                subEl.disabled = false;
            }

            // ตั้งค่าค่าเริ่มต้น (ถ้ามี)
            if (oldProv) {
                updateDistrict(oldProv, oldDist);
                if (oldDist) updateSubDistrict(oldProv, oldDist, oldSub);
            }

            // Event Listeners สำหรับ Cascading Dropdown
            provEl.addEventListener('change', function() {
                updateDistrict(this.value);
                zipEl.value = '';
            });

            distEl.addEventListener('change', function() {
                updateSubDistrict(provEl.value, this.value);
                zipEl.value = '';
            });

            subEl.addEventListener('change', function() {
                const match = db.find(r => r.province === provEl.value && r.amphoe === distEl.value && r.district === this.value);
                zipEl.value = match ? match.zipcode : '';
            });
        });
    </script>
@endpush