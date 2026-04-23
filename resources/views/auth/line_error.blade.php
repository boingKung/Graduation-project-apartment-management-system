<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เกิดข้อผิดพลาด - ระบบจัดการหอพัก</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        body {
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Sarabun', sans-serif; /* แนะนำให้ใช้ฟอนต์ภาษาไทยถ้ามี */
        }
        
        .error-card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }
        
        .icon-circle {
            width: 80px;
            height: 80px;
            background-color: #f8d7da; /* สีแดงอ่อนอิงจาก Bootstrap danger */
            color: #dc3545; /* สีแดงเข้ม */
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            font-size: 2.5rem;
        }

        .btn-line {
            background-color: #00B900;
            color: white;
            font-weight: 500;
        }
        
        .btn-line:hover {
            background-color: #009900;
            color: white;
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-6 col-xl-5">
                <div class="card error-card text-center p-4 p-md-5">
                    <div class="card-body">
                        
                        <div class="icon-circle mb-4 shadow-sm">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                        </div>
                        
                        <h3 class="fw-bold text-dark mb-3">ขออภัย เกิดข้อผิดพลาด</h3>
                        
                        <div class="alert alert-danger bg-opacity-10 border-danger border-opacity-25 rounded-3 mb-4">
                            <p class="mb-0 fs-6">
                                {{ $message ?? 'ไม่สามารถดำเนินการได้ในขณะนี้ กรุณาลองใหม่อีกครั้ง หรือติดต่อผู้ดูแลระบบ' }}
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-4 text-muted small">
                    &copy; {{ date('Y') }} ระบบจัดการหอพัก
                </div>
            </div>
        </div>
    </div>

    <script src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>
    <script>
        function closeLiffOrWindow() {
            // ตรวจสอบว่ากำลังเปิดอยู่ใน LINE LIFF หรือไม่
            if (typeof liff !== 'undefined') {
                liff.init({ liffId: "{{ env('LINE_LIFF_ID', '') }}" }) // ใส่ LIFF ID ถ้ามี
                    .then(() => {
                        if (liff.isInClient()) {
                            liff.closeWindow(); // สั่งปิดหน้าต่าง LIFF
                        } else {
                            window.close(); // ถ้าเปิดในเบราว์เซอร์ปกติ ลองใช้ window.close()
                        }
                    })
                    .catch((err) => {
                        console.error('LIFF Initialization failed', err);
                        window.close();
                    });
            } else {
                window.close();
            }
            
            // Fallback เผื่อ window.close() โดนเบราว์เซอร์บล็อก
            setTimeout(() => {
                window.location.href = "{{ url('/') }}";
            }, 500);
        }
    </script>
</body>
</html>