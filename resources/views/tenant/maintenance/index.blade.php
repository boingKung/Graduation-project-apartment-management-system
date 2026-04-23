@extends('tenant.layout')

@section('title', 'แจ้งซ่อม')

@push('styles')
    <style>
        .hover-lift {
            transition: all 0.3s ease;
        }

        .hover-lift:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1) !important;
        }

        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }

        .animate-pulse {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.5;
            }
        }
    </style>
@endpush

@section('content')

    @php
        $tenant = Auth::guard('tenant')->user();
        $maintenanceList = \App\Models\Maintenance::where('tenant_id', $tenant->id)
            ->orderBy('created_at', 'desc')
            ->get();
        $pendingCount    = $maintenanceList->where('status', 'pending')->count();
        $processingCount = $maintenanceList->where('status', 'processing')->count();
        $finishedCount   = $maintenanceList->where('status', 'finished')->count();
        $totalCount      = $maintenanceList->count();
    @endphp

    <div class="container-xl px-2 px-md-4 py-3">

        {{-- Header --}}
        <div class="mb-4 d-flex justify-content-between align-items-end flex-wrap gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span
                        class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle rounded-pill px-3 py-2 fw-semibold small">
                        <i class="fa-solid fa-screwdriver-wrench me-1"></i> แจ้งซ่อม
                    </span>
                </div>
                <h2 class="fw-bolder mb-2 display-6">รายการแจ้งซ่อม</h2>
                <p class="text-muted mb-0">
                    <i class="fa-solid fa-info-circle me-2"></i>
                    ประวัติและสถานะการแจ้งซ่อมของคุณ
                </p>
            </div>
            <button type="button" class="btn btn-primary rounded-pill px-4 py-2 shadow-sm fw-semibold mb-2 mb-md-0"
                data-bs-toggle="modal" data-bs-target="#maintenanceModal">
                <i class="fa-solid fa-plus me-1"></i> แจ้งซ่อมใหม่
            </button>
        </div>

        {{-- ALERTS --}}
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 d-flex align-items-center" role="alert">
                <i class="fa-solid fa-check-circle fs-4 me-3 text-success"></i>
                <div>{{ session('success') }}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 d-flex align-items-center" role="alert">
                <i class="fa-solid fa-exclamation-circle fs-4 me-3 text-danger"></i>
                <div>{{ $errors->first() }}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- Summary Cards --}}
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <div class="text-muted small">ทั้งหมด</div>
                        <div class="display-6 fw-bold text-primary">{{ $totalCount }}</div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <div class="text-muted small">รอดำเนินการ</div>
                        <div class="display-6 fw-bold text-warning">{{ $pendingCount }}</div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <div class="text-muted small">กำลังซ่อม</div>
                        <div class="display-6 fw-bold text-info">{{ $processingCount }}</div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <div class="text-muted small">เสร็จสิ้น</div>
                        <div class="display-6 fw-bold text-success">{{ $finishedCount }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Maintenance List --}}
        @if ($maintenanceList->isEmpty())
            <div class="card shadow-sm border-0 p-5 text-center">
                <div class="mb-3">
                    <i class="fa-solid fa-inbox text-secondary" style="font-size: 3rem; opacity: 0.5;"></i>
                </div>
                <h5 class="text-secondary">ไม่มีประวัติแจ้งซ่อม</h5>
                <p class="text-muted mb-0">กดปุ่ม "แจ้งซ่อมใหม่" ด้านบนเพื่อเริ่มต้น</p>
            </div>
        @else
            <div class="row g-3">
                @foreach ($maintenanceList as $item)
                    @php
                        $badgeLabel = match ($item->status) {
                            'pending' => 'รอดำเนินการ',
                            'processing' => 'กำลังซ่อม',
                            'finished' => 'เสร็จสิ้น',
                            default => 'ยกเลิก',
                        };
                        $badgeClass = match ($item->status) {
                            'pending' => 'bg-warning text-dark',
                            'processing' => 'bg-info',
                            'finished' => 'bg-success',
                            default => 'bg-secondary',
                        };
                        $iconClass = match ($item->status) {
                            'pending' => 'fa-clock text-warning',
                            'processing' => 'fa-gears text-info animate-pulse',
                            'finished' => 'fa-check-circle text-success',
                            default => 'fa-ban text-secondary',
                        };
                        $bgClass = match ($item->status) {
                            'pending' => 'bg-warning bg-opacity-10',
                            'processing' => 'bg-info bg-opacity-10',
                            'finished' => 'bg-success bg-opacity-10',
                            default => 'bg-secondary bg-opacity-10',
                        };
                        $dateCreated = \Carbon\Carbon::parse($item->created_at)->locale('th');
                    @endphp
                    <div class="col-12">
                        <div class="card shadow-sm border-0 overflow-hidden hover-lift">
                            <div class="row g-0">
                                <div class="col-auto p-3 {{ $bgClass }} d-flex align-items-center justify-content-center"
                                    style="width: 80px;">
                                    <i class="fa-solid {{ $iconClass }}" style="font-size: 2rem;"></i>
                                </div>

                                <div class="col p-3">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h6 class="mb-1 fw-bold text-body">
                                                {{ $item->title }}
                                            </h6>
                                            <p class="text-muted small mb-0">
                                                <i class="fa-regular fa-calendar-alt me-1"></i>
                                                วันที่แจ้ง: {{ $dateCreated->isoFormat('D MMM YYYY HH:mm') }} น.
                                            </p>
                                        </div>

                                        <div class="text-end ms-2">
                                            <span class="badge {{ $badgeClass }}">
                                                {{ $item->status == 'processing' ? '🔧 ' : '' }}{{ $badgeLabel }}
                                            </span>
                                        </div>
                                    </div>

                                    @if ($item->details)
                                        <p class="text-muted small mb-2 text-wrap text-truncate" style="max-height: 2.8em;">
                                            {{ Str::limit($item->details, 100) }}
                                        </p>
                                    @endif

                                    <div class="row pt-2 mt-2 border-top border-secondary-subtle">
                                        <div class="col-sm-6 small mb-1 mb-sm-0">
                                            <span class="text-muted"><i class="fa-solid fa-user-gear me-1"></i> ช่าง:</span>
                                            @if($item->technician_name)
                                                <span class="fw-semibold text-body ms-1">{{ $item->technician_name }}</span>
                                            @else
                                                <span class="fst-italic text-muted ms-1">รอจัดหาช่าง</span>
                                            @endif
                                        </div>
                                        <div class="col-sm-6 small text-sm-end">
                                            <span class="text-muted"><i class="fa-regular fa-clock me-1"></i> วันนัด:</span>
                                            @if($item->technician_time)
                                                <span class="fw-semibold text-body ms-1">{{ \Carbon\Carbon::parse($item->technician_time)->locale('th')->isoFormat('D MMM YYYY HH:mm') }} น.</span>
                                            @else
                                                <span class="fst-italic text-muted ms-1">รอนัดหมาย</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

    </div>

    {{-- ===== MAINTENANCE MODAL ===== --}}
    <div class="modal fade" id="maintenanceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-bottom bg-body-tertiary px-4 py-3">
                    <h5 class="modal-title fw-bold text-body">
                        <i class="fa-solid fa-screwdriver-wrench text-primary me-2"></i> แจ้งซ่อมใหม่
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('tenant.maintenance.send') }}" method="POST">
                    @csrf
                    <div class="modal-body px-4 py-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-muted small">เรื่องที่แจ้ง <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="maintenanceTitle" name="title"
                                   placeholder="เช่น แอร์ไม่เย็น, น้ำไม่ไหล" required autocomplete="off"
                                   value="{{ old('title') }}">
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold text-muted small">เลือกปัญหาที่พบบ่อย</label>
                            <div class="d-flex flex-wrap gap-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill quick-btn" data-value="แอร์ไม่เย็น">
                                    <i class="fa-solid fa-snowflake me-1"></i>แอร์ไม่เย็น
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill quick-btn" data-value="น้ำไม่ไหล">
                                    <i class="fa-solid fa-faucet me-1"></i>น้ำไม่ไหล
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill quick-btn" data-value="ไฟฟ้าเสีย">
                                    <i class="fa-solid fa-bolt me-1"></i>ไฟฟ้าเสีย
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill quick-btn" data-value="ประตูล็อคไม่ได้">
                                    <i class="fa-solid fa-lock me-1"></i>ประตูล็อค
                                </button>
                            </div>
                        </div>
                        <div class="mb-1">
                            <label class="form-label fw-semibold text-muted small">รายละเอียดเพิ่มเติม <span class="fw-normal">(ไม่บังคับ)</span></label>
                            <textarea class="form-control" name="details" rows="3"
                                      placeholder="บรรยายปัญหาที่พบเพิ่มเติม..." autocomplete="off">{{ old('details') }}</textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-top bg-body-tertiary px-4 py-3">
                        <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm text-white fw-semibold">
                            <i class="fa-regular fa-paper-plane me-1"></i> ส่งแจ้งซ่อม
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('.quick-btn').forEach(btn => {
                    btn.addEventListener('click', function () {
                        document.getElementById('maintenanceTitle').value = this.dataset.value;
                        document.querySelectorAll('.quick-btn').forEach(b => {
                            b.classList.remove('btn-primary', 'text-white');
                            b.classList.add('btn-outline-secondary');
                        });
                        this.classList.remove('btn-outline-secondary');
                        this.classList.add('btn-primary', 'text-white');
                    });
                });

                // Auto-open modal on validation error
                @if($errors->any())
                new bootstrap.Modal(document.getElementById('maintenanceModal')).show();
                @endif
            });
        </script>
    @endpush

@endsection
