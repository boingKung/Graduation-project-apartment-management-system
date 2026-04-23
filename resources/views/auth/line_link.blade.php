<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ผูกบัญชี LINE กับระบบอพาร์ทเม้นท์</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="min-height: 100vh;">

    <div class="card shadow border-0 m-3" style="max-width: 400px; width: 100%; border-radius: 1rem;">
        <div class="card-body p-4 text-center">
            
            {{-- รูปโปรไฟล์ไลน์ --}}
            <img src="{{ session('temp_line_avatar') ?? 'https://via.placeholder.com/90' }}" 
                 class="rounded-circle mb-3 border border-3 border-success shadow-sm" width="90" height="90">
            
            <h5 class="fw-bold mb-1">สวัสดีคุณ {{ session('temp_line_name') }}</h5>
            <p class="text-muted small mb-4">กรุณายืนยันตัวตนเพื่อเชื่อมต่อ LINE ของคุณเข้ากับข้อมูลห้องพัก</p>

            @if(session('error'))
                <div class="alert alert-danger small py-2 fw-semibold"><i class="bi bi-exclamation-triangle me-1"></i>{{ session('error') }}</div>
            @endif

            <form action="{{ route('line.link.save') }}" method="POST">
                @csrf
                {{-- เพิ่ม 2 บรรทัดนี้ (เป็นช่องที่มองไม่เห็น แต่เก็บข้อมูล LINE ID ไว้) --}}
                <input type="hidden" name="line_id" value="{{ $lineId }}">
                <input type="hidden" name="line_avatar" value="{{ $lineAvatar }}">
                <div class="mb-3 text-start">
                    <label class="form-label fw-bold small text-secondary">เลขประจำตัวประชาชน (13 หลัก)</label>
                    <input type="text" name="id_card" class="form-control form-control-lg bg-light" placeholder="" maxlength="13" required>
                </div>
                <div class="mb-4 text-start">
                    <label class="form-label fw-bold small text-secondary">เบอร์โทรศัพท์ที่ลงทะเบียนไว้</label>
                    <input type="tel" name="phone" class="form-control form-control-lg bg-light" placeholder="" maxlength="10" required>
                </div>
                
                <button type="submit" class="btn text-white w-100 fw-bold py-2 fs-5" style="background-color: #06C755; border-radius: 10px;">
                    <i class="bi bi-link-45deg me-1"></i> ยืนยันการผูกบัญชี
                </button>
            </form>
            
        </div>
    </div>

</body>
</html>