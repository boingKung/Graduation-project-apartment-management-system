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
            <th>เลขเดือนก่อน</th>
            <th>เลขปัจจุบัน {{ $thai_date }} <span class="badge bg-dark bg-opacity-25 ms-1">แก้ไข</span></th>
            <th style="width:100px;">หน่วยใช้</th>
        </tr>
    </thead>
    <tbody>
        @forelse($rooms as $room)
            @php
                $tenant = $room->tenants->first();
                $prev_val = $room->{"prev_{$type}"};
                $currentReading = $existingReadings->where('room_id', $room->id)->where('meter_type', $type)->first();
            @endphp
            @if($currentReading)
                <tr data-room-id="{{ $room->id }}" data-building-id="{{ $room->roomPrice->building->id }}">
                    {{-- Room --}}
                    <td class="text-center fw-bold fs-5 bg-light">{{ $room->room_number }}</td>

                    {{-- Previous --}}
                    <td class="bg-light text-center fw-bold">
                        {{ $currentReading->previous_value }}
                        <input type="hidden" name="data[{{ $room->id }}][{{ $type }}][previous_value]" class="prev-input" value="{{ $currentReading->previous_value }}">
                    </td>

                    {{-- Current (Editable) --}}
                    <td>
                        <div class="input-group">
                            <span class="input-group-text text-{{ $themeClass }}">
                                <i class="bi bi-pencil-fill"></i>
                            </span>
                            <input type="number" name="data[{{ $room->id }}][{{ $type }}][current_value]" 
                                   class="form-control form-control-lg current-input fw-bold border-warning" 
                                   value="{{ $currentReading->current_value }}" 
                                   oninput="calculateFromRow(this)" required
                                   min="0" autocomplete="off">
                            <input type="hidden" name="data[{{ $room->id }}][{{ $type }}][tenant_id]" value="{{ $currentReading->tenant_id }}">
                        </div>
                    </td>

                    {{-- Units --}}
                    <td class="text-center fw-bold fs-5">
                        <span class="units-used text-primary">{{ $currentReading->units_used }}</span>
                    </td>
                </tr>
            @endif
        @empty
            <tr>
                <td colspan="4" class="text-center py-4 text-muted">
                    <i class="bi bi-info-circle me-1"></i>
                    ยังไม่มีข้อมูลค่า{{ $isWater ? 'น้ำ' : 'ไฟ' }}ที่จดบันทึกในเดือน <strong>{{ $thai_date }}</strong>
                    &mdash; <a href="{{ route('admin.meter_readings.insertForm') }}">ไปจดมิเตอร์</a>
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
</div>