@extends('admin.layout')
@section('title', 'เพิ่มรายรับ/รายจ่าย')
@section('content')
    {{-- กำหนดธีมสีตามประเภท --}}
    @php
        $isIncome = $typeId == 1;
        $themeColor = $isIncome ? 'success' : 'danger';
        $bgLight = $isIncome ? '#f1fcf4' : '#fcf1f1';
    @endphp

    <div class="container-fluid py-4" style="min-height: 100vh; background-color: {{ $bgLight }};">
        <form action="{{ route('admin.accounting_transactions.store') }}" method="POST" id="transactionForm">
            @csrf
            <input type="hidden" name="type_id" value="{{ $typeId }}">

            <div class="row justify-content-center">
                <div class="col-xl-11">
                    <div class="card shadow-sm border-0 mb-4">
                        {{-- Header --}}
                        <div class="card-header bg-{{ $themeColor }} text-white py-3 border-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <h4 class="mb-0 fw-bold">
                                    <i class="bi {{ $isIncome ? 'bi-plus-circle' : 'bi-dash-circle' }} me-2"></i>
                                    บันทึกรายการ{{ $typeName }} (หลายรายการ)
                                </h4>
                                <a href="{{ route('admin.accounting_transactions.show') }}"
                                    class="btn btn-light btn-sm text-{{ $themeColor }} fw-bold">
                                    <i class="bi bi-arrow-left me-1"></i> กลับหน้าสรุป
                                </a>
                            </div>
                        </div>

                        <div class="card-body p-4">
                            <div class="row mb-4 justify-content-end align-items-center mb-3">
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">วันที่ทำรายการ</label>
                                    <input type="date" name="entry_date" class="form-control border-{{ $themeColor }}"
                                        value="{{ date('Y-m-d') }}" required>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered align-middle" id="itemsTable">
                                    <thead class="table-{{ $themeColor }} text-center small">
                                        <tr>
                                            <th width="15%">หมวดหมู่</th>
                                            <th width="18%">หัวข้อรายการธุรกรรม</th>
                                            <th width="18%">รายละเอียดเพิ่มเติม</th>
                                            <th width="20%">อ้างอิง อาคาร / ห้อง (ถ้ามี)</th> {{-- 🌟 คอลัมน์ตึก/ห้อง --}}
                                            <th width="24%">จำนวนเงิน (บาท)</th>
                                            <th width="5%">ลบ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="item-row">
                                            <td>
                                                <select name="items[0][category_id]" class="form-select border-{{ $themeColor }}" required>
                                                    <option value="">-- เลือก --</option>
                                                    @foreach ($categories as $cat)
                                                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <input type="text" name="items[0][title]" class="form-control" placeholder="เช่น ค่าส่วนกลาง" required>
                                            </td>
                                            <td>
                                                <textarea name="items[0][description]" class="form-control" rows="1" placeholder="ระบุรายละเอียด..."></textarea>
                                            </td>
                                            <td>
                                                {{-- 🌟 Dropdown อาคาร --}}
                                                <select name="items[0][building_id]" class="form-select form-select-sm border-{{ $themeColor }} mb-1" onchange="updateRoomOptions(this, 0)">
                                                    <option value="">-- ไม่ระบุอาคาร --</option>
                                                    @foreach($buildings as $b)
                                                        <option value="{{ $b->id }}">{{ $b->name }}</option>
                                                    @endforeach
                                                </select>
                                                {{-- 🌟 Dropdown ห้อง --}}
                                                <select name="items[0][room_id]" id="roomSelect-0" class="form-select form-select-sm border-{{ $themeColor }}">
                                                    <option value="">-- ไม่ระบุห้อง --</option>
                                                </select>
                                            </td>
                                            <td>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-white text-{{ $themeColor }} fw-bold">{{ $isIncome ? '+' : '-' }}</span>
                                                    <input type="number" name="items[0][amount]" class="form-control text-end fw-bold amount-input" step="0.01" min="0" oninput="calculateTotal()" required>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-{{ $themeColor }} btn-sm rounded-circle shadow-sm" onclick="removeRow(this)">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-3">
                                <button type="button" class="btn btn-outline-{{ $themeColor }} btn-sm px-4 fw-bold" onclick="addRow()">
                                    <i class="bi bi-plus-lg me-1"></i> เพิ่มอีก 1 รายการ
                                </button>
                            </div>

                            <hr class="my-4">

                            <div class="d-flex justify-content-between align-items-center">
                                <div class="text-muted small">
                                    * บันทึกโดยแอดมิน: <strong>{{ Auth::user()->firstname }}</strong>
                                </div>
                                <div class="text-end">
                                    <h4 class="mb-0 text-muted">รวมยอดเงิน{{ $typeName }}ทั้งหมด</h4>
                                    <h2 class="fw-bold text-{{ $themeColor }}">
                                        <span id="grandTotal">0.00</span> บาท
                                    </h2>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer bg-light p-4 text-end border-0">
                            <button type="button" class="btn btn-{{ $themeColor }} btn-lg px-5 shadow-sm fw-bold" onclick="confirmSave()">
                                <i class="bi bi-save me-2"></i> บันทึกรายการทั้งหมด
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        let rowIdx = 1;
        // 🌟 ดึงข้อมูลตึกและห้องจาก PHP มาเป็น JSON
        const buildingsData = @json($buildings);

        // 🌟 ฟังก์ชันเปลี่ยนตัวเลือกห้องเมื่อเลือกตึก
        function updateRoomOptions(buildingSelect, idx) {
            const buildingId = buildingSelect.value;
            const roomSelect = document.getElementById(`roomSelect-${idx}`);
            
            // ล้างค่าเก่า
            roomSelect.innerHTML = '<option value="">-- ไม่ระบุห้อง --</option>';
            
            if (buildingId) {
                const building = buildingsData.find(b => b.id == buildingId);
                if (building && building.rooms) {
                    building.rooms.forEach(room => {
                        roomSelect.innerHTML += `<option value="${room.id}">ห้อง ${room.room_number}</option>`;
                    });
                }
            }
        }

        function addRow() {
            const categories = @json($categories);
            let catOptions = categories.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
            
            let buildingOptions = '<option value="">-- ไม่ระบุอาคาร --</option>';
            buildingsData.forEach(b => { buildingOptions += `<option value="${b.id}">${b.name}</option>`; });

            const themeColor = "{{ $themeColor }}";
            const sign = "{{ $isIncome ? '+' : '-' }}";

            const html = `
                <tr class="item-row">
                    <td>
                        <select name="items[${rowIdx}][category_id]" class="form-select border-${themeColor}" required>
                            <option value="">-- เลือก --</option>${catOptions}
                        </select>
                    </td>
                    <td><input type="text" name="items[${rowIdx}][title]" class="form-control" placeholder="ระบุหัวข้อ..." required></td>
                    <td><textarea name="items[${rowIdx}][description]" class="form-control" rows="1" placeholder="ระบุรายละเอียด..."></textarea></td>
                    <td>
                        <select name="items[${rowIdx}][building_id]" class="form-select form-select-sm border-${themeColor} mb-1" onchange="updateRoomOptions(this, ${rowIdx})">
                            ${buildingOptions}
                        </select>
                        <select name="items[${rowIdx}][room_id]" id="roomSelect-${rowIdx}" class="form-select form-select-sm border-${themeColor}">
                            <option value="">-- ไม่ระบุห้อง --</option>
                        </select>
                    </td>
                    <td>
                        <div class="input-group">
                            <span class="input-group-text bg-white text-${themeColor} fw-bold">${sign}</span>
                            <input type="number" name="items[${rowIdx}][amount]" class="form-control text-end fw-bold amount-input" step="0.01" min="0" oninput="calculateTotal()" required>
                        </div>
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-${themeColor} btn-sm rounded-circle shadow-sm" onclick="removeRow(this)">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </td>
                </tr>`;

            document.querySelector('#itemsTable tbody').insertAdjacentHTML('beforeend', html);
            rowIdx++;
        }

        function removeRow(btn) {
            if (document.querySelectorAll('#itemsTable tbody tr').length > 1) {
                btn.closest('tr').remove();
                calculateTotal();
            }
        }

        function calculateTotal() {
            let total = 0;
            document.querySelectorAll('.amount-input').forEach(input => {
                total += parseFloat(input.value) || 0;
            });
            document.getElementById('grandTotal').innerText = total.toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function confirmSave() {
            const rows = document.querySelectorAll('.item-row');
            const dateInput = document.querySelector('input[name="entry_date"]').value;
            
            let isValid = true;
            let errorMsg = "";

            if (!dateInput) {
                Swal.fire({ icon: 'warning', title: 'กรุณาระบุวันที่', text: 'ต้องเลือกวันที่ทำรายการก่อนบันทึก' });
                return;
            }

            for (let i = 0; i < rows.length; i++) {
                const row = rows[i];
                const category = row.querySelector('select[name*="[category_id]"]').value;
                const title = row.querySelector('input[name*="[title]"]').value.trim();
                const amount = row.querySelector('input[name*="[amount]"]').value;

                if (!category || !title || !amount || parseFloat(amount) <= 0) {
                    isValid = false;
                    errorMsg = `ข้อมูลในแถวที่ ${i + 1} ไม่ครบถ้วน (กรุณาเลือกหมวดหมู่, ใส่หัวข้อ และจำนวนเงินที่มากกว่า 0)`;
                    break;
                }
            }

            if (!isValid) {
                Swal.fire({ icon: 'error', title: 'พบข้อมูลไม่ถูกต้อง', text: errorMsg, confirmButtonColor: '#dc3545' });
                return;
            }

            const d = new Date(dateInput);
            const thaiMonths = ["มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"];
            const thaiDateDisplay = `${d.getDate()} ${thaiMonths[d.getMonth()]} ${d.getFullYear() + 543}`;
            const totalText = document.getElementById('grandTotal').innerText;
            const themeColor = "{{ $themeColor == 'success' ? '#198754' : '#dc3545' }}";

            Swal.fire({
                title: `ยืนยันบันทึกรายการ{{ $typeName }}?`,
                html: `
                    <div class="text-center">
                        <div class="mb-3 p-2 bg-light rounded border">
                            <span class="text-muted d-block small mb-1">วันที่ทำรายการ</span>
                            <h5 class="fw-bold mb-0 text-dark">${thaiDateDisplay}</h5>
                        </div>
                        <div class="mb-3">
                            <span class="text-muted small">ยอดรวมสุทธิที่จะบันทึกลงบัญชี</span>
                            <h2 class="fw-bold" style="color: ${themeColor}">${totalText} บาท</h2>
                        </div>
                        <div class="alert alert-warning py-2 small border-0 shadow-sm">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i>
                            <b>คำเตือน:</b> เมื่อบันทึกแล้วจะไม่สามารถแก้ไขข้อมูลได้
                        </div>
                    </div>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: themeColor,
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'ยืนยัน บันทึกข้อมูล',
                cancelButtonText: 'กลับไปแก้ไข',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({ title: 'กำลังบันทึก...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
                    document.getElementById('transactionForm').submit();
                }
            });
        }
    </script>
@endpush