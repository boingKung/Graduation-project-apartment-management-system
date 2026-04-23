@extends('admin.dashboard.layout')
@php $title = 'วิเคราะห์มิเตอร์น้ำ-ไฟ'; @endphp

@push('styles')
<style>
    .custom-scrollbar::-webkit-scrollbar { width: 6px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #c1c1c1; border-radius: 10px; }
</style>
@endpush

@section('dashboard_content')

    {{-- 🌟 1. การ์ดสรุป KPI --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm h-100 rounded-4">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-warning bg-opacity-10 text-warning rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                            <i class="bi bi-lightning-charge-fill fs-5"></i>
                        </div>
                        <div class="text-muted fw-bold">ปริมาณการใช้ไฟ</div>
                    </div>
                    <h3 class="fw-bold mb-0 text-dark"><span id="kpiTotalElec">0</span> <span class="fs-6 text-muted">หน่วย</span></h3>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm h-100 rounded-4">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-info bg-opacity-10 text-info rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                            <i class="bi bi-droplet-fill fs-5"></i>
                        </div>
                        <div class="text-muted fw-bold">ปริมาณการใช้น้ำ</div>
                    </div>
                    <h3 class="fw-bold mb-0 text-dark"><span id="kpiTotalWater">0</span> <span class="fs-6 text-muted">หน่วย</span></h3>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm h-100 rounded-4 bg-light border">
                <div class="card-body p-4">
                    <div class="text-muted fw-bold mb-2"><i class="bi bi-calculator text-secondary me-2"></i>เฉลี่ยค่าไฟ / ห้อง</div>
                    <h4 class="fw-bold mb-0 text-secondary"><span id="kpiAvgElec">0</span> <span class="fs-6 fw-normal">หน่วย</span></h4>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm h-100 rounded-4 bg-light border">
                <div class="card-body p-4">
                    <div class="text-muted fw-bold mb-2"><i class="bi bi-calculator text-secondary me-2"></i>เฉลี่ยค่าน้ำ / ห้อง</div>
                    <h4 class="fw-bold mb-0 text-secondary"><span id="kpiAvgWater">0</span> <span class="fs-6 fw-normal">หน่วย</span></h4>
                </div>
            </div>
        </div>
    </div>

    {{-- 🌟 2. กราฟเส้นเปรียบเทียบแนวโน้ม (Trend) --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <h6 class="fw-bold text-dark mb-4">
                        <i class="bi bi-graph-up text-primary me-2"></i> แนวโน้มการใช้น้ำ-ไฟ (รายเดือน)
                    </h6>
                    <div class="position-relative w-100 d-flex justify-content-center" style="height: 350px;">
                        <canvas id="meterTrendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 🌟 3. กราฟแท่งเปรียบเทียบแต่ละตึก (ใหม่) --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <h6 class="fw-bold text-dark mb-4">
                        <i class="bi bi-building text-primary me-2"></i> เปรียบเทียบการใช้น้ำ-ไฟ (แยกตามอาคาร)
                    </h6>
                    <div class="position-relative w-100 d-flex justify-content-center" style="height: 350px;">
                        <canvas id="buildingComparisonChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 🌟 4. กราฟ Top 5 และ ตารางข้อมูล --}}
    <div class="row g-4 mb-4">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100 rounded-4">
                <div class="card-body p-4">
                    <h6 class="fw-bold text-dark mb-4"><i class="bi bi-exclamation-triangle-fill text-warning me-2"></i> 5 อันดับห้องใช้ไฟสูงสุด</h6>
                    <div class="position-relative w-100" style="height: 250px;">
                        <canvas id="topElecChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100 rounded-4">
                <div class="card-body p-4">
                    <h6 class="fw-bold text-dark mb-4"><i class="bi bi-exclamation-triangle-fill text-info me-2"></i> 5 อันดับห้องใช้น้ำสูงสุด</h6>
                    <div class="position-relative w-100" style="height: 250px;">
                        <canvas id="topWaterChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100 rounded-4">
                <div class="card-header bg-white border-0 py-3 px-4 pt-4 rounded-top-4">
                    <h6 class="fw-bold mb-0"><i class="bi bi-table text-secondary me-2"></i> ข้อมูลการใช้งาน (หน่วย)</h6>
                </div>
                <div class="card-body px-0 pt-0 pb-3">
                    <div class="table-responsive px-4 custom-scrollbar" style="max-height: 250px; overflow-y: auto;">
                        <table class="table table-hover align-middle mb-0 text-center">
                            <thead class="table-light" style="position: sticky; top: 0; z-index: 1;">
                                <tr>
                                    <th class="text-start">เดือน</th>
                                    <th class="text-warning"><i class="bi bi-lightning-fill"></i> ไฟ</th>
                                    <th class="text-info"><i class="bi bi-droplet-fill"></i> น้ำ</th>
                                </tr>
                            </thead>
                            <tbody id="meterTableBody"></tbody>
                        </table>
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
        let meterTrendChartObj, buildingComparisonChartObj, topElecChartObj, topWaterChartObj;

        document.addEventListener('DOMContentLoaded', function() {
            updateDashboard();
        });

        function updateDashboard() {
            const startMonth = document.getElementById('slicerStartMonth').value;
            const endMonth = document.getElementById('slicerEndMonth').value;
            const building = document.getElementById('slicerBuilding').value;
            const floor = document.getElementById('slicerFloor').value;

            fetch(`{{ route('admin.dashboard.api.meter') }}?start_month=${startMonth}&end_month=${endMonth}&building_id=${building}&floor_num=${floor}`)
                .then(response => {
                    if (!response.ok) throw new Error('Network error');
                    return response.json();
                })
                .then(data => {
                    document.getElementById('kpiTotalElec').innerText = new Intl.NumberFormat().format(data.summary.total_elec);
                    document.getElementById('kpiTotalWater').innerText = new Intl.NumberFormat().format(data.summary.total_water);
                    document.getElementById('kpiAvgElec').innerText = new Intl.NumberFormat().format(data.summary.avg_elec);
                    document.getElementById('kpiAvgWater').innerText = new Intl.NumberFormat().format(data.summary.avg_water);

                    renderTrendChart(data.trend);
                    renderBuildingComparisonChart(data.building_comparison); // 🌟 กราฟใหม่
                    renderTopChart('topElecChart', data.top_elec, 'rgba(245, 158, 11, 0.85)', '#f59e0b', topElecChartObj, true);
                    renderTopChart('topWaterChart', data.top_water, 'rgba(13, 202, 240, 0.85)', '#0dcaf0', topWaterChartObj, false);
                    populateTable(data.table_data);
                })
                .catch(error => console.error('Error loading meter data:', error));
        }

        // ==========================================
        // กราฟเส้นเปรียบเทียบ น้ำ-ไฟ (Trend)
        // ==========================================
        function renderTrendChart(trendData) {
            const ctx = document.getElementById('meterTrendChart').getContext('2d');
            if (meterTrendChartObj) meterTrendChartObj.destroy();

            meterTrendChartObj = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: trendData.labels,
                    datasets: [
                        {
                            label: 'ปริมาณใช้ไฟฟ้า (หน่วย)', data: trendData.elec,
                            borderColor: '#f59e0b', backgroundColor: 'rgba(245, 158, 11, 0.1)',
                            borderWidth: 3, tension: 0.4, fill: true, yAxisID: 'y'
                        },
                        {
                            label: 'ปริมาณใช้น้ำ (หน่วย)', data: trendData.water,
                            borderColor: '#0dcaf0', backgroundColor: 'rgba(13, 202, 240, 0.1)',
                            borderWidth: 3, tension: 0.4, fill: true, yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { position: 'top' },
                        datalabels: { 
                            display: true, align: 'top', anchor: 'end',
                            font: { weight: 'bold', size: 11 },
                            formatter: function(value) { return value > 0 ? new Intl.NumberFormat().format(value) : ''; },
                            color: function(context) { return context.dataset.borderColor; }
                        }
                    },
                    scales: {
                        x: { grid: { display: false } },
                        y: { type: 'linear', display: true, position: 'left', title: { display: true, text: 'หน่วยไฟ', color: '#f59e0b', font: {weight: 'bold'} }, grid: { borderDash: [4, 4] }, grace: '20%' },
                        y1: { type: 'linear', display: true, position: 'right', title: { display: true, text: 'หน่วยน้ำ', color: '#0dcaf0', font: {weight: 'bold'} }, grid: { drawOnChartArea: false }, grace: '20%' }
                    }
                }
            });
        }

        // ==========================================
        // 🌟 กราฟแท่งเปรียบเทียบแยกตามอาคาร (ใหม่)
        // ==========================================
        function renderBuildingComparisonChart(buildingData) {
            const ctx = document.getElementById('buildingComparisonChart').getContext('2d');
            if (buildingComparisonChartObj) buildingComparisonChartObj.destroy();

            buildingComparisonChartObj = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: buildingData.labels,
                    datasets: [
                        {
                            label: 'ปริมาณใช้ไฟฟ้า (หน่วย)',
                            data: buildingData.elec,
                            backgroundColor: 'rgba(245, 158, 11, 0.85)',
                            borderColor: '#f59e0b',
                            borderWidth: 1, borderRadius: 4, barPercentage: 0.6
                        },
                        {
                            label: 'ปริมาณใช้น้ำ (หน่วย)',
                            data: buildingData.water,
                            backgroundColor: 'rgba(13, 202, 240, 0.85)',
                            borderColor: '#0dcaf0',
                            borderWidth: 1, borderRadius: 4, barPercentage: 0.6
                        }
                    ]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'top' },
                        tooltip: { enabled: true },
                        // 🌟 แสดง Data Label บนยอดเสา
                        datalabels: {
                            display: true,
                            anchor: 'end', align: 'top',
                            color: '#495057', font: { weight: 'bold', size: 11 },
                            formatter: function(value) {
                                return value > 0 ? new Intl.NumberFormat().format(value) : '';
                            }
                        }
                    },
                    scales: {
                        y: { beginAtZero: true, grace: '15%', grid: { borderDash: [4, 4] } },
                        x: { grid: { display: false } }
                    }
                }
            });
        }

        // ==========================================
        // กราฟแท่งแนวนอน (Top 5)
        // ==========================================
        function renderTopChart(canvasId, dataList, bgColor, borderColor, chartRef, isElec) {
            const ctx = document.getElementById(canvasId).getContext('2d');
            let chartStatus = Chart.getChart(canvasId); 
            if (chartStatus != undefined) { chartStatus.destroy(); }

            const labels = dataList.map(d => 'ห้อง ' + d.room);
            const data = dataList.map(d => d.units);

            const newChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels.length > 0 ? labels : ['ไม่มีข้อมูล'],
                    datasets: [{
                        label: 'หน่วย', data: data.length > 0 ? data : [0],
                        backgroundColor: bgColor, borderColor: borderColor,
                        borderWidth: 1, borderRadius: 4, barPercentage: 0.6
                    }]
                },
                options: {
                    indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }, tooltip: { enabled: true },
                        datalabels: { anchor: 'end', align: 'right', color: '#495057', font: { weight: 'bold', size: 11 }, formatter: function(value) { return value > 0 ? new Intl.NumberFormat().format(value) : ''; } }
                    },
                    scales: { x: { beginAtZero: true, grace: '25%', grid: { borderDash: [4, 4] } }, y: { grid: { display: false } } }
                }
            });

            if (isElec) topElecChartObj = newChart;
            else topWaterChartObj = newChart;
        }

        // ใส่ข้อมูลลงตาราง
        function populateTable(tableData) {
            const tbody = document.getElementById('meterTableBody');
            tbody.innerHTML = '';
            if (tableData.length === 0) { tbody.innerHTML = '<tr><td colspan="3" class="text-muted py-3">ไม่มีข้อมูล</td></tr>'; return; }

            tableData.forEach(row => {
                tbody.innerHTML += `<tr>
                    <td class="text-start fw-bold text-dark">${row.month}</td>
                    <td class="text-warning fw-semibold">${new Intl.NumberFormat().format(row.elec)}</td>
                    <td class="text-info fw-semibold">${new Intl.NumberFormat().format(row.water)}</td>
                </tr>`;
            });
        }
    </script>
@endpush