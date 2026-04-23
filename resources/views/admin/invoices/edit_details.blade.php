@extends('admin.layout')
@section('title', 'แก้ไขใบแจ้งหนี้')
@section('content')
    <style>
        /* ขยายขนาดฟอนต์ให้ใหญ่ขึ้นตามคำขอ */
        #editInvoiceForm {
            font-size: 1rem;
        }

        #itemsTable input,
        #itemsTable select {
            font-size: 1rem;
            padding: 0.5rem;
            height: auto;
        }

        .table-primary-light {
            background-color: #f8fbff;
        }

        .fw-bold {
            font-weight: 700;
        }
    </style>

    <div class="container py-4">
        <form action="{{ route('admin.invoices.updateDetails', $invoice->id) }}" method="POST" id="editInvoiceForm">
            @csrf @method('PUT')
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    
                    <div class="mb-3">
                        <h4 class="fw-bold text-primary mb-1">
                            <i class="bi bi-receipt-cutoff me-1"></i>
                            แก้ไขรายการใบแจ้งหนี้
                        </h4>
                        <div class="small text-muted">
                            เลขที่บิล: <span class="fw-semibold text-dark">#{{ $invoice->invoice_number }}</span>
                            • รอบเดือน: {{ $thai_billing_month }}
                        </div>
                    </div>

                    <hr class="my-3">

                    <div class="row justify-content-end align-items-center mb-4 g-3">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold text-dark small mb-1">
                                <i class="bi bi-calendar-event me-1 text-primary"></i>
                                วันที่ออกบิล
                            </label>
                            <input type="date" name="issue_date" class="form-control shadow-sm"
                                value="{{ old('issue_date', $invoice->issue_date) }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold text-dark small mb-1">
                                <i class="bi bi-calendar-check me-1 text-danger"></i>
                                วันที่ครบกำหนดชำระ
                            </label>
                            <input type="date" name="due_date" class="form-control shadow-sm border-danger border-opacity-50"
                                value="{{ old('due_date', \Carbon\Carbon::parse($invoice->due_date)->format('Y-m-d')) }}" required>
                        </div>
                    </div>

                    <div class="table-responsive border rounded-3 mb-3">
                        <table class="table table-bordered align-middle mb-0" id="itemsTable">
                            <thead class="table-light text-center">
                                <tr>
                                    <th width="35%">รายการเรียกเก็บ</th>
                                    <th width="10%">จำนวน</th>
                                    <th width="20%">ราคา/หน่วย</th>
                                    <th width="20%">จำนวนเงิน</th>
                                    <th width="5%">ลบ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($invoice->details as $index => $item)
                                    @php
                                        // รายการระบบที่ห้ามเปลี่ยนชื่อ
                                        $isSystemItem =
                                            $item->name == 'ค่าเช่าห้อง' ||
                                            $item->name == 'ค่าน้ำ' ||
                                            $item->name == 'ค่าไฟ' ||
                                            $item->meter_reading_id;
                                        // รายการห้องพักที่ห้ามแก้จำนวน
                                        $isRoom = $item->name == 'ค่าเช่าห้อง';
                                    @endphp
                                    <tr>
                                        <td>
                                            @if ($item->tenant_expense_id && !$isSystemItem)
                                                <select name="items[{{ $index }}][expense_id]"
                                                    class="form-select expense-select"
                                                    onchange="updatePriceFromSelect(this)">
                                                    @foreach ($expenses as $ex)
                                                        <option value="{{ $ex->id }}" data-price="{{ $ex->price }}"
                                                            {{ $item->tenant_expense_id == $ex->id ? 'selected' : '' }}>
                                                            {{ $ex->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <input type="hidden" name="items[{{ $index }}][name]"
                                                    value="{{ $item->name }}">
                                            @else
                                                <input type="text" name="items[{{ $index }}][name]"
                                                    class="form-control @if ($isSystemItem)  @endif"
                                                    value="{{ $item->name }}"
                                                    @if ($isSystemItem)  @endif required>
                                                <input type="hidden" name="items[{{ $index }}][expense_id]"
                                                    value="{{ $item->tenant_expense_id }}">
                                            @endif

                                            <input type="hidden" name="items[{{ $index }}][meter_reading_id]"
                                                value="{{ $item->meter_reading_id }}">
                                            <input type="hidden" name="items[{{ $index }}][previous_unit]"
                                                value="{{ $item->previous_unit }}">
                                            <input type="hidden" name="items[{{ $index }}][current_unit]"
                                                value="{{ $item->current_unit }}">
                                        </td>
                                        <td>
                                            <input type="number" name="items[{{ $index }}][quantity]"
                                                min="0"
                                                class="form-control text-center qty @if ($isRoom || $item->meter_reading_id)  @endif"
                                                value="{{ $item->quantity }}" step="any" oninput="calculateRow(this)"
                                                @if ($isRoom || $item->meter_reading_id)  @endif required>
                                        </td>
                                        <td>
                                            <input type="number" name="items[{{ $index }}][price]" min="0"
                                                class="form-control text-end price" value="{{ $item->price_per_unit }}"
                                                step="0.01" oninput="calculateRow(this)" required>
                                        </td>
                                        <td>
                                            <input type="text"
                                                class="form-control text-end border-0 bg-transparent row-subtotal fw-bold"
                                                value="{{ number_format($item->subtotal, 2) }}" readonly>
                                        </td>
                                        <td class="text-center">
                                            @if (!$isSystemItem)
                                                <button type="button" class="btn btn-link text-danger p-0"
                                                    onclick="removeRow(this)">
                                                    <i class="bi bi-trash fs-5"></i>
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-start mt-3">
                        <div>
                            <button type="button" class="btn btn-outline-primary shadow-sm" onclick="addRow('standard')">
                                <i class="bi bi-plus-circle me-1"></i> เพิ่มรายการ
                            </button>
                        </div>
                        <div class="text-end">
                            <h3 class="fw-bold mb-0">ยอดรวมสุทธิ: <span id="grandTotal"
                                    class="text-primary">{{ number_format($invoice->total_amount, 2) }}</span> <small class="fs-6 text-muted fw-normal">บาท</small></h3>
                        </div>
                    </div>

                    <hr class="mt-4 mb-3">
                    
                    <div class="text-end">
                        <a href="{{ url()->previous() }}" class="btn btn-lg btn-light px-5 me-2 border">ยกเลิก</a>
                        <button type="button" onclick="confirmUpdate()" class="btn btn-lg btn-primary px-5 shadow">บันทึกการแก้ไข</button>
                    </div>

                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    {{-- (สคริปต์ Javascript ส่วนล่าง คงเดิมทุกประการครับ ผมไม่ได้แก้ไขเพราะทำงานได้ดีอยู่แล้ว) --}}
    <script>
        function confirmUpdate() {
            let form = document.getElementById('editInvoiceForm'); 
            if (!form.reportValidity()) return; 

            Swal.fire({
                title: 'กำลังบันทึกข้อมูล...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });
            form.submit();
        }
        
        let rowIdx = {{ $invoice->details->count() }};
        const masterExpenses = @json($expenses);

        function refreshDropdowns() {
            const selectedIds = Array.from(document.querySelectorAll('.expense-select'))
                .map(select => select.value)
                .filter(value => value !== "");

            document.querySelectorAll('.expense-select').forEach(select => {
                const currentValue = select.value;
                select.innerHTML = '<option value="">-- เลือกรายการมาตรฐาน --</option>';

                masterExpenses.forEach(ex => {
                    const exId = ex.id.toString();
                    if (!selectedIds.includes(exId) || exId === currentValue) {
                        const option = document.createElement('option');
                        option.value = exId;
                        option.textContent = ex.name;
                        option.dataset.price = ex.price;

                        if (exId === currentValue) {
                            option.selected = true;
                        }
                        select.appendChild(option);
                    }
                });
            });
        }

        function addRow(type) {
            let html = `
            <tr class="table-primary-light">
                <td>
                    <select name="items[${rowIdx}][expense_id]" class="form-select expense-select" onchange="updatePriceFromSelect(this)" required>
                        <option value="">-- เลือกรายการมาตรฐาน --</option>
                    </select>
                    <input type="hidden" name="items[${rowIdx}][name]" value="">
                </td>
                <td><input type="number" name="items[${rowIdx}][quantity]" class="form-control text-center qty" value="1" min="0" oninput="calculateRow(this)" required></td>
                <td><input type="number" name="items[${rowIdx}][price]" class="form-control text-end price" value="0" step="0.01" min="0" oninput="calculateRow(this)" required></td>
                <td><input type="text" class="form-control text-end border-0 bg-transparent row-subtotal fw-bold" value="0.00" readonly></td>
                <td class="text-center"><button type="button" class="btn btn-link text-danger p-0" onclick="removeRow(this)"><i class="bi bi-trash fs-5"></i></button></td>
            </tr>`;
            
            document.querySelector('#itemsTable tbody').insertAdjacentHTML('beforeend', html);
            rowIdx++;
            refreshDropdowns();
        }

        function calculateRow(input) {
            const row = input.closest('tr');
            const qtyInput = row.querySelector('.qty');
            const priceInput = row.querySelector('.price');

            if (qtyInput.value < 0) qtyInput.value = 0;
            if (priceInput.value < 0) priceInput.value = 0;

            const qty = parseFloat(qtyInput.value) || 0;
            const price = parseFloat(priceInput.value) || 0;
            const subtotal = qty * price;

            row.querySelector('.row-subtotal').value =
                subtotal.toLocaleString('th-TH', { minimumFractionDigits: 2 });

            updateGrandTotal();
        }

        function updatePriceFromSelect(select) {
            const row = select.closest('tr');
            const priceInput = row.querySelector('.price');
            const nameHidden = row.querySelector('input[type="hidden"]');

            if (select.value !== "") {
                const opt = select.options[select.selectedIndex];
                priceInput.value = opt.dataset.price;
                if (nameHidden) nameHidden.value = opt.text.trim();
            }

            calculateRow(priceInput);
            refreshDropdowns();
        }

        function updateGrandTotal() {
            let total = 0;
            document.querySelectorAll('.row-subtotal').forEach(el => {
                total += parseFloat(el.value.replace(/,/g, '')) || 0;
            });

            document.getElementById('grandTotal').innerText =
                total.toLocaleString('th-TH', { minimumFractionDigits: 2 });
        }

        function removeRow(btn) {
            Swal.fire({
                title: 'ยืนยันการลบรายการนี้?',
                text: "รายการที่ลบจะหายไปจากตารางและยอดรวมจะถูกคำนวณใหม่ทันที",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'ตกลง, ลบรายการ',
                cancelButtonText: 'ยกเลิก',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    btn.closest('tr').remove();
                    updateGrandTotal();
                    refreshDropdowns();
                }
            });
        }
        
        document.addEventListener('DOMContentLoaded', refreshDropdowns);
    </script>
@endpush