@php
    $isWater = $type === 'water';
    $iconName = $isWater ? 'droplet' : 'lightning';
    $themeClass = $isWater ? 'info' : 'warning';
@endphp

<div class="table-responsive">
<table class="table table-bordered table-hover align-middle mb-0">
    <thead class="{{ $isWater ? 'table-info' : 'table-warning' }} text-center">
        <tr>
            <th style="width:80px;">ห้อง</th>
            <th style="width:70px;">สถานะ</th>
            <th>เลขเดือนก่อน (ยกมา)</th>
            <th>เลขปัจจุบัน ({{ $thai_date }})</th>
            <th style="width:100px;">หน่วยใช้</th>
        </tr>
    </thead>
    <tbody>
        @forelse($rooms as $room)
            @php
                $tenant = $room->tenants->first();
                foreach(['water', 'electric'] as $m_type) {
                    if($type == $m_type) {
                        $prev_val = $room->{"prev_{$m_type}"};
                    }
                }
                $currentReading = $existingReadings->where('room_id', $room->id)->where('meter_type', $type)->first();
            @endphp
            @if(!$currentReading)
                <tr data-room-id="{{ $room->id }}" data-building-id="{{ $room->roomPrice->building->id }}">
                    {{-- Room Number --}}
                    <td class="text-center fw-bold fs-5">{{ $room->room_number }}</td>

                    {{-- Status --}}
                    <td class="text-center">
                        <span class="badge bg-success rounded-circle p-2" title="มีผู้เช่า">
                            <i class="bi bi-person-fill"></i>
                        </span>
                    </td>

                    {{-- Previous Value --}}
                    <td class="bg-light">
                        <div class="d-flex align-items-center gap-2">
                            <input type="number" 
                                name="data[{{ $room->id }}][{{ $type }}][previous_value]" 
                                class="form-control text-center fw-bold prev-input {{ is_null($prev_val) ? 'border-warning' : 'border-0 bg-transparent' }}" 
                                value="{{ $currentReading->previous_value ?? $prev_val ?? 0 }}" 
                                {{ !is_null($prev_val) ? 'readonly' : '' }}
                                placeholder="กรอกค่าเริ่มต้น"
                                oninput="calculateFromRow(this)"
                                min="0"
                                autocomplete="off"
                                style="max-width:150px;">
                            @if(is_null($prev_val))
                                <span class="badge bg-warning text-dark"><i class="bi bi-pencil-fill"></i></span>
                            @endif
                        </div>
                        @if(is_null($prev_val))
                            <small class="text-danger mt-1 d-block"><i class="bi bi-exclamation-circle me-1"></i>ไม่พบข้อมูลเดือนก่อน</small>
                        @endif
                    </td>

                    {{-- Current Value --}}
                    <td>
                        <div class="input-group">
                            <span class="input-group-text text-{{ $themeClass }}">
                                <i class="bi bi-{{ $iconName }}-fill"></i>
                            </span>
                            <input type="number" name="data[{{ $room->id }}][{{ $type }}][current_value]" 
                                class="form-control form-control-lg current-input fw-bold" 
                                value="{{ $currentReading->current_value ?? '' }}" 
                                required
                                oninput="calculateFromRow(this)"
                                placeholder="กรอกเลข..."
                                autocomplete="off">
                            
                            <input type="hidden" name="data[{{ $room->id }}][{{ $type }}][tenant_id]" value="{{ $tenant->id ?? '' }}">
                            <input type="hidden" name="data[{{ $room->id }}][{{ $type }}][meter_type]" value="{{ $type }}">
                            <input type="hidden" name="data[{{ $room->id }}][{{ $type }}][reading_date]" value="{{ date('Y-m-d') }}">
                        </div>
                    </td>

                    {{-- Units Used --}}
                    <td class="text-center fw-bold fs-5">
                        <span class="units-used text-muted">{{ $currentReading->units_used ?? 0 }}</span>
                    </td>
                </tr>
            @endif
        @empty
            <tr>
                <td colspan="5" class="text-center py-4 text-muted">
                    <i class="bi bi-check-circle-fill text-success me-1"></i>
                    ข้อมูลค่า{{ $isWater ? 'น้ำ' : 'ไฟ' }}ในเดือน <strong>{{ $thai_date }}</strong> ถูกจดครบแล้ว
                    &mdash; <a href="{{ route('admin.meter_readings.show') }}">ดูข้อมูลที่บันทึก</a>
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
</div>