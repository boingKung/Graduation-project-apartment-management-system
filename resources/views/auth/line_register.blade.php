<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ลงทะเบียนจองห้องพัก</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f4f7f6;
        }

        /* สไตล์สำหรับ Modal PDPA */
        .pdpa-content {
            max-height: 40vh;
            overflow-y: auto;
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
            font-size: 0.9rem;
            color: #495057;
        }

        .pdpa-content::-webkit-scrollbar {
            width: 6px;
        }

        .pdpa-content::-webkit-scrollbar-thumb {
            background-color: #ced4da;
            border-radius: 4px;
        }
    </style>
</head>

<body>
    {{-- 🌟 เพิ่ม Modal PDPA --}}
    <div class="modal fade" id="pdpaModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        aria-labelledby="pdpaModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold" id="pdpaModalLabel"><i
                            class="bi bi-shield-check me-2"></i>นโยบายคุ้มครองข้อมูลส่วนบุคคล (PDPA)</h5>
                </div>
                <div class="modal-body">
                    <p class="mb-3">เพื่อปฏิบัติตามพระราชบัญญัติคุ้มครองข้อมูลส่วนบุคคล พ.ศ. 2562 (PDPA)
                        ทางเราจำเป็นต้องขอความยินยอมในการเก็บรวบรวม ใช้ และเปิดเผยข้อมูลส่วนบุคคลของคุณ ดังนี้</p>

                    <div class="pdpa-content mb-3">
                        <h6 class="fw-bold">1. วัตถุประสงค์ในการเก็บข้อมูล</h6>
                        <p>เราจะเก็บรวบรวมและใช้ข้อมูลส่วนบุคคลของคุณ (เช่น ชื่อ-นามสกุล, เลขบัตรประจำตัวประชาชน,
                            เบอร์โทรศัพท์, ที่อยู่, ภาพถ่าย, สลิปการโอนเงิน และข้อมูลการติดต่อผ่าน LINE)
                            เพื่อวัตถุประสงค์ในการ:</p>
                        <ul>
                            <li>ดำเนินการลงทะเบียนและจัดทำสัญญาเช่าที่พักอาศัย</li>
                            <li>ยืนยันตัวตนและการตรวจสอบความถูกต้องของข้อมูล</li>
                            <li>ติดต่อสื่อสาร แจ้งเตือน หรือส่งเอกสารที่เกี่ยวข้องกับการเช่า</li>
                            <li>ประมวลผลการรับชำระเงินและออกใบเสร็จรับเงิน/ใบกำกับภาษี</li>
                            <li>ดำเนินการตามกฎหมาย หรือข้อบังคับที่เกี่ยวข้อง</li>
                        </ul>

                        <h6 class="fw-bold mt-4">2. การรักษาความปลอดภัยของข้อมูล</h6>
                        <p>เรามีมาตรการรักษาความปลอดภัยที่เหมาะสม เพื่อป้องกันการเข้าถึง การใช้ การเปลี่ยนแปลง
                            หรือการเปิดเผยข้อมูลส่วนบุคคลของคุณโดยไม่ได้รับอนุญาต</p>

                        <h6 class="fw-bold mt-4">3. สิทธิของเจ้าของข้อมูล</h6>
                        <p>คุณมีสิทธิในการขอเข้าถึง ขอแก้ไข ขอระงับการใช้ ขอเพิกถอนความยินยอม
                            หรือขอลบข้อมูลส่วนบุคคลของคุณตามที่กฎหมายกำหนด โดยสามารถติดต่อแอดมินหรือผู้ดูแลระบบได้โดยตรง
                        </p>
                    </div>

                    <div class="mt-4 mb-3">
                        {{-- สร้างกล่อง Label ให้ใหญ่ขึ้น กดได้ทั้งกรอบ และใส่สีพื้นหลังอ่อนๆ ให้ดูเด่น --}}
                        <label for="acceptPdpaCheck"
                            class="d-flex align-items-center p-3 border border-2 border-primary rounded-3 shadow-sm w-100"
                            style="cursor: pointer; background-color: #f8fbff; transition: all 0.2s;"
                            onmouseover="this.style.backgroundColor='#eef6ff'"
                            onmouseout="this.style.backgroundColor='#f8fbff'">

                            {{-- ส่วน Checkbox (ขยายขนาดด้วย width/height แทน scale เพื่อไม่ให้ Layout เพี้ยน) --}}
                            <div class="me-3 flex-shrink-0 d-flex align-items-center">
                                <input class="form-check-input m-0 border-primary shadow-none" type="checkbox"
                                    id="acceptPdpaCheck" style="width: 1.8rem; height: 1.8rem; cursor: pointer;">
                            </div>

                            {{-- ส่วนข้อความ (จัดกึ่งกลางแนวตั้งอัตโนมัติด้วย align-items-center ของกล่องแม่) --}}
                            <div class="fw-bold text-dark" style="font-size: 0.95rem; line-height: 1.5;">
                                ข้าพเจ้าได้อ่านและทำความเข้าใจนโยบายคุ้มครองข้อมูลส่วนบุคคล (PDPA) นี้แล้ว
                                และยินยอมให้ทางอพาร์ทเม้นท์ประมวลผลข้อมูลของข้าพเจ้าตามวัตถุประสงค์ที่ระบุไว้
                            </div>
                        </label>
                    </div>
                </div>
                <div class="modal-footer bg-light d-flex justify-content-center">
                    <button type="button" class="btn btn-secondary">ไม่ยินยอม (ปิดหน้านี้)</button>
                    <button type="button" class="btn btn-primary fw-bold px-4 disabled"
                        id="btnAcceptPdpa">ยินยอมและดำเนินการต่อ</button>
                </div>
            </div>
        </div>
    </div>

    {{-- เนื้อหาหลัก (ฟอร์มการลงทะเบียน) จะยังแสดงอยู่ด้านหลัง แต่กดไม่ได้จนกว่าจะผ่าน Modal --}}
    <div class="container-xl py-4 blur-target">
        <div class="row justify-content-center">
            <div class="col-lg-8">

                {{-- ส่วนหัวโปรไฟล์ LINE --}}
                <div class="text-center mb-4">
                    <img src="{{ session('temp_line_avatar') ?? 'https://via.placeholder.com/90' }}"
                        class="rounded-circle border border-3 border-primary shadow-sm mb-2" width="80"
                        height="80">
                    <h5 class="fw-bold">สวัสดีคุณ {{ session('temp_line_name') }}</h5>
                    <p class="text-muted small">กรุณากรอกข้อมูลและแนบสลิปมัดจำเพื่อจองห้องพัก
                        (แอดมินจะจัดสรรห้องให้ภายหลัง)</p>
                </div>

                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-header bg-primary bg-gradient text-white border-0 p-3 rounded-top-4 text-center">
                        <h5 class="fw-bold mb-0"><i class="bi bi-person-plus-fill me-2"></i>ฟอร์มลงทะเบียนผู้เช่าใหม่
                        </h5>
                    </div>

                    <div class="card-body p-4 bg-light bg-opacity-25">

                        {{-- แสดง Error ถ้ากรอกผิด --}}
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form action="{{ route('line.register.save') }}" method="POST" enctype="multipart/form-data"
                            id="addTenantForm">
                            @csrf
                            <input type="hidden" name="line_id" value="{{ $lineId }}">
                            <input type="hidden" name="line_avatar" value="{{ $lineAvatar }}">

                            {{-- 🌟 เพิ่มฟิลด์ hidden ยืนยันว่ากดยอมรับ PDPA แล้ว ส่งไปพร้อมกับฟอร์ม --}}
                            <input type="hidden" name="pdpa_accepted" value="1">

                            {{-- Section 1: ข้อมูลส่วนบุคคล --}}
                            <h6 class="fw-bold text-primary mb-3 border-bottom pb-2">1. ข้อมูลส่วนบุคคล</h6>
                            <div class="row g-3 mb-4">
                                <div class="col-md-5">
                                    <label class="form-label fw-bold">ชื่อ <span class="text-danger">*</span></label>
                                    <input type="text" name="first_name" class="form-control"
                                        value="{{ old('first_name') }}" required>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label fw-bold">นามสกุล <span class="text-danger">*</span></label>
                                    <input type="text" name="last_name" class="form-control"
                                        value="{{ old('last_name') }}" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-bold">อายุ <span class="text-danger">*</span></label>
                                    <input type="number" name="age" class="form-control"
                                        value="{{ old('age') }}" min="1" required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold">เลขบัตรประชาชน <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="id_card" class="form-control input-idcard"
                                        value="{{ old('id_card') }}" placeholder="x-xxxx-xxxxx-xx-x" >
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">เบอร์โทรศัพท์ <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="phone" class="form-control input-phone"
                                        value="{{ old('phone') }}" placeholder="xxx-xxx-xxxx" required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold">ออกบัตรเมื่อวันที่</label>
                                    <input type="date" name="id_card_issue_date" class="form-control"
                                        value="{{ old('id_card_issue_date') }}" >
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">บัตรหมดอายุวันที่</label>
                                    <input type="date" name="id_card_expiry_date" class="form-control"
                                        value="{{ old('id_card_expiry_date') }}" >
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold">สถานที่ออกบัตร (ณ)</label>
                                    <input type="text" name="id_card_issue_place" class="form-control"
                                        value="{{ old('id_card_issue_place') }}"
                                        placeholder="เช่น ที่ว่าการอำเภอเมือง..." >
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">จังหวัดที่ออกบัตร</label>
                                    <select name="id_card_issue_province" id="id_card_issue_province"
                                        class="form-select" >
                                        <option value="">กำลังโหลด...</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold">สถานที่ทำงาน</label>
                                    <input type="text" name="workplace" class="form-control"
                                        value="{{ old('workplace') }}" >
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">จำนวนผู้อยู่อาศัย <span
                                            class="text-danger">*</span></label>
                                    <input type="number" name="resident_count" class="form-control"
                                        value="{{ old('resident_count', 1) }}" min="1" required>
                                </div>
                            </div>

                            {{-- Section 2: ที่อยู่ตามทะเบียนบ้าน --}}
                            <h6 class="fw-bold text-primary mb-3 border-bottom pb-2">2. ที่อยู่ตามทะเบียนบ้าน</h6>
                            <div class="row g-3 mb-4">
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">เลขที่ <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="address_no" class="form-control"
                                        value="{{ old('address_no') }}" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">หมู่ที่</label>
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
                                    <select name="district" id="add_district" class="form-select" disabled required>
                                        <option value="">-- เลือกจังหวัดก่อน --</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">ตำบล/แขวง <span
                                            class="text-danger">*</span></label>
                                    <select name="sub_district" id="add_sub_district" class="form-select" disabled
                                        required>
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

                            {{-- Section 3: การชำระเงินมัดจำ --}}
                            <h6 class="fw-bold text-success mb-3 border-bottom pb-2">3. การชำระเงินมัดจำการจอง</h6>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">ยอดเงินมัดจำ (บาท) <span
                                            class="text-danger">*</span></label>
                                    <input type="number" name="deposit_amount"
                                        class="form-control fw-bold text-success" value="{{ old('deposit_amount') }}"
                                        placeholder="ระบุยอดเงินที่เตรียมชำระ" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">ช่องทางโอนเงิน <span
                                            class="text-danger">*</span></label>
                                    <select name="deposit_payment_method" id="payment_method" class="form-select"
                                        required>
                                        <option value="โอนผ่านธนาคาร">โอนผ่านธนาคาร</option>
                                        <option value="เงินสด">เงินสด</option>
                                    </select>
                                </div>

                                {{-- 🌟 ส่วนอัปโหลดและแสดงรูปสลิป --}}
                                <div class="col-12" id="slipUploadSection">
                                    <label class="form-label fw-bold">แนบรูปสลิปมัดจำ <span
                                            class="text-danger">*</span></label>
                                    <input type="file" name="deposit_slip" id="slipInput" class="form-control"
                                        accept="image/*" onchange="previewSlip(event)">

                                    <div class="mt-3 text-center bg-white border rounded shadow-sm p-3"
                                        style="min-height: 150px;">
                                        <div id="slipPreviewContainer"
                                            class="d-flex flex-column align-items-center justify-content-center h-100 text-muted">
                                            <i id="slipIcon" class="bi bi-image fs-1 d-block mb-2 text-black-50"></i>
                                            <img id="slipImage" class="img-fluid rounded border d-none"
                                                style="max-height: 250px; object-fit: contain;">
                                            <span id="slipText" class="small fw-bold">ตัวอย่างรูปสลิป</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Section 4: บริการเพิ่มเติม --}}
                            <h6 class="fw-bold text-dark mb-3 border-bottom pb-2">4. บริการเพิ่มเติม</h6>
                            <div class="row g-3 mb-4">
                                <div class="col-12">
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

                            <button type="submit"
                                class="btn btn-primary w-100 rounded-3 py-3 shadow-sm fw-bold fs-5">
                                <i class="bi bi-send-check-fill me-1"></i> ส่งข้อมูลจองห้องพัก
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Script เติมที่อยู่อัตโนมัติ และ Cleave.js --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cleave.js/1.6.0/cleave.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 🌟 1. ตั้งค่าและเปิด Modal PDPA
            // ตรวจสอบว่าเคยแสดงแล้วหรือยังใน session นี้ ถ้ายังให้เปิด (เผื่อมีการส่งข้อมูลแล้วหน้าโหลดซ้ำจาก error)
            @if (!session('pdpa_accepted_session') && !$errors->any())
                const pdpaModal = new bootstrap.Modal(document.getElementById('pdpaModal'));
                pdpaModal.show();
            @endif

            const acceptCheck = document.getElementById('acceptPdpaCheck');
            const btnAccept = document.getElementById('btnAcceptPdpa');

            // เช็คการเปิดใช้งานปุ่ม "ยินยอม"
            acceptCheck.addEventListener('change', function() {
                if (this.checked) {
                    btnAccept.classList.remove('disabled');
                } else {
                    btnAccept.classList.add('disabled');
                }
            });

            // เมื่อกดปุ่ม "ยินยอมและดำเนินการต่อ"
            btnAccept.addEventListener('click', function() {
                if (acceptCheck.checked) {
                    // ปิด Modal
                    bootstrap.Modal.getInstance(document.getElementById('pdpaModal')).hide();
                    // อาจจะบันทึกสถานะไว้ใน SessionStorage ด้วย JS เผื่อกันหน้าเว็บรีเฟรช
                    sessionStorage.setItem('pdpa_accepted', 'true');
                }
            });

            // ตรวจสอบตอนฟอร์มโหลดว่าเคยยอมรับไปแล้วหรือไม่ (เผื่อหน้าเว็บพังเพราะ Validation)
            if (sessionStorage.getItem('pdpa_accepted') === 'true') {
                const modalEl = document.getElementById('pdpaModal');
                if (modalEl) {
                    modalEl.classList.remove('show');
                    modalEl.style.display = 'none';
                    document.body.classList.remove('modal-open');
                    const backdrop = document.querySelector('.modal-backdrop');
                    if (backdrop) backdrop.remove();
                }
            }


            // 🌟 2. ฟังก์ชันแสดงตัวอย่างรูปสลิป
            window.previewSlip = function(event) {
                const reader = new FileReader();
                const imgElement = document.getElementById('slipImage');
                const iconElement = document.getElementById('slipIcon');
                const textElement = document.getElementById('slipText');

                if (iconElement) iconElement.classList.add('d-none');
                if (textElement) textElement.classList.add('d-none');

                reader.onload = function() {
                    imgElement.src = reader.result;
                    imgElement.classList.remove('d-none');
                }
                if (event.target.files[0]) {
                    reader.readAsDataURL(event.target.files[0]);
                }
            }

            // 🌟 3. ซ่อน/แสดงช่องอัปโหลดสลิป ถ้าเลือกจ่ายเงินสด
            const paymentMethodSelect = document.getElementById('payment_method');
            const slipUploadSection = document.getElementById('slipUploadSection');
            const slipInput = document.getElementById('slipInput');

            paymentMethodSelect.addEventListener('change', function() {
                if (this.value === 'เงินสด') {
                    slipUploadSection.style.display = 'none';
                    slipInput.removeAttribute('required'); // เลิกบังคับ
                    slipInput.value = ''; // เคลียร์ไฟล์ที่อาจจะเลือกไว้
                } else {
                    slipUploadSection.style.display = 'block';
                    slipInput.setAttribute('required', 'required'); // บังคับแนบสลิปกลับมา
                }
            });
            // รันเช็คครั้งแรกเผื่อไว้ตอนโหลดหน้า
            paymentMethodSelect.dispatchEvent(new Event('change'));

            // ********************************************
            // โค้ดที่อยู่และอื่นๆ ด้านล่าง (ใช้ของเดิม)
            // ********************************************
            let thaiDB = null;
            async function loadThaiDB() {
                if (thaiDB) return thaiDB;
                const res = await fetch('{{ asset('thai_address.json') }}');
                thaiDB = await res.json();
                return thaiDB;
            }

            function setupCascading(prefix) {
                const prov = document.getElementById(prefix + '_province'),
                    dist = document.getElementById(prefix + '_district'),
                    sub = document.getElementById(prefix + '_sub_district'),
                    zip = document.getElementById(prefix + '_postal_code');

                prov.addEventListener('change', function() {
                    dist.innerHTML = '<option value="">-- เลือกอำเภอ --</option>';
                    sub.innerHTML = '<option value="">-- เลือกตำบล --</option>';
                    zip.value = '';
                    dist.disabled = true;
                    sub.disabled = true;
                    if (!this.value) return;
                    const amphoes = [...new Set(thaiDB.filter(r => r.province === this.value).map(r => r
                        .amphoe))].sort();
                    amphoes.forEach(a => dist.add(new Option(a, a)));
                    dist.disabled = false;
                });

                dist.addEventListener('change', function() {
                    sub.innerHTML = '<option value="">-- เลือกตำบล --</option>';
                    zip.value = '';
                    sub.disabled = true;
                    if (!this.value) return;
                    const districts = [...new Set(thaiDB.filter(r => r.province === prov.value && r
                        .amphoe === this.value).map(r => r.district))].sort();
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

            loadThaiDB().then(db => {
                if (!db || !db.length) return;
                const provinces = [...new Set(db.map(r => r.province))].sort();

                // โหลดจังหวัดที่อยู่
                const sel = document.getElementById('add_province');
                sel.innerHTML = '<option value="">-- เลือกจังหวัด --</option>';
                provinces.forEach(p => sel.add(new Option(p, p)));
                setupCascading('add');

                // โหลดจังหวัดที่ออกบัตร
                const issueProvinceSel = document.getElementById('id_card_issue_province');
                if (issueProvinceSel) {
                    issueProvinceSel.innerHTML = '<option value="">-- เลือกจังหวัด --</option>';
                    provinces.forEach(p => {
                        let option = new Option(p, p);
                        if ('{{ old('id_card_issue_province') }}' === p) {
                            option.selected = true;
                        }
                        issueProvinceSel.add(option);
                    });
                }
            });

            new Cleave('.input-idcard', {
                blocks: [1, 4, 5, 2, 1],
                delimiter: '-',
                numericOnly: true
            });
            new Cleave('.input-phone', {
                blocks: [3, 3, 4],
                delimiter: '-',
                numericOnly: true
            });

            document.getElementById('addTenantForm').addEventListener('submit', function(e) {
                let idInput = document.querySelector('.input-idcard');
                let phoneInput = document.querySelector('.input-phone');
                idInput.value = idInput.value.replace(/\D/g, '');
                phoneInput.value = phoneInput.value.replace(/\D/g, '');
            });
        });
    </script>
</body>

</html>
