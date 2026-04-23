@extends('auth.layout')

@section('title', 'เข้าสู่ระบบผู้เช่า')

@section('content')
<style>
    /* ======== TENANT LOGIN THEME (Orange-Gray-Yellow Smooth UI) ======== */
    body {
        background: #fafaf9 !important;
        overflow-x: hidden;
        color: #1c1917;
    }

    /* Animated gradient background - Orange/Gray/Yellow */
    .tenant-bg {
        position: fixed;
        inset: 0;
        background: linear-gradient(135deg, #fafaf9 0%, #fef3c7 30%, #fed7aa 60%, #f5f5f4 100%);
        background-size: 400% 400%;
        animation: tenantBgShift 14s ease infinite;
        z-index: 0;
    }

    @keyframes tenantBgShift {
        0%   { background-position: 0% 50%; }
        50%  { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }

    /* Floating orbs - Orange, Yellow, Gray */
    .t-orb {
        position: fixed;
        border-radius: 50%;
        filter: blur(80px);
        opacity: 0.28;
        animation: tFloatOrb linear infinite;
        z-index: 0;
        pointer-events: none;
    }
    .t-orb-1 { width: 480px; height: 480px; background: #fb923c; top: -100px; right: -100px; animation-duration: 24s; }
    .t-orb-2 { width: 320px; height: 320px; background: #fde047; bottom: -80px; left: -80px; animation-duration: 20s; animation-delay: -7s; }
    .t-orb-3 { width: 240px; height: 240px; background: #a8a29e; top: 50%; left: 20%; animation-duration: 18s; animation-delay: -4s; }

    @keyframes tFloatOrb {
        0%   { transform: translate(0, 0) scale(1); }
        33%  { transform: translate(-30px, 40px) scale(1.06); }
        66%  { transform: translate(20px, -30px) scale(0.94); }
        100% { transform: translate(0, 0) scale(1); }
    }

    /* Bubble particles */
    .bubble {
        position: fixed;
        border-radius: 50%;
        border: 1.5px solid rgba(249, 115, 22, 0.25);
        background: rgba(251, 146, 60, 0.08);
        animation: bubbleRise linear infinite;
        z-index: 0;
        pointer-events: none;
    }
    @keyframes bubbleRise {
        0%   { transform: translateY(0) scale(1); opacity: 0; }
        10%  { opacity: 1; }
        90%  { opacity: 0.4; }
        100% { transform: translateY(-110vh) scale(0.75); opacity: 0; }
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

    .tenant-card {
        width: 100%;
        max-width: 440px;
        background: rgba(255, 255, 255, 0.94);
        backdrop-filter: blur(24px);
        -webkit-backdrop-filter: blur(24px);
        border: 1px solid rgba(255, 255, 255, 0.7);
        border-radius: 28px;
        padding: 48px 42px;
        box-shadow: 0 20px 50px rgba(249, 115, 22, 0.1), 0 6px 20px rgba(0,0,0,0.03);
        animation: tenantCardIn 0.7s cubic-bezier(0.22, 1, 0.36, 1) both;
    }

    @keyframes tenantCardIn {
        from { opacity: 0; transform: translateY(30px) scale(0.98); }
        to   { opacity: 1; transform: translateY(0) scale(1); }
    }

    /* Icon - Orange/Yellow gradient */
    .tenant-icon-wrap {
        width: 78px;
        height: 78px;
        background: linear-gradient(135deg, #ea580c, #f97316, #fbbf24);
        border-radius: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        box-shadow: 0 10px 30px rgba(249, 115, 22, 0.32);
        animation: iconPop 0.6s 0.3s cubic-bezier(0.34, 1.56, 0.64, 1) both;
        position: relative;
    }

    @keyframes iconPop {
        from { opacity: 0; transform: scale(0.5) rotate(10deg); }
        to   { opacity: 1; transform: scale(1) rotate(0deg); }
    }

    .tenant-icon-wrap i { font-size: 36px; color: #fff; }

    /* Pulse ring */
    .tenant-icon-wrap::before {
        content: '';
        position: absolute;
        inset: -10px;
        border-radius: 34px;
        border: 2px solid rgba(249, 115, 22, 0.2);
        animation: pulseRing 2.5s ease-out infinite;
    }
    @keyframes pulseRing {
        0%   { transform: scale(1); opacity: 0.85; }
        100% { transform: scale(1.4); opacity: 0; }
    }

    /* Title - Orange gradient */
    .tenant-title {
        font-size: 1.65rem;
        font-weight: 700;
        background: linear-gradient(90deg, #c2410c, #d97706, #b45309);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        letter-spacing: 0.5px;
    }
    .tenant-subtitle { color: #78716c; font-size: 0.87rem; font-weight: 400; }

    /* Badge */
    .tenant-badge {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        background: linear-gradient(135deg, rgba(249, 115, 22, 0.12), rgba(251, 191, 36, 0.12));
        border: 1px solid rgba(249, 115, 22, 0.25);
        border-radius: 22px;
        padding: 5px 14px;
        font-size: 0.74rem;
        font-weight: 600;
        color: #c2410c;
        letter-spacing: 0.4px;
        margin-bottom: 10px;
    }
    .tenant-badge i { font-size: 0.68rem; animation: blink 1.5s ease-in-out infinite; color: #eab308; }
    @keyframes blink { 0%,100%{opacity:1;} 50%{opacity:0.35;} }

    /* Divider */
    .t-divider {
        height: 1px;
        background: linear-gradient(90deg, transparent, rgba(249, 115, 22, 0.2), transparent);
        margin: 22px 0;
    }

    /* Input */
    .tenant-input-group { margin-bottom: 20px; }
    .tenant-input-group label {
        display: block;
        color: #44403c;
        font-size: 0.83rem;
        font-weight: 600;
        letter-spacing: 0.3px;
        margin-bottom: 9px;
    }

    .tenant-input-wrap {
        position: relative;
        display: flex;
        align-items: center;
    }
    .tenant-input-wrap .t-icon {
        position: absolute;
        left: 15px;
        color: #a8a29e;
        font-size: 1.05rem;
        transition: color 0.3s;
        pointer-events: none;
    }
    .tenant-input-wrap input {
        width: 100%;
        background: #fafaf9;
        border: 1.5px solid #e7e5e4;
        border-radius: 14px;
        color: #1c1917;
        font-family: 'Prompt', sans-serif;
        font-size: 0.95rem;
        padding: 14px 46px;
        outline: none;
        transition: all 0.3s ease;
        box-shadow: inset 0 1px 2px rgba(0,0,0,0.02);
    }
    .tenant-input-wrap input::placeholder { color: #a8a29e; }
    .tenant-input-wrap input:focus {
        border-color: #f97316;
        background: #fff;
        box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.12);
    }
    .tenant-input-wrap input:focus + .t-icon { color: #ea580c; }

    /* Room number special styling */
    #room_number { font-size: 1.15rem; font-weight: 600; letter-spacing: 5px; text-align: center; }
    #room_number::placeholder { font-size: 0.88rem; letter-spacing: 0; font-weight: 400; text-align: center; }

    .tenant-input-wrap .t-pw-toggle {
        position: absolute;
        right: 13px;
        background: none;
        border: none;
        color: #a8a29e;
        cursor: pointer;
        padding: 4px 6px;
        font-size: 1.05rem;
        transition: color 0.2s;
    }
    .tenant-input-wrap .t-pw-toggle:hover { color: #ea580c; }

    /* Is-invalid styling */
    .tenant-input-wrap input.is-invalid { border-color: #ef4444 !important; }
    .tenant-error { color: #dc2626; font-size: 0.82rem; margin-top: 6px; display: flex; align-items: center; gap: 5px; font-weight: 500; }

    /* Info tip */
    .input-tip {
        color: #78716c;
        font-size: 0.77rem;
        margin-top: 7px;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    .input-tip i { font-size: 0.72rem; color: #f97316; }

    /* Submit button - Orange/Yellow gradient */
    .btn-tenant-login {
        width: 100%;
        padding: 15px;
        border: none;
        border-radius: 14px;
        background: linear-gradient(135deg, #ea580c 0%, #f97316 50%, #f59e0b 100%);
        background-size: 200% auto;
        color: #fff;
        font-family: 'Prompt', sans-serif;
        font-size: 1.02rem;
        font-weight: 600;
        letter-spacing: 0.4px;
        cursor: pointer;
        position: relative;
        overflow: hidden;
        transition: all 0.35s ease;
        box-shadow: 0 8px 26px rgba(249, 115, 22, 0.32);
        margin-top: 12px;
    }
    .btn-tenant-login:hover {
        background-position: right center;
        transform: translateY(-2px);
        box-shadow: 0 12px 32px rgba(249, 115, 22, 0.42);
    }
    .btn-tenant-login:active { transform: translateY(0); }

    /* Ripple */
    .btn-tenant-login .t-ripple {
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

    /* Loading */
    .btn-tenant-login .t-btn-text { transition: opacity 0.2s; }
    .btn-tenant-login .t-btn-loader { display: none; }
    .btn-tenant-login.loading .t-btn-text { opacity: 0; }
    .btn-tenant-login.loading .t-btn-loader {
        display: flex;
        position: absolute;
        inset: 0;
        align-items: center;
        justify-content: center;
        gap: 7px;
    }
    .btn-tenant-login.loading .t-btn-loader span {
        display: block;
        width: 8px; height: 8px;
        background: #fff;
        border-radius: 50%;
        animation: dotBounce 0.6s ease-in-out infinite alternate;
    }
    .btn-tenant-login.loading .t-btn-loader span:nth-child(2) { animation-delay: 0.2s; }
    .btn-tenant-login.loading .t-btn-loader span:nth-child(3) { animation-delay: 0.4s; }
    @keyframes dotBounce {
        from { transform: translateY(3px); opacity: 0.4; }
        to   { transform: translateY(-3px); opacity: 1; }
    }

    /* Footer */
    .tenant-footer-link { color: #78716c; font-size: 0.85rem; text-align: center; margin-top: 22px; }
    .tenant-footer-link a { color: #ea580c; font-weight: 600; text-decoration: none; transition: 0.2s; }
    .tenant-footer-link a:hover { color: #c2410c; text-decoration: underline; }
    .t-copyright { color: #a8a29e; font-size: 0.76rem; text-align: center; margin-top: 26px; }

    /* Alert boxes */
    .tenant-alert {
        border-radius: 12px; padding: 13px 17px;
        font-size: 0.86rem; font-weight: 500;
        display: flex; align-items: center;
        margin-bottom: 20px;
        animation: slideIn 0.3s ease;
    }
    .tenant-alert-success {
        background: linear-gradient(135deg, #ecfdf5, #f0fdf4);
        border: 1px solid #86efac;
        color: #16a34a;
    }
    .tenant-alert-error {
        background: linear-gradient(135deg, #fef2f2, #fff1f2);
        border: 1px solid #fca5a5;
        color: #dc2626;
    }
    @keyframes slideIn {
        from { opacity: 0; transform: translateY(-6px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    /* Shake error */
    @keyframes shake {
        0%,100% { transform: translateX(0); }
        20%      { transform: translateX(-8px); }
        40%      { transform: translateX(8px); }
        60%      { transform: translateX(-5px); }
        80%      { transform: translateX(5px); }
    }
    .shake { animation: shake 0.4s ease; }

    /* Manual Button */
    .tenant-manual-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 8px 16px;
        background: rgba(249, 115, 22, 0.08); /* สีส้มอ่อนๆ กลมกลืนกับธีม */
        border: 1px solid rgba(249, 115, 22, 0.2);
        border-radius: 20px;
        color: #ea580c !important; /* บังคับให้เป็นสีส้ม */
        font-size: 0.85rem;
        font-weight: 600;
        text-decoration: none !important; /* ลบเส้นใต้ */
        transition: all 0.25s ease;
        
        /* 🌟 ส่วนที่เพิ่มใหม่: เปิดใช้อนิเมชั่นวงแหวนกระเพื่อม */
        position: relative;
        animation: manualPulse 2.5s infinite;
    }
    
    .tenant-manual-btn:hover {
        background: rgba(249, 115, 22, 0.15);
        border-color: rgba(249, 115, 22, 0.4);
        transform: translateY(-2px);
        color: #c2410c !important;
        box-shadow: 0 4px 12px rgba(249, 115, 22, 0.15);
        
        /* 🌟 หยุดกระเพื่อมเมื่อเอาเมาส์ชี้ เพื่อไม่ให้ตาลาย */
        animation: none; 
    }
    
    .tenant-manual-btn i {
        font-size: 1rem;
        /* 🌟 ส่วนที่เพิ่มใหม่: ให้ไอคอนหนังสือดุ๊กดิ๊กเบาๆ */
        display: inline-block;
        animation: bookWiggle 3s ease-in-out infinite;
    }

    /* =========================================
       🌟 Keyframes อนิเมชั่น (เอาไปวางไว้ท้ายสุดของ <style> ได้เลย) 
       ========================================= */
       
    /* อนิเมชั่นวงแหวนสีส้มกระจายออก (Pulse) */
    @keyframes manualPulse {
        0% { box-shadow: 0 0 0 0 rgba(249, 115, 22, 0.3); }
        70% { box-shadow: 0 0 0 10px rgba(249, 115, 22, 0); }
        100% { box-shadow: 0 0 0 0 rgba(249, 115, 22, 0); }
    }

    /* อนิเมชั่นหนังสือขยับซ้ายขวาเบาๆ (Wiggle) ทุกๆ 3 วินาที */
    @keyframes bookWiggle {
        0%, 80%, 100% { transform: rotate(0deg); }
        85% { transform: rotate(-12deg); }
        90% { transform: rotate(12deg); }
        95% { transform: rotate(-6deg); }
    }
</style>

<div class="tenant-bg"></div>
<div class="t-orb t-orb-1"></div>
<div class="t-orb t-orb-2"></div>
<div class="t-orb t-orb-3"></div>

<div id="bubbles-layer"></div>

<div class="login-wrapper">
    <div style="width:100%;max-width:440px;">
        <div class="tenant-card">
            <div class="text-center">
                <div class="tenant-icon-wrap">
                    <i class="fas fa-house-chimney"></i>
                </div>
                <div class="tenant-badge"><i class="fas fa-circle"></i> พื้นที่สำหรับผู้เช่า</div>
                <h1 class="tenant-title">ยินดีต้อนรับ</h1>
                <p class="tenant-subtitle">อาทิตย์ อพาร์ทเม้นท์ — เข้าสู่ระบบเพื่อดูข้อมูลห้องพัก</p>
                {{-- 🌟 ส่วนที่เพิ่มใหม่: ปุ่มคู่มือการใช้งาน 🌟 --}}
                <div class="mb-3">
                    <a href="{{ asset('docs/manual_tenant.pdf') }}" target="_blank" class="tenant-manual-btn">
                        <i class="bi bi-book-half me-1"></i>
                        <span>คู่มือการใช้งานระบบสำหรับผู้เช่า</span>
                    </a>
                </div>
            </div>

            <div class="t-divider"></div>

            {{-- Session & auth messages --}}
            @if(session('success'))
            <div class="tenant-alert tenant-alert-success">
                <i class="fas fa-circle-check me-2 fs-5"></i>{{ session('success') }}
            </div>
            @endif
            @error('error')
            <div class="tenant-alert tenant-alert-error">
                <i class="fas fa-circle-exclamation me-2 fs-5"></i>{{ $message }}
            </div>
            @enderror

            <form action="{{ route('tenant.login') }}" method="POST" id="tenantLoginForm" novalidate>
                @csrf

                <div class="tenant-input-group">
                    <label>หมายเลขห้อง</label>
                    <div class="tenant-input-wrap">
                        <i class="fas fa-door-open t-icon"></i>
                        <input
                            type="text"
                            name="room_number"
                            id="room_number"
                            autocomplete="off"
                            placeholder="เช่น 101"
                            value="{{ old('room_number') }}"
                            required
                            inputmode="numeric"
                            maxlength="10"
                            class="@error('room_number') is-invalid @enderror"
                        >
                    </div>
                    <div class="input-tip"><i class="fas fa-info-circle"></i> กรอกหมายเลขห้องพักของคุณ</div>
                    @error('room_number')
                        <div class="tenant-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</div>
                    @enderror
                </div>

                <div class="tenant-input-group">
                    <label>เลขบัตรประชาชน</label>
                    <div class="tenant-input-wrap">
                        <i class="fas fa-id-card t-icon"></i>
                        <input
                            type="password"
                            name="password"
                            id="tenant_password"
                            autocomplete="off"
                            placeholder="••••••••••••••"
                            required
                            class="@error('password') is-invalid @enderror"
                        >
                        <button type="button" class="t-pw-toggle" onclick="toggleTenantPw()" id="tenantPwToggle" title="แสดง/ซ่อนรหัสผ่าน">
                            <i class="fas fa-eye" id="tenantPwIcon"></i>
                        </button>
                    </div>
                    <div class="input-tip"><i class="fas fa-info-circle"></i> ใช้เลขบัตรประชาชน 13 หลักเป็นรหัสผ่าน</div>
                    @error('password')
                        <div class="tenant-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn-tenant-login" id="tenantLoginBtn">
                    <span class="t-btn-text"><i class="fas fa-right-to-bracket me-2"></i>เข้าสู่ระบบห้องพัก</span>
                    <span class="t-btn-loader">
                        <span></span><span></span><span></span>
                    </span>
                </button>
            </form>

            <div class="t-divider" style="margin-top:24px;"></div>

            <div class="tenant-footer-link">
                
                <div class="">
                    <i class="fas fa-headset me-1"></i>
                    มีปัญหาการเข้าสู่ระบบ? ติดต่อ <a href="tel:0812345678">ผู้ดูแลอพาร์ทเม้นท์</a>
                </div>
                
            </div>
            
        </div>

        <div class="t-copyright">&copy; {{ date('Y') }} อาทิตย์ อพาร์ทเม้นท์</div>
    </div>
</div>

<script>
    /* ---- Floating bubbles ---- */
    (function () {
        const layer = document.getElementById('bubbles-layer');
        for (let i = 0; i < 15; i++) {
            const b       = document.createElement('div');
            b.className   = 'bubble';
            const size    = Math.random() * 40 + 20;
            const left    = Math.random() * 100;
            const dur     = Math.random() * 15 + 10;
            const delay   = Math.random() * -20;
            b.style.cssText = `
                width:${size}px; height:${size}px;
                left:${left}vw; bottom:-${size}px;
                animation-duration:${dur}s;
                animation-delay:${delay}s;
            `;
            layer.appendChild(b);
        }
    })();

    /* ---- Toggle password ---- */
    function toggleTenantPw() {
        const input = document.getElementById('tenant_password');
        const icon  = document.getElementById('tenantPwIcon');
        if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'fas fa-eye-slash';
        } else {
            input.type = 'password';
            icon.className = 'fas fa-eye';
        }
    }

    /* ---- Ripple ---- */
    document.getElementById('tenantLoginBtn').addEventListener('click', function (e) {
        const btn  = this;
        const rect = btn.getBoundingClientRect();
        const size = Math.max(btn.clientWidth, btn.clientHeight);
        const x    = e.clientX - rect.left - size / 2;
        const y    = e.clientY - rect.top  - size / 2;
        const span = document.createElement('span');
        span.className = 't-ripple';
        span.style.cssText = `width:${size}px;height:${size}px;left:${x}px;top:${y}px;`;
        btn.appendChild(span);
        setTimeout(() => span.remove(), 700);
    });

    /* ---- Loading state ---- */
    document.getElementById('tenantLoginForm').addEventListener('submit', function (e) {
        const room = document.getElementById('room_number').value.trim();
        const pw   = document.getElementById('tenant_password').value.trim();
        if (!room || !pw) { e.preventDefault(); return; }
        const btn = document.getElementById('tenantLoginBtn');
        btn.classList.add('loading');
        btn.disabled = true;
    });

    /* ---- Shake on error ---- */
    @if($errors->any())
        document.querySelector('.tenant-card').classList.add('shake');
    @endif
</script>
@endsection