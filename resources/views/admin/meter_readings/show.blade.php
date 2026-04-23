@extends('admin.layout')

@section('title', 'ดู/แก้ไขเลขมิเตอร์น้ำ-ไฟ')

@section('content')
<div class="container-fluid">

    {{-- Header --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3 px-4">
            <div class="row align-items-center g-3">
                <div class="col-md-5">
                    <h5 class="fw-bold mb-1"><i class="bi bi-journal-check me-2 text-success"></i>ข้อมูลมิเตอร์ (จดแล้ว)</h5>
                    <span class="text-muted small">รอบเดือน: <strong>{{ $thai_date }}</strong> &middot; {{ collect($rooms)->count() }} ห้อง</span>
                </div>
                <div class="col-md-7 text-md-end">
                    <div class="d-flex flex-wrap justify-content-md-end align-items-center gap-2">
                        <a href="{{ route('admin.meter_readings.insertForm') }}" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-plus-circle me-1"></i> ไปหน้าจดมิเตอร์ใหม่
                        </a>

                        <div class="vr d-none d-md-block opacity-25"></div>

                        <div class="d-flex align-items-center gap-2">
                            <label class="text-muted small mb-0">วันที่จด:</label>
                            <input type="date" name="reading_date" form="meterForm" id="reading_date"
                                   class="form-control form-control-sm text-success fw-bold" style="width:155px;"
                                   value="{{ $recordedDate }}" required autocomplete="off">
                        </div>

                        <form method="GET" action="{{ route('admin.meter_readings.show') }}" class="d-flex align-items-center gap-2">
                            <input type="hidden" name="search_room" value="{{ request('search_room') }}">
                            <input type="hidden" name="filter_floor" value="{{ request('filter_floor') }}">
                            <label class="text-muted small mb-0">เดือน:</label>
                            <input type="month" name="billing_month" class="form-control form-control-sm fw-bold text-success"
                                   style="width:150px;" value="{{ $billing_month }}" onchange="this.form.submit()">
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="card border-0 shadow-sm mb-3 bg-white">
        <div class="card-body py-2 px-3">
            <form method="GET" action="{{ route('admin.meter_readings.show') }}" class="row g-2 align-items-center">
                <input type="hidden" name="billing_month" value="{{ $billing_month }}">
                
                <div class="col-auto text-muted d-none d-sm-block">
                    <i class="bi bi-funnel-fill"></i> คัดกรองห้อง:
                </div>
                
                {{-- ค้นหาเลขห้อง --}}
                <div class="col-auto">
                    <input type="text" name="search_room" class="form-control form-control-sm bg-light" 
                           placeholder="ค้นหาเลขห้อง..." value="{{ request('search_room') }}" 
                           onkeydown="if(event.key === 'Enter') this.form.submit();" style="width: 140px;">
                </div>

                {{-- 🌟 เพิ่ม Filter ชั้น --}}
                <div class="col-auto">
                    <select name="filter_floor" class="form-select form-select-sm bg-light text-muted" style="width:120px;" onchange="this.form.submit()">
                        <option value="">- ทุกชั้น -</option>
                        @foreach($floorNums as $floor)
                            <option value="{{ $floor }}" {{ request('filter_floor') == $floor ? 'selected' : '' }}>ชั้น {{ $floor }}</option>
                        @endforeach
                    </select>
                </div>
                
                {{-- กรองตึก (JavaScript ฝั่งหน้าบ้าน) --}}
                <div class="col-auto">
                    <select id="filterBuilding" class="form-select form-select-sm bg-light text-muted" style="width:160px;" onchange="filterByBuilding()">
                        <option value="">- เลือกทุกตึก -</option>
                        @php $uniqueBuildings = collect($rooms)->map(fn($r) => $r->roomPrice->building)->unique('id')->sortBy('name'); @endphp
                        @foreach($uniqueBuildings as $bld)
                            <option value="{{ $bld->id }}">{{ $bld->name }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-auto ms-auto">
                    <a href="{{ route('admin.meter_readings.show') }}?billing_month={{ $billing_month }}" class="btn btn-sm btn-light border" title="ล้างตัวกรอง">
                        <i class="bi bi-eraser"></i> ล้างตัวกรอง
                    </a>
                    <button type="submit" class="d-none">Search</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Table --}}
    <form action="{{ route('admin.meter_readings.update') }}" method="POST" id="meterForm">
        @csrf @method('PUT')
        <input type="hidden" name="billing_month" value="{{ $billing_month }}">
        <input type="hidden" name="reading_date" id="hidden_reading_date" value="{{ $recordedDate }}">

        @if(collect($rooms)->count() > 0)
            @foreach(collect($rooms)->groupBy(fn($r) => $r->roomPrice->building->name) as $buildingName => $buildingRooms)
                <div class="building-group mb-4" data-building-id="{{ $buildingRooms->first()->roomPrice->building->id }}">
                    <div class="d-flex align-items-center gap-2 mb-2 px-1">
                        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-building me-1 text-success"></i>{{ $buildingName }}</h6>
                        <span class="badge bg-secondary room-counter">{{ $buildingRooms->count() }} ห้อง</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle mb-0 bg-white shadow-sm">
                            <thead class="table-light text-center small text-nowrap">
                                <tr>
                                    <th rowspan="2" style="width:70px;" class="align-middle">ห้อง</th>
                                    <th colspan="3" class="text-info border-bottom-0" style="background: rgba(13,202,240,0.08);">
                                        <i class="bi bi-droplet-fill me-1"></i>ค่าน้ำ
                                    </th>
                                    <th colspan="3" class="text-warning text-dark border-bottom-0" style="background: rgba(255,193,7,0.15);">
                                        <i class="bi bi-lightning-charge-fill me-1"></i>ค่าไฟ
                                    </th>
                                </tr>
                                <tr>
                                    <th style="width:110px; background: rgba(13,202,240,0.05);">ก่อนหน้า</th>
                                    <th style="width:130px; background: rgba(13,202,240,0.05);">ปัจจุบัน (แก้ไข)</th>
                                    <th style="width:65px; background: rgba(13,202,240,0.05);">หน่วย</th>
                                    <th style="width:110px; background: rgba(255,193,7,0.05);">ก่อนหน้า</th>
                                    <th style="width:130px; background: rgba(255,193,7,0.05);">ปัจจุบัน (แก้ไข)</th>
                                    <th style="width:65px; background: rgba(255,193,7,0.05);">หน่วย</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($buildingRooms->sortBy('room_number') as $room)
                                    @php
                                        $water = $existingReadings->where('room_id', $room->id)->where('meter_type', 'water')->first();
                                        $electric = $existingReadings->where('room_id', $room->id)->where('meter_type', 'electric')->first();
                                    @endphp
                                    <tr class="room-row" data-building-id="{{ $buildingRooms->first()->roomPrice->building->id }}">
                                        <td class="text-center fw-bold bg-light text-success fs-5">{{ $room->room_number }}</td>

                                        {{-- Water --}}
                                        <td class="text-center" style="background: rgba(13,202,240,0.04);">
                                            <input type="number" name="data[{{ $room->id }}][water][previous_value]" 
                                                class="form-control form-control-sm text-center fw-bold prev-input border-info" min="0" 
                                                value="{{ $water->previous_value ?? 0 }}" oninput="calcRow(this)" autocomplete="off" style="max-width:90px; margin:0 auto; opacity:0.85;">
                                        </td>
                                        <td style="background: rgba(13,202,240,0.04);">
                                            <input type="number" name="data[{{ $room->id }}][water][current_value]" 
                                                class="form-control form-control-sm current-input fw-bold text-center border-info" min="0" 
                                                value="{{ $water->current_value ?? 0 }}" oninput="calcRow(this)" required autocomplete="off" style="max-width:110px; margin:0 auto;">
                                            <input type="hidden" name="data[{{ $room->id }}][water][tenant_id]" value="{{ $water->tenant_id ?? '' }}">
                                        </td>
                                        <td class="text-center" style="background: rgba(13,202,240,0.04);">
                                            <span class="units-used fw-bold text-primary fs-6">{{ $water->units_used ?? 0 }}</span>
                                        </td>

                                        {{-- Electric --}}
                                        <td class="text-center" style="background: rgba(255,193,7,0.04);">
                                            <input type="number" name="data[{{ $room->id }}][electric][previous_value]" 
                                                class="form-control form-control-sm text-center fw-bold prev-input border-warning" min="0" 
                                                value="{{ $electric->previous_value ?? 0 }}" oninput="calcRow(this)" autocomplete="off" style="max-width:90px; margin:0 auto; opacity:0.85;">
                                        </td>
                                        <td style="background: rgba(255,193,7,0.04);">
                                            <input type="number" name="data[{{ $room->id }}][electric][current_value]" 
                                                class="form-control form-control-sm text-center border-warning fw-bold current-input" min="0" 
                                                value="{{ $electric->current_value ?? 0 }}" oninput="calcRow(this)" required autocomplete="off" style="max-width:110px; margin:0 auto;">
                                            <input type="hidden" name="data[{{ $room->id }}][electric][tenant_id]" value="{{ $electric->tenant_id ?? '' }}">
                                        </td>
                                        <td class="text-center" style="background: rgba(255,193,7,0.04);">
                                            <span class="units-used fw-bold text-primary fs-6">{{ $electric->units_used ?? 0 }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach

            <div class="text-end mt-3 sticky-bottom bg-white p-3 border-top shadow-sm" style="bottom: 0; z-index: 100;">
                <div class="d-inline-flex align-items-center me-3 text-muted small" id="summaryCount">
                    จำนวนห้องที่พร้อมอัปเดต: <strong class="ms-1 text-dark fs-6">{{ collect($rooms)->count() }}</strong> ห้อง
                </div>
                <button type="button" class="btn btn-warning px-5 py-2 fw-bold text-dark shadow-sm" onclick="validateAndSubmit()">
                    <i class="bi bi-save2-fill me-1"></i> ยืนยันการอัปเดตข้อมูล
                </button>
            </div>
        @else
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-5 text-muted">
                    <i class="bi bi-info-circle text-secondary fs-1 d-block mb-3"></i>
                    <h5 class="fw-bold text-dark">ไม่พบข้อมูลมิเตอร์</h5>
                    <p class="mb-3">ไม่มีข้อมูลการจดมิเตอร์ตามเงื่อนไขที่ค้นหา</p>
                    <a href="{{ route('admin.meter_readings.show') }}" class="btn btn-outline-secondary me-2">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> ล้างการค้นหา
                    </a>
                </div>
            </div>
        @endif
    </form>
</div>
@endsection

@push('scripts')
<script>
    // อัปเดตวันที่
    document.getElementById('reading_date').addEventListener('change', function() {
        document.getElementById('hidden_reading_date').value = this.value;
    });

    // กรองตึก
    function filterByBuilding() {
        const val = document.getElementById('filterBuilding').value;
        let visibleRooms = 0;
        
        document.querySelectorAll('.building-group').forEach(group => {
            let groupVisible = 0;
            group.querySelectorAll('.room-row').forEach(row => {
                if (!val || row.dataset.buildingId === val) {
                    row.style.display = '';
                    row.classList.remove('filtered-out');
                    groupVisible++;
                    visibleRooms++;
                } else {
                    row.style.display = 'none';
                    row.classList.add('filtered-out');
                }
            });
            
            if (groupVisible > 0) {
                group.style.display = '';
                group.querySelector('.room-counter').textContent = groupVisible + ' ห้อง';
            } else {
                group.style.display = 'none';
            }
        });
        
        document.getElementById('summaryCount').innerHTML = `จำนวนห้องที่พร้อมอัปเดต: <strong class="ms-1 text-dark fs-6">${visibleRooms}</strong> ห้อง`;
    }

    // 🌟 ฟังก์ชันคำนวณ (ใช้กฎ Rollover >= 9000 เท่านั้น)
    function calculateUnits(prev, curr) {
        if (curr >= prev) {
            return curr - prev;
        } else {
            if (prev >= 9000) {
                let prevString = Math.floor(prev).toString();
                let maxMeterValue = Math.pow(10, prevString.length);
                return (maxMeterValue - prev) + curr;
            } else {
                return -1; // ถ้าไม่ถึง 9000 แต่น้อยกว่าเดิม ถือว่าแอดมินพิมพ์ผิด
            }
        }
    }

    function calcRow(element) {
        const row = element.closest('tr');
        const cell = element.closest('td');
        const ci = cell.cellIndex;

        let pCell, cCell, uCell;
        if (ci <= 3) { pCell = row.cells[1]; cCell = row.cells[2]; uCell = row.cells[3]; } 
        else { pCell = row.cells[4]; cCell = row.cells[5]; uCell = row.cells[6]; }

        const pInput = pCell.querySelector('.prev-input');
        const cInput = cCell.querySelector('.current-input');
        const display = uCell.querySelector('.units-used');

        if (!pInput || !cInput || !display) return;

        const prev = parseFloat(pInput.value) || 0;
        const curr = parseFloat(cInput.value) || 0;
        
        if(cInput.value.length > 5) cInput.value = cInput.value.slice(0,5);
        if(pInput.value.length > 5) pInput.value = pInput.value.slice(0,5);

        if (cInput.value === '') {
            display.innerText = '0';
            display.className = 'units-used fw-bold text-muted small';
            return;
        }

        const used = calculateUnits(prev, curr);

        if (used < 0) {
            display.innerText = 'น้อยกว่าเดิม';
            display.className = 'units-used fw-bold text-danger small';
        } else if (used > 2000) {
            display.innerText = 'ผิดปกติ?';
            display.className = 'units-used fw-bold text-danger small';
        } else {
            display.innerText = used;
            display.className = 'units-used fw-bold text-primary fs-6';
        }
    }

    function validateAndSubmit() {
        const readingDate = document.getElementById('reading_date').value;
        if (!readingDate) { Swal.fire({ icon: 'warning', title: 'กรุณาเลือกวันที่จดมิเตอร์' }); return; }

        let hasError = false;
        let emptyCount = 0;
        let submitCount = 0;

        document.querySelectorAll('.room-row:not(.filtered-out)').forEach(row => {
            submitCount++;
            const cInputs = row.querySelectorAll('.current-input');
            const errors = row.querySelectorAll('.units-used.text-danger');
            
            cInputs.forEach(i => { if (!i.value) emptyCount++; });
            if (errors.length > 0) hasError = true;
        });
        
        if (submitCount === 0) {
            Swal.fire({ icon: 'info', title: 'ไม่มีข้อมูลให้บันทึก' }); return;
        }

        if (emptyCount > 0) { 
            Swal.fire({ icon: 'warning', title: 'กรอกไม่ครบ', text: `มีช่องว่าง ${emptyCount} ช่องในหน้าที่แสดงผล` }); return; 
        }
        if (hasError) { 
            Swal.fire({ icon: 'error', title: 'ตัวเลขมิเตอร์ผิดปกติ', text: 'ตรวจพบเลขปัจจุบันน้อยกว่าเลขเดิม (อนุญาตเฉพาะเลขเดิมที่เกิน 9,000 ขึ้นไป) หรือยอดใช้สูงเกิน 1,000 หน่วย' }); return; 
        }

        const thaiDate = new Date(readingDate).toLocaleDateString('th-TH', { year: 'numeric', month: 'long', day: 'numeric' });

        Swal.fire({
            title: 'ยืนยันการแก้ไข?',
            html: `วันที่จด: <b class="text-success">${thaiDate}</b><br>จำนวนห้องที่จะอัปเดต: <b class="text-warning text-dark fs-5">${submitCount}</b> ห้อง`,
            icon: 'question', 
            showCancelButton: true, 
            confirmButtonColor: '#ffc107',
            confirmButtonText: 'ยืนยันอัปเดต', 
            cancelButtonText: 'ยกเลิก', 
            reverseButtons: true
        }).then(r => {
            if (r.isConfirmed) {
                Swal.fire({ title: 'กำลังอัปเดต...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                document.querySelectorAll('.room-row.filtered-out input').forEach(input => { input.disabled = true; });
                document.getElementById('meterForm').submit();
            }
        });
    }
</script>
@endpush