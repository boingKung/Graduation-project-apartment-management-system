@extends('admin.dashboard.layout')
@section('title', 'ภาพรวมหอพัก')

@section('dashboard_content')
    {{-- 🌟 Summary KPI Cards (เริ่มเนื้อหาของหน้านี้ได้เลย เพราะ Header/Slicer อยู่ใน Layout แล้ว) --}}
    <div class="row g-3 mb-4">
        {{-- 1. ห้องพัก --}}
        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm h-100 rounded-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="text-muted fw-bold">อัตราการเข้าพัก</div>
                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2 d-flex align-items-center justify-content-center"
                            style="width: 45px; height: 45px;">
                            <i class="bi bi-door-open-fill fs-5"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-baseline mb-1">
                        <h2 class="fw-bold mb-0 text-dark" id="kpiOccupied">{{ $occupiedRooms }}</h2>
                        <span class="text-muted ms-1">/ <span id="kpiTotalRooms">{{ $totalRooms }}</span> ห้อง</span>
                    </div>
                    @php $pct = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100) : 0; @endphp
                    <div class="progress mt-3" style="height: 6px;">
                        <div class="progress-bar bg-primary" id="kpiRoomProgress" style="width: {{ $pct }}%">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. จองออนไลน์ --}}
        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm h-100 rounded-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="text-muted fw-bold">จองออนไลน์รออนุมัติ</div>
                        <div class="bg-info bg-opacity-10 text-info rounded-circle p-2 d-flex align-items-center justify-content-center"
                            style="width: 45px; height: 45px;">
                            <i class="bi bi-globe fs-5"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-baseline mb-1">
                        <h2 class="fw-bold mb-0 {{ $pendingRegistrations > 0 ? 'text-info' : 'text-success' }}">
                            {{ $pendingRegistrations }}</h2>
                        <span class="text-muted ms-2 small">รายการ</span>
                    </div>
                    <a href="{{ route('admin.registrations.show') }}"
                        class="text-decoration-none small mt-3 d-inline-block fw-semibold text-info">ตรวจสอบรายการ <i
                            class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>

        {{-- 3. แจ้งซ่อม --}}
        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm h-100 rounded-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="text-muted fw-bold">แจ้งซ่อมรอดำเนินการ</div>
                        <div class="bg-warning bg-opacity-10 text-warning rounded-circle p-2 d-flex align-items-center justify-content-center"
                            style="width: 45px; height: 45px;">
                            <i class="bi bi-wrench fs-5"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-baseline mb-1">
                        <h2
                            class="fw-bold mb-0 {{ $pendingMaintenance->count() > 0 ? 'text-warning' : 'text-success' }}">
                            {{ $pendingMaintenance->count() }}</h2>
                        <span class="text-muted ms-2 small">รายการ</span>
                    </div>
                    <a href="{{ route('admin.maintenance.index') }}"
                        class="text-decoration-none small mt-3 d-inline-block fw-semibold text-primary">ดูรายการแจ้งซ่อม
                        <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>

        {{-- 4. จดมิเตอร์ --}}
        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm h-100 rounded-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="text-muted fw-bold">มิเตอร์รอจด (เดือนนี้)</div>
                        <div class="bg-danger bg-opacity-10 text-danger rounded-circle p-2 d-flex align-items-center justify-content-center"
                            style="width: 45px; height: 45px;">
                            <i class="bi bi-speedometer fs-5"></i>
                        </div>
                    </div>
                    @if ($roomsNeedMeter > 0)
                        <div class="d-flex align-items-baseline mb-1">
                            <h2 class="fw-bold mb-0 text-danger" id="kpiMeter">{{ $roomsNeedMeter }}</h2>
                            <span class="text-muted ms-2 small">รายการ</span>
                        </div>
                        <a href="{{ route('admin.meter_readings.insertForm') }}"
                            class="text-decoration-none small mt-3 d-inline-block fw-semibold text-danger">ไปหน้าจดมิเตอร์
                            <i class="bi bi-arrow-right"></i></a>
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

    {{-- Action Lists (บิลค้าง & สัญญาหมดอายุ) --}}
    <div class="row g-4 mb-4">
        {{-- บิลค้างชำระ --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100 rounded-4">
                <div
                    class="card-header bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center rounded-top-4">
                    <h6 class="fw-bold mb-0">
                        <i class="bi bi-exclamation-triangle-fill text-danger me-2"></i>บิลค้างชำระเกินกำหนด
                        @if ($overdueInvoices->count() > 0)
                            <span class="badge bg-danger rounded-pill ms-2">{{ $overdueInvoices->count() }}</span>
                        @endif
                    </h6>
                    <a href="{{ route('admin.payments.pendingInvoicesShow') }}"
                        class="btn btn-sm btn-light text-primary">ดูทั้งหมด</a>
                </div>
                <div class="card-body px-4 pt-0 pb-3">
                    @if ($overdueInvoices->count() > 0)
                        <div class="table-responsive pe-2 custom-scrollbar"
                            style="max-height: 280px; overflow-y: auto;">
                            <table class="table table-hover align-middle mb-0">
                                <tbody>
                                    @foreach ($overdueInvoices as $inv)
                                        @php
                                            $dueDate = \Carbon\Carbon::parse($inv->due_date)->startOfDay();
                                            $today = \Carbon\Carbon::now()->startOfDay();
                                            $daysOverdue = (int) $dueDate->diffInDays($today);
                                            $urgencyClass =
                                                $daysOverdue >= 15 ? 'text-danger' : 'text-warning text-dark';
                                        @endphp
                                        <tr>
                                            <td class="py-3 border-0 border-bottom">
                                                <div class="fw-bold text-dark">ห้อง
                                                    {{ $inv->room->room_number ?? '-' }}</div>
                                                <small class="text-muted">{{ $inv->tenant->full_name ?? '-' }}</small>
                                            </td>
                                            <td class="text-end py-3 border-0 border-bottom">
                                                <div class="fw-bold text-danger mb-1">
                                                    ฿{{ number_format($inv->remaining_balance, 0) }}</div>
                                                <span
                                                    class="badge bg-danger bg-opacity-10 {{ $urgencyClass }} rounded-pill small">เกินมา
                                                    {{ $daysOverdue }} วัน</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-check2-circle fs-1 text-success mb-2 d-block"></i>
                            ไม่มีบิลค้างชำระเกินกำหนด
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- สัญญาใกล้หมดอายุ / เลยกำหนด --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100 rounded-4">
                <div class="card-header bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center rounded-top-4">
                    <h6 class="fw-bold mb-0">
                        <i class="bi bi-calendar-x text-secondary me-2"></i>สัญญาใกล้หมด/เลยกำหนด
                        @if ($expiringContracts->count() > 0)
                            <span class="badge bg-secondary rounded-pill ms-2" id="kpiExpiringContracts">{{ $expiringContracts->count() }}</span>
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
                                            
                                            $diffDays = (int) $today->diffInDays($endDate, false); 
                                            
                                            if ($diffDays < 0) {
                                                $statusText = 'เลยกำหนด ' . abs($diffDays) . ' วัน';
                                                $badgeClass = 'bg-danger text-white';
                                            } else {
                                                $statusText = 'เหลือ ' . $diffDays . ' วัน';
                                                $urgencyClass = $diffDays <= 7 ? 'text-danger' : 'text-warning text-dark';
                                                $badgeClass = 'bg-light border ' . $urgencyClass;
                                            }
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
                                                <div class="small {{ $diffDays < 0 ? 'text-danger fw-bold' : 'text-muted' }} mb-1">
                                                    หมด: {{ \Carbon\Carbon::parse($tenant->end_date)->locale('th')->isoFormat('D MMM YY') }}
                                                </div>
                                                <span class="badge {{ $badgeClass }} rounded-pill small">{{ $statusText }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-calendar-check fs-1 text-success mb-2 d-block"></i> ไม่มีสัญญาใกล้หมด/เลยกำหนด
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- กราฟโดนัท (เฉพาะสถานะบิล & สัดส่วนห้อง) --}}
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100 rounded-4">
                <div class="card-body p-4 text-center">
                    <h6 class="fw-bold text-dark mb-4 text-start"><i
                            class="bi bi-receipt text-warning me-2"></i>สถานะบิล</h6>
                    <div class="position-relative w-100 d-flex justify-content-center" style="height: 250px;">
                        <canvas id="invoiceChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100 rounded-4">
                <div class="card-body p-4 text-center">
                    <h6 class="fw-bold text-dark mb-4 text-start"><i
                            class="bi bi-building text-info me-2"></i>สัดส่วนห้องพัก</h6>
                    <div class="position-relative w-100 d-flex justify-content-center" style="height: 250px;">
                        <canvas id="roomChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- กราฟแท่ง (ปริมาณผู้เข้าพัก) --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4 text-center">
                    <h6 class="fw-bold text-dark mb-4 text-start">
                        <i class="bi bi-bar-chart-fill text-primary me-2"></i>ปริมาณผู้เข้าพักย้อนหลัง
                    </h6>
                    <div class="position-relative w-100 d-flex justify-content-center" style="height: 300px;">
                        <canvas id="occupancyBarChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>

    <script>
        Chart.register(ChartDataLabels);
        let invoiceChartObj, roomChartObj, occupancyBarChartObj;

        document.addEventListener('DOMContentLoaded', function() {
            updateDashboard();
        });

        function updateDashboard() {
            const startMonth = document.getElementById('slicerStartMonth').value;
            const endMonth = document.getElementById('slicerEndMonth').value;
            const building = document.getElementById('slicerBuilding').value;
            const floor = document.getElementById('slicerFloor').value;

            fetch( `{{ route('admin.dashboard.api.overview') }}?start_month=${startMonth}&end_month=${endMonth}&building_id=${building}&floor_num=${floor}`)
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    renderInvoiceChart(data.invoice_status);
                    renderRoomChart(data.room_status);
                    renderOccupancyBarChart(data.occupancy_trend);
                    
                    // คอมเมนต์ไว้เผื่อว่าคุณยังไม่ได้เขียนฟังก์ชันนี้ จะได้ไม่เกิด Error ใน Console
                    // populateExpiringContractsTable(data.expiring_contracts);

                    if (data.room_status) {
                        const occupied = data.room_status.occupied;
                        const vacant = data.room_status.vacant;
                        const total = occupied + vacant;

                        document.getElementById('kpiOccupied').innerText = occupied;
                        document.getElementById('kpiTotalRooms').innerText = total;

                        const pct = total > 0 ? Math.round((occupied / total) * 100) : 0;
                        document.getElementById('kpiRoomProgress').style.width = pct + '%';
                    }
                    if (data.kpi) {
                        if(document.getElementById('kpiExpiringContracts')) document.getElementById('kpiExpiringContracts').innerText = data.kpi.expiring_contracts_count || 0;
                        if(document.getElementById('kpiMaintenance')) document.getElementById('kpiMaintenance').innerText = data.kpi.pending_maintenance;
                        if(document.getElementById('kpiMeter')) document.getElementById('kpiMeter').innerText = data.kpi.rooms_need_meter;
                    }
                })
                .catch(error => console.error('Error loading chart data:', error));
        }

        // ==========================================
        // 🌟 วาดกราฟแท่ง (ยอดเข้า-ออก รายเดือน)
        // ==========================================
        function renderOccupancyBarChart(data) {
            const ctx = document.getElementById('occupancyBarChart').getContext('2d');
            if (occupancyBarChartObj) occupancyBarChartObj.destroy();

            occupancyBarChartObj = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [
                        {
                            label: 'ย้ายเข้าใหม่ (ห้อง)', // 👈 เปลี่ยนชื่อ Label ตรงนี้
                            data: data.data,
                            backgroundColor: 'rgba(16, 185, 129, 0.85)', // สีเขียว Success (เข้าใหม่)
                            borderColor: '#10b981',
                            borderWidth: 1, borderRadius: 4, barPercentage: 0.5
                        },
                        {
                            label: 'ย้ายออก (ห้อง)',
                            data: data.terminated,
                            backgroundColor: 'rgba(220, 53, 69, 0.85)',  // สีแดง Danger (ย้ายออก)
                            borderColor: '#dc3545',
                            borderWidth: 1, borderRadius: 4, barPercentage: 0.5
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: true, position: 'top' },
                        tooltip: { enabled: true },
                        datalabels: {
                            anchor: 'end', align: 'top', color: '#495057', font: { weight: 'bold' },
                            formatter: function(value) { return value > 0 ? value : ''; } // ซ่อนเลข 0
                        }
                    },
                    scales: {
                        y: { 
                            beginAtZero: true, 
                            ticks: { precision: 0, stepSize: 1 }, // บังคับให้สเกลกระโดดทีละ 1 (ไม่เอา 0.5 ห้อง)
                            grid: { borderDash: [4, 4] }, 
                            grace: '15%' 
                        },
                        x: { grid: { display: false } }
                    }
                }
            });
        }

        // ==========================================
        // 🌟 วาดกราฟโดนัท พร้อม Data Labels (%)
        // ==========================================
        const doughnutOptions = {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: { position: 'bottom', labels: { usePointStyle: true, font: { size: 11 } } },
                tooltip: { enabled: true },
                datalabels: {
                    color: '#fff',
                    font: { weight: 'bold', size: 12 },
                    formatter: (value, ctx) => {
                        let sum = 0;
                        let dataArr = ctx.chart.data.datasets[0].data;
                        dataArr.map(data => { sum += data; });
                        let percentage = sum > 0 ? (value * 100 / sum).toFixed(0) + "%" : "-";
                        return value > 0 ? percentage : ''; // โชว์เฉพาะอันที่ไม่ใช่ 0
                    }
                }
            }
        };

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
                        backgroundColor: total === 0 ? ['#f3f4f6'] : ['#10b981', '#f59e0b', '#dc3545', '#6c757d'],
                        borderWidth: 2, borderColor: '#fff'
                    }]
                },
                options: doughnutOptions
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
                        backgroundColor: total === 0 ? ['#f3f4f6'] : ['#0d6efd', '#10b981'],
                        borderWidth: 2, borderColor: '#fff'
                    }]
                },
                options: doughnutOptions
            });
        }
    </script>
@endpush