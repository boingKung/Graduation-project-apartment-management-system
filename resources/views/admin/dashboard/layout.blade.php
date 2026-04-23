@extends('admin.layout')
@section('title', $title ?? 'แผงควบคุม (Dashboard)')

@push('styles')
<style>
    /* แต่ง Scrollbar และ Tab Menu */
    .custom-scrollbar::-webkit-scrollbar { width: 6px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #c1c1c1; border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #a8a8a8; }
    
    .dashboard-tabs .nav-link {
        color: #6c757d;
        border-radius: 50rem;
        padding: 0.5rem 1.5rem;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    .dashboard-tabs .nav-link:hover { background-color: rgba(13, 110, 253, 0.05); }
    .dashboard-tabs .nav-link.active {
        background-color: #0d6efd; color: #fff; box-shadow: 0 4px 6px rgba(13, 110, 253, 0.2);
    }
</style>
@endpush

@section('content')
<div class="container-fluid pb-5">

    {{-- 1. Header & Greeting --}}
    <div class="d-flex flex-wrap align-items-end justify-content-between mb-4 gap-2">
        <div>
            <h4 class="fw-bold mb-1">
                @php
                    $hour = (int) date('H');
                    $greeting = $hour < 12 ? 'อรุณสวัสดิ์' : ($hour < 17 ? 'สวัสดีตอนบ่าย' : 'สวัสดีตอนเย็น');
                @endphp
                {{ $greeting }} {{ Auth::guard('admin')->user()->firstname ?? 'Admin' }}
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

    {{-- 2. เมนู Dashboard 4 หน้า --}}
    <div class="d-flex justify-content-center mb-4">
        <ul class="nav nav-pills dashboard-tabs gap-2 bg-white p-2 rounded-pill shadow-sm d-inline-flex">
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                    <i class="bi bi-grid-1x2-fill me-1"></i> ภาพรวมหอพัก
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.dashboard.rental_income') ? 'active' : '' }}" href="{{ route('admin.dashboard.rental_income') }}">
                    <i class="bi bi-cash-coin me-1"></i> วิเคราะห์รายรับค่าเช่า
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.dashboard.cashflow') ? 'active' : '' }}" href="{{ route('admin.dashboard.cashflow') }}">
                    <i class="bi bi-graph-up-arrow me-1"></i> วิเคราะห์รายรับ-รายจ่าย
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.dashboard.meter') ? 'active' : '' }}" href="{{ route('admin.dashboard.meter') }}">
                    <i class="bi bi-lightning-charge-fill me-1"></i> วิเคราะห์มิเตอร์น้ำ-ไฟ
                </a>
            </li>
        </ul>
    </div>

    {{-- 3. Slicer (ตัวกรองข้อมูลแบบใช้ร่วมกันทุกหน้า) --}}
    <div class="card border-0 shadow-sm mb-4 rounded-3 bg-white">
        <div class="card-body p-3">
            {{-- url()->current() จะส่งฟอร์มไปที่หน้าปัจจุบันเสมอ --}}
            <form action="{{ url()->current() }}" method="GET" id="dashboardFilterForm" class="row align-items-center g-2">
                <div class="col-auto">
                    <span class="fw-bold text-muted me-2"><i class="bi bi-funnel-fill"></i> ตัวกรองข้อมูล:</span>
                </div>
                <div class="col-md-2 col-5">
                    <input type="month" name="start_month" id="slicerStartMonth" class="form-control form-control-sm border-secondary shadow-none" 
                           value="{{ request('start_month', now()->subMonths(5)->format('Y-m')) }}" onchange="triggerFilter()">
                </div>
                <div class="col-auto text-muted small px-0">ถึง</div>
                <div class="col-md-2 col-5">
                    <input type="month" name="end_month" id="slicerEndMonth" class="form-control form-control-sm border-secondary shadow-none" 
                           value="{{ request('end_month', now()->format('Y-m')) }}" onchange="triggerFilter()">
                </div>
                <div class="col-md-3 col-6">
                    <select name="building_id" id="slicerBuilding" class="form-select form-select-sm border-secondary shadow-none" onchange="triggerFilter()">
                        <option value="">ทุกอาคาร</option>
                        @foreach ($buildings ?? [] as $b)
                            <option value="{{ $b->id }}" {{ request('building_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                {{-- 🌟 ตัวกรองชั้น --}}
                <div class="col-md-2 col-6">
                    <select name="floor_num" id="slicerFloor" class="form-select form-select-sm border-secondary shadow-none" onchange="triggerFilter()">
                        <option value="">ทุกชั้น</option>
                        @foreach ($floors ?? [] as $f)
                            <option value="{{ $f }}" {{ request('floor_num') == $f ? 'selected' : '' }}>ชั้น {{ $f }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto ms-auto d-none d-md-block">
                    <button type="button" class="btn btn-sm btn-light border" onclick="location.href='{{ url()->current() }}'">
                        <i class="bi bi-arrow-clockwise"></i> รีเฟรช
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- 🌟 4. ส่วนสำหรับแทรกเนื้อหาของแต่ละหน้า (Dynamic Content) --}}
    @yield('dashboard_content')

</div>
@endsection

@push('scripts')
<script>
    // ฟังก์ชันนี้จะทำหน้าที่ตัดสินใจว่า จะรีโหลดหน้าจอใหม่ หรือจะยิง AJAX 
    // ขึ้นอยู่กับว่าหน้านั้นๆ มีฟังก์ชัน updateDashboard() ให้เรียกใช้หรือไม่
    function triggerFilter() {
        const start = document.getElementById('slicerStartMonth').value;
        const end = document.getElementById('slicerEndMonth').value;
        if (start > end) {
            Swal.fire({ icon: 'warning', title: 'วันที่ไม่ถูกต้อง', text: 'เดือนเริ่มต้นต้องก่อนหรือเท่ากับเดือนสิ้นสุด' });
            return;
        }

        if (typeof updateDashboard === 'function') {
            // ถ้าหน้าลูกมีการเขียน AJAX ไว้ ให้เรียก AJAX แทนการรีโหลด
            updateDashboard(); 
        } else {
            // ถ้าไม่มี ให้ Submit ฟอร์มเพื่อรีโหลดหน้าแบบปกติ
            document.getElementById('dashboardFilterForm').submit();
        }
    }
</script>
@endpush