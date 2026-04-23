@extends('admin.dashboard.layout')
@php $title = 'วิเคราะห์รายรับ-รายจ่าย'; @endphp

@push('styles')
<style>
    /* แต่ง Scrollbar และ Data Bar */
    .custom-scrollbar::-webkit-scrollbar { width: 6px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #c1c1c1; border-radius: 10px; }
    .data-bar-bg { background-color: #f8f9fa; border-radius: 6px; overflow: hidden; height: 38px; }
    .data-bar-fill { height: 100%; border-radius: 6px; transition: width 0.8s ease-in-out; }
    .doughnut-center-text { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; pointer-events: none; }
</style>
@endpush

@section('dashboard_content')

    {{-- 1. การ์ดสรุปยอดสุทธิ --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100 rounded-4">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-success bg-opacity-10 text-success rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                            <i class="bi bi-arrow-down-left-circle-fill fs-5"></i>
                        </div>
                        <div class="text-muted fw-bold">รายรับรวมทั้งหมด</div>
                    </div>
                    <h3 class="fw-bold mb-0 text-success">฿<span id="kpiTotalIncome">0</span></h3>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100 rounded-4">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-danger bg-opacity-10 text-danger rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                            <i class="bi bi-arrow-up-right-circle-fill fs-5"></i>
                        </div>
                        <div class="text-muted fw-bold">รายจ่ายรวมทั้งหมด</div>
                    </div>
                    <h3 class="fw-bold mb-0 text-danger">฿<span id="kpiTotalExpense">0</span></h3>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100 rounded-4">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                            <i class="bi bi-wallet2 fs-5"></i>
                        </div>
                        <div class="text-muted fw-bold">กำไรสุทธิ (Net Profit)</div>
                    </div>
                    <h3 class="fw-bold mb-0" id="kpiNetProfit">฿0</h3>
                </div>
            </div>
        </div>
    </div>

    {{-- 2. กราฟแท่งเปรียบเทียบรายเดือน --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4 text-center">
                    <h6 class="fw-bold text-dark mb-4 text-start">
                        <i class="bi bi-bar-chart-fill text-primary me-2"></i>กระแสเงินสดรายรับ-รายจ่าย (รายเดือน)
                    </h6>
                    <div class="position-relative w-100 d-flex justify-content-center" style="height: 320px;">
                        <canvas id="cashflowTrendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 🌟 3. กราฟแท่งเปรียบเทียบแยกตามอาคาร (อันใหม่) --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4 text-center">
                    <h6 class="fw-bold text-dark mb-4 text-start">
                        <i class="bi bi-buildings-fill text-info me-2"></i>เปรียบเทียบรายรับ-รายจ่าย (แยกตามอาคาร)
                    </h6>
                    <div class="position-relative w-100 d-flex justify-content-center" style="height: 320px;">
                        <canvas id="buildingCashflowChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 4. กราฟวงกลม สัดส่วนรายรับ-รายจ่าย --}}
    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100 rounded-4">
                <div class="card-body p-4 text-center">
                    <h6 class="fw-bold text-dark mb-4 text-start">
                        <i class="bi bi-pie-chart-fill text-success me-2"></i>สัดส่วนรายรับ (ตามหมวดหมู่)
                    </h6>
                    <div class="position-relative w-100 d-flex justify-content-center" style="height: 280px;">
                        <canvas id="incomePieChart"></canvas>
                        <div class="doughnut-center-text">
                            <span class="small text-muted d-block mb-1">รายรับทั้งหมด</span>
                            <span class="fs-5 fw-bold text-success">฿<span id="centerTotalIncome">0</span></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100 rounded-4">
                <div class="card-body p-4 text-center">
                    <h6 class="fw-bold text-dark mb-4 text-start">
                        <i class="bi bi-pie-chart-fill text-danger me-2"></i>สัดส่วนรายจ่าย (ตามหมวดหมู่)
                    </h6>
                    <div class="position-relative w-100 d-flex justify-content-center" style="height: 280px;">
                        <canvas id="expensePieChart"></canvas>
                        <div class="doughnut-center-text">
                            <span class="small text-muted d-block mb-1">รายจ่ายทั้งหมด</span>
                            <span class="fs-5 fw-bold text-danger">฿<span id="centerTotalExpense">0</span></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 5. Top Income & Expense (แบบ Data Bar PowerBI) --}}
    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100 rounded-4">
                <div class="card-header bg-white border-0 py-3 px-4 pt-4 rounded-top-4">
                    <h6 class="fw-bold mb-0"><i class="bi bi-sort-down-alt text-success me-2"></i>อันดับรายรับทั้งหมด</h6>
                </div>
                <div class="card-body px-4 pt-0 pb-4">
                    <div id="topIncomeContainer" class="pe-2 custom-scrollbar" style="max-height: 300px; overflow-y: auto;"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100 rounded-4">
                <div class="card-header bg-white border-0 py-3 px-4 pt-4 rounded-top-4">
                    <h6 class="fw-bold mb-0"><i class="bi bi-sort-down-alt text-danger me-2"></i>อันดับรายจ่ายทั้งหมด</h6>
                </div>
                <div class="card-body px-4 pt-0 pb-4">
                    <div id="topExpenseContainer" class="pe-2 custom-scrollbar" style="max-height: 300px; overflow-y: auto;"></div>
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
        let cashflowTrendChartObj, buildingCashflowChartObj, incomePieChartObj, expensePieChartObj;

        document.addEventListener('DOMContentLoaded', function() {
            updateDashboard();
        });

        function updateDashboard() {
            const startMonth = document.getElementById('slicerStartMonth').value;
            const endMonth = document.getElementById('slicerEndMonth').value;
            const building = document.getElementById('slicerBuilding').value;
            const floor = document.getElementById('slicerFloor').value;

            fetch(`{{ route('admin.dashboard.api.cashflow') }}?start_month=${startMonth}&end_month=${endMonth}&building_id=${building}&floor_num=${floor}`)
                .then(response => {
                    if (!response.ok) throw new Error('Network error');
                    return response.json();
                })
                .then(data => {
                    updateSummaryCards(data.summary);
                    renderCashflowTrendChart(data.trend);
                    renderBuildingCashflowChart(data.building_comparison); // 🌟 เรียกวาดกราฟใหม่
                    renderPieChart('incomePieChart', data.breakdown.income_labels, data.breakdown.income_data, incomePieChartObj, true);
                    renderPieChart('expensePieChart', data.breakdown.expense_labels, data.breakdown.expense_data, expensePieChartObj, false);
                    renderDataBarList('topIncomeContainer', data.top_income, 'bg-success');
                    renderDataBarList('topExpenseContainer', data.top_expense, 'bg-danger');
                })
                .catch(error => console.error('Error loading cashflow data:', error));
        }

        // --- Helper Formatting ---
        function formatCurrency(num) {
            return new Intl.NumberFormat('th-TH', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(num);
        }
        function formatShortCurrency(num) {
            if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
            if (num >= 1000) return (num / 1000).toFixed(0) + 'k';
            return num;
        }

        // --- อัปเดตตัวเลข ---
        function updateSummaryCards(summary) {
            document.getElementById('kpiTotalIncome').innerText = formatCurrency(summary.income);
            document.getElementById('centerTotalIncome').innerText = formatShortCurrency(summary.income);
            document.getElementById('kpiTotalExpense').innerText = formatCurrency(summary.expense);
            document.getElementById('centerTotalExpense').innerText = formatShortCurrency(summary.expense);
            
            const profitEl = document.getElementById('kpiNetProfit');
            profitEl.innerText = '฿' + formatCurrency(summary.net_profit);
            if (summary.net_profit > 0) profitEl.className = 'fw-bold mb-0 text-success';
            else if (summary.net_profit < 0) profitEl.className = 'fw-bold mb-0 text-danger';
            else profitEl.className = 'fw-bold mb-0 text-dark';
        }

        // --- กราฟแท่งเปรียบเทียบรายเดือน ---
        function renderCashflowTrendChart(trendData) {
            const ctx = document.getElementById('cashflowTrendChart').getContext('2d');
            if (cashflowTrendChartObj) cashflowTrendChartObj.destroy();

            cashflowTrendChartObj = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: trendData.labels,
                    datasets: [
                        { label: 'รายรับ', data: trendData.income, backgroundColor: 'rgba(16, 185, 129, 0.85)', borderColor: '#10b981', borderWidth: 1, borderRadius: 4, barPercentage: 0.6 },
                        { label: 'รายจ่าย', data: trendData.expense, backgroundColor: 'rgba(220, 53, 69, 0.85)', borderColor: '#dc3545', borderWidth: 1, borderRadius: 4, barPercentage: 0.6 }
                    ]
                },
                options: {
                    responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: true, position: 'top' },
                        tooltip: { callbacks: { label: function(context) { return context.dataset.label + ': ฿' + formatCurrency(context.raw); } } },
                        
                        // 🌟 เปิดการใช้งาน Data Labels ให้แสดงบนยอดกราฟ
                        datalabels: { 
                            display: true,
                            anchor: 'end',
                            align: 'top',
                            color: '#495057',
                            font: { weight: 'bold', size: 11 },
                            formatter: function(value) {
                                // ซ่อนเลข 0 และใช้ตัวเลขแบบย่อ (เช่น 15k, 1.5M) เพื่อไม่ให้รกเกินไป
                                return value > 0 ? formatShortCurrency(value) : ''; 
                            }
                        }
                    },
                    scales: {
                        // 🌟 เพิ่ม grace เป็น 15% เพื่อเผื่อพื้นที่ด้านบนกราฟ ไม่ให้ตัวเลขโดนตัด
                        y: { beginAtZero: true, grid: { borderDash: [4, 4] }, ticks: { callback: val => '฿' + formatShortCurrency(val) }, grace: '15%' },
                        x: { grid: { display: false } }
                    }
                }
            });
        }

        // ==========================================
        // 🌟 กราฟแท่งเปรียบเทียบแยกตามอาคาร (ใหม่ล่าสุด!)
        // ==========================================
        function renderBuildingCashflowChart(buildingData) {
            const ctx = document.getElementById('buildingCashflowChart').getContext('2d');
            if (buildingCashflowChartObj) buildingCashflowChartObj.destroy();

            buildingCashflowChartObj = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: buildingData.labels,
                    datasets: [
                        {
                            label: 'รายรับ',
                            data: buildingData.income,
                            backgroundColor: 'rgba(16, 185, 129, 0.85)', // สีเขียว
                            borderColor: '#10b981',
                            borderWidth: 1, borderRadius: 4, barPercentage: 0.6
                        },
                        {
                            label: 'รายจ่าย',
                            data: buildingData.expense,
                            backgroundColor: 'rgba(220, 53, 69, 0.85)', // สีแดง
                            borderColor: '#dc3545',
                            borderWidth: 1, borderRadius: 4, barPercentage: 0.6
                        }
                    ]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'top' },
                        tooltip: { callbacks: { label: function(context) { return context.dataset.label + ': ฿' + formatCurrency(context.raw); } } },
                        // 🌟 แสดง Data Label บนยอดเสา
                        datalabels: {
                            display: true,
                            anchor: 'end', align: 'top',
                            color: '#495057', font: { weight: 'bold', size: 11 },
                            formatter: function(value) {
                                return value > 0 ? formatShortCurrency(value) : ''; // แสดงแบบย่อ (เช่น 15k) เพื่อไม่ให้ชนกัน
                            }
                        }
                    },
                    scales: {
                        y: { beginAtZero: true, grace: '15%', grid: { borderDash: [4, 4] }, ticks: { callback: val => '฿' + formatShortCurrency(val) } },
                        x: { grid: { display: false } }
                    }
                }
            });
        }

        // --- กราฟวงกลม ---
        function renderPieChart(canvasId, labels, data, chartObjRef, isIncome) {
            const ctx = document.getElementById(canvasId).getContext('2d');
            let chartStatus = Chart.getChart(canvasId); 
            if (chartStatus != undefined) { chartStatus.destroy(); }

            const colorPalette = isIncome 
                ? ['#10b981', '#0ea5e9', '#3b82f6', '#14b8a6', '#8b5cf6', '#6366f1'] 
                : ['#ef4444', '#f97316', '#f59e0b', '#eab308', '#d946ef', '#ec4899'];
            const hasData = data.reduce((a, b) => a + b, 0) > 0;

            const newChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: hasData ? labels : ['ไม่มีข้อมูล'],
                    datasets: [{ data: hasData ? data : [1], backgroundColor: hasData ? colorPalette : ['#f3f4f6'], borderWidth: 2, borderColor: '#fff', hoverOffset: 6 }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false, cutout: '75%',
                    plugins: {
                        legend: { display: false },
                        tooltip: { enabled: hasData, callbacks: { label: function(context) { return context.label + ': ฿' + formatCurrency(context.raw); } } },
                        datalabels: {
                            color: '#fff', font: { weight: 'bold', size: 11 },
                            formatter: (value, ctx) => {
                                if (!hasData || value === 0) return '';
                                let sum = ctx.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                                let percentage = (value * 100 / sum).toFixed(0);
                                return percentage >= 5 ? percentage + "%" : ''; 
                            }
                        }
                    }
                }
            });
            if(isIncome) incomePieChartObj = newChart; else expensePieChartObj = newChart;
        }

        // --- Data Bar Lists ---
        function renderDataBarList(containerId, data, bgClass) {
            const container = document.getElementById(containerId);
            if (!data || data.length === 0) {
                container.innerHTML = '<div class="text-center text-muted py-4 small"><i class="bi bi-inbox fs-2 d-block mb-2"></i>ไม่มีรายการในหมวดหมู่นี้</div>';
                return;
            }
            const maxVal = Math.max(...data.map(d => parseFloat(d.total)));
            let html = '<div class="d-flex flex-column gap-3 mt-1">';
            data.forEach(item => {
                const total = parseFloat(item.total);
                const pct = maxVal > 0 ? (total / maxVal) * 100 : 0;
                html += `<div class="position-relative data-bar-bg">
                            <div class="position-absolute start-0 top-0 data-bar-fill ${bgClass}" style="width: ${pct}%; opacity: 0.15;"></div>
                            <div class="position-absolute w-100 h-100 d-flex justify-content-between align-items-center px-3" style="z-index: 1;">
                                <span class="small fw-bold text-dark text-truncate pe-2">${item.name}</span>
                                <span class="small fw-bold text-dark">฿${formatCurrency(total)}</span>
                            </div>
                        </div>`;
            });
            html += '</div>';
            container.innerHTML = html;
        }
    </script>
@endpush