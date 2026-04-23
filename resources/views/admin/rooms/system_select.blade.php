@extends('admin.layout')

@section('content')
<div class="container-fluid">
    <div class="text-center mb-5">
        <h2 class="text-primary fw-bold"><i class="fas fa-building"></i> เลือกอาคารที่ต้องการจัดการ</h2>
        <p class="text-muted">ระบบบริหารจัดการผังห้องพักอัจฉริยะ</p>
    </div>

    <div class="row justify-content-center">
        @foreach($buildings as $b)
        <div class="col-md-6 col-lg-4 col-xl-3 mb-4">
            <a href="{{ route('admin.rooms.system', ['building_id' => $b->id]) }}" class="text-decoration-none">
                <div class="card h-100 shadow-sm building-card border-0">
                    <div class="card-body text-center p-4">
                        <div class="building-icon mb-3">
                            <i class="fas fa-city fa-4x text-secondary"></i>
                        </div>
                        <h3 class="card-title text-dark fw-bold">{{ $b->name }}</h3>
                        <p class="text-muted mb-4">{{ $b->total_floors }} {{ $b->total_rooms }} ห้อง</p>
                        
                        {{-- สรุปสถานะ --}}
                        <div class="d-flex justify-content-around mt-3">
                            <div class="text-success">
                                <i class="fas fa-door-open"></i> ว่าง <br> <strong>{{ $b->available_rooms }}</strong>
                            </div>
                            <div class="text-danger">
                                <i class="fas fa-user"></i> เช่าแล้ว <br> <strong>{{ $b->occupied_rooms }}</strong>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-primary text-white text-center">
                        จัดการข้อมูล{{ $b->name }} <i class="fas fa-arrow-right ms-2"></i>
                    </div>
                </div>
            </a>
        </div>
        @endforeach
    </div>
</div>

<style>
    .building-card { transition: transform 0.3s, box-shadow 0.3s; cursor: pointer; }
    .building-card:hover { transform: translateY(-10px); box-shadow: 0 15px 30px rgba(0,0,0,0.1)!important; }
    .building-card:hover .building-icon i { color: #0d6efd !important; transition: color 0.3s; }
</style>
@endsection