@extends('admin.layout')

@section('content')
<div class="container-fluid">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-primary"><i class="fas fa-th-large"></i> ผังห้องพักรวม (All Rooms Overview)</h2>
    </div>

    {{-- Filter Bar --}}
    <div class="card mb-4 border-0 shadow-sm bg-light">
        <div class="card-body py-3">
            <form action="{{ route('admin.rooms.system') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-auto"><strong><i class="fas fa-filter"></i> กรอง:</strong></div>
                <div class="col-auto">
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">-- สถานะทั้งหมด --</option>
                        <option value="ว่าง" {{ request('status') == 'ว่าง' ? 'selected' : '' }}>ว่าง (Available)</option>
                        <option value="มีผู้เช่า" {{ request('status') == 'มีผู้เช่า' ? 'selected' : '' }}>มีผู้เช่า (Occupied)</option>
                        <option value="ซ่อมแซม" {{ request('status') == 'ซ่อมแซม' ? 'selected' : '' }}>ซ่อม (Repair)</option>
                    </select>
                </div>
                <div class="col-auto">
                    <input type="text" name="search" class="form-control form-select-sm" placeholder="เลขห้อง..." value="{{ request('search') }}">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-primary">ค้นหา</button>
                    <a href="{{ route('admin.rooms.system') }}" class="btn btn-sm btn-secondary">รีเซ็ต</a>
                </div>
            </form>
        </div>
    </div>

    {{-- วนลูปแสดงตามตึก --}}
    @forelse($roomsByBuilding as $buildingName => $rooms)
        <div class="card mb-5 border-0 shadow-sm">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0"><i class="fas fa-building"></i> {{ $buildingName }}</h4>
                <span class="badge bg-light text-dark">{{ count($rooms) }} ห้อง</span>
            </div>
            <div class="card-body bg-light">
                
                {{-- Grid Layout --}}
                <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-5 row-cols-xl-6 g-3">
                    @foreach($rooms as $room)
                        {{-- ดึงข้อมูลผู้เช่าคนปัจจุบัน (ถ้ามี) --}}
                        @php
                            $currentTenant = $room->tenants->first();
                        @endphp

                        <div class="col">
                            <div class="room-box shadow-sm status-{{ $room->status }}">
                                {{-- ส่วนหัว: เลขห้อง --}}
                                <div class="room-number">
                                    {{ $room->room_number }}
                                </div>
                                
                                {{-- ส่วนเนื้อหา: สถานะและราคา --}}
                                <div class="room-info">
                                    <span class="badge badge-status">
                                        @if($room->status == 'ว่าง') 
                                            ว่าง
                                        @elseif($room->status == 'มีผู้เช่า') 
                                            มีผู้เช่า 
                                            @if($currentTenant) <br><small>({{ $currentTenant->first_name }})</small> @endif
                                        @else 
                                            {{ $room->status }}
                                        @endif
                                    </span>
                                    <div class="price small text-muted mt-1">{{ number_format($room->price) }}฿</div>
                                </div>

                                {{-- ส่วนท้าย: ปุ่ม Action --}}
                                <div class="room-actions">
                                    @if($room->status == 'ว่าง')
                                        {{-- ปุ่มจอง --}}
                                        <a href="{{ route('admin.tenants.create', ['room_id' => $room->id]) }}" 
                                           class="btn btn-success btn-xs w-100">
                                            <i class="fas fa-plus"></i> จองห้อง
                                        </a>
                                    @elseif($room->status == 'มีผู้เช่า' && $currentTenant)
                                        {{-- ปุ่มดูรายละเอียด (เปิด Modal) --}}
                                        <button type="button" class="btn btn-primary btn-xs w-100 btn-view-tenant"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#tenantModal"
                                            data-room="{{ $room->room_number }}"
                                            data-name="{{ $currentTenant->first_name }} {{ $currentTenant->last_name }}"
                                            data-phone="{{ $currentTenant->phone }}"
                                            data-start="{{ \Carbon\Carbon::parse($currentTenant->start_date)->locale('th')->isoFormat('D MMM YYYY') }}"
                                        >
                                            <i class="fas fa-user"></i> รายละเอียด
                                        </button>
                                    @else
                                        {{-- ปุ่มแจ้งซ่อม/สถานะอื่นๆ --}}
                                        <button class="btn btn-warning btn-xs w-100">
                                            <i class="fas fa-wrench"></i> ตรวจสอบ
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @empty
        <div class="alert alert-warning text-center">ไม่พบข้อมูลห้องพัก</div>
    @endforelse
</div>

{{-- Modal แสดงรายละเอียดผู้เช่า --}}
<div class="modal fade" id="tenantModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">ข้อมูลผู้เช่าห้อง <span id="modal-room-number"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><strong>ชื่อ-สกุล:</strong> <span id="modal-tenant-name">-</span></p>
                <p><strong>เบอร์โทร:</strong> <span id="modal-tenant-phone">-</span></p>
                <p><strong>เริ่มสัญญา:</strong> <span id="modal-tenant-start">-</span></p>
            </div>
            <div class="modal-footer p-1">
                <button type="button" class="btn btn-secondary btn-sm w-100" data-bs-dismiss="modal">ปิด</button>
            </div>
        </div>
    </div>
</div>

{{-- Script จัดการ Modal --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tenantModal = document.getElementById('tenantModal');
        tenantModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget; // ปุ่มที่กด
            
            // ดึงข้อมูลจาก data-attributes
            const room = button.getAttribute('data-room');
            const name = button.getAttribute('data-name');
            const phone = button.getAttribute('data-phone');
            const start = button.getAttribute('data-start');

            // อัปเดตข้อมูลใน Modal
            document.getElementById('modal-room-number').textContent = room;
            document.getElementById('modal-tenant-name').textContent = name;
            document.getElementById('modal-tenant-phone').textContent = phone;
            document.getElementById('modal-tenant-start').textContent = start;
        });
    });
</script>

<style>
    /* CSS ตกแต่ง */
    .room-box {
        background: #fff;
        border-radius: 8px;
        overflow: hidden;
        text-align: center;
        transition: all 0.3s;
        border: 1px solid #e0e0e0;
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    .room-box:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1)!important;
        z-index: 10;
    }
    .room-number {
        font-size: 1.2rem;
        font-weight: bold;
        padding: 8px 0;
        background: rgba(0,0,0,0.03);
    }
    .room-info {
        padding: 10px;
        flex-grow: 1;
    }
    .room-actions {
        padding: 5px;
        background: #f8f9fa;
        border-top: 1px solid #eee;
    }
    .btn-xs { font-size: 0.8rem; padding: 4px 8px; }

    /* Map สีตาม Database: 'ว่าง', 'มีผู้เช่า', 'ซ่อมแซม' */
    .status-ว่าง .room-number { color: #28a745; border-bottom: 2px solid #28a745; }
    .status-ว่าง .badge-status { background-color: #d4edda; color: #155724; }

    .status-มีผู้เช่า .room-number { color: #dc3545; border-bottom: 2px solid #dc3545; }
    .status-มีผู้เช่า .badge-status { background-color: #f8d7da; color: #721c24; }

    .status-ซ่อมแซม .room-number { color: #ffc107; border-bottom: 2px solid #ffc107; }
    .status-ซ่อมแซม .badge-status { background-color: #fff3cd; color: #856404; }
</style>
@endsection