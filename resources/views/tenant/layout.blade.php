@php
    // ดึงข้อมูลแถวแรกจากตาราง apartment (เพราะมีแค่ข้อมูลเดียว)
    $apartmentInfo = \Illuminate\Support\Facades\DB::table('apartment')->first();
@endphp
<!DOCTYPE html>
<html lang="th" data-bs-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'บริการผู้เช่า') - {{ $apartmentInfo ? $apartmentInfo->name : 'อพาร์ทเม้นท์' }}</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    <style>
        :root {
            --primary: #ff5d2c;
            --primary-dark: #3730a3;
            --primary-light: #eef2ff;
            --primary-ring: rgba(79, 70, 229, 0.18);
            --navbar-h: 62px;
            --bottom-nav-h: 66px;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        body {
            font-family: 'Sarabun', sans-serif;
            background-color: var(--bs-body-bg);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            padding-bottom: var(--bottom-nav-h);
        }

        @media (min-width: 992px) {
            body {
                padding-bottom: 0;
            }
        }

        /* =================== TOP NAVBAR =================== */
        .top-navbar {
            height: var(--navbar-h);
            background: rgba(255, 255, 255, 0.92) !important;
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            border-bottom: 1px solid rgba(79, 70, 229, 0.08) !important;
            box-shadow: 0 1px 24px rgba(0, 0, 0, 0.05);
        }

        [data-bs-theme="dark"] .top-navbar {
            background: rgba(15, 23, 42, 0.94) !important;
            border-bottom-color: rgba(255, 255, 255, 0.06) !important;
        }

        /* Brand */
        .brand-icon {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #ff7c01, #ffd23df8);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1rem;
            flex-shrink: 0;
            box-shadow: 0 4px 14px rgba(79, 70, 229, 0.35);
        }

        .brand-label {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--primary);
            line-height: 1.1;
        }

        .brand-sub {
            font-size: 0.62rem;
            color: var(--bs-secondary-color);
        }

        /* Room chip */
        .room-chip {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: var(--primary-light);
            color: var(--primary);
            border: 1px solid rgba(79, 70, 229, 0.15);
            border-radius: 20px;
            padding: 3px 11px;
            font-size: 0.76rem;
            font-weight: 600;
        }

        [data-bs-theme="dark"] .room-chip {
            background: rgba(79, 70, 229, 0.14);
            border-color: rgba(79, 70, 229, 0.3);
            color: #a5b4fc;
        }

        /* Desktop nav pills */
        .nav-pill-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.87rem;
            font-weight: 500;
            color: var(--bs-secondary-color);
            text-decoration: none;
            transition: all 0.2s;
        }

        .nav-pill-link:hover {
            background: var(--primary-light);
            color: var(--primary);
        }

        .nav-pill-link.active {
            background: var(--primary-light);
            color: var(--primary);
            font-weight: 600;
        }

        .nav-pill-badge {
            background: #ef4444;
            color: #fff;
            border-radius: 20px;
            padding: 1px 6px;
            font-size: 0.6rem;
            font-weight: 700;
            line-height: 1.5;
        }

        /* Icon action buttons */
        .icon-btn {
            width: 36px;
            height: 36px;
            border: none;
            background: transparent;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--bs-body-color);
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
            text-decoration: none;
        }

        .icon-btn:hover {
            background: var(--primary-light);
            color: var(--primary);
        }

        [data-bs-theme="dark"] .icon-btn:hover {
            background: rgba(79, 70, 229, 0.2);
            color: #a5b4fc;
        }

        /* Notification badge */
        .notif-dot {
            position: absolute;
            top: 3px;
            right: 3px;
            min-width: 17px;
            height: 17px;
            background: #ef4444;
            color: #fff;
            border-radius: 20px;
            font-size: 0.58rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 4px;
            border: 2px solid var(--bs-body-bg);
            animation: badgePop 0.35s cubic-bezier(0.34, 1.56, 0.64, 1) both;
        }

        @keyframes badgePop {
            from {
                transform: scale(0);
            }

            to {
                transform: scale(1);
            }
        }

        /* User avatar */
        .user-avatar {
            width: 34px;
            height: 34px;
            background: linear-gradient(135deg, #ffbc41, #ffa600);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 0.82rem;
            font-weight: 700;
            box-shadow: 0 2px 10px rgba(79, 70, 229, 0.3);
            cursor: pointer;
            transition: all 0.2s;
            user-select: none;
        }

        .user-avatar:hover {
            transform: scale(1.06);
            box-shadow: 0 4px 16px rgba(79, 70, 229, 0.4);
        }

        /* Dropdown */
        .tenant-dropdown {
            border: none !important;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.12) !important;
            border-radius: 16px !important;
            padding: 8px !important;
            min-width: 210px;
        }

        [data-bs-theme="dark"] .tenant-dropdown {
            background: #1e293b !important;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5) !important;
        }

        .tenant-dropdown .dropdown-item {
            border-radius: 10px !important;
            font-size: 0.87rem;
            padding: 8px 12px !important;
            transition: 0.15s;
        }

        /* Cards */
        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s;
        }

        [data-bs-theme="dark"] .card {
            background-color: #1e293b;
            box-shadow: none;
            border: 1px solid #334155;
        }

        /* =================== BOTTOM NAV =================== */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            height: var(--bottom-nav-h);
            background: var(--bs-body-bg);
            border-top: 1px solid var(--bs-border-color);
            display: flex;
            align-items: stretch;
            justify-content: space-around;
            z-index: 1050;
            padding: 0;
            padding-bottom: env(safe-area-inset-bottom);
            box-shadow: 0 -4px 28px rgba(0, 0, 0, 0.07);
        }

        @media (min-width: 992px) {
            .bottom-nav {
                display: none !important;
            }
        }

        @media (max-width: 991px) {
            .desktop-menu-items {
                display: none !important;
            }
        }

        .bnav-item {
            flex: 1;
            text-decoration: none;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: var(--bs-secondary-color);
            font-size: 0.68rem;
            font-weight: 500;
            padding-top: 8px;
            padding-bottom: 4px;
            position: relative;
            transition: color 0.2s;
            -webkit-tap-highlight-color: transparent;
        }

        .bnav-item i {
            font-size: 1.25rem;
            margin-bottom: 3px;
            transition: transform 0.25s;
            display: block;
        }

        .bnav-item.active {
            color: var(--primary);
        }

        .bnav-item.active i {
            transform: translateY(-2px);
        }

        /* active underline dot */
        .bnav-item.active::before {
            content: '';
            position: absolute;
            bottom: 6px;
            width: 4px;
            height: 4px;
            background: var(--primary);
            border-radius: 50%;
        }

        /* Invoice badge on bottom nav */
        .bnav-badge {
            position: absolute;
            top: 7px;
            left: 50%;
            transform: translateX(6px);
            min-width: 16px;
            height: 16px;
            background: #ef4444;
            color: #fff;
            border-radius: 20px;
            font-size: 0.58rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 3px;
            border: 2px solid var(--bs-body-bg);
        }

        /* =================== BREADCRUMB =================== */
        .breadcrumb-bar {
            background: var(--bs-body-bg);
            border-bottom: 1px solid var(--bs-border-color);
            padding: 8px 0;
            font-size: 0.8rem;
        }

        [data-bs-theme="light"] .breadcrumb-bar {
            background: #f8faff;
        }

        .breadcrumb-bar .breadcrumb {
            margin-bottom: 0;
        }

        .breadcrumb-bar .breadcrumb-item a {
            color: var(--primary);
            text-decoration: none;
        }

        .breadcrumb-bar .breadcrumb-item a:hover {
            text-decoration: underline;
        }

        .breadcrumb-bar .breadcrumb-item.active {
            color: var(--bs-secondary-color);
            font-weight: 500;
        }

        .breadcrumb-bar .breadcrumb-item+.breadcrumb-item::before {
            content: "›";
            font-size: 1rem;
            line-height: 1;
            color: var(--bs-secondary-color);
        }

        .btn-back {
            font-size: 0.78rem;
            padding: 3px 10px;
            border-radius: 20px;
            border: 1px solid var(--bs-border-color);
            background: transparent;
            color: var(--bs-secondary-color);
            transition: 0.2s;
            cursor: pointer;
        }

        .btn-back:hover {
            background: var(--bs-tertiary-bg);
            color: var(--primary);
            border-color: var(--primary);
        }

        @media (max-width: 575px) {
            .breadcrumb-bar {
                display: none !important;
            }
        }

        footer {
            margin-top: auto;
            border-top: 1px solid var(--bs-border-color);
        }

        /* Legacy compat */
        @media (max-width: 991px) {
            .desktop-menu-items {
                display: none !important;
            }
        }

        /* =================== SWEETALERT2 CUSTOM =================== */
        .custom-swal-popup {
            border-radius: 24px !important;
            font-family: 'Sarabun', sans-serif !important;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.15) !important;
            padding: 1.5em 1.5em 2em !important;
        }

        [data-bs-theme="dark"] .custom-swal-popup {
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5) !important;
            border: 1px solid #334155;
        }

        .custom-swal-title {
            font-weight: 700 !important;
            font-size: 1.4rem !important;
        }

        .custom-swal-btn {
            border-radius: 12px !important;
            font-weight: 500 !important;
            padding: 10px 24px !important;
            transition: all 0.2s !important;
        }

        .custom-swal-btn:hover {
            transform: translateY(-2px);
        }
    </style>
    @stack('styles')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>

    {{-- =================== TOP NAVBAR =================== --}}
    <nav class="navbar top-navbar sticky-top">
        <div class="container d-flex align-items-center gap-2">

            {{-- Brand --}}
            <a href="{{ route('tenant.dashboard') }}"
                class="d-flex align-items-center gap-2 text-decoration-none me-1 me-md-2">
                <div class="brand-icon"><i class="fa-solid fa-building-columns"></i></div>
                <div class="lh-sm d-none d-sm-block">
                    <div class="brand-label">{{ $apartmentInfo ? $apartmentInfo->name : '' }}</div>
                    <div class="brand-sub">For Tenants Only</div>
                </div>
            </a>

            {{-- Room number chip (md+) --}}
            @auth('tenant')
                <span class="room-chip d-none d-md-inline-flex flex-shrink-0">
                    <i class="fa-solid fa-door-open" style="font-size:0.7rem;"></i>
                    ห้อง {{ Auth::guard('tenant')->user()->room->room_number ?? '-' }}
                </span>
            @endauth

            {{-- Desktop nav pills --}}
            <div class="desktop-menu-items d-none d-lg-flex align-items-center gap-1 ms-2">
                <a class="nav-pill-link {{ request()->routeIs('tenant.dashboard') ? 'active' : '' }}"
                    href="{{ route('tenant.dashboard') }}">
                    <i class="fa-solid fa-house"></i> หน้าแรก
                </a>
                <a class="nav-pill-link {{ request()->routeIs('tenant.invoices.index') ? 'active' : '' }}"
                    href="{{ route('tenant.invoices.index') }}">
                    <i class="fa-solid fa-file-invoice-dollar"></i> บิลค่าเช่า
                    @if (($unpaidInvoiceCount ?? 0) > 0)
                        <span class="nav-pill-badge">{{ $unpaidInvoiceCount }}</span>
                    @endif
                </a>
                <a class="nav-pill-link {{ request()->routeIs('tenant.maintenance.index') ? 'active' : '' }}"
                    href="{{ route('tenant.maintenance.index') }}">
                    <i class="fa-solid fa-screwdriver-wrench"></i> แจ้งซ่อม
                </a>
            </div>

            {{-- Right-side actions --}}
            <div class="d-flex align-items-center gap-1 ms-auto">

                <a href="{{ asset('docs/manual_tenant.pdf') }}" 
                    target="_blank" 
                    class="btn btn-sm d-flex align-items-center gap-2 px-3" 
                    style="background: #ffffff; color: #ff9d42; border: 1px solid #ff9d42; border-radius: 10px; font-weight: 600; transition: all 0.3s;" 
                    onmouseover="this.style.background='#ff9d42'; this.style.color='#ffffff';" 
                    onmouseout="this.style.background='#ffffff'; this.style.color='#ff9d42';">
                    <i class="bi bi-book-half"></i>
                    {{-- 🌟 แก้บรรทัดนี้: ลบ d-none d-sm-block ออก --}}
                    <span>คู่มือการใช้งาน</span> 
                </a>
                {{-- Notification bell → invoices page --}}
                <a href="{{ route('tenant.invoices.index') }}" class="icon-btn" title="ใบแจ้งหนี้">
                    <i class="fa-solid fa-bell"></i>
                    @if (($unpaidInvoiceCount ?? 0) > 0)
                        <span class="notif-dot">{{ $unpaidInvoiceCount }}</span>
                    @endif
                </a>

                {{-- Theme toggle --}}
                <button class="icon-btn" id="themeToggle" title="เปลี่ยนธีม">
                    <i class="fa-solid fa-moon"></i>
                </button>

                {{-- User avatar + dropdown --}}
                <div class="dropdown">
                    <div class="user-avatar ms-1" data-bs-toggle="dropdown" aria-expanded="false">
                        {{ mb_substr(Auth::guard('tenant')->user()->first_name ?? 'U', 0, 1) }}
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end tenant-dropdown mt-2">
                        <li class="px-2 py-1 mb-1">
                            <div class="fw-bold lh-sm" style="font-size:.88rem;">
                                คุณ{{ Auth::guard('tenant')->user()->first_name ?? '' }}
                            </div>
                            <div class="text-muted" style="font-size:.73rem;">
                                <i class="fa-solid fa-door-open me-1"></i>
                                ห้อง {{ Auth::guard('tenant')->user()->room->room_number ?? '-' }}
                            </div>
                        </li>
                        <li>
                            <hr class="dropdown-divider my-1">
                        </li>
                        <li>
                            <a class="dropdown-item {{ request()->routeIs('tenant.profile') ? 'active' : '' }}"
                                href="{{ route('tenant.profile') }}">
                                <i class="fa-regular fa-id-badge me-2"></i> ข้อมูลส่วนตัว
                            </a>
                        </li>
                        <li>
                            <hr class="dropdown-divider my-1">
                        </li>
                        <li>
                            <form action="{{ route('tenant.logout') }}" method="POST" id="logout-form">
                                @csrf
                                <button type="button" class="dropdown-item text-danger" onclick="confirmLogout()">
                                    <i class="fa-solid fa-right-from-bracket me-2"></i> ออกจากระบบ
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    {{-- =================== BREADCRUMB =================== --}}
    <div class="breadcrumb-bar d-none d-sm-block">
        <div class="container d-flex align-items-center justify-content-between gap-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="{{ route('tenant.dashboard') }}">
                            <i class="fa-solid fa-house me-1" style="font-size:.75rem;"></i>หน้าแรก
                        </a>
                    </li>
                    @if (request()->routeIs('tenant.invoices.index'))
                        <li class="breadcrumb-item active">บิลค่าเช่า</li>
                    @elseif(request()->routeIs('tenant.maintenance.index'))
                        <li class="breadcrumb-item active">แจ้งซ่อม</li>
                    @elseif(request()->routeIs('tenant.profile'))
                        <li class="breadcrumb-item active">ข้อมูลส่วนตัว</li>
                    @elseif(!request()->routeIs('tenant.dashboard'))
                        <li class="breadcrumb-item active">@yield('title')</li>
                    @endif
                </ol>
            </nav>
        </div>
    </div>

    {{-- =================== MAIN CONTENT =================== --}}
    <div class="container py-4 flex-grow-1">
        @yield('content')
    </div>

    {{-- =================== MOBILE BOTTOM NAV =================== --}}
    <div class="bottom-nav d-lg-none">
        <a href="{{ route('tenant.dashboard') }}"
            class="bnav-item {{ request()->routeIs('tenant.dashboard') ? 'active' : '' }}">
            <i class="fa-solid fa-house"></i>
            <span>หน้าแรก</span>
        </a>

        <a href="{{ route('tenant.invoices.index') }}"
            class="bnav-item {{ request()->routeIs('tenant.invoices.index') ? 'active' : '' }}">
            <i class="fa-solid fa-file-invoice-dollar"></i>
            <span>บิลค่าเช่า</span>
            @if (($unpaidInvoiceCount ?? 0) > 0)
                <span class="bnav-badge">{{ $unpaidInvoiceCount }}</span>
            @endif
        </a>

        <a href="{{ route('tenant.maintenance.index') }}"
            class="bnav-item {{ request()->routeIs('tenant.maintenance.index') ? 'active' : '' }}">
            <i class="fa-solid fa-screwdriver-wrench"></i>
            <span>แจ้งซ่อม</span>
        </a>

        <a href="{{ route('tenant.profile') }}"
            class="bnav-item {{ request()->routeIs('tenant.profile') ? 'active' : '' }}">
            <i class="fa-solid fa-user"></i>
            <span>โปรไฟล์</span>
        </a>
    </div>

    <footer class="py-3 bg-body-tertiary d-none d-lg-block">
        <div class="container text-center small text-muted">
            &copy; {{ date('Y') }} อาทิตย์ อพาร์ทเม้นท์. All Rights Reserved.
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // ---- Theme Toggle ----
        const themeToggle = document.getElementById('themeToggle');
        const icon = themeToggle.querySelector('i');
        const html = document.documentElement;

        const currentTheme = localStorage.getItem('theme') || 'light';
        applyTheme(currentTheme);

        function applyTheme(theme) {
            html.setAttribute('data-bs-theme', theme);
            icon.className = theme === 'dark' ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
        }

        themeToggle.addEventListener('click', () => {
            const newTheme = html.getAttribute('data-bs-theme') === 'light' ? 'dark' : 'light';
            localStorage.setItem('theme', newTheme);
            applyTheme(newTheme);
        });

        // ---- Alerts ----
        @if (session('success'))
            Swal.fire({
                icon: 'success',
                title: 'สำเร็จ!',
                text: "{{ session('success') }}",
                timer: 2000,
                showConfirmButton: false,
                background: document.documentElement.getAttribute('data-bs-theme') === 'dark' ? '#1e293b' : '#fff',
                color: document.documentElement.getAttribute('data-bs-theme') === 'dark' ? '#fff' : '#000',
            });
        @endif

        @if (session('error'))
            Swal.fire({
                icon: 'error',
                title: 'ผิดพลาด',
                text: "{{ session('error') }}",
                background: document.documentElement.getAttribute('data-bs-theme') === 'dark' ? '#1e293b' : '#fff',
                color: document.documentElement.getAttribute('data-bs-theme') === 'dark' ? '#fff' : '#000',
            });
        @endif

        // ---- Logout confirm ----
        function confirmLogout() {
            const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';

            Swal.fire({
                title: 'ออกจากระบบ?',
                text: 'คุณแน่ใจหรือไม่ว่าต้องการออกจากระบบ?',
                icon: 'question', // เปลี่ยนจาก warning เป็น question ให้ดูซอฟต์ลง
                showCancelButton: true,
                confirmButtonColor: '#ef4444', // ใช้สีแดงสำหรับ Action ที่เป็นการออกจากระบบ/ลบ
                cancelButtonColor: isDark ? '#334155' : '#e2e8f0', // ปุ่มยกเลิกสีเทาเนียนๆ
                confirmButtonText: '<i class="fa-solid fa-right-from-bracket me-1"></i> ออกจากระบบ',
                cancelButtonText: 'ยกเลิก',
                background: isDark ? '#1e293b' : '#ffffff',
                color: isDark ? '#f8fafc' : '#1e293b',
                backdrop: isDark ? 'rgba(0,0,0,0.6)' : 'rgba(15, 23, 42, 0.4)',
                customClass: {
                    popup: 'custom-swal-popup',
                    title: 'custom-swal-title',
                    confirmButton: 'custom-swal-btn',
                    cancelButton: `custom-swal-btn ${isDark ? 'text-white' : 'text-dark'}`
                }
            }).then(result => {
                if (result.isConfirmed) {
                    // โชว์ Loading สวยๆ ระหว่างรอระบบประมวลผลการ Logout
                    Swal.fire({
                        title: 'กำลังออกจากระบบ...',
                        text: 'กรุณารอสักครู่',
                        allowOutsideClick: false,
                        background: isDark ? '#1e293b' : '#ffffff',
                        color: isDark ? '#f8fafc' : '#1e293b',
                        customClass: {
                            popup: 'custom-swal-popup'
                        },
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    document.getElementById('logout-form').submit();
                }
            });
        }
    </script>

    @stack('scripts')

    {{-- =================== REVERB REAL-TIME NOTIFICATIONS =================== --}}
    @auth('tenant')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof window.Echo === 'undefined') {
                    console.warn('[Reverb] window.Echo not loaded');
                    return;
                }

                const tenantId = {{ Auth::guard('tenant')->id() ?? 'null' }};
                if (!tenantId) {
                    console.log('[Reverb] Skipping private subscription because tenantId is missing');
                    return;
                }

                console.log('[Reverb] Subscribing to private channel tenant.' + tenantId);

                const channel = window.Echo.private('tenant.' + tenantId);

                channel.subscribed(() => {
                    console.log('[Reverb] ✅ Subscribed to tenant.' + tenantId);
                }).error((err) => {
                    console.error('[Reverb] ❌ Subscription error:', err);
                });

                channel.listen('.maintenance.status.updated', (data) => {
                    console.log('[Reverb] maintenance.status.updated', data);
                    showReverbToast(data.message, data.status);

                    // รีเฟรช badge / teaser หลัง 1.5 วินาที
                    setTimeout(() => {
                        // ถ้าอยู่หน้า dashboard หรือ maintenance ให้ soft-reload
                        const path = window.location.pathname;
                        if (path.includes('/maintenance') || path.includes('/dashboard') || path
                            .includes('/index')) {
                            window.location.reload();
                        }
                    }, 2500);
                });
                channel.listen('.invoice.sent', (data) => {
                    console.log('[Reverb] invoice.sent', data);
                    showReverbToast(data.message, 'invoice_sent');
                    setTimeout(() => {
                        const path = window.location.pathname;
                        if (path.includes('/invoice') || path.includes('/dashboard')) {
                            window.location.reload();
                        }
                    }, 2500);
                });
                channel.listen('.payment.recorded', (data) => {
                    console.log('[Reverb] payment.recorded', data);
                    const st = data.status === 'ชำระแล้ว' ? 'finished' : 'payment_partial';
                    showReverbToast(data.message, st);
                });
            });

            function showReverbToast(message, status) {
                const colors = {
                    processing: {
                        bg: '#f0f9ff',
                        border: '#38bdf8',
                        icon: '🔧',
                        iconBg: '#e0f2fe',
                        text: '#0c4a6e'
                    },
                    finished: {
                        bg: '#f0fdf4',
                        border: '#4ade80',
                        icon: '✅',
                        iconBg: '#dcfce7',
                        text: '#14532d'
                    },
                    cancelled: {
                        bg: '#fef2f2',
                        border: '#f87171',
                        icon: '❌',
                        iconBg: '#fee2e2',
                        text: '#7f1d1d'
                    },
                    invoice_sent: {
                        bg: '#fffbeb',
                        border: '#fbbf24',
                        icon: '📋',
                        iconBg: '#fef3c7',
                        text: '#78350f'
                    },
                    payment_partial: {
                        bg: '#fff7ed',
                        border: '#fb923c',
                        icon: '💰',
                        iconBg: '#ffedd5',
                        text: '#7c2d12'
                    },
                };
                const c = colors[status] || {
                    bg: '#f8fafc',
                    border: '#94a3b8',
                    icon: 'ℹ️',
                    iconBg: '#f1f5f9',
                    text: '#1e293b'
                };

                // สร้าง container ถ้ายังไม่มี
                let container = document.getElementById('reverb-toast-container');
                if (!container) {
                    container = document.createElement('div');
                    container.id = 'reverb-toast-container';
                    container.style.cssText =
                        'position:fixed;bottom:90px;right:16px;z-index:9999;display:flex;flex-direction:column;gap:8px;max-width:340px;width:calc(100vw - 32px);';
                    document.body.appendChild(container);
                }

                const toast = document.createElement('div');
                toast.style.cssText = `
            background:${c.bg};
            border:1.5px solid ${c.border};
            border-radius:14px;
            padding:12px 14px;
            display:flex;
            align-items:flex-start;
            gap:10px;
            box-shadow:0 8px 30px rgba(0,0,0,0.12);
            animation:toastIn .35s cubic-bezier(0.34,1.56,0.64,1);
            font-family:'Sarabun',sans-serif;
        `;
                toast.innerHTML = `
            <div style="width:34px;height:34px;border-radius:50%;background:${c.iconBg};display:flex;align-items:center;justify-content:center;font-size:1rem;flex-shrink:0;">${c.icon}</div>
            <div style="flex:1;">
                <div style="font-weight:700;color:${c.text};font-size:.82rem;line-height:1.3;">${message}</div>
                <div style="font-size:.7rem;color:#64748b;margin-top:3px;">การแจ้งซ่อม</div>
            </div>
            <button onclick="this.closest('div[style]').remove()" style="background:none;border:none;color:#94a3b8;cursor:pointer;font-size:1rem;padding:0;line-height:1;flex-shrink:0;">×</button>
        `;
                container.appendChild(toast);

                // auto-dismiss
                setTimeout(() => {
                    toast.style.animation = 'toastOut .3s ease forwards';
                    setTimeout(() => toast.remove(), 300);
                }, 5000);
            }
        </script>
        <style>
            @keyframes toastIn {
                from {
                    opacity: 0;
                    transform: translateY(16px) scale(.95);
                }

                to {
                    opacity: 1;
                    transform: translateY(0) scale(1);
                }
            }

            @keyframes toastOut {
                from {
                    opacity: 1;
                    transform: translateX(0);
                }

                to {
                    opacity: 0;
                    transform: translateX(24px);
                }
            }
        </style>
    @endauth
</body>

</html>
