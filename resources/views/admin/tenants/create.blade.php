@extends('admin.layout')

@section('title', 'ลงทะเบียนผู้เช่าใหม่')

@section('content')

    <div class="container-xl py-4">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-header bg-primary bg-gradient text-white border-0 p-4 rounded-top-4">
                        <div class="d-flex justify-content-between align-items-start">

                            {{-- 🌟 เพิ่มปุ่มย้อนกลับตรงนี้ + จัดกลุ่มให้อยู่คู่กับหัวข้อ --}}
                            <div class="d-flex align-items-center">
                                @php
                                    // คำนวณลิงก์ย้อนกลับ: ถ้ามีห้องที่เลือก ให้กลับไปผังตึกนั้น ถ้าไม่มีให้กลับไปหน้าเลือกตึก
                                    $backUrl =
                                        $selectedRoom && isset($selectedRoom->roomPrice->building_id)
                                            ? route('admin.rooms.system', [
                                                'building_id' => $selectedRoom->roomPrice->building_id,
                                            ])
                                            : route('admin.rooms.system');
                                @endphp

                                <a href="{{ $backUrl }}"
                                    class="btn btn-light text-primary rounded-circle me-3 shadow-sm d-flex align-items-center justify-content-center"
                                    style="width: 45px; height: 45px; transition: 0.2s;" title="กลับไปผังห้อง">
                                    <i class="bi bi-arrow-left fs-5"></i>
                                </a>

                                <div>
                                    <h4 class="fw-bold mb-1">
                                        <i class="bi bi-person-plus-fill me-2"></i>ลงทะเบียนผู้เช่าใหม่
                                    </h4>
                                    <p class="text-white-50 small mb-0">กรอกข้อมูลผู้เช่าและรายละเอียดสัญญาเช่าให้ครบถ้วน
                                    </p>
                                </div>
                            </div>

                            @if ($selectedRoom)
                                <div class="text-end">
                                    <div class="badge bg-white text-primary px-3 py-2 fs-6 shadow-sm rounded-pill">
                                        <i class="bi bi-door-closed-fill me-1"></i> ห้อง {{ $selectedRoom->room_number }}
                                        <span class="fw-normal ms-1">({{ number_format($selectedRoom->price ?? 0, 0) }}
                                            บ./เดือน)</span>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="card-body p-4 bg-light bg-opacity-25">
                        <form action="{{ route('admin.tenants.insert') }}" method="POST" enctype="multipart/form-data"
                            id="addTenantForm" novalidate>
                            @csrf

                            {{-- Section 1: ข้อมูลห้องพัก --}}
                            <div class="card border-0 shadow-sm mb-4">
                                <div class="card-body">
                                    <h6 class="fw-bold text-primary mb-3 border-bottom pb-2">1. ข้อมูลการเช่า</h6>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            @if ($selectedRoom)
                                                <label class="form-label fw-bold">ห้องพัก (เลือกแล้ว)</label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control bg-light"
                                                        value="ห้อง {{ $selectedRoom->room_number }}" readonly>
                                                    <a href="{{ route('admin.tenants.create') }}"
                                                        class="btn btn-outline-secondary">เปลี่ยนห้อง</a>
                                                </div>
                                                <input type="hidden" name="room_id" value="{{ $selectedRoom->id }}">
                                            @else
                                                <label class="form-label fw-bold">เลือกห้องพัก <span
                                                        class="text-danger">*</span></label>
                                                <select name="room_id" id="room_id" class="form-select border-primary"
                                                    required>
                                                    <option value="">-- เลือกห้องที่ว่าง --</option>
                                                    @foreach ($rooms as $room)
                                                        <option value="{{ $room->id }}"
                                                            data-price="{{ $room->roomPrice->price ?? 0 }}">
                                                            ห้อง {{ $room->room_number }} (
                                                            {{ $room->roomPrice->building->name ?? '-' }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                            @endif
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">จำนวนผู้อยู่อาศัย <span
                                                    class="text-danger">*</span></label>
                                            <input type="number" name="resident_count" class="form-control"
                                                value="{{ old('resident_count', 1) }}" min="1" required>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Section 2: ข้อมูลส่วนตัว --}}
                            <div class="card border-0 shadow-sm mb-4">
                                <div class="card-body">
                                    <h6 class="fw-bold text-primary mb-3 border-bottom pb-2">2. ข้อมูลส่วนบุคคล</h6>
                                    <div class="row g-3">
                                        <div class="col-md-5">
                                            <label class="form-label fw-bold">ชื่อ <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" name="first_name" class="form-control"
                                                value="{{ old('first_name') }}" required>
                                        </div>
                                        <div class="col-md-5">
                                            <label class="form-label fw-bold">นามสกุล <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" name="last_name" class="form-control"
                                                value="{{ old('last_name') }}" required>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label fw-bold">อายุ <span
                                                    class="text-danger">*</span></label>
                                            <input type="number" name="age" class="form-control"
                                                value="{{ old('age') }}" min="1" required>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">เลขบัตรประชาชน <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" name="id_card" class="form-control input-idcard"
                                                value="{{ old('id_card') }}" placeholder="x-xxxx-xxxxx-xx-x" >
                                            @error('id_card')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">เบอร์โทรศัพท์ <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" name="phone" class="form-control input-phone"
                                                value="{{ old('phone') }}" placeholder="xxx-xxx-xxxx" required>
                                            @error('phone')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">ออกบัตรเมื่อวันที่</label>
                                            <input type="date" name="id_card_issue_date" class="form-control"
                                                value="{{ old('id_card_issue_date') }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">บัตรหมดอายุวันที่</label>
                                            <input type="date" name="id_card_expiry_date" class="form-control"
                                                value="{{ old('id_card_expiry_date') }}">
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">สถานที่ออกบัตร (ณ)</label>
                                            <input type="text" name="id_card_issue_place" class="form-control"
                                                value="{{ old('id_card_issue_place') }}"
                                                placeholder="เช่น ที่ว่าการอำเภอเมือง...">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">จังหวัดที่ออกบัตร</label>
                                            <select name="id_card_issue_province" id="id_card_issue_province"
                                                class="form-select">
                                                <option value="">กำลังโหลด...</option>
                                            </select>
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label fw-bold">สถานที่ทำงาน</label>
                                            <input type="text" name="workplace" class="form-control"
                                                value="{{ old('workplace') }}">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Section 3: ที่อยู่ตามทะเบียนบ้าน --}}
                            <div class="card border-0 shadow-sm mb-4">
                                <div class="card-body">
                                    <h6 class="fw-bold text-primary mb-3 border-bottom pb-2">3. ที่อยู่ตามทะเบียนบ้าน</h6>
                                    <div class="row g-3">
                                        <div class="col-md-3">
                                            <label class="form-label fw-bold">เลขที่ <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" name="address_no" class="form-control"
                                                value="{{ old('address_no') }}" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-bold">หมู่ที่ <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" name="moo" class="form-control"
                                                value="{{ old('moo') }}" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-bold">ตรอก/ซอย</label>
                                            <input type="text" name="alley" class="form-control"
                                                value="{{ old('alley') }}">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-bold">ถนน</label>
                                            <input type="text" name="street" class="form-control"
                                                value="{{ old('street') }}">
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">จังหวัด <span
                                                    class="text-danger">*</span></label>
                                            <select name="province" id="add_province" class="form-select" required>
                                                <option value="">กำลังโหลด...</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">อำเภอ/เขต <span
                                                    class="text-danger">*</span></label>
                                            <select name="district" id="add_district" class="form-select" disabled
                                                required>
                                                <option value="">-- เลือกจังหวัดก่อน --</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">ตำบล/แขวง <span
                                                    class="text-danger">*</span></label>
                                            <select name="sub_district" id="add_sub_district" class="form-select"
                                                disabled required>
                                                <option value="">-- เลือกอำเภอก่อน --</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">รหัสไปรษณีย์ <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" name="postal_code" id="add_postal_code"
                                                class="form-control bg-light" readonly
                                                placeholder="เลือกตำบลเพื่อเติมอัตโนมัติ" required>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Section 4: สัญญาและค่าใช้จ่าย --}}
                            <div class="card border-0 shadow-sm mb-4">
                                <div class="card-body">
                                    <h6 class="fw-bold text-primary mb-3 border-bottom pb-2">4. สัญญาและค่าใช้จ่าย <span
                                            class="text-danger">ระบบสร้างไฟล์ PDF อัตโนมัติ</span> </h6>
                                    <div class="row g-3">
                                        <div class="col-md-3">
                                            <label class="form-label fw-bold">วันที่เริ่มเช่า <span
                                                    class="text-danger">*</span></label>
                                            <input type="date" name="start_date" class="form-control"
                                                value="{{ old('start_date', date('Y-m-d')) }}" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-bold">วันที่สิ้นสุด (ถ้ามี)</label>
                                            <input type="date" name="end_date" class="form-control"
                                                value="{{ old('end_date') }}">
                                        </div>

                                        <div class="col-12 mt-4">
                                            <h6 class="fw-bold text-dark mb-2"><i
                                                    class="bi bi-cash-coin me-2 text-success"></i>การชำระเงินมัดจำ</h6>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-bold">วันที่รับเงินมัดจำ <span class="text-danger">*</span></label>
                                            <input type="date" name="deposit_date" class="form-control" 
                                                value="{{ old('deposit_date', date('Y-m-d')) }}" required>
                                            <div class="form-text small">วันที่เงินเข้าบัญชีหรือรับเงินสด</div>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-bold">เงินมัดจำ (บาท) <span
                                                    class="text-danger">*</span></label>
                                            <input type="number" name="deposit_amount" id="deposit_amount"
                                                class="form-control text-success fw-bold"
                                                value="{{ old('deposit_amount') }}" min="1" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-bold">ช่องทางชำระมัดจำ <span
                                                    class="text-danger">*</span></label>
                                            <select name="deposit_payment_method" class="form-select" required>
                                                <option value="เงินสด">เงินสด</option>
                                                <option value="โอนผ่านธนาคาร">โอนผ่านธนาคาร</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-bold">แนบสลิปมัดจำ (ถ้ามี)</label>
                                            <input type="file" name="deposit_slip" class="form-control"
                                                accept="image/*">
                                        </div>

                                        {{-- บริการที่จอดรถ (Card UI) --}}
                                        <div class="col-12 pt-3">
                                            <label class="w-100" style="cursor: pointer;" for="parkingCheck">
                                                <div class="card border-2 shadow-sm" id="parkingCardUI"
                                                    style="transition: all 0.2s ease;">
                                                    <div class="card-body d-flex align-items-center p-3">
                                                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0"
                                                            style="width: 50px; height: 50px;">
                                                            <i class="bi bi-car-front-fill fs-4"></i>
                                                        </div>
                                                        <div class="flex-grow-1">
                                                            <h6 class="fw-bold mb-1 text-dark">บริการที่จอดรถส่วนตัว</h6>
                                                            <div class="text-muted small">เพิ่มสิทธิการจอดรถ
                                                                (ระบบจะเพิ่มค่าบริการในบิลรายเดือน)</div>
                                                        </div>
                                                        <div class="form-check form-switch pe-2 mb-0 ms-3 flex-shrink-0">
                                                            <input class="form-check-input m-0" type="checkbox"
                                                                name="has_parking" value="1" id="parkingCheck"
                                                                style="transform: scale(1.6); cursor: pointer;"
                                                                onchange="
                                                                let card = document.getElementById('parkingCardUI');
                                                                if(this.checked){
                                                                    card.classList.add('border-primary', 'bg-primary', 'bg-opacity-10');
                                                                } else {
                                                                    card.classList.remove('border-primary', 'bg-primary', 'bg-opacity-10');
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
                                    <i class="bi bi-x-circle me-1"></i> กลับไปที่ผังห้อง
                                </a>
                                <button type="submit" class="btn btn-primary rounded-3 px-5 shadow-sm fw-bold" id="btnSubmitTenant">
                                    <span id="btnText">
                                        <i class="bi bi-save me-1"></i> ยืนยันการลงทะเบียน
                                    </span>
                                    <span id="btnLoading" class="d-none">
                                        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                        กำลังบันทึกและสร้างสัญญา...
                                    </span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        {{-- โหลดไลบรารีที่จำเป็น (Cleave.js) --}}
        <script src="https://cdnjs.cloudflare.com/ajax/libs/cleave.js/1.6.0/cleave.min.js"></script>

        <script>
            // 1. ระบบเติมเงินมัดจำอัตโนมัติเมื่อเลือกห้องพัก
            document.getElementById('room_id')?.addEventListener('change', function() {
                const selected = this.options[this.selectedIndex];
                const price = selected.getAttribute('data-price');
                if (price) {
                    document.getElementById('deposit_amount').value = price;
                }
            });

            // ********************************************
            // 2. Global: Thai Address API (jquery.Thailand.js)
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

            function setupCascading(prefix) {
                const prov = document.getElementById(prefix + '_province');
                const dist = document.getElementById(prefix + '_district');
                const sub = document.getElementById(prefix + '_sub_district');
                const zip = document.getElementById(prefix + '_postal_code');

                prov.addEventListener('change', function() {
                    dist.innerHTML = '<option value="">-- เลือกอำเภอ --</option>';
                    sub.innerHTML = '<option value="">-- เลือกตำบล --</option>';
                    zip.value = '';
                    dist.disabled = true;
                    sub.disabled = true;
                    if (!this.value) return;

                    const amphoes = [...new Set(thaiDB.filter(r => r.province === this.value).map(r => r.amphoe))]
                        .sort();
                    amphoes.forEach(a => dist.add(new Option(a, a)));
                    dist.disabled = false;
                });

                dist.addEventListener('change', function() {
                    sub.innerHTML = '<option value="">-- เลือกตำบล --</option>';
                    zip.value = '';
                    sub.disabled = true;
                    if (!this.value) return;

                    const districts = [...new Set(thaiDB.filter(r => r.province === prov.value && r.amphoe === this
                        .value).map(r => r.district))].sort();
                    districts.forEach(d => sub.add(new Option(d, d)));
                    sub.disabled = false;
                });

                sub.addEventListener('change', function() {
                    zip.value = '';
                    if (!this.value) return;
                    const match = thaiDB.find(r => r.province === prov.value && r.amphoe === dist.value && r
                        .district === this.value);
                    if (match) zip.value = match.zipcode;
                });
            }

            document.addEventListener('DOMContentLoaded', function() {

                // โหลดข้อมูลจังหวัดลง Select
                loadThaiDB().then(db => {
                    if (!db || !db.length) return;
                    const provinces = [...new Set(db.map(r => r.province))].sort();
                    // 1. สำหรับ: จังหวัดที่อยู่ตามทะเบียนบ้าน
                    const sel = document.getElementById('add_province');
                    sel.innerHTML = '<option value="">-- เลือกจังหวัด --</option>';
                    provinces.forEach(p => sel.add(new Option(p, p)));

                    // 🌟 2. สำหรับ: จังหวัดที่ออกบัตร (เพิ่มใหม่)
                    const issueProvinceSel = document.getElementById('id_card_issue_province');
                    if (issueProvinceSel) {
                        issueProvinceSel.innerHTML = '<option value="">-- เลือกจังหวัด --</option>';
                        provinces.forEach(p => {
                            let option = new Option(p, p);
                            // 🌟 ตรวจสอบว่ามีการส่งค่า old() กลับมาจากการ Validate Error หรือไม่
                            if ('{{ old("id_card_issue_province") }}' === p) {
                                option.selected = true;
                            }
                            issueProvinceSel.add(option);
                        });
                    }
                    // เริ่มเปิดใช้งานระบบ Cascading Dropdown
                    setupCascading('add');
                });

                // 3. Setup Input Masks (Cleave.js) 
                // ทำให้พิมเลขบัตร / เบอร์ แล้วมีขีดคั่นสวยงาม
                document.querySelectorAll('.input-idcard').forEach(function(el) {
                    new Cleave(el, {
                        blocks: [1, 4, 5, 2, 1],
                        delimiter: '-',
                        numericOnly: true
                    });
                });

                document.querySelectorAll('.input-phone').forEach(function(el) {
                    new Cleave(el, {
                        blocks: [3, 3, 4],
                        delimiter: '-',
                        numericOnly: true
                    });
                });

                // 4. ลบขีดออกจากฟอร์มก่อนกดยืนยันส่งข้อมูลให้ Controller
                document.getElementById('addTenantForm').addEventListener('submit', function(e) {
                    const form = e.target;
                    const btnSubmit = document.getElementById('btnSubmitTenant');
                    const btnText = document.getElementById('btnText');
                    const btnLoading = document.getElementById('btnLoading');

                    // ตรวจสอบความถูกต้องของฟอร์มเบื้องต้น (HTML5 Validation)
                    if (!form.checkValidity()) {
                        // หากข้อมูลไม่ครบ ให้เบราว์เซอร์จัดการแจ้งเตือนตามปกติ และไม่ต้องหมุนโหลด
                        return;
                    }

                    // 🌟 1. ลบขีดออกจากฟอร์ม (Logic เดิมของคุณ)
                    let idInput = document.querySelector('input[name="id_card"]');
                    let phoneInput = document.querySelector('input[name="phone"]');
                    idInput.value = idInput.value.replace(/\D/g, '');
                    phoneInput.value = phoneInput.value.replace(/\D/g, '');

                    // 🌟 2. เริ่มสถานะการโหลด
                    // ปิดปุ่มเพื่อป้องกันการกดซ้ำ
                    btnSubmit.disabled = true;
                    
                    // สลับการแสดงผลข้อความและตัวหมุน
                    btnText.classList.add('d-none');
                    btnLoading.classList.remove('d-none');
                    
                    // ปล่อยให้ฟอร์มส่งข้อมูลไปที่ Controller ตามปกติ
                });
            });
        </script>
    @endpush

@endsection
