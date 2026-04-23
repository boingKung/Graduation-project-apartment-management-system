@extends('admin.layout')

@section('title', 'บันทึกเลขมิเตอร์น้ำ-ไฟ')

@section('content')
    <div class="container-fluid">

        {{-- Header --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body py-3 px-4">
                <div class="row align-items-center g-3">
                    <div class="col-md-5">
                        <h5 class="fw-bold mb-1"><i class="bi bi-speedometer2 me-2 text-primary"></i>จดมิเตอร์ น้ำ + ไฟ</h5>
                        <span class="text-muted small">รอบเดือน: <strong>{{ $thai_date }}</strong></span>

                        {{-- Progress --}}
                        @php
                            $filterMode = request('filter_tenant', 'occupied');
                            if ($filterMode === 'all') {
                                $totalTarget = \App\Models\Room::count();
                            } else {
                                $totalTarget = \App\Models\Room::whereIn('status', ['มีผู้เช่า', 'ซ่อมบำรุง'])->count();
                            }

                            $doneCount = \App\Models\MeterReading::where('billing_month', $billing_month)
                                ->select('room_id')
                                ->groupBy('room_id')
                                ->havingRaw('COUNT(DISTINCT meter_type) >= 2')
                                ->get()
                                ->count();

                            if ($doneCount > $totalTarget) $doneCount = $totalTarget;
                            $pct = $totalTarget > 0 ? round(($doneCount / $totalTarget) * 100) : 0;
                            $remainingCount = $totalTarget - $doneCount;
                        @endphp

                        <div class="d-flex align-items-center gap-2 mt-2">
                            <div class="progress flex-grow-1" style="height: 8px;">
                                <div class="progress-bar {{ $pct === 100 ? 'bg-success' : 'bg-primary' }}"
                                    style="width: {{ $pct }}%"></div>
                            </div>
                            <span class="badge {{ $pct === 100 ? 'bg-success' : 'bg-primary' }}">
                                {{ $doneCount }}/{{ $totalTarget }}
                            </span>
                        </div>

                        @if ($remainingCount > 0)
                            <small class="text-muted">เป้าหมายเหลืออีก <strong class="text-danger">{{ $remainingCount }}</strong> ห้อง</small>
                        @else
                            <small class="text-success fw-bold"><i class="bi bi-check-circle-fill me-1"></i>จดครบตามเป้าแล้ว!</small>
                        @endif
                    </div>

                    <div class="col-md-7">
                        <div class="d-flex flex-wrap justify-content-md-end align-items-center gap-2">
                            <a href="{{ route('admin.meter_readings.show') }}" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-journal-check me-1"></i> ดู/แก้ไขที่จดแล้ว
                            </a>

                            <div class="vr d-none d-md-block opacity-25"></div>

                            <div class="d-flex align-items-center gap-2">
                                <label class="text-muted small text-nowrap mb-0">วันที่จด:</label>
                                <input type="date" name="reading_date" form="meterForm" id="reading_date"
                                    class="form-control form-control-sm" style="width:155px;" value="{{ date('Y-m-d') }}"
                                    required autocomplete="off">
                            </div>

                            <div class="vr d-none d-md-block opacity-25"></div>

                            <form method="GET" action="{{ route('admin.meter_readings.insertForm') }}" id="searchForm"
                                class="d-flex align-items-center gap-2">
                                <input type="hidden" name="search_room" value="{{ request('search_room') }}">
                                <input type="hidden" name="filter_tenant" value="{{ request('filter_tenant', 'occupied') }}">
                                <input type="hidden" name="filter_floor" value="{{ request('filter_floor') }}">

                                <label class="text-muted small text-nowrap mb-0">เดือน:</label>
                                <input type="month" name="billing_month"
                                    class="form-control form-control-sm fw-bold text-primary" style="width:150px;"
                                    value="{{ $billing_month }}" onchange="this.form.submit()" autocomplete="off">
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filter Bar (แถบเครื่องมือกรองข้อมูล) --}}
        <div class="card border-0 shadow-sm mb-3 bg-white">
            <div class="card-body py-2 px-3">
                <form method="GET" action="{{ route('admin.meter_readings.insertForm') }}"
                    class="row g-2 align-items-center">
                    <input type="hidden" name="billing_month" value="{{ $billing_month }}">

                    <div class="col-auto text-muted d-none d-sm-block">
                        <i class="bi bi-funnel-fill"></i> คัดกรอง:
                    </div>

                    {{-- 1. ค้นหาเลขห้อง --}}
                    <div class="col-auto">
                        <input type="text" name="search_room" class="form-control form-control-sm bg-light"
                            placeholder="ค้นหาเลขห้อง..." value="{{ request('search_room') }}"
                            onkeydown="if(event.key === 'Enter') this.form.submit();" style="width: 120px;">
                    </div>

                    {{-- 🌟 2. เพิ่ม Filter ค้นหาชั้น --}}
                    <div class="col-auto">
                        <select name="filter_floor" class="form-select form-select-sm bg-light text-muted"
                            style="width:120px;" onchange="this.form.submit()">
                            <option value="">- ทุกชั้น -</option>
                            @foreach ($floorNums as $floor)
                                <option value="{{ $floor }}" {{ request('filter_floor') == $floor ? 'selected' : '' }}>ชั้น {{ $floor }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- 3. กรองตึก (JavaScript ฝั่งหน้าบ้าน) --}}
                    <div class="col-auto">
                        <select id="filterBuilding" class="form-select form-select-sm bg-light text-muted"
                            style="width:140px;" onchange="filterByBuilding()">
                            <option value="">- เลือกทุกตึก -</option>
                            @php $uniqueBuildings = collect($rooms)->map(fn($r) => $r->roomPrice->building)->unique('id')->sortBy('name'); @endphp
                            @foreach ($uniqueBuildings as $bld)
                                <option value="{{ $bld->id }}">{{ $bld->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- 4. กรองสถานะห้อง (Backend) --}}
                    <div class="col-auto ms-sm-auto">
                        <select name="filter_tenant" class="form-select form-select-sm border-primary fw-bold"
                            style="width:200px;" onchange="this.form.submit()">
                            <option value="all" {{ request('filter_tenant', 'occupied') == 'all' ? 'selected' : '' }}>
                                แสดงห้องทั้งหมด
                            </option>
                            <option value="occupied" class="text-success"
                                {{ request('filter_tenant', 'occupied') == 'occupied' ? 'selected' : '' }}>
                                แสดงเฉพาะห้องที่มีผู้เช่า
                            </option>
                        </select>
                    </div>

                    <div class="col-auto">
                        <a href="{{ route('admin.meter_readings.insertForm') }}?billing_month={{ $billing_month }}"
                            class="btn btn-sm btn-light border" title="ล้างตัวกรอง">
                            <i class="bi bi-eraser"></i>
                        </a>
                        <button type="submit" class="d-none">Search</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Form ส่งข้อมูล --}}
        <form action="{{ route('admin.meter_readings.insert') }}" method="POST" id="meterForm">
            @csrf
            <input type="hidden" name="billing_month" value="{{ $billing_month }}">
            <input type="hidden" name="reading_date" id="hidden_reading_date" value="{{ date('Y-m-d') }}">

            @if ($rooms->count() > 0)
                {{-- Group by Building --}}
                @php $grouped = collect($rooms)->groupBy(fn($r) => $r->roomPrice->building->name); @endphp

                @foreach ($grouped as $buildingName => $buildingRooms)
                    @php $buildingId = $buildingRooms->first()->roomPrice->building->id; @endphp
                    <div class="building-group mb-4" data-building-id="{{ $buildingId }}">
                        <div class="d-flex align-items-center gap-2 mb-2 px-1">
                            <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-building me-1 text-primary"></i>{{ $buildingName }}</h6>
                            <span class="badge bg-secondary room-counter">{{ $buildingRooms->count() }} ห้อง</span>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle mb-0 bg-white shadow-sm">
                                <thead class="table-light text-center small">
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
                                        <th style="width:130px; background: rgba(13,202,240,0.05);">ปัจจุบัน</th>
                                        <th style="width:65px; background: rgba(13,202,240,0.05);">หน่วย</th>
                                        <th style="width:110px; background: rgba(255,193,7,0.05);">ก่อนหน้า</th>
                                        <th style="width:130px; background: rgba(255,193,7,0.05);">ปัจจุบัน</th>
                                        <th style="width:65px; background: rgba(255,193,7,0.05);">หน่วย</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($buildingRooms->sortBy('room_number') as $room)
                                        @php
                                            $tenant = $room->tenants->first();
                                            $prevWater = is_null($room->prev_water) ? 0 : $room->prev_water;
                                            $prevElectric = is_null($room->prev_electric) ? 0 : $room->prev_electric;
                                            
                                            $existWater = $existingReadings->where('room_id', $room->id)->where('meter_type', 'water')->first();
                                            $existElectric = $existingReadings->where('room_id', $room->id)->where('meter_type', 'electric')->first();
                                        @endphp
                                        <tr data-room-id="{{ $room->id }}" data-building-id="{{ $buildingId }}" class="room-row">
                                            {{-- Room --}}
                                            <td class="text-center fw-bold bg-light text-primary fs-5">
                                                {{ $room->room_number }}
                                            </td>

                                            {{-- === ค่าน้ำ === --}}
                                            <td class="text-center" style="background: rgba(13,202,240,0.04);">
                                                @if (!$existWater)
                                                    {{-- 🌟 เปลียนเป็น Input เพื่อให้แก้ไขเลขเดือนก่อนได้เสมอ --}}
                                                    <input type="number" name="data[{{ $room->id }}][water][previous_value]"
                                                        class="form-control form-control-sm text-center text-muted prev-input border-warning"
                                                        value="{{ $prevWater }}" oninput="calcRow(this)" min="0" autocomplete="off" style="max-width:90px; margin:0 auto; font-size:0.85rem;">
                                                @else
                                                    <span class="fw-bold small text-muted">{{ number_format($existWater->previous_value) }}</span>
                                                @endif
                                            </td>
                                            <td style="background: rgba(13,202,240,0.04);">
                                                @if (!$existWater)
                                                    <input type="number" name="data[{{ $room->id }}][water][current_value]"
                                                        class="form-control form-control-sm current-input fw-bold text-center border-info"
                                                        value="" required oninput="calcRow(this)" placeholder="..."
                                                        autocomplete="off" style="max-width:110px; margin:0 auto;" min="0">
                                                    <input type="hidden" name="data[{{ $room->id }}][water][tenant_id]" value="{{ $tenant->id ?? '' }}">
                                                @else
                                                    <span class="fw-bold text-success small">{{ number_format($existWater->current_value) }} ✓</span>
                                                @endif
                                            </td>
                                            <td class="text-center" style="background: rgba(13,202,240,0.04);">
                                                <span class="units-used fw-bold text-muted small">{{ $existWater->units_used ?? 0 }}</span>
                                            </td>

                                            {{-- === ค่าไฟ === --}}
                                            <td class="text-center" style="background: rgba(255,193,7,0.04);">
                                                @if (!$existElectric)
                                                    {{-- 🌟 เปลียนเป็น Input เพื่อให้แก้ไขเลขเดือนก่อนได้เสมอ --}}
                                                    <input type="number" name="data[{{ $room->id }}][electric][previous_value]"
                                                        class="form-control form-control-sm text-center text-muted prev-input border-warning"
                                                        value="{{ $prevElectric }}" oninput="calcRow(this)" min="0" autocomplete="off" style="max-width:90px; margin:0 auto; font-size:0.85rem;">
                                                @else
                                                    <span class="fw-bold small text-muted">{{ number_format($existElectric->previous_value) }}</span>
                                                @endif
                                            </td>
                                            <td style="background: rgba(255,193,7,0.04);">
                                                @if (!$existElectric)
                                                    <input type="number" name="data[{{ $room->id }}][electric][current_value]"
                                                        class="form-control form-control-sm current-input fw-bold text-center border-warning"
                                                        value="" required oninput="calcRow(this)" placeholder="..."
                                                        autocomplete="off" style="max-width:110px; margin:0 auto;" min="0">
                                                    <input type="hidden" name="data[{{ $room->id }}][electric][tenant_id]" value="{{ $tenant->id ?? '' }}">
                                                @else
                                                    <span class="fw-bold text-success small">{{ number_format($existElectric->current_value) }} ✓</span>
                                                @endif
                                            </td>
                                            <td class="text-center" style="background: rgba(255,193,7,0.04);">
                                                <span class="units-used fw-bold text-muted small">{{ $existElectric->units_used ?? 0 }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach

                {{-- Submit --}}
                <div class="text-end mt-3 sticky-bottom bg-white p-3 border-top shadow-sm" style="bottom: 0; z-index: 100;">
                    <div class="d-inline-flex align-items-center me-3 text-muted small" id="summaryCount">
                        เตรียมบันทึกข้อมูล: <strong class="ms-1 text-dark fs-6">{{ collect($rooms)->count() }} </strong> <span> &nbsp;ห้อง</span>
                    </div>
                    <button type="button" class="btn btn-primary px-4 py-2 fw-bold shadow-sm" onclick="validateAndSubmit()">
                        <i class="bi bi-save2-fill me-1"></i> บันทึกรายการ
                    </button>
                </div>
            @else
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5 text-muted">
                        <i class="bi bi-info-circle text-secondary fs-1 d-block mb-3"></i>
                        <h5 class="fw-bold text-dark">ไม่พบห้องที่รอจดมิเตอร์</h5>
                        <p class="mb-3">
                            @if ($searchRoom || request('filter_floor'))
                                ไม่มีห้องที่ตรงกับเงื่อนไขการค้นหาที่ยังไม่ได้จดมิเตอร์ในเดือนนี้
                            @else
                                ห้องทั้งหมดถูกจดครบแล้ว หรือยังไม่มีผู้เช่าเข้าพักในเดือนนี้
                            @endif
                        </p>
                        <a href="{{ route('admin.meter_readings.insertForm') }}" class="btn btn-outline-secondary me-2">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> ล้างการค้นหา
                        </a>
                        <a href="{{ route('admin.meter_readings.show') }}" class="btn btn-primary shadow-sm">
                            <i class="bi bi-journal-check me-1"></i> ดูข้อมูลที่จดไปแล้ว
                        </a>
                    </div>
                </div>
            @endif
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        // อัปเดตวันที่ซ่อน เมื่อเปลี่ยนวันที่ด้านบน
        document.getElementById('reading_date').addEventListener('change', function() {
            document.getElementById('hidden_reading_date').value = this.value;
        });

        // กรองตึก (JavaScript ฝั่งหน้าบ้าน)
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

            document.getElementById('summaryCount').innerHTML =
                `เตรียมบันทึกข้อมูล: <strong class="ms-1 text-dark fs-6">${visibleRooms}</strong> ห้อง`;
        }

        // 🌟 แก้ไขฟังก์ชันคำนวณหน่วยที่ใช้ (บังคับห้ามน้อยกว่าเดิม ยกเว้น 9000 ขึ้นไป)
        function calculateUnits(prev, curr) {
            if (curr >= prev) {
                return curr - prev; // กรณีปกติ
            } else {
                // กรณีเลขปัจจุบันน้อยกว่าเลขเดิม (curr < prev)
                // 🌟 อนุญาตให้ตีกลับได้ "เฉพาะ" เมื่อเลขเดิม >= 9000 ขึ้นไป
                if (prev >= 9000) {
                    let prevString = Math.floor(prev).toString();
                    let maxMeterValue = Math.pow(10, prevString.length); // เช่น 9999 หลักคือ 4 -> max คือ 10000
                    return (maxMeterValue - prev) + curr;
                } else {
                    // ถ้าไม่ถึง 9000 แต่น้อยกว่า ถือว่าแอดมินกรอกผิด
                    return -1; 
                }
            }
        }

        // คำนวณหน่วยที่หน้าจอ
        function calcRow(element) {
            const row = element.closest('tr');
            const cell = element.closest('td');
            const ci = cell.cellIndex;

            let pCell, cCell, uCell;
            if (ci <= 3) {
                pCell = row.cells[1]; cCell = row.cells[2]; uCell = row.cells[3];
            } else {
                pCell = row.cells[4]; cCell = row.cells[5]; uCell = row.cells[6];
            }

            const pInput = pCell.querySelector('.prev-input');
            const cInput = cCell.querySelector('.current-input');
            const display = uCell.querySelector('.units-used');

            if (!pInput || !cInput || !display) return;

            const prev = parseFloat(pInput.value) || 0;
            const curr = parseFloat(cInput.value) || 0;
            
            // ป้องกันไม่ให้พิมพ์เลขยาวเกินไป (ให้พิมพ์ได้ยาวสุด 5 หลัก)
            if(cInput.value.length > 5) cInput.value = cInput.value.slice(0,5);
            if(pInput.value.length > 5) pInput.value = pInput.value.slice(0,5);

            if (cInput.value === '') {
                display.innerText = '0';
                display.className = 'units-used fw-bold text-muted small';
                return;
            }

            const used = calculateUnits(prev, curr);

            // 🌟 ตรวจสอบเงื่อนไขว่าผ่านไหม
            if (used < 0) {
                display.innerText = 'ค่าน้อยกว่าเดิม';
                display.className = 'units-used fw-bold text-danger small';
            } else if (used > 2000) {
                display.innerText = 'ผิดปกติ?';
                display.className = 'units-used fw-bold text-danger small';
            } else {
                display.innerText = used;
                display.className = 'units-used fw-bold text-primary fs-6';
            }
        }

        // ตรวจสอบและส่งฟอร์ม
        function validateAndSubmit() {
            const readingDate = document.getElementById('reading_date').value;
            if (!readingDate) {
                Swal.fire({ icon: 'warning', title: 'ยังไม่ได้เลือกวันที่' });
                return;
            }

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
                Swal.fire({ icon: 'info', title: 'ไม่มีข้อมูลให้บันทึก' });
                return;
            }

            if (emptyCount > 0) {
                Swal.fire({
                    icon: 'warning', title: 'กรอกไม่ครบ',
                    text: `มีช่องว่าง ${emptyCount} ช่องในหน้าที่แสดงผล`
                });
                return;
            }
            
            if (hasError) {
                Swal.fire({
                    icon: 'warning', 
                    title: 'ตัวเลขมิเตอร์ผิดปกติ',
                    text: 'ตรวจพบเลขปัจจุบันน้อยกว่าเลขเดิม (อนุญาตเฉพาะเลขเดิมที่เกิน 9,000 ขึ้นไป) หรือยอดใช้น้ำไฟสูงเกิน 1,000 หน่วย กรุณาตรวจสอบให้แน่ใจอีกครั้ง'
                });
                return;
            }

            const thaiDate = new Date(readingDate).toLocaleDateString('th-TH', { year: 'numeric', month: 'long', day: 'numeric' });

            Swal.fire({
                title: 'ยืนยันการบันทึก?',
                html: `วันที่จด: <b class="text-primary">${thaiDate}</b><br>จำนวนห้องที่จะบันทึก: <b class="text-success fs-5">${submitCount}</b> ห้อง`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#0d6efd',
                confirmButtonText: 'ใช่, บันทึกเลย!',
                cancelButtonText: 'ยกเลิก',
                reverseButtons: true
            }).then(r => {
                if (r.isConfirmed) {
                    Swal.fire({ title: 'กำลังบันทึกข้อมูล...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                    document.querySelectorAll('.room-row.filtered-out input').forEach(input => { input.disabled = true; });
                    document.getElementById('meterForm').submit();
                }
            });
        }
    </script>
@endpush