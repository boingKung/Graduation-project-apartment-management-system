<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>success</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="min-height: 100vh;">

    <div class="card shadow border-0 m-3 text-center" style="max-width: 400px; width: 100%; border-radius: 1rem;">
        <div class="card-body p-5">
            <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
            <h4 class="fw-bold mt-3 mb-2">สำเร็จ!</h4>
            <p class="text-muted">{{ $message ?? 'ผูกบัญชีเรียบร้อยแล้ว' }}</p>
            <button class="btn btn-outline-secondary w-100 mt-3 rounded-pill">
                กรุณาปิดหน้านี้
            </button>
        </div>
    </div>
    
    <script src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>
</body>
</html>