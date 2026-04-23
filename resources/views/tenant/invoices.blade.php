@extends('tenant.layout')

@section('title', 'ประวัติชำระเงิน')

@section('content')
<div class="container-fluid px-0 px-md-3">
    <div class="d-flex justify-content-between align-items-center mb-4 px-3 px-md-0 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1">
                <i class="fa-solid fa-receipt me-2"></i>รายการใบแจ้งหนี้
            </h4>
            <p class="text-muted small mb-0">ประวัติการชำระเงินและยอดค้างชำระทั้งหมด</p>
        </div>
        <a href="{{ route('tenant.dashboard') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
            <i class="fa-solid fa-arrow-left me-1"></i> กลับหน้าหลัก
        </a>
    </div>

    {{-- Desktop Table --}}
    <div class="card border-0 shadow-sm d-none d-md-block overflow-hidden invoice-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr class="text-uppercase text-muted small">
                        <th class="ps-4 py-3">ประจำเดือน</th>
                        <th>เลขที่บิล</th>
                        <th>วันครบกำหนด</th>
                        <th class="text-end">ยอดรวม</th>
                        <th class="text-center">สถานะ</th>
                        <th class="text-center pe-4">ดำเนินการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $inv)
                        <tr class="invoice-row">
                            <td class="ps-4 fw-bold">
                                {{ \Carbon\Carbon::parse($inv->billing_month)->locale('th')->isoFormat('MMMM YYYY') }}
                            </td>
                            <td class="text-muted small">{{ $inv->invoice_number }}</td>
                            <td>
                                <span class="invoice-date-badge">
                                    {{ \Carbon\Carbon::parse($inv->due_date)->format('d/m/Y') }}
                                </span>
                            </td>
                            <td class="text-end fw-bold invoice-amount">
                                {{ number_format($inv->total_amount, 2) }} ฿
                            </td>
                            <td class="text-center">
                                @if($inv->status == 'ชำระแล้ว')
                                    <span class="status-badge status-paid">
                                        <i class="fa-solid fa-check-circle me-1"></i> ชำระแล้ว
                                    </span>
                                @elseif($inv->status == 'ค้างชำระ')
                                    <span class="status-badge status-unpaid">
                                        <i class="fa-solid fa-clock me-1"></i> ค้างชำระ
                                    </span>
                                @else
                                    <span class="status-badge status-pending">
                                        {{ $inv->status }}
                                    </span>
                                @endif
                            </td>
                            <td class="text-center pe-4">
                                @if($inv->status != 'ชำระแล้ว')
                                    <button class="btn btn-pay-action">
                                        <i class="fa-solid fa-qrcode me-1"></i> จ่ายเงิน
                                    </button>
                                @else
                                    <button class="btn btn-paid-action" disabled>
                                        <i class="fa-solid fa-check me-1"></i> เรียบร้อย
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="empty-state-icon mb-3">
                                    <i class="fa-regular fa-file-excel"></i>
                                </div>
                                <p class="text-muted mb-0">ยังไม่มีประวัติใบแจ้งหนี้</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Mobile Cards --}}
    <div class="d-md-none">
        @forelse($invoices as $inv)
            <div class="card mb-3 border-0 shadow-sm invoice-mobile-card">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="fw-bold invoice-month">
                            {{ \Carbon\Carbon::parse($inv->billing_month)->locale('th')->isoFormat('MMMM YYYY') }}
                        </div>
                        @if($inv->status == 'ชำระแล้ว')
                            <span class="status-badge status-paid">ชำระแล้ว</span>
                        @elseif($inv->status == 'ค้างชำระ')
                            <span class="status-badge status-unpaid">ค้างชำระ</span>
                        @else
                            <span class="status-badge status-pending">{{ $inv->status }}</span>
                        @endif
                    </div>

                    <div class="invoice-details-grid mb-3">
                        <div class="invoice-detail-item">
                            <span class="detail-label">เลขที่บิล</span>
                            <span class="detail-value">#{{ $inv->invoice_number }}</span>
                        </div>
                        <div class="invoice-detail-item text-end">
                            <span class="detail-label">ครบกำหนด</span>
                            <span class="detail-value due-date">{{ \Carbon\Carbon::parse($inv->due_date)->format('d/m/Y') }}</span>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-end pt-3 border-top">
                        <div>
                            <span class="detail-label d-block">ยอดชำระ</span>
                            <div class="invoice-total-amount">{{ number_format($inv->total_amount, 2) }} ฿</div>
                        </div>

                        @if($inv->status != 'ชำระแล้ว')
                            <button class="btn btn-pay-action-mobile">
                                <i class="fa-solid fa-qrcode me-1"></i> จ่ายบิล
                            </button>
                        @else
                            <button class="btn btn-paid-action-mobile">
                                ใบเสร็จ
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-5">
                <div class="empty-state-circle mb-3">
                    <i class="fa-regular fa-folder-open"></i>
                </div>
                <p class="text-muted mb-0">ไม่พบรายการบิล</p>
            </div>
        @endforelse
    </div>
</div>

<style>
    :root {
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

    /* === Page Header === */
    h4.fw-bold {
        color: var(--neutral-gray-800);
        font-size: 1.5rem;
    }

    /* === Invoice Card === */
    .invoice-card {
        border-radius: 12px;
        overflow: hidden;
    }

    .invoice-card thead tr {
        background: var(--neutral-gray-100);
        border-bottom: 2px solid var(--neutral-gray-200);
    }

    .invoice-card thead th {
        color: var(--neutral-gray-600);
        font-weight: 600;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
    }

    .invoice-row {
        border-bottom: 1px solid var(--neutral-gray-100);
        transition: background 0.2s;
    }

    .invoice-row:hover {
        background: var(--neutral-gray-50);
    }

    .invoice-row td {
        padding: 1rem 0.75rem;
        vertical-align: middle;
    }

    .invoice-row .fw-bold {
        color: var(--primary-orange-dark);
    }

    .invoice-amount {
        color: var(--neutral-gray-800) !important;
    }

    /* === Invoice Date Badge === */
    .invoice-date-badge {
        display: inline-block;
        padding: 4px 10px;
        background: var(--neutral-gray-100);
        border: 1px solid var(--neutral-gray-200);
        border-radius: 6px;
        font-size: 0.8rem;
        color: var(--neutral-gray-600);
        font-weight: 500;
    }

    /* === Status Badges === */
    .status-badge {
        display: inline-flex;
        align-items: center;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.78rem;
        font-weight: 600;
    }

    .status-paid {
        background: var(--neutral-gray-100);
        color: var(--neutral-gray-700);
        border: 1px solid var(--neutral-gray-300);
    }

    .status-unpaid {
        background: var(--accent-yellow-light);
        color: var(--neutral-gray-800);
        border: 1px solid var(--accent-yellow);
    }

    .status-pending {
        background: var(--neutral-gray-100);
        color: var(--neutral-gray-600);
        border: 1px solid var(--neutral-gray-200);
    }

    /* === Action Buttons === */
    .btn-pay-action {
        display: inline-flex;
        align-items: center;
        background: linear-gradient(135deg, var(--primary-orange), var(--primary-orange-dark));
        color: #fff;
        border: none;
        border-radius: 20px;
        padding: 6px 16px;
        font-size: 0.82rem;
        font-weight: 600;
        transition: all 0.2s;
        box-shadow: 0 2px 8px rgba(234, 88, 12, 0.25);
    }

    .btn-pay-action:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(234, 88, 12, 0.35);
        color: #fff;
    }

    .btn-paid-action {
        display: inline-flex;
        align-items: center;
        background: var(--neutral-gray-100);
        color: var(--neutral-gray-600);
        border: 1px solid var(--neutral-gray-200);
        border-radius: 20px;
        padding: 6px 16px;
        font-size: 0.82rem;
        font-weight: 500;
        transition: all 0.2s;
    }

    .btn-paid-action:hover {
        background: var(--neutral-gray-200);
        color: var(--neutral-gray-700);
    }

    /* === Mobile Invoice Cards === */
    .invoice-mobile-card {
        border-radius: 12px;
        transition: transform 0.2s;
    }

    .invoice-mobile-card:active {
        transform: scale(0.98);
    }

    .invoice-month {
        color: var(--primary-orange-dark);
        font-size: 1.05rem;
    }

    .invoice-details-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }

    .invoice-detail-item {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .detail-label {
        font-size: 0.7rem;
        color: var(--neutral-gray-500);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 500;
    }

    .detail-value {
        font-size: 0.85rem;
        color: var(--neutral-gray-700);
        font-weight: 500;
    }

    .detail-value.due-date {
        color: var(--primary-orange-dark);
    }

    .invoice-total-amount {
        font-size: 1.4rem;
        font-weight: 800;
        color: var(--neutral-gray-800);
        line-height: 1;
    }

    .btn-pay-action-mobile {
        display: inline-flex;
        align-items: center;
        background: linear-gradient(135deg, var(--primary-orange), var(--primary-orange-dark));
        color: #fff;
        border: none;
        border-radius: 20px;
        padding: 8px 18px;
        font-size: 0.85rem;
        font-weight: 600;
        transition: all 0.2s;
        box-shadow: 0 2px 8px rgba(234, 88, 12, 0.25);
    }

    .btn-pay-action-mobile:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(234, 88, 12, 0.35);
        color: #fff;
    }

    .btn-paid-action-mobile {
        display: inline-flex;
        align-items: center;
        background: var(--neutral-gray-100);
        color: var(--neutral-gray-600);
        border: 1px solid var(--neutral-gray-200);
        border-radius: 20px;
        padding: 8px 18px;
        font-size: 0.85rem;
        font-weight: 600;
        transition: all 0.2s;
    }

    .btn-paid-action-mobile:hover {
        background: var(--neutral-gray-200);
        color: var(--neutral-gray-700);
    }

    /* === Empty State === */
    .empty-state-icon {
        width: 80px;
        height: 80px;
        margin: 0 auto;
        background: var(--neutral-gray-100);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        color: var(--neutral-gray-400);
    }

    .empty-state-circle {
        width: 80px;
        height: 80px;
        margin: 0 auto;
        background: linear-gradient(135deg, var(--accent-yellow-light), var(--neutral-gray-100));
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        color: var(--primary-orange-light);
    }

    /* === Responsive Adjustments === */
    @media (max-width: 768px) {
        .invoice-amount {
            font-size: 0.95rem;
        }
        
        .invoice-total-amount {
            font-size: 1.2rem;
        }
    }

    @media (max-width: 576px) {
        h4.fw-bold {
            font-size: 1.25rem;
        }
        
        .invoice-month {
            font-size: 0.95rem;
        }
        
        .invoice-total-amount {
            font-size: 1.1rem;
        }
        
        .btn-pay-action-mobile,
        .btn-paid-action-mobile {
            padding: 6px 14px;
            font-size: 0.8rem;
        }
    }
</style>
@endsection
