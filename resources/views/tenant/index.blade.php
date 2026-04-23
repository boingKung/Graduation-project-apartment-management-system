@extends('tenant.layout')

@section('title', 'ภาพรวมห้องพัก')

@section('content')

    @php
        $hour = (int) date('H');
        if ($hour < 12) {
            $greeting = 'อรุณสวัสดิ์';
        } elseif ($hour < 17) {
            $greeting = 'สวัสดีตอนบ่าย';
        } else {
            $greeting = 'สวัสดีตอนเย็น';
        }

        $isOverdue = false;
        $daysOverdue = 0;
        if ($latestUnpaidInvoice) {
            $dueDate = \Carbon\Carbon::parse($latestUnpaidInvoice->due_date);
            $isOverdue = $dueDate->isPast();
            $daysOverdue = (int) abs($dueDate->diffInDays(now()));
        }

        $contractStart = $tenant->created_at ? \Carbon\Carbon::parse($tenant->created_at) : null;
    @endphp

    {{-- ============================================================
     SECTION 0  :  OVERDUE ALERT BANNER
     ============================================================ --}}
    @if ($latestUnpaidInvoice && $isOverdue)
        <div class="overdue-banner mb-4 rounded-4 p-3 p-md-4 d-flex align-items-start align-items-md-center gap-3 flex-column flex-md-row">
            <div class="overdue-icon flex-shrink-0">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div class="flex-grow-1">
                <div class="overdue-title">⚠️ คุณมียอดค้างชำระเกินกำหนด {{ $daysOverdue }} วัน</div>
                <div class="overdue-sub">
                    ยอดรวม <strong>฿{{ number_format($totalUnpaidAmount, 0) }}</strong>
                    &nbsp;·&nbsp; กำหนดชำระ
                    {{ \Carbon\Carbon::parse($latestUnpaidInvoice->due_date)->locale('th')->isoFormat('D MMM YYYY') }}
                </div>
            </div>
            <a href="{{ route('tenant.invoices.index') }}" class="overdue-btn flex-shrink-0">
                <i class="fa-solid fa-arrow-right me-1"></i> ดูรายละเอียด
            </a>
        </div>
    @endif

    {{-- Success Alert --}}
    @if (session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-4 d-flex align-items-center mb-4 fade show alert-animate">
            <div class="bg-success text-white rounded-circle p-2 me-3 d-flex align-items-center justify-content-center"
                style="width:36px;height:36px;">
                <i class="fa-solid fa-check"></i>
            </div>
            <div class="fw-semibold">{{ session('success') }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- ============================================================
     SECTION 1  :  GREETING HEADER
     ============================================================ --}}
    <div class="row align-items-center mb-4 g-2">
        <div class="col">
            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                <span class="page-chip">
                    <i class="fa-solid fa-building me-1" style="font-size:.7rem;"></i>
                    {{ $apartment->name ?? 'หอพัก' }}
                </span>
                <span class="page-chip text-secondary">
                    <i class="fa-regular fa-calendar me-1" style="font-size:.7rem;"></i>
                    {{ \Carbon\Carbon::now()->locale('th')->isoFormat('D MMMM YYYY') }}
                </span>
            </div>
            <h2 class="greeting-text fw-bolder mb-0">
                {{ $greeting }}, คุณ{{ $tenant->first_name }}!
                <span class="d-inline-block animate-wave">👋</span>
            </h2>
            <p class="text-muted small mt-1 mb-0">ยินดีต้อนรับกลับสู่ระบบจัดการห้องพัก</p>
        </div>
        <div class="col-auto d-none d-sm-block">
            <span class="status-pill">
                <span class="status-dot bg-success me-2 animate-pulse"></span>
                สัญญาปกติ
            </span>
        </div>
    </div>

    {{-- ============================================================
     SECTION 2  :  HERO ROW  (Room card + Bill card + Quick actions)
     ============================================================ --}}
    <div class="row g-3 g-lg-4 mb-4">

        {{-- ---- LEFT: Room Hero Card ---- --}}
        <div class="col-12 col-lg-7">
            <div class="card h-100 border-0 rounded-5 shadow-sm overflow-hidden room-hero-card text-white position-relative"
                style="min-height:230px;">
                {{-- Background decoration --}}
                <div class="rhc-circle rhc-c1"></div>
                <div class="rhc-circle rhc-c2"></div>
                <div class="rhc-grid"></div>

                <div class="card-body p-4 p-lg-5 position-relative" style="z-index:2;">
                    <div class="d-flex justify-content-between align-items-start">
                        {{-- Room number --}}
                        <div>
                            <div class="rhc-label mb-1">ROOM NUMBER</div>
                            <div class="rhc-number">{{ $tenant->room->room_number ?? '-' }}</div>
                            <div class="d-flex gap-2 mt-2 flex-wrap">
                                <span class="rhc-chip">
                                    <i class="fa-solid fa-layer-group me-1" style="font-size:.7rem;"></i>
                                    ชั้น {{ $tenant->room->floor ?? '-' }}
                                </span>
                                @if ($tenant->resident_count ?? 0)
                                    <span class="rhc-chip">
                                        <i class="fa-solid fa-users me-1" style="font-size:.7rem;"></i>
                                        {{ $tenant->resident_count }} คน
                                    </span>
                                @endif
                            </div>
                        </div>
                        {{-- Room type badge --}}
                        <div class="rhc-type-card text-center">
                            <i class="fa-solid fa-door-open fs-5 mb-1 d-block text-dark"></i>
                            <div class="rhc-type-label">ประเภท</div>
                            <div class="rhc-type-name">{{ $tenant->room->roomPrice->roomType->name ?? 'Standard' }}</div>
                        </div>
                    </div>

                    {{-- Bottom stats row --}}
                    <div class="row g-3 mt-3 pt-3 border-top border-white border-opacity-20">
                        <div class="col-6">
                            <div class="rhc-stat-label">ค่าเช่ารายเดือน</div>
                            <div class="d-flex align-items-baseline gap-1">
                                <span
                                    class="rhc-stat-value">{{ number_format($tenant->room->price ?? 0) }}</span>
                                <small class="text-white text-opacity-50 fs-6">฿</small>
                            </div>
                        </div>
                        @if ($contractStart)
                            <div class="col-6">
                                <div class="rhc-stat-label">เริ่มสัญญา</div>
                                <div class="rhc-stat-value fs-6">
                                    {{ $contractStart->locale('th')->isoFormat('D MMMM') }}
                                    {{ $contractStart->year + 543 }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- ---- RIGHT column ---- --}}
        <div class="col-12 col-lg-5 d-flex flex-column gap-3">

            {{-- Bill summary card --}}
            @if ($latestUnpaidInvoice)
                <div
                    class="card border-0 rounded-4 shadow-sm bill-card {{ $isOverdue ? 'bill-overdue' : '' }} flex-grow-1">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-start justify-content-between mb-3">
                            <div>
                                <div class="bill-label">
                                    @if ($isOverdue)
                                        <i class="fa-solid fa-circle-exclamation me-1"></i> เกินกำหนดแล้ว
                                    @else
                                        <i class="fa-regular fa-clock me-1"></i> รอชำระเงิน
                                    @endif
                                </div>
                                <div class="bill-amount">฿{{ number_format($totalUnpaidAmount, 0) }}</div>
                            </div>
                            @if ($unpaidInvoiceCount > 1)
                                <span
                                    class="badge px-2 rounded-pill">
                                    {{ $unpaidInvoiceCount }} บิล
                                </span>
                            @endif
                        </div>
                        <div class="d-flex align-items-center gap-2 mb-3 text-muted small">
                            <i class="fa-regular fa-calendar-xmark" style="font-size:.8rem;"></i>
                            <span>
                                กำหนดชำระ:
                                <strong class="{{ $isOverdue ? 'text-danger' : 'text-dark' }}">
                                    {{ \Carbon\Carbon::parse($latestUnpaidInvoice->due_date)->locale('th')->isoFormat('D MMMM YYYY') }}
                                </strong>
                                @if ($isOverdue)
                                    <span class="badge bg-danger ms-1 rounded-pill" style="font-size:.65rem;">เกิน
                                        {{ $daysOverdue }} วัน</span>
                                @else
                                    @php $daysLeft = (int) now()->diffInDays(\Carbon\Carbon::parse($latestUnpaidInvoice->due_date), false); @endphp
                                    @if ($daysLeft <= 3)
                                        <span class="badge bg-warning text-dark ms-1 rounded-pill"
                                            style="font-size:.65rem;">อีก {{ $daysLeft }} วัน</span>
                                    @endif
                                @endif
                            </span>
                        </div>
                        <a href="{{ route('tenant.invoices.index') }}" class="btn-pay w-100">
                            <i class="fa-solid fa-file-invoice-dollar me-2"></i> ดูใบแจ้งหนี้ทั้งหมด
                        </a>
                    </div>
                </div>
            @else
                <div class="card border-0 rounded-4 shadow-sm flex-grow-1 paid-card">
                    <div
                        class="card-body p-4 text-center d-flex flex-column align-items-center justify-content-center gap-2">
                        <div class="paid-icon mx-auto">
                            <i class="fa-solid fa-circle-check fs-2"></i>
                        </div>
                        <div class="fw-bold fs-5">ชำระครบแล้ว!</div>
                        <div class="text-muted small">ไม่มียอดค้างชำระ</div>
                        <a href="{{ route('tenant.invoices.index') }}"
                            class="btn btn-sm btn-outline-primary rounded-pill mt-2 px-4">
                            ประวัติการชำระ <i class="fa-solid fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            @endif

            {{-- Quick action buttons --}}
            <div class="row g-2">
                <div class="col-6">
                    <a href="{{ route('tenant.invoices.index') }}"
                        class="quick-action-card text-decoration-none d-flex align-items-center gap-2 p-3 rounded-4 border h-100">
                        <div class="qa-icon rounded-3">
                            <i class="fa-solid fa-file-invoice-dollar"></i>
                        </div>
                        <div>
                            <div class="fw-semibold" style="font-size:.82rem; line-height:1.2;">ใบแจ้งหนี้
                            </div>
                            <div class="text-muted" style="font-size:.7rem;">ดูและชำระ</div>
                        </div>
                    </a>
                </div>
                <div class="col-6">
                    <a href="{{ route('tenant.maintenance.index') }}"
                        class="quick-action-card text-decoration-none d-flex align-items-center gap-2 p-3 rounded-4 border h-100">
                        <div class="qa-icon rounded-3">
                            <i class="fa-solid fa-screwdriver-wrench"></i>
                        </div>
                        <div>
                            <div class="fw-semibold" style="font-size:.82rem; line-height:1.2;">
                                แจ้งซ่อม</div>
                            <div class="text-muted" style="font-size:.7rem;">รายงานปัญหา</div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
     SECTION 3  :  METER READINGS + RECENT PAYMENT
     ============================================================ --}}
    <div class="row g-3 mb-4">

        {{-- Water meter --}}
        <div class="col-6 col-md-4">
            <div class="card border-0 rounded-4 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="meter-icon rounded-3">
                            <i class="fa-solid fa-droplet"></i>
                        </div>
                        <span class="fw-semibold text-muted" style="font-size:.78rem;">น้ำประปา</span>
                    </div>
                    @if ($meterWater)
                        <div class="meter-value">{{ number_format($meterWater->units_used) }}</div>
                        <div class="meter-unit">หน่วย / เดือนนี้</div>
                        <div class="meter-reading-row mt-2">
                            <i class="fa-solid fa-gauge me-1"></i>
                            <span class="text-muted" style="font-size:.72rem;">มิเตอร์:
                                {{ number_format($meterWater->current_value) }}</span>
                        </div>
                    @else
                        <div class="meter-value text-muted">—</div>
                        <div class="meter-unit">ยังไม่จดมิเตอร์</div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Electric meter --}}
        <div class="col-6 col-md-4">
            <div class="card border-0 rounded-4 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="meter-icon rounded-3">
                            <i class="fa-solid fa-bolt"></i>
                        </div>
                        <span class="fw-semibold text-muted" style="font-size:.78rem;">ไฟฟ้า</span>
                    </div>
                    @if ($meterElectric)
                        <div class="meter-value">{{ number_format($meterElectric->units_used) }}</div>
                        <div class="meter-unit">หน่วย / เดือนนี้</div>
                        <div class="meter-reading-row mt-2">
                            <i class="fa-solid fa-gauge me-1"></i>
                            <span class="text-muted" style="font-size:.72rem;">มิเตอร์:
                                {{ number_format($meterElectric->current_value) }}</span>
                        </div>
                    @else
                        <div class="meter-value text-muted">—</div>
                        <div class="meter-unit">ยังไม่จดมิเตอร์</div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Recent payment --}}
        <div class="col-12 col-md-4">
            <div class="card border-0 rounded-4 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="meter-icon rounded-3">
                            <i class="fa-solid fa-money-bill-wave"></i>
                        </div>
                        <span class="fw-semibold text-muted" style="font-size:.78rem;">ชำระล่าสุด</span>
                    </div>
                    @if ($recentPayments->isNotEmpty())
                        @php $lastPay = $recentPayments->first(); @endphp
                        <div class="meter-value">฿{{ number_format($lastPay->amount_paid, 0) }}</div>
                        <div class="meter-unit">
                            <i class="fa-regular fa-calendar-check me-1"></i>
                            {{ $lastPay->payment_date->locale('th')->isoFormat('D MMM YY') }}
                        </div>
                        {{-- Mini payment timeline --}}
                        @if ($recentPayments->count() > 1)
                            <div class="mt-2 d-flex flex-column gap-1">
                                @foreach ($recentPayments->skip(1)->take(2) as $pay)
                                    <div class="d-flex align-items-center justify-content-between"
                                        style="font-size:.7rem;">
                                        <span
                                            class="text-muted">{{ $pay->payment_date->locale('th')->isoFormat('D MMM YY') }}</span>
                                        <span
                                            class="fw-semibold">฿{{ number_format($pay->amount_paid, 0) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    @else
                        <div class="meter-value text-muted">—</div>
                        <div class="meter-unit">ยังไม่มีประวัติ</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
     SECTION 4  :  MAINTENANCE TEASER
     ============================================================ --}}
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="section-icon rounded-3">
                        <i class="fa-solid fa-screwdriver-wrench"></i>
                    </div>
                    <div>
                        <div class="fw-bold lh-sm" style="font-size:.95rem;">การแจ้งซ่อม</div>
                        <div class="text-muted" style="font-size:.74rem;">รายการล่าสุด</div>
                    </div>
                </div>
                <a href="{{ route('tenant.maintenance.index') }}"
                    class="btn btn-sm rounded-pill px-3 shadow-sm" style="font-size:.8rem;">
                    <i class="fa-solid fa-plus me-1"></i> แจ้งซ่อม
                </a>
            </div>
            @forelse(($maintenanceRequests ?? collect())->take(3) as $item)
                <div class="d-flex align-items-center gap-2 py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                    <div class="flex-shrink-0">
                        @if ($item->status == 'pending')
                            <span class="status-badge status-pending"><span class="sbadge-dot"></span>รอ</span>
                        @elseif($item->status == 'processing')
                            <span class="status-badge status-processing"><span
                                    class="sbadge-dot animate-blink"></span>ซ่อม</span>
                        @elseif($item->status == 'finished')
                            <span class="status-badge status-finished"><i
                                    class="fa-solid fa-check-circle me-1"></i>เสร็จ</span>
                        @else
                            <span class="status-badge status-cancelled"><i class="fa-solid fa-ban me-1"></i>ยกเลิก</span>
                        @endif
                    </div>
                    <div class="flex-grow-1 text-truncate">
                        <span class="fw-semibold text-body small">{{ $item->title }}</span>
                    </div>
                    @php
                        $date = \Carbon\Carbon::parse($item->created_at)->locale('th');
                    @endphp

                    <div class="flex-shrink-0 text-muted" style="font-size:.72rem; white-space:nowrap;">
                        {{ $date->isoFormat('D MMM') }} {{ $date->year + 543 }} · {{ $date->format('H:i') }} น.
                    </div>
                </div>
            @empty
                <div class="text-center py-3">
                    <i class="fa-solid fa-inbox text-muted mb-2 d-block" style="font-size:1.5rem;"></i>
                    <p class="text-muted small mb-0">ยังไม่มีรายการแจ้งซ่อม</p>
                </div>
            @endforelse
            @if (($maintenanceRequests ?? collect())->isNotEmpty())
                <div class="text-end mt-2 pt-1 border-top">
                    <a href="{{ route('tenant.maintenance.index') }}" class="fw-semibold"
                        style="font-size:.8rem; text-decoration:none;">
                        ดูประวัติทั้งหมด <i class="fa-solid fa-arrow-right ms-1"></i>
                    </a>
                </div>
            @endif
        </div>
    </div>

    {{-- ============================================================
     SECTION 6  :  TIPS FOOTER
     ============================================================ --}}
    <div class="tips-card rounded-4 p-3 p-md-4 d-flex align-items-center gap-3 mb-2">
        <div class="tips-icon flex-shrink-0"><i class="fa-solid fa-lightbulb"></i></div>
        <div>
            <div class="fw-semibold mb-1" style="font-size:.9rem;">เคล็ดลับการใช้งาน</div>
            <p class="mb-0" style="font-size:.82rem;">
                คุณสามารถ<strong class="">แจ้งซ่อม</strong>ได้ตลอด 24 ชม.
                และ<strong class="">ตรวจสอบใบแจ้งหนี้</strong>ก่อนวันกำหนดชำระเพื่อความสะดวก
            </p>
        </div>
    </div>

    {{-- ============================================================
     STYLES
     ============================================================ --}}
    <style>
        :root {
            /* Orange-gray-yellow two-tone palette */
            --primary-orange: #ea580c;
            --primary-orange-light: #fb923c;
            --primary-orange-dark: #c2410c;
            --accent-yellow: #fbbf24;
            --accent-yellow-light: #fde68a;
            --accent-yellow-dark: #b45309;
            --neutral-gray-50: #fafafa;
            --neutral-gray-100: #f5f5f5;
            --neutral-gray-200: #e5e5e5;
            --neutral-gray-300: #d4d4d4;
            --neutral-gray-400: #a3a3a3;
            --neutral-gray-500: #737373;
            --neutral-gray-600: #525252;
            --neutral-gray-700: #404040;
            --neutral-gray-800: #262626;
            --neutral-gray-900: #171717;
        }

        /* === Overdue Banner === */
        .overdue-banner {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border: 1.5px solid #fbbf24;
            animation: slideDown 0.4s ease;
        }

        .overdue-icon {
            width: 44px;
            height: 44px;
            background: var(--primary-orange);
            color: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            flex-shrink: 0;
            animation: pulseOrange 2s ease-in-out infinite;
        }

        @keyframes pulseOrange {
            0%, 100% {
                box-shadow: 0 0 0 0 rgba(234, 88, 12, 0.4);
            }
            50% {
                box-shadow: 0 0 0 8px rgba(234, 88, 12, 0);
            }
        }

        .overdue-title {
            font-weight: 700;
            font-size: 0.95rem;
            color: var(--neutral-gray-800);
        }

        .overdue-sub {
            font-size: 0.82rem;
            color: var(--neutral-gray-700);
            margin-top: 2px;
        }

        .overdue-btn {
            display: inline-flex;
            align-items: center;
            background: var(--primary-orange);
            color: #fff;
            border-radius: 20px;
            padding: 7px 18px;
            font-size: 0.83rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
            white-space: nowrap;
        }

        .overdue-btn:hover {
            background: var(--primary-orange-dark);
            color: #fff;
            transform: translateY(-1px);
        }

        /* === Greeting === */
        .greeting-text {
            background: linear-gradient(135deg, var(--primary-orange), var(--accent-yellow-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 1.7rem;
        }

        @media (max-width: 576px) {
            .greeting-text {
                font-size: 1.35rem;
            }
        }

        .page-chip {
            display: inline-flex;
            align-items: center;
            background: var(--neutral-gray-100);
            border: 1px solid var(--neutral-gray-200);
            border-radius: 20px;
            padding: 3px 10px;
            font-size: 0.72rem;
            font-weight: 500;
            color: var(--neutral-gray-600);
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            background: var(--neutral-gray-100);
            border: 1px solid var(--neutral-gray-200);
            border-radius: 20px;
            padding: 5px 14px;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--neutral-gray-700);
        }

        .status-dot {
            width: 9px;
            height: 9px;
            border-radius: 50%;
            display: inline-block;
        }

        /* === Room Hero Card === */
        .room-hero-card {
            background: linear-gradient(135deg, var(--primary-orange) 0%, var(--primary-orange-dark) 100%);
            transition: transform 0.4s, box-shadow 0.4s;
        }

        .room-hero-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(234, 88, 12, 0.25) !important;
        }

        .rhc-circle {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.07);
            pointer-events: none;
        }

        .rhc-c1 {
            width: 240px;
            height: 240px;
            top: -70px;
            right: -70px;
        }

        .rhc-c2 {
            width: 170px;
            height: 170px;
            bottom: -50px;
            left: -50px;
        }

        .rhc-grid {
            position: absolute;
            inset: 0;
            background: radial-gradient(rgba(255, 255, 255, 0.06) 1px, transparent 1px);
            background-size: 20px 20px;
            pointer-events: none;
        }

        .rhc-label {
            font-size: 0.6rem;
            letter-spacing: 2.5px;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.55);
            font-weight: 600;
        }

        .rhc-number {
            font-size: 3.6rem;
            font-weight: 800;
            line-height: 1;
            letter-spacing: -2px;
            color: #fff;
            text-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }

        .rhc-chip {
            display: inline-flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.18);
            color: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 3px 10px;
            font-size: 0.72rem;
            font-weight: 500;
            backdrop-filter: blur(4px);
        }

        .rhc-type-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 16px;
            padding: 12px 16px;
            min-width: 100px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            transition: transform 0.3s;
        }

        .rhc-type-card:hover {
            transform: scale(1.04);
        }

        .rhc-type-label {
            font-size: 0.58rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--neutral-gray-400);
            font-weight: 600;
        }

        .rhc-type-name {
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--neutral-gray-800);
            margin-top: 2px;
        }

        .rhc-stat-label {
            font-size: 0.62rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(255, 255, 255, 0.55);
            font-weight: 600;
            margin-bottom: 2px;
        }

        .rhc-stat-value {
            font-size: 1.4rem;
            font-weight: 700;
            color: #fff;
            line-height: 1;
        }

        /* === Bill Card === */
        .bill-card {
            border-left: 4px solid var(--accent-yellow) !important;
        }

        .bill-overdue {
            border-left-color: var(--primary-orange) !important;
            background: #fffbeb;
        }

        .bill-label {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--neutral-gray-500);
            font-weight: 600;
            margin-bottom: 2px;
        }

        .bill-amount {
            font-size: 2rem;
            font-weight: 800;
            color: var(--neutral-gray-800);
            line-height: 1.1;
        }

        [data-bs-theme="dark"] .bill-amount {
            color: #f1f5f9;
        }

        .btn-pay {
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary-orange), var(--primary-orange-dark));
            color: #fff;
            border-radius: 12px;
            padding: 11px;
            font-size: 0.88rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.25s;
            border: none;
            box-shadow: 0 4px 14px rgba(234, 88, 12, 0.3);
        }

        .btn-pay:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 22px rgba(234, 88, 12, 0.4);
            color: #fff;
        }

        /* === Quick Action Cards === */
        .quick-action-card {
            transition: all 0.25s;
            background: var(--neutral-gray-50) !important;
            border-color: var(--neutral-gray-200) !important;
        }

        .quick-action-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08) !important;
            background: var(--neutral-gray-100) !important;
        }

        .qa-icon {
            width: 38px;
            height: 38px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.95rem;
            background: var(--accent-yellow-light);
            color: var(--primary-orange-dark);
        }

        /* === Paid Card === */
        .paid-card {
            background: linear-gradient(135deg, var(--neutral-gray-50) 0%, var(--neutral-gray-100) 100%) !important;
        }

        .paid-icon {
            width: 64px;
            height: 64px;
            background: var(--accent-yellow-light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            color: var(--primary-orange-dark);
        }

        /* === Meter Cards === */
        .meter-icon {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            background: var(--accent-yellow-light);
            color: var(--primary-orange-dark);
        }

        .meter-value {
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--neutral-gray-800);
            line-height: 1.1;
        }

        .meter-unit {
            font-size: 0.72rem;
            color: var(--neutral-gray-500);
            margin-top: 1px;
        }

        .meter-reading-row {
            display: flex;
            align-items: center;
            font-size: 0.72rem;
        }

        /* === Section header === */
        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .section-icon {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.88rem;
            background: var(--accent-yellow-light);
            color: var(--primary-orange-dark);
        }

        /* === Maintenance stats === */
        .mstat-card {
            background: var(--neutral-gray-50);
            transition: all 0.25s;
        }

        .mstat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.06) !important;
        }

        .mstat-icon {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
        }

        .mstat-num {
            font-size: 1.6rem;
            font-weight: 800;
            line-height: 1.1;
        }

        .mstat-label {
            font-size: 0.7rem;
            color: var(--neutral-gray-500);
            margin-top: 2px;
        }

        /* === Status badges === */
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.76rem;
            font-weight: 600;
        }

        .sbadge-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
            background: var(--accent-yellow-dark);
        }

        .status-pending {
            background: var(--accent-yellow-light);
            color: var(--neutral-gray-800);
            border: 1px solid var(--accent-yellow);
        }

        .status-processing {
            background: var(--neutral-gray-100);
            color: var(--neutral-gray-700);
            border: 1px solid var(--neutral-gray-300);
        }

        .status-finished {
            background: var(--neutral-gray-100);
            color: var(--neutral-gray-700);
            border: 1px solid var(--neutral-gray-300);
        }

        .status-cancelled {
            background: var(--neutral-gray-100);
            color: var(--neutral-gray-500);
            border: 1px solid var(--neutral-gray-200);
        }

        /* === Technician avatars === */
        .tech-avatar {
            width: 30px;
            height: 30px;
            background: linear-gradient(135deg, var(--primary-orange), var(--accent-yellow-dark));
            border-radius: 50%;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.78rem;
            font-weight: 700;
        }

        .tech-avatar-sm {
            width: 24px;
            height: 24px;
            background: linear-gradient(135deg, var(--primary-orange), var(--accent-yellow-dark));
            border-radius: 50%;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.65rem;
            font-weight: 700;
        }

        /* === Date box === */
        .date-box {
            line-height: 1;
        }

        /* === Mobile maintenance cards === */
        .mobile-mcard {
            transition: transform 0.2s;
        }

        .mobile-mcard:active {
            transform: scale(0.98);
        }

        .mcard-strip {
            height: 3px;
        }

        .status-icon-bubble {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        /* === Empty state === */
        .empty-icon {
            width: 72px;
            height: 72px;
            margin: 0 auto;
            background: linear-gradient(135deg, var(--accent-yellow-light), var(--neutral-gray-100));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: var(--primary-orange-light);
        }

        /* === Tips card === */
        .tips-card {
            background: linear-gradient(135deg, var(--neutral-gray-50) 0%, var(--neutral-gray-100) 100%);
            border-left: 4px solid var(--primary-orange);
        }

        .tips-icon {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: var(--accent-yellow-light);
            color: var(--primary-orange-dark);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        /* === Quick-select modal buttons === */
        .quick-select-btn {
            transition: all 0.2s;
        }

        .quick-select-btn:hover,
        .quick-select-btn.active-quick {
            background: var(--primary-orange) !important;
            color: #fff !important;
            border-color: var(--primary-orange) !important;
        }

        /* === Animations === */
        .animate-wave {
            animation: wave 2.5s infinite;
            transform-origin: 70% 70%;
            display: inline-block;
        }

        @keyframes wave {
            0%, 60%, 100% {
                transform: rotate(0deg);
            }
            10% {
                transform: rotate(14deg);
            }
            20% {
                transform: rotate(-8deg);
            }
            30% {
                transform: rotate(14deg);
            }
            40% {
                transform: rotate(-4deg);
            }
            50% {
                transform: rotate(10deg);
            }
        }

        .animate-pulse {
            animation: pulseGlow 2s infinite;
        }

        @keyframes pulseGlow {
            0% {
                box-shadow: 0 0 0 0 rgba(234, 88, 12, 0.7);
            }
            70% {
                box-shadow: 0 0 0 5px rgba(234, 88, 12, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(234, 88, 12, 0);
            }
        }

        @keyframes blink {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.3;
            }
        }

        .animate-blink {
            animation: blink 1.5s infinite;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-8px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-animate {
            animation: slideDown 0.4s ease;
        }

        /* === Responsive adjustments === */
        @media (max-width: 768px) {
            .rhc-number {
                font-size: 2.8rem;
            }
            
            .bill-amount {
                font-size: 1.6rem;
            }
            
            .meter-value {
                font-size: 1.3rem;
            }
        }

        @media (max-width: 576px) {
            .rhc-number {
                font-size: 2.2rem;
            }
            
            .rhc-type-card {
                padding: 8px 12px;
                min-width: 80px;
            }
            
            .bill-amount {
                font-size: 1.4rem;
            }
            
            .meter-value {
                font-size: 1.1rem;
            }
        }
    </style>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Poll invoice version every 30s and reload when changed
                let currentVersion = @json($dashboardInvoiceVersion ?? null);
                if (currentVersion) {
                    setInterval(async () => {
                        if (document.hidden) return;
                        try {
                            const res = await fetch("{{ route('tenant.dashboard.invoiceVersion') }}?_=" +
                                Date.now(), {
                                    headers: {
                                        'X-Requested-With': 'XMLHttpRequest',
                                        Accept: 'application/json'
                                    },
                                    cache: 'no-store',
                                });
                            if (!res.ok) return;
                            const data = await res.json();
                            if (data?.version && data.version !== currentVersion) window.location.reload();
                        } catch (e) {
                            /* ignore */
                        }
                    }, 30000);
                }
            });
        </script>
    @endpush

@endsection
