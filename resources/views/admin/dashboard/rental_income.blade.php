@extends('admin.dashboard.layout')
@php $title = 'วิเคราะห์รายรับค่าเช่า'; @endphp

@section('dashboard_content')
    {{-- แถวที่ 1: สรุปยอดวงกลม (ซ้าย) + กราฟแท่งรายเดือน (ขวา) --}}
    <div class="row g-4 mb-4">

        {{-- 🌟 1. กราฟวงกลมพร้อมข้อมูลตัวเลขด้านซ้าย --}}
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100 rounded-4">
                <div class="card-body p-4">
                    <h6 class="fw-bold text-dark mb-4">
                        <i class="bi bi-pie-chart-fill text-primary me-2"></i> สัดส่วนการรับชำระ
                    </h6>
                    <div class="row align-items-center">
                        <div class="col-sm-5">
                            {{-- ข้อมูลตัวเลข --}}
                            <div class="mb-4">
                                <div class="text-muted small fw-semibold mb-1">ยอดรวมสุทธิ (คาดการณ์)</div>
                                <h3 class="fw-bold text-dark mb-0">
                                    <span class="fs-6">฿</span><span id="sumTotal">0</span>
                                </h3>
                            </div>
                            <div class="mb-3">
                                <div class="text-success small fw-bold mb-1"><i
                                        class="bi bi-circle-fill me-1"></i>รับชำระแล้ว</div>
                                <h5 class="fw-bold mb-0 text-success" id="sumPaid">0</h5>
                            </div>
                            <div>
                                <div class="text-danger small fw-bold mb-1"><i class="bi bi-circle-fill me-1"></i>ค้างรับ
                                    (บิลค้าง)</div>
                                <h5 class="fw-bold mb-0 text-danger" id="sumUnpaid">0</h5>
                            </div>
                        </div>
                        <div class="col-sm-7">
                            {{-- กราฟวงกลม --}}
                            <div class="position-relative w-100 d-flex justify-content-center" style="height: 220px;">
                                <canvas id="paymentStatusChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 🌟 2. กราฟแท่งบอกรายรับค่าเช่าในแต่ละเดือน --}}
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100 rounded-4">
                <div class="card-body p-4 text-center">
                    <h6 class="fw-bold text-dark mb-4 text-start">
                        <i class="bi bi-bar-chart-line-fill text-success me-2"></i> แนวโน้มการรับชำระรายเดือน
                    </h6>
                    <div class="position-relative w-100 d-flex justify-content-center" style="height: 260px;">
                        <canvas id="incomeTrendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- แถวที่ 2: 🌟 3. กราฟอื่นๆ (สัดส่วนรายรับแยกตามอาคาร/ประเภทห้อง) --}}
    <div class="row mb-4">
        <div class="col-lg-12">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <h6 class="fw-bold text-dark mb-4" id="breakdownTitle">
                        <i class="bi bi-buildings-fill text-info me-2"></i> รายรับแยกตามอาคาร
                    </h6>
                    <div class="position-relative w-100 d-flex justify-content-center" style="height: 320px;">
                        <canvas id="incomeBreakdownChart"></canvas>
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
        let paymentChartObj, trendChartObj, breakdownChartObj;

        document.addEventListener('DOMContentLoaded', function() {
            updateDashboard();
        });

        // ฟังก์ชันอัปเดตข้อมูลเมื่อมีการเปลี่ยน Filter (เรียกใช้จาก layout.blade.php)
        function updateDashboard() {
            const startMonth = document.getElementById('slicerStartMonth').value;
            const endMonth = document.getElementById('slicerEndMonth').value;
            const building = document.getElementById('slicerBuilding').value;
            const floor = document.getElementById('slicerFloor').value;

            // ยิง API ไปที่ apiRentalIncomeChart
            fetch(
                    `{{ route('admin.dashboard.api.rental_income') }}?start_month=${startMonth}&end_month=${endMonth}&building_id=${building}&floor_num=${floor}`)
                .then(response => {
                    if (!response.ok) throw new Error('Network error');
                    return response.json();
                })
                .then(data => {
                    // 1. อัปเดตตัวเลข
                    updateSummaryNumbers(data.summary);
                    // 2. วาดกราฟวงกลม
                    renderPaymentStatusChart(data.summary);
                    // 3. วาดกราฟแท่งรายเดือน
                    renderIncomeTrendChart(data.trend);
                    // 4. วาดกราฟแยกตึก
                    renderBreakdownChart(data.breakdown);
                })
                .catch(error => console.error('Error loading chart data:', error));
        }

        function formatCurrency(num) {
            return new Intl.NumberFormat('th-TH', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(num);
        }

        // ==========================================
        // 🌟 อัปเดตตัวเลขสรุป
        // ==========================================
        function updateSummaryNumbers(summary) {
            document.getElementById('sumTotal').innerText = formatCurrency(summary.total);
            document.getElementById('sumPaid').innerText = formatCurrency(summary.paid);
            document.getElementById('sumUnpaid').innerText = formatCurrency(summary.unpaid);
        }

        // ==========================================
        // 🌟 1. กราฟวงกลม สัดส่วนการชำระ (Doughnut)
        // ==========================================
        function renderPaymentStatusChart(summary) {
            const ctx = document.getElementById('paymentStatusChart').getContext('2d');
            if (paymentChartObj) paymentChartObj.destroy();

            paymentChartObj = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: summary.total === 0 ? ['ไม่มีข้อมูล'] : ['รับชำระแล้ว', 'ค้างรับ'],
                    datasets: [{
                        data: summary.total === 0 ? [1] : [summary.paid, summary.unpaid],
                        backgroundColor: summary.total === 0 ? ['#f3f4f6'] : ['#10b981', '#dc3545'],
                        borderWidth: 2,
                        borderColor: '#fff',
                        hoverOffset: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                font: {
                                    size: 12
                                }
                            }
                        },
                        tooltip: {
                            enabled: summary.total > 0
                        },
                        // แสดงเปอร์เซ็นต์
                        datalabels: {
                            color: '#fff',
                            font: {
                                weight: 'bold',
                                size: 14
                            },
                            formatter: (value, ctx) => {
                                if (summary.total === 0 || value === 0) return '';
                                let percentage = (value * 100 / summary.total).toFixed(0) + "%";
                                return percentage;
                            }
                        }
                    }
                }
            });
        }

        // ==========================================
        // 🌟 2. กราฟแท่ง รายรับรายเดือน
        // ==========================================
        function renderIncomeTrendChart(trendData) {
            const ctx = document.getElementById('incomeTrendChart').getContext('2d');
            if (trendChartObj) trendChartObj.destroy();

            trendChartObj = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: trendData.labels,
                    datasets: [{
                            label: 'รับชำระแล้ว',
                            data: trendData.paid,
                            backgroundColor: 'rgba(16, 185, 129, 0.85)', // สีเขียว
                            borderColor: '#10b981',
                            borderWidth: 1,
                            borderRadius: 4
                        },
                        {
                            label: 'ค้างรับ',
                            data: trendData.unpaid,
                            backgroundColor: 'rgba(220, 53, 69, 0.85)', // สีแดง
                            borderColor: '#dc3545',
                            borderWidth: 1,
                            borderRadius: 4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    // เปิดให้กราฟเรียงต่อกัน (Stacked Bar) เพื่อดูยอดรวมแต่ละเดือนได้ง่ายขึ้น
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        },
                        tooltip: {
                            enabled: true,
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ฿' + formatCurrency(context.raw);
                                }
                            }
                        },
                        // 🌟 เปิดใช้งาน DataLabels สำหรับกราฟแท่ง
                        datalabels: {
                            display: true,
                            color: '#ffffff', // ใช้ตัวหนังสือสีขาวเพื่อให้ตัดกับสีแท่งกราฟ
                            font: {
                                weight: 'bold',
                                size: 11
                            },
                            formatter: function(value) {
                                // ถ้าค่าเป็น 0 ไม่ต้องแสดงตัวเลข (จะได้ไม่รก)
                                if (value === 0) return '';

                                // สามารถเลือกแสดงผลได้ 2 แบบ (เลือกใช้แบบใดแบบหนึ่ง):

                                // แบบที่ 1: แสดงตัวเลขเต็ม (เช่น ฿15,000)
                                // return '฿' + formatCurrency(value);

                                // แบบที่ 2: แสดงตัวเลขแบบย่อ เพื่อไม่ให้ล้นแท่งกราฟ (เช่น 15k) แนะนำแบบนี้ครับ
                                if (value >= 1000) {
                                    return (value / 1000).toFixed(0) + 'k';
                                }
                                return value;
                            }
                        }
                    },
                    scales: {
                        x: {
                            stacked: true,
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            stacked: true,
                            beginAtZero: true,
                            grid: {
                                borderDash: [4, 4]
                            },
                            ticks: {
                                callback: val => '฿' + formatCurrency(val)
                            }
                        }
                    }
                }
            });
        }

        // ==========================================
        // 🌟 3. กราฟแท่งแนวนอน แยกตามอาคาร/ประเภท
        // ==========================================
        function renderBreakdownChart(breakdownData) {
            const ctx = document.getElementById('incomeBreakdownChart').getContext('2d');
            if (breakdownChartObj) breakdownChartObj.destroy();

            // อัปเดตชื่อหัวข้อการ์ดตาม Filter
            document.getElementById('breakdownTitle').innerHTML =
                `<i class="bi bi-buildings-fill text-info me-2"></i> ${breakdownData.title}`;

            breakdownChartObj = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: breakdownData.labels,
                    datasets: [{
                        label: 'รายรับ',
                        data: breakdownData.data,
                        backgroundColor: 'rgba(13, 110, 253, 0.7)', // สีน้ำเงินอ่อน
                        borderColor: '#0d6efd',
                        borderWidth: 1,
                        borderRadius: 4,
                        barPercentage: 0.6
                    }]
                },
                options: {
                    indexAxis: 'y', // เปลี่ยนเป็นกราฟแท่งแนวนอนเพื่อให้อ่านชื่อตึกง่าย
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            enabled: true,
                            callbacks: {
                                label: function(context) {
                                    return 'รายรับ: ฿' + formatCurrency(context.raw);
                                }
                            }
                        },
                        // แสดงตัวเลขไว้ท้ายแท่งกราฟ
                        datalabels: {
                            anchor: 'end',
                            align: 'right',
                            color: '#495057',
                            font: {
                                weight: 'bold'
                            },
                            formatter: function(value) {
                                return value > 0 ? '฿' + formatCurrency(value) : '';
                            }
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            grid: {
                                borderDash: [4, 4]
                            },
                            ticks: {
                                callback: val => formatCurrency(val)
                            },
                            grace: '15%'
                        },
                        y: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }
    </script>
@endpush
