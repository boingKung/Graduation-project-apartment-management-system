@extends('admin.layout')
@section('title', 'สรุปงบรับ-จ่าย')
@section('content')
    <div class="container py-4 bg-white shadow-sm rounded">
        {{-- ส่วนปุ่มนำทาง (Navigation Buttons) --}}
        <div class="card border-0 shadow-sm mb-4 d-print-none">
            <div class="card-body p-3">
                <div class="row g-3 align-items-center">
                    <div class="col-xl-5 col-lg-12">
                        <div class="btn-group w-100 shadow-sm">
                            <a href="{{ route('admin.accounting_transactions.summary', request()->query()) }}"
                                class="btn {{ request()->routeIs('admin.accounting_transactions.summary') ? 'btn-dark' : 'btn-outline-dark' }} btn-sm px-3">
                                <i class="bi bi-file-earmark-bar-graph me-1"></i> สรุปงบรับ-จ่าย
                            </a>
                            <a href="{{ route('admin.accounting_transactions.income', request()->query()) }}"
                                class="btn btn-outline-success btn-sm px-3">
                                <i class="bi bi-graph-up-arrow me-1"></i> รายงานรายรับ
                            </a>
                            <a href="{{ route('admin.accounting_transactions.expense', request()->query()) }}"
                                class="btn {{ request()->routeIs('admin.accounting_transactions.expense') ? 'btn-danger' : 'btn-outline-danger' }} btn-sm px-3">
                                <i class="bi bi-graph-down-arrow me-1"></i> รายงานรายจ่าย
                            </a>
                        </div>
                    </div>

                    <div class="col-xl-7 col-lg-12">
                        <form method="GET" class="row g-2 justify-content-xl-end align-items-center">
                            <div class="col-auto">
                                <label class="small fw-bold text-muted">ตั้งแต่วันที่</label>
                            </div>
                            <div class="col-auto">
                                <input type="date" name="date_start"
                                    class="form-control form-control-sm border-secondary-subtle"
                                    value="{{ $startDate }}" onchange="this.form.submit()">
                            </div>
                            <div class="col-auto">
                                <label class="small fw-bold text-muted">ถึงวันที่</label>
                            </div>
                            <div class="col-auto">
                                <input type="date" name="date_end"
                                    class="form-control form-control-sm border-secondary-subtle"
                                    value="{{ $endDate }}" onchange="this.form.submit()">
                            </div>
                            <div class="col-auto d-flex align-items-end gap-1">
                                <a href="{{ route('admin.accounting_transactions.summary') }}"
                                    class="btn btn-light btn-sm border" title="ล้างค่า">
                                    <i class="bi bi-arrow-clockwise"></i>
                                </a>
                            </div>
                        </form>
                        {{-- ใส่ไว้ข้างๆ ปุ่มพิมพ์รายงานเดิม (Window.print) --}}
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center mb-4">
            <h3 class="fw-bold">งบรับ - จ่าย อาทิตย์อพาร์ทเม้นท์ เดือน {{ $displayDate }}</h3>
            ตั้งแต่วันที่ <span class="fw-bold">{{ $thai_startDate }}</span> ถึงวันที่ <span
                class="fw-bold">{{ $thai_endDate }}</span>
        </div>
        <div class="d-flex justify-content-end mb-3">
            {{-- 🌟 ปุ่ม Excel สีเขียว --}}
            <a href="{{ route('admin.accounting_transactions.exportSummaryExcel', request()->query()) }}" 
            class="btn btn-outline-success btn-sm me-3">
                <i class="bi bi-file-earmark-excel-fill me-1"></i> โหลด Excel
            </a>
            <a href="{{ route('admin.accounting_transactions.printSummaryPdf', request()->query()) }}"
                class="btn btn-outline-danger btn-sm" target="_blank">
                <i class="bi bi-file-earmark-pdf-fill me-1"></i> โหลด PDF งบสรุป
            </a>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-hover  align-middle border-dark ">
                <thead class="bg-light text-center border-dark">
                    <tr>
                        <th width="20%">รายการ</th>
                        <th width="30%">รายละเอียด</th>
                        <th width="25%">รายรับ (บาท)</th>
                        <th width="25%">รายจ่าย (บาท)</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- 🏢 ส่วนที่ 1: รายรับแยกตามตึก --}}
                    @foreach ($buildingIncome as $buildingName => $items)
                        @php
                            $rentSum = $items->filter(fn($i) => str_contains($i->title, 'ค่าเช่า'))->sum('amount');
                            $elecSum = $items->filter(fn($i) => str_contains($i->title, 'ค่าไฟ'))->sum('amount');
                        @endphp
                        <tr onclick="fetchDetails('building_rent', '{{ $buildingName }}')" style="cursor: pointer;"
                            title="คลิกเพื่อดูรายละเอียด">
                            <td rowspan="2" class="fw-bold text-center bg-light">{{ $buildingName }}</td>
                            <td class="ps-3 small">ค่าเช่า</td>
                            <td class="text-end pe-3">{{ number_format($rentSum, 2) }}</td>
                            <td></td>
                        </tr>
                        <tr onclick="fetchDetails('building_electric', '{{ $buildingName }}')" style="cursor: pointer;"
                            title="คลิกเพื่อดูรายละเอียด">
                            <td class="ps-3 small">ค่าไฟ</td>
                            <td class="text-end pe-3">{{ number_format($elecSum, 2) }}</td>
                            <td></td>
                        </tr>
                    @endforeach

                    {{-- ส่วนที่ 2: รายรับอื่นๆ [cite: 6] --}}
                    @foreach ($otherIncome as $name => $amount)
                        <tr onclick="fetchDetails('other_income', '{{ $name }}')" style="cursor: pointer;"
                            title="คลิกเพื่อดูรายละเอียด">
                            <td colspan="2" class="ps-3">{{ $name }}</td>
                            <td class="text-end pe-3">{{ number_format($amount, 2) }}</td>
                            <td></td>
                        </tr>
                    @endforeach

                    {{-- ส่วนที่ 3: ยอดค้างรับ --}}
                    <tr onclick="fetchDetails('unpaid', '')" style="cursor: pointer;" title="คลิกเพื่อดูรายละเอียด">
                        <td colspan="2" class="ps-3">เก็บค่าเช่าคงค้างเดือน {{ $displayDate }}</td>
                        <td class="text-end pe-3">{{ number_format($outstandingAmount, 2) }}</td>
                        <td></td>
                    </tr>

                    {{-- ส่วนที่ 4: รายจ่าย --}}
                    @foreach ($expenseByCats as $name => $amount)
                        <tr onclick="fetchDetails('expense', '{{ $name }}')" style="cursor: pointer;"
                            title="คลิกเพื่อดูรายละเอียด">
                            <td colspan="2" class="ps-3 text-secondary">{{ $name }}</td>
                            <td></td>
                            <td class="text-end pe-3 text-danger">{{ number_format($amount, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="">
                    <tr>
                        <th colspan="2" class="text-center">รวมยอด</th>
                        <th class="text-end pe-3">
                            {{ number_format($buildingIncome->flatten()->sum('amount') + $otherIncome->sum() + $outstandingAmount, 2) }}
                        </th>
                        <th class="text-end text-danger pe-3">{{ number_format($expenseByCats->sum(), 2) }}</th>
                    </tr>
                    <tr>
                        <th colspan="2" class="text-center py-3">รายรับสุทธิ (กำไร/ขาดทุน):</th>
                        <th colspan="2" class="text-end fs-4 py-3">
                            {{ number_format($buildingIncome->flatten()->sum('amount') + $otherIncome->sum() + $outstandingAmount - $expenseByCats->sum(), 2) }}
                            บาท
                        </th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    {{-- 📑 Modal สำหรับแสดงรายละเอียดรายการย่อย --}}
    <div class="modal fade" id="summaryDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-dark text-white border-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-list-ul me-2"></i><span
                            id="modalSummaryTitle">รายละเอียด</span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="table-responsive" style="max-height: 450px;">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th class="ps-3 py-3">วันที่</th>
                                    <th>รายการ</th>
                                    <th>รายละเอียดเพิ่มเติม</th>
                                    <th class="text-center">ห้อง</th>
                                    <th class="text-end pe-3">จำนวนเงิน</th>
                                </tr>
                            </thead>
                            <tbody id="modalTableBody">
                                {{-- ข้อมูลจาก AJAX จะมาแสดงที่นี่ --}}
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 py-2">
                    <button type="button" class="btn btn-secondary btn-sm px-4"
                        data-bs-dismiss="modal">ปิดหน้าต่าง</button>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script>
        function fetchDetails(target, name) {
            const modal = new bootstrap.Modal(document.getElementById('summaryDetailModal'));
            const tbody = document.getElementById('modalTableBody');
            const title = document.getElementById('modalSummaryTitle');

            // แสดง Loading ระหว่างรอข้อมูล
            title.innerText = 'กำลังโหลดข้อมูล...';
            tbody.innerHTML =
                '<tr><td colspan="5" class="text-center py-4"><div class="spinner-border text-primary"></div></td></tr>';
            modal.show();

            // ดึงข้อมูลผ่าน AJAX
            const params = new URLSearchParams({
                date_start: '{{ $startDate }}',
                date_end: '{{ $endDate }}',
                target: target,
                name: name
            });

            fetch(`{{ route('admin.accounting_transactions.getSummaryDetails') }}?${params.toString()}`)
                .then(response => response.json())
                .then(data => {
                    title.innerText = data.title;
                    if (data.items.length === 0) {
                        console.log(data);
                        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4">ไม่พบรายการข้อมูล</td></tr>';
                        return;
                    }

                    tbody.innerHTML = data.items.map(item => `
                <tr>
                    <td class="ps-3 small text-muted">${item.date}</td>
                    <td class="small fw-bold">${item.title}</td>
                    <td class="small ">${item.description}</td>
                    <td class="text-center">${item.room}</td>
                    <td class="text-end pe-3 fw-bold ${item.class}">${item.amount}</td>
                </tr>
            `).join('');
                })
                .catch(error => {
                    tbody.innerHTML =
                        '<tr><td colspan="4" class="text-center text-danger py-4">เกิดข้อผิดพลาดในการโหลดข้อมูล</td></tr>';
                });
        }
    </script>
@endpush
