@extends('auth.layout')

@section('title', 'ลงทะเบียนพนักงาน')

@section('content')
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-6">
            <div class="card border-0 shadow-lg" style="border-radius: 20px;">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <h3 class="fw-bold text-primary">ลงทะเบียนสมาชิกใหม่</h3>
                        <p class="text-muted small">กรุณากรอกข้อมูลให้ครบถ้วนเพื่อเข้าใช้งานระบบ</p>
                    </div>

                    <form action="{{ route('admin.register') }}" method="POST">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="firstname" class="form-label text-secondary small">ชื่อจริง</label>
                                <input type="text" name="firstname" id="firstname" autocomplete="off"
                                       class="form-control @error('firstname') is-invalid @enderror" 
                                       placeholder="ชื่อ" value="{{ old('firstname') }}" required>
                                @error('firstname')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="lastname" class="form-label text-secondary small">นามสกุล</label>
                                <input type="text" name="lastname" id="lastname" autocomplete="off"
                                       class="form-control @error('lastname') is-invalid @enderror" 
                                       placeholder="นามสกุล" value="{{ old('lastname') }}" required>
                                @error('lastname')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="username" class="form-label text-secondary small">ชื่อผู้ใช้งาน (Username)</label>
                            <div class="input-group">
                                <input type="text" name="username" id="username" autocomplete="off"
                                       class="form-control border-start-0 @error('username') is-invalid @enderror" 
                                       placeholder="ภาษาอังกฤษหรือตัวเลข" value="{{ old('username') }}" required>
                            </div>
                            @error('username')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label text-secondary small">รหัสผ่าน</label>
                            <div class="password-input-wrapper">
                                <input type="password" name="password" id="password" autocomplete="off"
                                       class="form-control @error('password') is-invalid @enderror" 
                                       placeholder="รหัสผ่านอย่างน้อย 6 ตัวอักษร" required style="padding-right: 45px;">
                                <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('password')" data-toggle="password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            @error('password')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="password_confirmation" class="form-label text-secondary small">ยืนยันรหัสผ่านอีกครั้ง</label>
                            <div class="password-input-wrapper">
                                <input type="password" name="password_confirmation" id="password_confirmation" autocomplete="off"
                                       class="form-control" placeholder="ยืนยันรหัสผ่าน" required style="padding-right: 45px;">
                                <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('password_confirmation')" data-toggle="password_confirmation">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg shadow-sm py-3" 
                                    style="border-radius: 12px; font-weight: 600; font-size: 1rem;">
                                สร้างบัญชีผู้ใช้งาน
                            </button>
                        </div>
                    </form>

                    <div class="text-center mt-4">
                        <p class="small text-secondary">มีบัญชีอยู่แล้ว? <a href="{{ route('admin.loginForm') }}" class="text-primary fw-bold text-decoration-none">เข้าสู่ระบบที่นี่</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* ปรับแต่งสไตล์เพิ่มเติมให้ดูทันสมัย */
    body {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
    }
    .form-control {
        padding: 0.75rem 1rem;
        border-radius: 10px;
        border: 1px solid #e0e0e0;
        background-color: #f9f9f9;
        transition: all 0.2s ease-in-out;
    }
    .form-control:focus {
        background-color: #fff;
        border-color: #667eea;
        box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.1);
    }
    .input-group-text {
        border-radius: 10px 0 0 10px;
        border: 1px solid #e0e0e0;
    }
</style>
@endsection