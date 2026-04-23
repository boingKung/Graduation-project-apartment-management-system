@extends('auth.layout')

@section('title', 'เข้าสู่ระบบแอดมิน')

@section('content')
<style>
    /* ======== ADMIN LOGIN THEME (Orange-Gray-Yellow) ======== */
    body {
        background: #f5f5f4 !important;
        overflow: hidden;
    }

    /* Animated gradient background - Orange/Gray/Yellow tones */
    .admin-bg {
        position: fixed;
        inset: 0;
        background: linear-gradient(135deg, #fafaf9 0%, #f5f5f4 25%, #fef3c7 50%, #fed7aa 75%, #f9fafb 100%);
        background-size: 400% 400%;
        animation: bgShift 15s ease infinite;
        z-index: 0;
    }

    @keyframes bgShift {
        0%   { background-position: 0% 50%; }
        50%  { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }

    /* Floating orbs - Orange, Gray, Yellow */
    .orb {
        position: fixed;
        border-radius: 50%;
        filter: blur(80px);
        opacity: 0.25;
        animation: floatOrb linear infinite;
        z-index: 0;
        pointer-events: none;
    }
    .orb-1 { width: 450px; height: 450px; background: #f97316; top: -120px; left: -120px; animation-duration: 22s; }
    .orb-2 { width: 350px; height: 350px; background: #fbbf24; bottom: -100px; right: -100px; animation-duration: 26s; animation-delay: -8s; }
    .orb-3 { width: 280px; height: 280px; background: #78716c; top: 45%; left: 55%; animation-duration: 20s; animation-delay: -5s; }

    @keyframes floatOrb {
        0%   { transform: translate(0, 0) scale(1); }
        33%  { transform: translate(30px, -40px) scale(1.05); }
        66%  { transform: translate(-20px, 30px) scale(0.95); }
        100% { transform: translate(0, 0) scale(1); }
    }

    /* Stars canvas */
    #stars-canvas {
        position: fixed;
        inset: 0;
        z-index: 0;
        pointer-events: none;
    }

    /* Card */
    .login-wrapper {
        position: relative;
        z-index: 10;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .admin-card {
        width: 100%;
        max-width: 440px;
        background: rgba(255, 255, 255, 0.92);
        backdrop-filter: blur(24px);
        -webkit-backdrop-filter: blur(24px);
        border: 1px solid rgba(255, 255, 255, 0.6);
        border-radius: 28px;
        padding: 48px 42px;
        box-shadow: 0 20px 60px rgba(249, 115, 22, 0.08), 0 8px 30px rgba(0,0,0,0.04);
        animation: cardEntrance 0.7s cubic-bezier(0.22, 1, 0.36, 1) both;
    }

    @keyframes cardEntrance {
        from { opacity: 0; transform: translateY(40px) scale(0.96); }
        to   { opacity: 1; transform: translateY(0) scale(1); }
    }

    /* Logo / Icon badge - Orange gradient */
    .admin-icon-wrap {
        width: 76px;
        height: 76px;
        background: linear-gradient(135deg, #ea580c, #f97316, #f59e0b);
        border-radius: 22px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 22px;
        box-shadow: 0 10px 35px rgba(249, 115, 22, 0.35);
        animation: iconPop 0.6s 0.3s cubic-bezier(0.34, 1.56, 0.64, 1) both;
        position: relative;
    }

    .admin-icon-wrap::before {
        content: '';
        position: absolute;
        inset: -10px;
        border-radius: 32px;
        border: 2px solid rgba(249, 115, 22, 0.18);
        animation: pulseRing 2.8s ease-out infinite;
    }

    @keyframes pulseRing {
        0%   { transform: scale(1); opacity: 0.9; }
        100% { transform: scale(1.35); opacity: 0; }
    }

    @keyframes iconPop {
        from { opacity: 0; transform: scale(0.5) rotate(-15deg); }
        to   { opacity: 1; transform: scale(1) rotate(0deg); }
    }

    .admin-icon-wrap i { font-size: 34px; color: #fff; }

    /* Title - Orange/Gray gradient */
    .admin-title {
        font-size: 1.55rem;
        font-weight: 700;
        background: linear-gradient(90deg, #c2410c, #d97706);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        letter-spacing: 0.5px;
    }
    .admin-subtitle { color: #78716c; font-size: 0.85rem; }

    /* Divider */
    .divider-line {
        height: 1px;
        background: linear-gradient(90deg, transparent, rgba(249, 115, 22, 0.2), transparent);
        margin: 22px 0;
    }

    /* Input group */
    .admin-input-group { margin-bottom: 20px; }

    .admin-input-group label {
        display: block;
        color: #44403c;
        font-size: 0.82rem;
        font-weight: 600;
        letter-spacing: 0.3px;
        margin-bottom: 9px;
    }

    .admin-input-wrap {
        position: relative;
        display: flex;
        align-items: center;
    }

    .admin-input-wrap .input-icon {
        position: absolute;
        left: 15px;
        color: #a8a29e;
        font-size: 0.95rem;
        transition: color 0.3s;
        pointer-events: none;
    }

    .admin-input-wrap input {
        width: 100%;
        background: #fafaf9;
        border: 1.5px solid #e7e5e4;
        border-radius: 14px;
        color: #1c1917;
        font-family: 'Prompt', sans-serif;
        font-size: 0.92rem;
        padding: 14px 46px;
        outline: none;
        transition: all 0.3s ease;
    }

    .admin-input-wrap input::placeholder { color: #a8a29e; }

    .admin-input-wrap input:focus {
        border-color: #f97316;
        background: #fff;
        box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.12);
    }

    .admin-input-wrap input:focus + .input-icon { color: #ea580c; }

    .admin-input-wrap .pw-toggle {
        position: absolute;
        right: 13px;
        background: none;
        border: none;
        color: #a8a29e;
        cursor: pointer;
        padding: 4px 6px;
        font-size: 0.95rem;
        transition: color 0.2s;
        z-index: 2;
    }
    .admin-input-wrap .pw-toggle:hover { color: #ea580c; }

    /* Is-invalid styling */
    .admin-input-wrap input.is-invalid { border-color: #ef4444 !important; }
    .admin-error { color: #dc2626; font-size: 0.8rem; margin-top: 6px; display: flex; align-items: center; gap: 5px; font-weight: 500; }

    /* Submit button - Orange/Yellow gradient */
    .btn-admin-login {
        width: 100%;
        padding: 15px;
        border: none;
        border-radius: 14px;
        background: linear-gradient(135deg, #ea580c 0%, #f97316 50%, #f59e0b 100%);
        background-size: 200% auto;
        color: #fff;
        font-family: 'Prompt', sans-serif;
        font-size: 1rem;
        font-weight: 600;
        letter-spacing: 0.5px;
        cursor: pointer;
        position: relative;
        overflow: hidden;
        transition: all 0.35s ease;
        box-shadow: 0 8px 28px rgba(249, 115, 22, 0.35);
        margin-top: 10px;
    }

    .btn-admin-login:hover {
        background-position: right center;
        transform: translateY(-2px);
        box-shadow: 0 12px 38px rgba(249, 115, 22, 0.45);
    }

    .btn-admin-login:active { transform: translateY(0); }

    /* Ripple effect */
    .btn-admin-login .ripple {
        position: absolute;
        border-radius: 50%;
        background: rgba(255,255,255,0.35);
        transform: scale(0);
        animation: rippleAnim 0.6s linear;
        pointer-events: none;
    }
    @keyframes rippleAnim {
        to { transform: scale(4); opacity: 0; }
    }

    /* Loading state */
    .btn-admin-login .btn-text { transition: opacity 0.2s; }
    .btn-admin-login .btn-loader { display: none; }
    .btn-admin-login.loading .btn-text { opacity: 0; }
    .btn-admin-login.loading .btn-loader {
        display: flex;
        position: absolute;
        inset: 0;
        align-items: center;
        justify-content: center;
        gap: 7px;
    }
    .btn-admin-login.loading .btn-loader span {
        display: block;
        width: 8px; height: 8px;
        background: #fff;
        border-radius: 50%;
        animation: dotBounce 0.6s ease-in-out infinite alternate;
    }
    .btn-admin-login.loading .btn-loader span:nth-child(2) { animation-delay: 0.2s; }
    .btn-admin-login.loading .btn-loader span:nth-child(3) { animation-delay: 0.4s; }
    @keyframes dotBounce {
        from { transform: translateY(3px); opacity: 0.4; }
        to   { transform: translateY(-3px); opacity: 1; }
    }

    /* Footer link */
    .admin-footer-link { color: #78716c; font-size: 0.82rem; text-align: center; margin-top: 22px; }
    .admin-footer-link a { color: #ea580c; font-weight: 600; text-decoration: none; }
    .admin-footer-link a:hover { color: #c2410c; text-decoration: underline; }

    .copyright { color: #a8a29e; font-size: 0.74rem; text-align: center; margin-top: 24px; }

    /* Badge */
    .admin-badge {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        background: linear-gradient(135deg, rgba(249, 115, 22, 0.12), rgba(245, 158, 11, 0.12));
        border: 1px solid rgba(249, 115, 22, 0.25);
        border-radius: 22px;
        padding: 5px 14px;
        font-size: 0.74rem;
        font-weight: 600;
        color: #c2410c;
        letter-spacing: 0.4px;
        margin-bottom: 10px;
    }
    .admin-badge i { font-size: 0.68rem; }

    /* Alert boxes */
    .admin-alert {
        border-radius: 12px; padding: 12px 16px;
        font-size: 0.85rem; font-weight: 500;
        display: flex; align-items: center;
        margin-bottom: 18px;
        animation: slideIn 0.3s ease;
    }
    .admin-alert-success {
        background: linear-gradient(135deg, #ecfdf5, #f0fdf4);
        border: 1px solid #86efac;
        color: #16a34a;
    }
    .admin-alert-error {
        background: linear-gradient(135deg, #fef2f2, #fff1f2);
        border: 1px solid #fca5a5;
        color: #dc2626;
    }
    @keyframes slideIn {
        from { opacity: 0; transform: translateY(-6px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    /* Shake animation for error */
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        20%       { transform: translateX(-8px); }
        40%       { transform: translateX(8px); }
        60%       { transform: translateX(-5px); }
        80%       { transform: translateX(5px); }
    }
    .shake { animation: shake 0.4s ease; }
</style>

<!-- Background layers -->
<div class="admin-bg"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>
<canvas id="stars-canvas"></canvas>

<div class="login-wrapper">
    <div style="width:100%;max-width:440px;">
        <div class="admin-card">
            <!-- Icon -->
            <div class="text-center">
                <div class="admin-icon-wrap">
                    <i class="fas fa-shield-halved"></i>
                </div>
                <div class="admin-badge"><i class="fas fa-circle-dot"></i> ระบบจัดการอพาร์ทเม้นท์</div>
                <h1 class="admin-title">อาทิตย์ อพาร์ทเม้นท์</h1>
                <p class="admin-subtitle">เข้าสู่ระบบสำหรับผู้ดูแลระบบ</p>
            </div>

            <div class="divider-line"></div>

            {{-- Session & auth messages --}}
            @if(session('success'))
            <div class="admin-alert admin-alert-success">
                <i class="fas fa-circle-check me-2"></i>{{ session('success') }}
            </div>
            @endif
            @error('error')
            <div class="admin-alert admin-alert-error">
                <i class="fas fa-circle-exclamation me-2"></i>{{ $message }}
            </div>
            @enderror

            <form action="{{ route('admin.login') }}" method="POST" id="adminLoginForm" novalidate>
                @csrf

                <!-- Username -->
                <div class="admin-input-group">
                    <label for="username"><i class="fas fa-user me-1"></i> ชื่อผู้ใช้งาน</label>
                    <div class="admin-input-wrap">
                        <i class="fas fa-user input-icon"></i>
                        <input
                            type="text"
                            name="username"
                            id="username"
                            autocomplete="off"
                            placeholder="ระบุชื่อผู้ใช้งาน"
                            value="{{ old('username') }}"
                            required
                            class="@error('username') is-invalid @enderror"
                        >
                    </div>
                    @error('username')
                        <div class="admin-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</div>
                    @enderror
                </div>

                <!-- Password -->
                <div class="admin-input-group">
                    <label for="admin_password"><i class="fas fa-lock me-1"></i> รหัสผ่าน</label>
                    <div class="admin-input-wrap">
                        <i class="fas fa-lock input-icon"></i>
                        <input
                            type="password"
                            name="password"
                            id="admin_password"
                            autocomplete="off"
                            placeholder="••••••••"
                            required
                            class="@error('password') is-invalid @enderror"
                        >
                        <button type="button" class="pw-toggle" onclick="toggleAdminPw()" id="adminPwToggle" title="แสดง/ซ่อนรหัสผ่าน">
                            <i class="fas fa-eye" id="adminPwIcon"></i>
                        </button>
                    </div>
                    @error('password')
                        <div class="admin-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</div>
                    @enderror
                </div>

                <!-- Submit -->
                <button type="submit" class="btn-admin-login" id="adminLoginBtn">
                    <span class="btn-text"><i class="fas fa-arrow-right-to-bracket me-2"></i>เข้าสู่ระบบ</span>
                    <span class="btn-loader">
                        <span></span><span></span><span></span>
                    </span>
                </button>
            </form>

            <div class="divider-line" style="margin-top:22px;"></div>

        </div>

        <div class="copyright">&copy; {{ date('Y') }} อาทิตย์ อพาร์ทเม้นท์. All Rights Reserved.</div>
    </div>
</div>

<script>
    /* ---- Starfield canvas ---- */
    (function () {
        const canvas = document.getElementById('stars-canvas');
        const ctx = canvas.getContext('2d');
        let stars = [];

        function resize() {
            canvas.width  = window.innerWidth;
            canvas.height = window.innerHeight;
        }

        function initStars() {
            stars = [];
            for (let i = 0; i < 140; i++) {
                stars.push({
                    x: Math.random() * canvas.width,
                    y: Math.random() * canvas.height,
                    r: Math.random() * 1.4 + 0.3,
                    alpha: Math.random(),
                    dAlpha: (Math.random() * 0.008 + 0.002) * (Math.random() < 0.5 ? 1 : -1),
                    speed: Math.random() * 0.15 + 0.05,
                });
            }
        }

        function tick() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            stars.forEach(s => {
                s.alpha += s.dAlpha;
                if (s.alpha <= 0 || s.alpha >= 1) s.dAlpha *= -1;
                s.y -= s.speed;
                if (s.y < -2) s.y = canvas.height + 2;
                ctx.beginPath();
                ctx.arc(s.x, s.y, s.r, 0, Math.PI * 2);
                ctx.fillStyle = `rgba(255,255,255,${s.alpha.toFixed(2)})`;
                ctx.fill();
            });
            requestAnimationFrame(tick);
        }

        window.addEventListener('resize', () => { resize(); initStars(); });
        resize();
        initStars();
        tick();
    })();

    /* ---- Toggle password ---- */
    function toggleAdminPw() {
        const input = document.getElementById('admin_password');
        const icon  = document.getElementById('adminPwIcon');
        if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'fas fa-eye-slash';
        } else {
            input.type = 'password';
            icon.className = 'fas fa-eye';
        }
    }

    /* ---- Ripple on button ---- */
    document.getElementById('adminLoginBtn').addEventListener('click', function (e) {
        const btn  = this;
        const rect = btn.getBoundingClientRect();
        const size = Math.max(btn.clientWidth, btn.clientHeight);
        const x    = e.clientX - rect.left - size / 2;
        const y    = e.clientY - rect.top  - size / 2;
        const span = document.createElement('span');
        span.className = 'ripple';
        span.style.cssText = `width:${size}px;height:${size}px;left:${x}px;top:${y}px;`;
        btn.appendChild(span);
        setTimeout(() => span.remove(), 700);
    });

    /* ---- Loading state on submit ---- */
    document.getElementById('adminLoginForm').addEventListener('submit', function (e) {
        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('admin_password').value.trim();
        if (!username || !password) { e.preventDefault(); return; }
        const btn = document.getElementById('adminLoginBtn');
        btn.classList.add('loading');
        btn.disabled = true;
    });

    /* ---- Shake card on validation error ---- */
    @if($errors->any())
        document.querySelector('.admin-card').classList.add('shake');
    @endif
</script>
@endsection