@extends('admin.layout')
@section('title', 'แผงควบคุม (Dashboard)')

@section('content')
    @push('styles')
    <style>
        /* แต่ง Scrollbar ให้สวยงาม */
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #c1c1c1; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #a8a8a8; }
        
        /* สไตล์สำหรับหลอด Data Bar */
        .data-bar-bg { background-color: #f8f9fa; border-radius: 6px; overflow: hidden; height: 38px; }
        .data-bar-fill { height: 100%; border-radius: 6px; transition: width 0.8s ease-in-out; }
    </style>
    @endpush

    <div class="container-fluid pb-5">

        {{-- Header & Greeting --}}
        <div class="d-flex flex-wrap align-items-end justify-content-between mb-4 gap-2">
            <div>
                <h4 class="fw-bold mb-1">
                    @php
                        $hour = (int) date('H');
                        $greeting = $hour < 12 ? 'อรุณสวัสดิ์' : ($hour < 17 ? 'สวัสดีตอนบ่าย' : 'สวัสดีตอนเย็น');
                    @endphp
                    {{ $greeting }}, {{ Auth::guard('admin')->user()->firstname ?? 'Admin' }} 👋
                </h4>
                <p class="text-muted mb-0">ภาพรวมกิจการอพาร์ทเม้นท์</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.meter_readings.insertForm') }}" class="btn btn-primary btn-sm shadow-sm">
                    <i class="bi bi-speedometer2 me-1"></i> จดมิเตอร์
                </a>
                <a href="{{ route('admin.invoices.show') }}" class="btn btn-outline-primary btn-sm shadow-sm bg-white">
                    <i class="bi bi-receipt me-1"></i> จัดการบิล
                </a>
            </div>
        </div>

        {{-- Slicer (ตัวกรองข้อมูลแบบ PowerBI) --}}
        <div class="card border-0 shadow-sm mb-4 rounded-3 bg-white">
            <div class="card-body p-3">
                <div class="row align-items-center g-2">
                    <div class="col-auto">
                        <span class="fw-bold text-muted me-2"><i class="bi bi-funnel-fill"></i> ตัวกรองข้อมูล:</span>
                    </div>
                    <div class="col-md-2 col-5">
                        <input type="month" id="slicerStartMonth" class="form-control form-control-sm border-secondary shadow-none" value="{{ now()->subMonths(5)->format('Y-m') }}" onchange="updateDashboard()">
                    </div>
                    <div class="col-auto text-muted small px-0">ถึง</div>
                    <div class="col-md-2 col-5">
                        <input type="month" id="slicerEndMonth" class="form-control form-control-sm border-secondary shadow-none" value="{{ now()->format('Y-m') }}" onchange="updateDashboard()">
                    </div>
                    <div class="col-md-3 col-12">
                        <select id="slicerBuilding" class="form-select form-select-sm border-secondary shadow-none" onchange="updateDashboard()">
                            <option value="">ทุกอาคาร</option>
                            @foreach ($buildings as $b)
                                <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-auto ms-auto d-none d-md-block">
                        <button class="btn btn-sm btn-light border" onclick="location.reload()">
                            <i class="bi bi-arrow-clockwise"></i> รีเฟรช
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- 🌟 Summary KPI Cards (ปรับเป็น col-lg-3 เพื่อให้เรียง 4 กล่องพอดี) 🌟 --}}
        <div class="row g-3 mb-4">
            
            {{-- 1. ห้องพัก --}}
            <div class="col-lg-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 rounded-4">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="text-muted fw-bold">อัตราการเข้าพัก</div>
                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                                <i class="bi bi-door-open-fill fs-5"></i>
                            </div>
                        </div>
                        <div class="d-flex align-items-baseline mb-1">
                            <h2 class="fw-bold mb-0 text-dark">{{ $occupiedRooms }}</h2>
                            <span class="text-muted ms-1">/ {{ $totalRooms }} ห้อง</span>
                        </div>
                        @php $pct = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100) : 0; @endphp
                        <div class="progress mt-3" style="height: 6px;">
                            <div class="progress-bar bg-primary" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 2. จองออนไลน์ (ใหม่) --}}
            <div class="col-lg-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 rounded-4">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="text-muted fw-bold">จองออนไลน์รออนุมัติ</div>
                            <div class="bg-info bg-opacity-10 text-info rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                                <i class="bi bi-globe fs-5"></i>
                            </div>
                        </div>
                        <div class="d-flex align-items-baseline mb-1">
                            <h2 class="fw-bold mb-0 {{ $pendingRegistrations > 0 ? 'text-info' : 'text-success' }}">{{ $pendingRegistrations }}</h2>
                            <span class="text-muted ms-2 small">รายการ</span>
                        </div>
                        <a href="{{ route('admin.registrations.show') }}" class="text-decoration-none small mt-3 d-inline-block fw-semibold text-info">ตรวจสอบรายการ <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
            </div>

            {{-- 3. แจ้งซ่อม --}}
            <div class="col-lg-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 rounded-4">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="text-muted fw-bold">แจ้งซ่อมรอดำเนินการ</div>
                            <div class="bg-warning bg-opacity-10 text-warning rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                                <i class="bi bi-wrench fs-5"></i>
                            </div>
                        </div>
                        <div class="d-flex align-items-baseline mb-1">
                            <h2 class="fw-bold mb-0 {{ $pendingMaintenance->count() > 0 ? 'text-warning' : 'text-success' }}">{{ $pendingMaintenance->count() }}</h2>
                            <span class="text-muted ms-2 small">รายการ</span>
                        </div>
                        <a href="{{ route('admin.maintenance.index') }}" class="text-decoration-none small mt-3 d-inline-block fw-semibold text-primary">ดูรายการแจ้งซ่อม <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
            </div>

            {{-- 4. จดมิเตอร์ --}}
            <div class="col-lg-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 rounded-4">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="text-muted fw-bold">มิเตอร์รอจด (เดือนนี้)</div>
                            <div class="bg-danger bg-opacity-10 text-danger rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                                <i class="bi bi-speedometer fs-5"></i>
                            </div>
                        </div>
                        @if ($roomsNeedMeter > 0)
                            <div class="d-flex align-items-baseline mb-1">
                                <h2 class="fw-bold mb-0 text-danger">{{ $roomsNeedMeter }}</h2>
                                <span class="text-muted ms-2 small">รายการ</span>
                            </div>
                            <a href="{{ route('admin.meter_readings.insertForm') }}" class="text-decoration-none small mt-3 d-inline-block fw-semibold text-danger">ไปหน้าจดมิเตอร์ <i class="bi bi-arrow-right"></i></a>
                        @else
                            <div class="d-flex align-items-center gap-2 mt-2">
                                <h2 class="fw-bold mb-0 text-success"><i class="bi bi-check-circle-fill"></i></h2>
                                <span class="fw-bold text-success fs-5">จดครบแล้ว</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Charts Section --}}
        <div class="row g-4 mb-4">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100 rounded-4">
                    <div class="card-body p-4">
                        <h6 class="fw-bold text-dark mb-4"><i class="bi bi-graph-up-arrow text-primary me-2"></i>แนวโน้มกระแสเงินสด</h6>
                        <div class="position-relative w-100" style="height: 280px;">
                            <canvas id="cashflowChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 rounded-4">
                    <div class="card-body p-4 text-center">
                        <h6 class="fw-bold text-dark mb-4 text-start"><i class="bi bi-receipt text-warning me-2"></i>สถานะบิล</h6>
                        <div class="position-relative w-100 d-flex justify-content-center" style="height: 220px;">
                            <canvas id="invoiceChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 rounded-4">
                    <div class="card-body p-4 text-center">
                        <h6 class="fw-bold text-dark mb-4 text-start"><i class="bi bi-building text-info me-2"></i>สัดส่วนห้องพัก</h6>
                        <div class="position-relative w-100 d-flex justify-content-center" style="height: 220px;">
                            <canvas id="roomChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

{{-- 🌟 Top Income & Expense (แบบ Data Bar PowerBI) 🌟 --}}
        <div class="row g-4 mb-4">
            {{-- Top Income --}}
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100 rounded-4">
                    <div class="card-header bg-white border-0 py-3 px-4 pt-4 rounded-top-4">
                        <h6 class="fw-bold mb-0"><i class="bi bi-sort-down-alt text-success me-2"></i>อันดับรายรับทั้งหมด</h6>
                    </div>
                    <div class="card-body px-4 pt-0 pb-4">
                        {{-- 🌟 เพิ่มกล่อง Scrollbar ความสูง 300px --}}
                        <div id="topIncomeContainer" class="pe-2 custom-scrollbar" style="max-height: 300px; overflow-y: auto;">
                            <div class="text-center py-4 text-muted">
                                <div class="spinner-border spinner-border-sm text-success me-2"></div> กำลังโหลด...
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            {{-- Top Expense --}}
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100 rounded-4">
                    <div class="card-header bg-white border-0 py-3 px-4 pt-4 rounded-top-4">
                        <h6 class="fw-bold mb-0"><i class="bi bi-sort-down-alt text-danger me-2"></i>อันดับรายจ่ายทั้งหมด</h6>
                    </div>
                    <div class="card-body px-4 pt-0 pb-4">
                        {{-- 🌟 เพิ่มกล่อง Scrollbar ความสูง 300px --}}
                        <div id="topExpenseContainer" class="pe-2 custom-scrollbar" style="max-height: 300px; overflow-y: auto;">
                            <div class="text-center py-4 text-muted">
                                <div class="spinner-border spinner-border-sm text-danger me-2"></div> กำลังโหลด...
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Action Lists (บิลค้าง & สัญญาหมดอายุ) --}}
        <div class="row g-4">
            {{-- บิลค้างชำระ --}}
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100 rounded-4">
                    <div class="card-header bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center rounded-top-4">
                        <h6 class="fw-bold mb-0">
                            <i class="bi bi-exclamation-triangle-fill text-danger me-2"></i>บิลค้างชำระเกินกำหนด
                            @if ($overdueInvoices->count() > 0)
                                <span class="badge bg-danger rounded-pill ms-2">{{ $overdueInvoices->count() }}</span>
                            @endif
                        </h6>
                        <a href="{{ route('admin.payments.pendingInvoicesShow') }}" class="btn btn-sm btn-light text-primary">ดูทั้งหมด</a>
                    </div>
                    <div class="card-body px-4 pt-0 pb-3">
                        @if ($overdueInvoices->count() > 0)
                            <div class="table-responsive pe-2 custom-scrollbar" style="max-height: 280px; overflow-y: auto;">
                                <table class="table table-hover align-middle mb-0">
                                    <tbody>
                                        @foreach ($overdueInvoices as $inv)
                                            @php
                                                $dueDate = \Carbon\Carbon::parse($inv->due_date)->startOfDay();
                                                $today = \Carbon\Carbon::now()->startOfDay();
                                                $daysOverdue = (int) $dueDate->diffInDays($today);
                                                $urgencyClass = $daysOverdue >= 15 ? 'text-danger' : 'text-warning text-dark';
                                            @endphp
                                            <tr>
                                                <td class="py-3 border-0 border-bottom">
                                                    <div class="fw-bold text-dark">ห้อง {{ $inv->room->room_number ?? '-' }}</div>
                                                    <small class="text-muted">{{ $inv->tenant->full_name ?? '-' }}</small>
                                                </td>
                                                <td class="text-end py-3 border-0 border-bottom">
                                                    <div class="fw-bold text-danger mb-1">฿{{ number_format($inv->remaining_balance, 0) }}</div>
                                                    <span class="badge bg-danger bg-opacity-10 {{ $urgencyClass }} rounded-pill small">เกินมา {{ $daysOverdue }} วัน</span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-5 text-muted">
                                <i class="bi bi-check2-circle fs-1 text-success mb-2 d-block"></i> ไม่มีบิลค้างชำระเกินกำหนด
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- สัญญาใกล้หมดอายุ --}}
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100 rounded-4">
                    <div class="card-header bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center rounded-top-4">
                        <h6 class="fw-bold mb-0">
                            <i class="bi bi-calendar-x text-secondary me-2"></i>สัญญาใกล้หมดอายุ (30 วัน)
                            @if ($expiringContracts->count() > 0)
                                <span class="badge bg-secondary rounded-pill ms-2">{{ $expiringContracts->count() }}</span>
                            @endif
                        </h6>
                    </div>
                    <div class="card-body px-4 pt-0 pb-3">
                        @if ($expiringContracts->count() > 0)
                            <div class="table-responsive pe-2 custom-scrollbar" style="max-height: 280px; overflow-y: auto;">
                                <table class="table table-hover align-middle mb-0">
                                    <tbody>
                                        @foreach ($expiringContracts as $tenant)
                                            @php
                                                $endDate = \Carbon\Carbon::parse($tenant->end_date)->startOfDay();
                                                $today = \Carbon\Carbon::now()->startOfDay();
                                                $daysLeft = (int) $today->diffInDays($endDate);
                                                $urgency = $daysLeft <= 7 ? 'text-danger' : 'text-warning text-dark';
                                            @endphp
                                            <tr>
                                                <td class="py-3 border-0 border-bottom">
                                                    <div class="d-flex align-items-center">
                                                        <div class="bg-light text-secondary rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0" style="width:40px;height:40px;">
                                                            <i class="bi bi-person-fill fs-5"></i>
                                                        </div>
                                                        <div>
                                                            <div class="fw-bold text-dark">ห้อง {{ $tenant->room->room_number ?? '-' }}</div>
                                                            <small class="text-muted">{{ $tenant->full_name }}</small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="text-end py-3 border-0 border-bottom">
                                                    <div class="small text-muted mb-1">หมด: {{ \Carbon\Carbon::parse($tenant->end_date)->locale('th')->isoFormat('D MMM YY') }}</div>
                                                    <span class="badge bg-light border {{ $urgency }} rounded-pill small">เหลือ {{ $daysLeft }} วัน</span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-5 text-muted">
                                <i class="bi bi-calendar-check fs-1 text-success mb-2 d-block"></i> ไม่มีสัญญาใกล้หมดอายุ
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        let cashflowChartObj, invoiceChartObj, roomChartObj;

        document.addEventListener('DOMContentLoaded', function() {
            updateDashboard();
        });

        function updateDashboard() {
            const startMonth = document.getElementById('slicerStartMonth').value;
            const endMonth = document.getElementById('slicerEndMonth').value;
            const building = document.getElementById('slicerBuilding').value;

            if (startMonth > endMonth) {
                alert('กรุณาเลือกเดือนเริ่มต้น ให้ก่อนหรือเท่ากับ เดือนสิ้นสุด');
                return;
            }

            fetch(`{{ route('admin.dashboard.chart_data') }}?start_month=${startMonth}&end_month=${endMonth}&building_id=${building}`)
                .then(response => response.json())
                .then(data => {
                    renderCashflowChart(data.cashflow);
                    renderInvoiceChart(data.invoice_status);
                    renderRoomChart(data.room_status);
                    
                    // 🌟 วาดตาราง Data Bar
                    renderDataBarList('topIncomeContainer', data.top_income, 'bg-success');
                    renderDataBarList('topExpenseContainer', data.top_expense, 'bg-danger');
                })
                .catch(error => console.error('Error loading chart data:', error));
        }

        // 🌟 ฟังก์ชันสร้างตารางแบบ Data Bar (คล้าย Power BI)
        function renderDataBarList(containerId, data, bgClass) {
            const container = document.getElementById(containerId);
            
            if (!data || data.length === 0) {
                container.innerHTML = '<div class="text-center text-muted py-4 small"><i class="bi bi-inbox fs-2 d-block mb-2"></i>ไม่มีรายการในระบบบัญชี</div>';
                return;
            }

            const maxVal = Math.max(...data.map(d => parseFloat(d.total)));
            let html = '<div class="d-flex flex-column gap-3 mt-1">';

            data.forEach(item => {
                const total = parseFloat(item.total);
                const pct = maxVal > 0 ? (total / maxVal) * 100 : 0;
                const formattedVal = new Intl.NumberFormat('th-TH', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(total);

                html += `
                    <div class="position-relative data-bar-bg">
                        <div class="position-absolute start-0 top-0 data-bar-fill ${bgClass}" style="width: ${pct}%; opacity: 0.15;"></div>
                        <div class="position-absolute w-100 h-100 d-flex justify-content-between align-items-center px-3" style="z-index: 1;">
                            <span class="small fw-bold text-dark text-truncate pe-2">${item.name}</span>
                            <span class="small fw-bold text-dark">฿${formattedVal}</span>
                        </div>
                    </div>
                `;
            });

            html += '</div>';
            container.innerHTML = html;
        }

        // --- ส่วนวาดกราฟ Chart.js ของเดิม ---
        function renderCashflowChart(data) {
            const ctx = document.getElementById('cashflowChart').getContext('2d');
            if (cashflowChartObj) cashflowChartObj.destroy();
            cashflowChartObj = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [
                        { label: 'รายรับ', data: data.income, backgroundColor: '#10b981', borderRadius: 6, barPercentage: 0.6 },
                        { label: 'รายจ่าย', data: data.expense, backgroundColor: '#f43f5e', borderRadius: 6, barPercentage: 0.6 }
                    ]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { position: 'top', labels: { usePointStyle: true, boxWidth: 8 } } },
                    scales: {
                        y: { beginAtZero: true, grid: { borderDash: [4, 4] }, ticks: { callback: val => new Intl.NumberFormat('th-TH').format(val) } },
                        x: { grid: { display: false } }
                    }
                }
            });
        }

        function renderInvoiceChart(data) {
            const ctx = document.getElementById('invoiceChart').getContext('2d');
            if (invoiceChartObj) invoiceChartObj.destroy();
            const total = data.paid + data.partial + data.unpaid + data.pending_send;
            invoiceChartObj = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: total === 0 ? ['ไม่มีข้อมูล'] : ['จ่ายแล้ว', 'จ่ายบางส่วน', 'ค้างชำระ', 'รอส่งบิล'],
                    datasets: [{
                        data: total === 0 ? [1] : [data.paid, data.partial, data.unpaid, data.pending_send],
                        backgroundColor: total === 0 ? ['#f3f4f6'] : ['#10b981', '#f59e0b', '#ef4444', '#9ca3af'],
                        borderWidth: 0, hoverOffset: 6
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false, cutout: '75%', plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, font: { size: 11 } } }, tooltip: { enabled: total > 0 } } }
            });
        }

        function renderRoomChart(data) {
            const ctx = document.getElementById('roomChart').getContext('2d');
            if (roomChartObj) roomChartObj.destroy();
            const total = data.occupied + data.vacant;
            roomChartObj = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: total === 0 ? ['ไม่มีข้อมูล'] : ['มีผู้เช่า', 'ว่าง'],
                    datasets: [{
                        data: total === 0 ? [1] : [data.occupied, data.vacant],
                        backgroundColor: total === 0 ? ['#f3f4f6'] : ['#3b82f6', '#10b981'],
                        borderWidth: 0, hoverOffset: 6
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false, cutout: '75%', plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, font: { size: 11 } } }, tooltip: { enabled: total > 0 } } }
            });
        }
    </script>
@endpush