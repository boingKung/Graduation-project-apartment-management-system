@extends('tenant.layout')

@section('title', 'บิลค่าเช่า')

@section('content')

    <div class="container-xl px-2 px-md-4 py-3">

        {{-- Header --}}
        <div class="mb-4">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span
                    class="badge bg-warning bg-opacity-10 text-warning border border-warning-subtle rounded-pill px-3 py-2 fw-semibold small">
                    <i class="fa-solid fa-file-invoice me-1"></i> ใบแจ้งหนี้
                </span>
            </div>
            <h2 class="fw-bolder mb-3 display-6">บิลค่าเช่า</h2>
            <p class="text-muted mb-0">
                <i class="fa-solid fa-info-circle me-2"></i>
                ดูรายละเอียดใบแจ้งหนี้ของคุณ
            </p>
        </div>

        {{-- Summary Cards --}}
        <div class="row g-3 mb-4">
            @php
                $totalInvoices = $invoices->count();
                $unpaidInvoices = $invoices->whereIn('status', $unpaidStatuses)->count();
                $paidInvoices = $invoices->where('status', 'ชำระแล้ว')->count();
            @endphp

            <div class="col-6 col-md-3">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <div class="text-muted small">ใบแจ้งหนี้ทั้งหมด</div>
                        <div class="display-6 fw-bold text-primary">{{ $totalInvoices }}</div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <div class="text-muted small">รอชำระ</div>
                        <div class="display-6 fw-bold text-danger">{{ $unpaidInvoices }}</div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <div class="text-muted small">ชำระแล้ว</div>
                        <div class="display-6 fw-bold text-success">{{ $paidInvoices }}</div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <div class="text-muted small">ห้องของคุณ</div>
                        <div class="display-6 fw-bold text-info">{{ $tenant->room->room_number ?? '-' }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Invoices List --}}
        @if ($invoices->isEmpty())
            <div class="card shadow-sm border-0 p-5 text-center">
                <div class="mb-3">
                    <i class="fa-solid fa-inbox text-secondary" style="font-size: 3rem; opacity: 0.5;"></i>
                </div>
                <h5 class="text-secondary">ไม่มีใบแจ้งหนี้</h5>
                <p class="text-muted mb-0">คุณยังไม่มีใบแจ้งหนี้ที่บันทึก</p>
            </div>
        @else
            <div class="row g-3">
                @foreach ($invoices as $invoice)
                    @php
                        $isPaid = $invoice->status === 'ชำระแล้ว';
                        $badgeLabel = $statusLabels[$invoice->status] ?? $invoice->status;
                        $badgeClass = match ($invoice->status) {
                            'ชำระแล้ว' => 'bg-success',
                            'ชำระบางส่วน' => 'bg-info',
                            default => 'bg-warning text-dark',
                        };
                        $iconClass = $isPaid ? 'fa-check-circle text-success' : 'fa-clock text-warning';
                        $bgClass = $isPaid ? 'bg-success bg-opacity-10' : 'bg-warning bg-opacity-10';
                    @endphp
                    <div class="col-12">
                        <div class="card shadow-sm border-0 overflow-hidden hover-lift">
                            <div class="row g-0">
                                <div class="col-auto p-3 {{ $bgClass }}">
                                    <div class="d-flex align-items-center justify-content-center"
                                        style="width: 60px; height: 60px;">
                                        <i class="fa-solid {{ $iconClass }}" style="font-size: 2rem;"></i>
                                    </div>
                                </div>
                                @php
                                    $billMonth = \Carbon\Carbon::parse($invoice->billing_month)->locale('th');
                                    $dueDate = \Carbon\Carbon::parse($invoice->due_date)->locale('th');
                                @endphp

                                <div class="col p-3">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h6 class="mb-1 fw-bold">
                                                {{ $billMonth->isoFormat('MMMM') }} {{ $billMonth->year + 543 }}
                                            </h6>

                                            <p class="text-muted small mb-0">
                                                <i class="fa-regular fa-calendar me-1"></i>
                                                วันครบกำหนด:
                                                {{ $dueDate->isoFormat('D MMMM') }} {{ $dueDate->year + 543 }}
                                            </p>
                                        </div>

                                        <div class="text-end">
                                            <div class="text-primary fw-bold" style="font-size: 1.2rem;">
                                                ฿{{ number_format($invoice->total_amount, 2) }}
                                            </div>

                                            @if (!$isPaid && $invoice->remaining_balance > 0 && $invoice->remaining_balance != $invoice->total_amount)
                                                <div class="text-muted small">
                                                    คงเหลือ ฿{{ number_format($invoice->remaining_balance, 2) }}
                                                </div>
                                            @endif

                                            <span class="badge {{ $badgeClass }} mt-1">
                                                {{ $badgeLabel }}
                                            </span>
                                        </div>
                                    </div>

                                    {{-- <p class="text-muted small mb-0 pb-2">
                                        <i class="fa-solid fa-door-closed me-1"></i>
                                        ห้อง {{ $tenant->room->number ?? '-' }}
                                    </p> --}}
                                    <div class="mt-2">
                                        <button class="btn btn-sm btn-outline-info"
                                            onclick="viewPaymentDetail({{ $invoice->id }})">
                                            <i class="fa-solid fa-eye me-1"></i> ดูรายละเอียดการชำระ
                                        </button>
                                    </div>
                                    <div class="mt-2">
                                        {{-- 🌟 เพิ่มปุ่มใหม่ (แสดงเฉพาะถ้าชำระแล้ว หรือตามที่คุณต้องการ) --}}
                                        <a href="{{ route('tenant.invoices.print', $invoice->id) }}" target="_blank"
                                            class="btn btn-sm btn-outline-danger">
                                            <i class="fa-solid fa-file-pdf me-1"></i> พิมพ์ใบเสร็จ (PDF)
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            {{-- 🌟 เพิ่มปุ่มเปลี่ยนหน้าตรงนี้ --}}
            <div class="d-flex justify-content-center mt-4">
                {{ $invoices->links('pagination::bootstrap-5') }}
            </div>
        @endif

    </div>

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

    @push('scripts')
        <script>
            function viewPaymentDetail(invoiceId) {
                // เรียก API endpoint เพื่อดึงข้อมูลการชำระ
                fetch(`/tenant/invoices/${invoiceId}/payment-detail`)
                    .then(res => res.json())
                    .then(data => {
                        showPaymentDetailModal(data);
                    })
                    .catch(err => {
                        Swal.fire('ข้อผิดพลาด', 'ไม่สามารถโหลดข้อมูลได้', 'error');
                    });
            }

            function showPaymentDetailModal(data) {
                const invoice = data.invoice;
                const breakdown = data.breakdown;
                const payments = data.payments;
                console.log('ข้อมูลการชำระเงิน:', payments);
                let breakdownHtml = `
            <div class="table-responsive mt-3">
                <table class="table table-borderless table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>รายการ</th>
                            <th class="text-end">ยอดเต็ม</th>
                            <th class="text-end">ชำระแล้ว</th>
                            <th class="text-end">คงเหลือ</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

                breakdown.forEach(item => {
                    breakdownHtml += `
                <tr>
                    <td>${item.name}</td>
                    <td class="text-end">฿${item.subtotal.toLocaleString('th-TH', {minimumFractionDigits: 2})}</td>
                    <td class="text-end text-success">฿${item.paid.toLocaleString('th-TH', {minimumFractionDigits: 2})}</td>
                    <td class="text-end text-danger">฿${item.remaining.toLocaleString('th-TH', {minimumFractionDigits: 2})}</td>
                </tr>
            `;
                });

                breakdownHtml += `
                    <tr class="table-light fw-bold">
                        <td>รวมทั้งสิ้น</td>
                        <td class="text-end">฿${invoice.total_amount.toLocaleString('th-TH', {minimumFractionDigits: 2})}</td>
                        <td class="text-end text-success">฿${invoice.total_paid.toLocaleString('th-TH', {minimumFractionDigits: 2})}</td>
                        <td class="text-end text-danger">฿${invoice.remaining_balance.toLocaleString('th-TH', {minimumFractionDigits: 2})}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        `;

                let paymentHistoryHtml = '';
                if (payments.length > 0) {
                    paymentHistoryHtml = `
                <div class="mt-4 pt-3 border-top bg-light p-3 rounded-3">
                    <h6 class="fw-bold mb-3 text-secondary"><i class="fa-solid fa-clock-rotate-left me-2"></i>ประวัติการโอนเงิน (บิลนี้)</h6>
                    <div class="timeline">
            `;
                    payments.forEach((payment, idx) => {
                        // 🌟 สร้าง HTML ปุ่มดูสลิป (ถ้ามีข้อมูลสลิปส่งมา)
                        let slipButtonHtml = payment.slip_url ?
                            `<a href="${payment.slip_url}" target="_blank" class="btn btn-sm btn-outline-primary mt-2 shadow-sm" style="font-size:0.75rem;">
                                 <i class="fa-solid fa-image me-1"></i> ดูหลักฐานการโอน
                               </a>` :
                            '';

                        paymentHistoryHtml += `
                    <div class="d-flex mb-3 pb-3 border-bottom border-secondary-subtle">
                        <div class="flex-shrink-0">
                            <div class="badge rounded-pill bg-success shadow-sm" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                                <i class="fa-solid fa-check text-white"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <p class="mb-0 d-flex justify-content-between align-items-center">
                                <strong class="text-success fs-6">฿${payment.amount.toLocaleString('th-TH', {minimumFractionDigits: 2})}</strong>
                                <span class="text-muted small"><i class="fa-regular fa-calendar me-1"></i>${payment.date}</span>
                            </p>
                            <small class="text-muted d-block mt-1"><i class="fa-solid fa-building-columns me-1"></i> ชำระผ่าน: ${payment.method}</small>
                            ${slipButtonHtml} </div>
                    </div>
                `;
                    });
                    paymentHistoryHtml += '</div></div>';
                } else {
                    // กรณีบิลนี้ยังไม่มีการชำระเงินเลยสักครั้ง
                    paymentHistoryHtml = `
                        <div class="mt-4 pt-3 border-top text-center text-muted">
                            <i class="fa-solid fa-comment-slash fs-2 mb-2 opacity-50"></i>
                            <p class="small mb-0">ยังไม่มีประวัติการชำระเงินสำหรับบิลนี้</p>
                        </div>
                    `;
                }

                const modalHtml = `
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title">
                        <i class="fa-solid fa-receipt me-2 text-primary"></i>
                        รายละเอียดการชำระ
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                    <div class="row mb-3">
                        <div class="col-6">
                            <div class="text-muted small">เลขที่ใบแจ้งหนี้</div>
                            <div class="fw-bold">${invoice.invoice_number}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted small">รอบการใช้</div>
                            <div class="fw-bold">${invoice.billing_month}</div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-6">
                            <div class="text-muted small">วันครบกำหนด</div>
                            <div class="fw-bold">${invoice.due_date}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted small">สถานะ</div>
                            <span class="badge bg-info">${invoice.status_label}</span>
                        </div>
                    </div>

                    <hr>

                    <h6 class="fw-bold mb-3">รายระเอียดการชำระแยกตามรายการ</h6>
                    ${breakdownHtml}

                    ${paymentHistoryHtml}
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                </div>
            </div>
        `;

                // แสดง Modal
                let detailModal = document.getElementById('paymentDetailModal');
                if (!detailModal) {
                    detailModal = document.createElement('div');
                    detailModal.id = 'paymentDetailModal';
                    detailModal.className = 'modal fade';
                    detailModal.setAttribute('tabindex', '-1');
                    detailModal.innerHTML = `<div class="modal-dialog modal-lg">${modalHtml}</div>`;
                    document.body.appendChild(detailModal);
                } else {
                    detailModal.querySelector('.modal-dialog').innerHTML = modalHtml;
                }

                const modal = new bootstrap.Modal(detailModal);
                modal.show();
            }
        </script>
    @endpush

@endsection
