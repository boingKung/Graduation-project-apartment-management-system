<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แจ้งชำระเงิน (แนบสลิป)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f4f7f6; }
    </style>
</head>
<body>
    <div class="container-xl py-4">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                
                {{-- ส่วนหัวโปรไฟล์ --}}
                <div class="text-center mb-4">
                    <img src="{{ $tenant->line_avatar ?? 'https://via.placeholder.com/90' }}" class="rounded-circle border border-3 border-success shadow-sm mb-2" width="80" height="80">
                    <h5 class="fw-bold">ห้อง {{ $tenant->room->room_number ?? '-' }}</h5>
                    <p class="text-muted small">คุณ {{ $tenant->first_name }} {{ $tenant->last_name }}</p>
                </div>

                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-header bg-success bg-gradient text-white border-0 p-3 rounded-top-4 text-center">
                        <h5 class="fw-bold mb-0"><i class="bi bi-wallet2 me-2"></i>ฟอร์มแจ้งชำระเงิน</h5>
                    </div>

                    <div class="card-body p-4 bg-light bg-opacity-25">
                        
                        @if($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                                </ul>
                            </div>
                        @endif

                        @if(!$invoice)
                            {{-- กรณีไม่มีบิลค้างชำระ --}}
                            <div class="text-center py-5">
                                <i class="bi bi-emoji-smile fs-1 text-success d-block mb-3"></i>
                                <h5 class="fw-bold text-dark">ยอดเยี่ยม!</h5>
                                <p class="text-muted">คุณไม่มีบิลค้างชำระในขณะนี้ครับ</p>
                            </div>
                        @else
                            {{-- กรณีมีบิลค้างชำระ --}}
                            <div class="alert alert-warning border-warning shadow-sm mb-4">
                                <h6 class="fw-bold mb-1"><i class="bi bi-info-circle-fill me-1"></i> ยอดที่ต้องชำระ: 
                                    <span class="text-danger fs-4">{{ number_format($invoice->remaining_balance, 2) }}</span> บาท
                                </h6>
                                <div class="small text-muted">รอบบิลเดือน: {{ $invoice->thai_billing_month }}</div>
                                <div class="small text-muted">กำหนดชำระ: {{ $invoice->thai_due_date }}</div>
                            </div>

                            <form action="{{ route('line.payment.save') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="tenant_id" value="{{ $tenant->id }}">
                                <input type="hidden" name="invoice_id" value="{{ $invoice->id }}">

                                <div class="row g-3 mb-4">
                                    <div class="col-12">
                                        <label class="form-label fw-bold">ยอดเงินที่โอน (บาท) <span class="text-danger">*</span></label>
                                        <input type="number" name="amount_paid" class="form-control fw-bold text-success fs-5" value="" step="0.01" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">วันที่โอนเงิน <span class="text-danger">*</span></label>
                                        <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">ช่องทางชำระเงิน <span class="text-danger">*</span></label>
                                        <select name="payment_method" class="form-select" required>
                                            <option value="โอนผ่านธนาคาร">โอนผ่านธนาคาร</option>
                                        </select>
                                    </div>
                                    
                                    {{-- ส่วนอัปโหลดและแสดงรูปสลิป --}}
                                    <div class="col-12">
                                        <label class="form-label fw-bold">แนบรูปสลิปธนาคาร <span class="text-danger">*</span></label>
                                        <input type="file" name="slip_image" id="slipInput" class="form-control" accept="image/*" onchange="previewSlip(event)" required>
                                        
                                        <div class="mt-3 text-center bg-white border rounded shadow-sm p-3" style="min-height: 150px;">
                                            <div id="slipPreviewContainer" class="d-flex flex-column align-items-center justify-content-center h-100 text-muted">
                                                <i id="slipIcon" class="bi bi-image fs-1 d-block mb-2 text-black-50"></i>
                                                <img id="slipImage" class="img-fluid rounded border d-none" style="max-height: 250px; object-fit: contain;">
                                                <span id="slipText" class="small fw-bold">อัปโหลดสลิปเพื่อดูตัวอย่าง</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-success w-100 rounded-3 py-3 shadow-sm fw-bold fs-5">
                                    <i class="bi bi-send-check-fill me-1"></i> ส่งหลักฐานการชำระเงิน
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function previewSlip(event) {
            const reader = new FileReader();
            const imgElement = document.getElementById('slipImage');
            const iconElement = document.getElementById('slipIcon');
            const textElement = document.getElementById('slipText');

            if(iconElement) iconElement.classList.add('d-none');
            if(textElement) textElement.classList.add('d-none');

            reader.onload = function() {
                imgElement.src = reader.result;
                imgElement.classList.remove('d-none');
            }
            if(event.target.files[0]) {
                reader.readAsDataURL(event.target.files[0]);
            }
        }
    </script>
</body>
</html>