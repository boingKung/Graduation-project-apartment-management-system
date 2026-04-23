@php
    // ดึงข้อมูลแถวแรกจากตาราง apartment (เพราะมีแค่ข้อมูลเดียว)
    $apartmentInfo = \Illuminate\Support\Facades\DB::table('apartment')->first();

    // 🌟 ดึงข้อมูลสำหรับระบบแจ้งเตือน (Notification)
    $notiMaintenance = \App\Models\Maintenance::with('room')->where('status', 'pending')->latest()->get();
    $notiTenants = \App\Models\Tenant::with('room')->where('status', 'รออนุมัติ')->latest()->get();
    $notiPayments = \App\Models\Payment::with(['invoice.tenant.room', 'accounting_transactions.tenant.room'])->where('status', 'รอตรวจสอบ')->latest()->get();

    $totalNotiCount = $notiMaintenance->count() + $notiTenants->count() + $notiPayments->count();
@endphp
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $apartmentInfo->name ?? 'ชื่ออพาร์ทเม้นท์' }} @yield('title') </title>

    {{-- === Stylesheets & Fonts === --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    
    <style>
        /* === CSS Variables === */
        :root {
            --sidebar-bg: #0f172a;
            --sidebar-hover: rgba(255, 255, 255, 0.06);
            --sidebar-active: rgba(99, 102, 241, 0.18);
            --primary-color: #6366f1;
            --primary-glow: rgba(99, 102, 241, 0.4);
            --text-muted: #94a3b8;
            --sidebar-width: 270px;
            --sidebar-collapsed-width: 82px;
        }

        body {
            font-family: 'Prompt', sans-serif;
            background: #f1f5f9;
            margin: 0;
        }

        /* === Custom Scrollbar === */
        .sidebar ::-webkit-scrollbar { width: 4px; }
        .sidebar ::-webkit-scrollbar-track { background: transparent; }
        .sidebar ::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }
        .sidebar ::-webkit-scrollbar-thumb:hover { background: rgba(255, 255, 255, 0.2); }

        /* === Sidebar === */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            background: var(--sidebar-bg);
            color: white;
            position: fixed;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            border-right: 1px solid rgba(255, 255, 255, 0.04);
            z-index: 1000;
            overflow: hidden;
        }

        .sidebar.collapsed { width: var(--sidebar-collapsed-width); }

        /* Sidebar Header */
        .sidebar-header {
            padding: 16px 18px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-height: 68px;
            background: rgba(0, 0, 0, 0.15);
        }

        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sidebar-logo-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary-color), #818cf8);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            font-weight: 700;
            color: white;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
            flex-shrink: 0;
            transition: all 0.3s;
        }

        .sidebar-logo-icon:hover { transform: rotate(-5deg) scale(1.05); }

        .sidebar-header h5 {
            font-size: 0.92rem;
            white-space: nowrap;
            letter-spacing: -0.3px;
        }

        .sidebar-header p {
            font-size: 0.68rem;
            color: var(--text-muted);
            white-space: nowrap;
        }

        .sidebar.collapsed .sidebar-logo-text,
        .sidebar.collapsed .menu-category,
        .sidebar.collapsed .nav-text,
        .sidebar.collapsed .badge,
        .sidebar.collapsed .user-info-text {
            display: none;
        }

        .sidebar.collapsed .sidebar-header {
            justify-content: center;
            padding: 16px 0;
        }

        .sidebar.collapsed .sidebar-logo { justify-content: center; }

        /* Menu Category */
        .menu-category {
            font-size: 0.62rem;
            text-transform: uppercase;
            color: var(--text-muted);
            padding: 20px 22px 6px;
            letter-spacing: 1.2px;
            white-space: nowrap;
            font-weight: 600;
        }

        /* Nav Links */
        .nav-link {
            color: #94a3b8;
            padding: 9px 16px;
            margin: 2px 10px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            text-decoration: none;
            transition: all 0.2s ease;
            line-height: 1.25rem;
            white-space: nowrap;
            overflow: hidden;
            position: relative;
            font-weight: 400;
        }

        .nav-text { font-size: 0.85rem; }

        .nav-link i {
            min-width: 32px;
            text-align: center;
            font-size: 1.05rem;
            margin-right: 8px;
            opacity: 0.7;
            transition: all 0.2s;
        }

        .nav-link:hover {
            background: var(--sidebar-hover);
            color: #e2e8f0;
            transform: translateX(3px);
        }

        .nav-link:hover i {
            opacity: 1;
            color: var(--primary-color);
        }

        .nav-link.active {
            background: var(--sidebar-active);
            color: white;
            font-weight: 500;
            box-shadow: inset 3px 0 0 var(--primary-color);
        }

        .nav-link.active i {
            opacity: 1;
            color: #a5b4fc;
        }

        .nav-link.text-danger { color: #f87171; }
        .nav-link.text-danger i { opacity: 0.8; color: #f87171; }

        .nav-link.text-danger:hover {
            background: rgba(239, 68, 68, 0.12);
            color: #fca5a5;
            box-shadow: none;
            transform: translateX(3px);
        }

        /* Badge pill */
        .nav-link .badge {
            font-size: 0.65rem;
            padding: 3px 8px;
            font-weight: 600;
            border-radius: 20px;
            animation: badgePulse 2s infinite;
        }

        @keyframes badgePulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.5); }
            50% { box-shadow: 0 0 0 4px rgba(239, 68, 68, 0); }
        }

        .sidebar.collapsed .nav-link {
            justify-content: center;
            padding: 10px 0;
            margin: 2px 10px;
        }

        .sidebar.collapsed .nav-link i {
            margin-right: 0;
            font-size: 1.15rem;
        }

        /* Collapse Button */
        #btnCollapse {
            border-radius: 8px;
            transition: 0.2s;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        #btnCollapse:hover { background: rgba(255, 255, 255, 0.12); }

        #collapseIcon {
            transition: transform 0.3s ease, color 0.3s ease;
            font-size: 0.75rem;
        }

        .sidebar.collapsed #collapseIcon {
            transform: rotate(180deg);
            color: var(--primary-color);
        }

        /* Sidebar User Card */
        .sidebar-user-card {
            padding: 14px 16px;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
            background: rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sidebar-user-avatar {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            font-weight: 600;
            color: white;
            flex-shrink: 0;
        }

        .sidebar.collapsed .sidebar-user-card {
            justify-content: center;
            padding: 14px 0;
        }

        /* === Main Wrapper === */
        .main-wrapper {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
        }

        .main-wrapper.expanded { margin-left: var(--sidebar-collapsed-width); }

        /* === Topbar === */
        .topbar {
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 28px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.06);
            position: sticky;
            top: 0;
            z-index: 900;
        }

        .topbar-breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .topbar-breadcrumb .page-title {
            font-weight: 600;
            font-size: 0.95rem;
            color: #1e293b;
        }

        .topbar-datetime {
            display: flex;
            align-items: center;
            gap: 6px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 6px 14px;
            border-radius: 10px;
            font-size: 0.78rem;
            color: #64748b;
            font-weight: 500;
        }

        .topbar-datetime i {
            color: var(--primary-color);
            font-size: 0.85rem;
        }

        /* User Profile Button */
        .topbar-user {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 6px 8px 6px 16px;
            border-radius: 14px;
            transition: background 0.2s;
            cursor: pointer;
            border: 1px solid transparent;
        }

        .topbar-user:hover, .topbar-user.show { 
            background: #f8fafc; 
            border-color: #e2e8f0;
        }

        .topbar-user-avatar {
            width: 38px;
            height: 38px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.85rem;
            box-shadow: 0 2px 8px rgba(99, 102, 241, 0.25);
        }

        .topbar-user-name {
            font-weight: 600;
            font-size: 0.82rem;
            color: #1e293b;
            line-height: 1.2;
        }

        .topbar-user-role {
            font-size: 0.68rem;
            color: #94a3b8;
            font-weight: 500;
        }

        /* Custom Dropdown Styling */
        .custom-dropdown-menu {
            border: 1px solid rgba(0,0,0,0.08);
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .content-body {
            padding: 28px;
            flex: 1;
        }

        /* === Footer === */
        footer {
            color: #94a3b8;
            background: #fff;
            border-top: 1px solid rgba(0, 0, 0, 0.04);
            font-size: 0.75rem;
        }

        /* === Mobile Overlay === */
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            backdrop-filter: blur(2px);
        }

        .sidebar-overlay.active { display: block; }

        /* === Tooltip for collapsed sidebar === */
        .sidebar.collapsed .nav-link { position: relative; }
        .sidebar.collapsed .nav-link::after {
            content: attr(data-tooltip);
            position: absolute;
            left: calc(100% + 12px);
            top: 50%;
            transform: translateY(-50%);
            background: #1e293b;
            color: white;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.78rem;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s, transform 0.2s;
            transform: translateY(-50%) translateX(-5px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            z-index: 1100;
        }

        .sidebar.collapsed .nav-link::before {
            content: '';
            position: absolute;
            left: calc(100% + 6px);
            top: 50%;
            transform: translateY(-50%);
            border: 5px solid transparent;
            border-right-color: #1e293b;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s;
            z-index: 1100;
        }

        .sidebar.collapsed .nav-link:hover::after {
            opacity: 1;
            transform: translateY(-50%) translateX(0);
        }

        .sidebar.collapsed .nav-link:hover::before { opacity: 1; }

        /* === Notifications Animations === */
        @keyframes amtIn {
            from { opacity: 0; transform: translateX(80px); }
            to { opacity: 1; transform: translateX(0); }
        }

        @keyframes amtOut {
            from { opacity: 1; transform: translateX(0); }
            to { opacity: 0; transform: translateX(80px); }
        }

        /* === Responsive === */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                width: var(--sidebar-width) !important;
            }
            .sidebar.active { transform: translateX(0); }
            .main-wrapper { margin-left: 0 !important; }
            .sidebar.collapsed { width: var(--sidebar-width); }
            .topbar-datetime { display: none; }
        }
    </style>
    @stack('styles')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>

    {{-- Mobile Overlay --}}
    <div class="sidebar-overlay no-print" id="sidebarOverlay"></div>

    {{-- === Sidebar (เมนูซ้ายมือ) === --}}
    <aside class="sidebar no-print" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <div class="sidebar-logo-icon">
                    <i class="bi bi-building"></i>
                </div>
                <div class="sidebar-logo-text">
                    <h5 class="mb-0 fw-bold">Admin Panel</h5>
                    <p class="mb-0">{{ $apartmentInfo->name ?? 'ชื่ออพาร์ทเม้นท์' }}</p>
                </div>
            </div>
            <button class="btn btn-sm text-white d-none d-md-block" id="btnCollapse">
                <i class="bi bi-chevron-left" id="collapseIcon"></i>
            </button>
        </div>

        <div class="flex-grow-1 overflow-auto py-2" id="sidebarScrollArea">

            <div class="menu-category">ภาพรวม</div>

            <a href="{{ route('admin.dashboard') }}"
                class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" data-tooltip="แผงควบคุม">
                <i class="bi bi-grid-1x2-fill"></i>
                <span class="nav-text">Dashboard</span>
            </a>

            <a href="{{ route('admin.rooms.system') }}"
                class="nav-link {{ request()->routeIs('admin.rooms.system') ? 'active' : '' }}"
                data-tooltip="ผังห้องพัก">
                <i class="bi bi-layout-three-columns"></i>
                <span class="nav-text">ผังห้องพัก & การจอง</span>
            </a>

            <a href="{{ route('admin.maintenance.index') }}"
                class="nav-link {{ request()->routeIs('admin.maintenance.*') ? 'active' : '' }}"
                data-tooltip="แจ้งซ่อม">
                <i class="bi bi-tools"></i>
                <span class="nav-text">รายการแจ้งซ่อม</span>
                @if ($notiMaintenance->count() > 0)
                    <span class="badge bg-danger ms-auto">{{ $notiMaintenance->count() }}</span>
                @endif
            </a>

            <a href="{{ route('admin.registrations.show') }}"
                class="nav-link {{ request()->routeIs('admin.registrations.*') ? 'active' : '' }}">
                <i class="bi bi-card-list text-warning fs-5"></i>
                <span class="nav-text">ผู้เช่าลงทะเบียนออนไลน์</span>
                @if ($notiTenants->count() > 0)
                    <span class="badge bg-danger ms-auto">{{ $notiTenants->count() }}</span>
                @endif
            </a>

            <a href="{{ route('admin.tenants.show') }}"
                class="nav-link {{ request()->routeIs('admin.tenants.*') ? 'active' : '' }}"
                data-tooltip="จัดการผู้เช่า">
                <i class="bi bi-person-badge-fill"></i>
                <span class="nav-text">จัดการผู้เช่า</span>
            </a>

            <a href="{{ route('admin.meter_readings.show') }}"
                class="nav-link {{ request()->routeIs('admin.meter_readings.*') ? 'active' : '' }}"
                data-tooltip="จดมิเตอร์">
                <i class="bi bi-speedometer"></i>
                <span class="nav-text">จดมิเตอร์น้ำ-ไฟ</span>
            </a>

            <a href="{{ route('admin.invoices.show') }}"
                class="nav-link {{ request()->routeIs('admin.invoices.show') || request()->routeIs('admin.invoices.details') ? 'active' : '' }}"
                data-tooltip="ออกใบแจ้งหนี้">
                <i class="bi bi-file-earmark-text-fill"></i>
                <span class="nav-text">ออกใบแจ้งหนี้</span>
            </a>

            <a href="{{ route('admin.payments.pendingInvoicesShow') }}"
                class="nav-link {{ request()->routeIs('admin.payments.pendingInvoicesShow') ? 'active' : '' }}"
                data-tooltip="รับชำระเงิน">
                <i class="bi bi-wallet2"></i>
                <span class="nav-text">รับชำระเงิน (Pay Bill)</span>
                @if ($notiPayments->count() > 0)
                    <span class="badge bg-danger ms-auto">{{ $notiPayments->count() }}</span>
                @endif
            </a>

            <div class="menu-category">การเงิน & บัญชี</div>

            <a href="{{ route('admin.payments.history') }}"
                class="nav-link {{ request()->routeIs('admin.payments.history') ? 'active' : '' }}"
                data-tooltip="ประวัติรับเงิน">
                <i class="bi bi-clock-history"></i>
                <span class="nav-text">ประวัติรับเงินค่าเช่า</span>
            </a>

            <a href="{{ route('admin.accounting_transactions.show') }}"
                class="nav-link {{ request()->routeIs('admin.accounting_transactions.show') || request()->routeIs('admin.accounting_transactions.create') ? 'active' : '' }}"
                data-tooltip="รายรับ-รายจ่าย">
                <i class="bi bi-journal-check"></i>
                <span class="nav-text">บันทึกรายรับ-รายจ่าย</span>
            </a>

            <a href="{{ route('admin.accounting_category.show') }}"
                class="nav-link {{ request()->routeIs('admin.accounting_category.*') ? 'active' : '' }}"
                data-tooltip="หมวดหมู่บัญชี">
                <i class="bi bi-list-stars"></i>
                <span class="nav-text">หมวดหมู่บัญชี</span>
            </a>

            <a href="{{ route('admin.tenant_expenses.show') }}"
                class="nav-link {{ request()->routeIs('admin.tenant_expenses.*') ? 'active' : '' }}"
                data-tooltip="ค่าใช้จ่ายเพิ่ม">
                <i class="bi bi-gear-wide-connected"></i>
                <span class="nav-text">ค่าใช้จ่ายของผู้เช่า</span>
            </a>

            <div class="menu-category">รายงาน & สถิติ</div>

            <a href="{{ route('admin.invoices.collectionReport') }}"
                class="nav-link {{ request()->routeIs('admin.invoices.collectionReport') ? 'active' : '' }}"
                data-tooltip="รายงานเก็บเงิน">
                <i class="bi bi-clipboard-data"></i>
                <span class="nav-text">รายงานการเก็บเงิน</span>
            </a>

            <a href="{{ route('admin.accounting_transactions.summary') }}"
                class="nav-link {{ request()->routeIs('admin.accounting_transactions.summary') ? 'active' : '' }}"
                data-tooltip="สรุปรายรับ-รายจ่าย">
                <i class="bi bi-pie-chart-fill"></i>
                <span class="nav-text">สรุปรายรับ-รายจ่าย</span>
            </a>

            <a href="{{ route('admin.accounting_transactions.income') }}"
                class="nav-link {{ request()->routeIs('admin.accounting_transactions.income') ? 'active' : '' }}"
                data-tooltip="รายงานรายรับ">
                <i class="bi bi-graph-up-arrow"></i>
                <span class="nav-text">รายงานรายรับ</span>
            </a>

            <a href="{{ route('admin.accounting_transactions.expense') }}"
                class="nav-link {{ request()->routeIs('admin.accounting_transactions.expense') ? 'active' : '' }}"
                data-tooltip="รายงานรายจ่าย">
                <i class="bi bi-graph-down-arrow"></i>
                <span class="nav-text">รายงานรายจ่าย</span>
            </a>

            <div class="menu-category">ข้อมูลหลัก</div>

            <a href="{{ route('admin.rooms.show') }}"
                class="nav-link {{ request()->routeIs('admin.rooms.show') ? 'active' : '' }}"
                data-tooltip="ข้อมูลห้องพัก">
                <i class="bi bi-door-closed-fill"></i>
                <span class="nav-text">ข้อมูลห้องพัก</span>
            </a>

            <a href="{{ route('admin.room_prices.show') }}"
                class="nav-link {{ request()->routeIs('admin.room_prices.*') ? 'active' : '' }}"
                data-tooltip="ราคาห้อง">
                <i class="bi bi-tags-fill"></i>
                <span class="nav-text">หมวดหมู่ห้องแต่ละตึก</span>
            </a>

            <a href="{{ route('admin.room_types.show') }}"
                class="nav-link {{ request()->routeIs('admin.room_types.*') ? 'active' : '' }}"
                data-tooltip="ประเภทห้อง">
                <i class="bi bi-box-fill"></i>
                <span class="nav-text">ประเภทห้อง</span>
            </a>

            <a href="{{ route('admin.building.show') }}"
                class="nav-link {{ request()->routeIs('admin.building.*') ? 'active' : '' }}"
                data-tooltip="ข้อมูลตึก">
                <i class="bi bi-buildings-fill"></i>
                <span class="nav-text">ข้อมูลตึก</span>
            </a>

            <a href="{{ route('admin.apartment.show') }}"
                class="nav-link {{ request()->routeIs('admin.apartment.*') ? 'active' : '' }}"
                data-tooltip="ตั้งค่าอพาร์ทเม้นท์">
                <i class="bi bi-shop-window"></i>
                <span class="nav-text">ตั้งค่าอพาร์ทเม้นท์</span>
            </a>

            @if (Auth::guard('admin')->user()->role === 'ผู้บริหาร')
                <div class="menu-category">ระบบหลังบ้าน</div>
                <a href="{{ route('admin.users_manage.show') }}"
                    class="nav-link {{ request()->routeIs('admin.users_manage.*') ? 'active' : '' }}">
                    <i class="bi bi-person-lock"></i>
                    <span class="nav-text">จัดการทีมงาน</span>
                </a>
            @endif

            <div style="height: 20px;"></div>
        </div>

        {{-- Sidebar Footer: User Card --}}
        <div class="sidebar-user-card">
            <div class="sidebar-user-avatar">
                {{ mb_substr(Auth::user()->firstname ?? 'A', 0, 1) }}
            </div>
            <div class="user-info-text flex-grow-1" style="min-width: 0;">
                <div class="text-white fw-semibold small text-truncate">{{ Auth::user()->firstname ?? 'ผู้ดูแล' }}</div>
                <div style="font-size:0.65rem; color:var(--text-muted);">{{ Auth::user()->role ?? 'Admin' }}</div>
            </div>
            <a class="text-danger fw-bold py-2 d-flex align-items-center" href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                <i class="bi bi-box-arrow-right me-2 fs-5"></i> 
                            </a>
        </div>
    </aside>

    {{-- === Main Content === --}}
    <div class="main-wrapper" id="mainWrapper">
        <header class="topbar no-print">
            <div class="d-flex align-items-center gap-3">
                <button class="btn d-md-none p-0" id="toggleSidebar">
                    <i class="bi bi-list fs-3"></i>
                </button>
                <div class="topbar-breadcrumb d-none d-md-flex">
                    <span class="page-title">@yield('title', 'แผงควบคุม')</span>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2 gap-sm-3">
                <a href="{{ asset('docs/manual.pdf') }}" target="_blank" class="btn btn-sm d-flex align-items-center gap-2 px-3" style="background: rgba(99, 102, 241, 0.1); color: var(--primary-color); border: 1px solid rgba(99, 102, 241, 0.2); border-radius: 10px; font-weight: 500; transition: all 0.2s;" onmouseover="this.style.background='rgba(99, 102, 241, 0.2)'" onmouseout="this.style.background='rgba(99, 102, 241, 0.1)'">
                    <i class="bi bi-book-half"></i>
                    <span class="d-none d-sm-block">คู่มือการใช้งาน</span>
                </a>
                
                <div class="topbar-datetime d-none d-md-flex">
                    <i class="bi bi-calendar3"></i>
                    <span id="topbarDate">
                        {{ \Carbon\Carbon::now()->locale('th')->isoFormat('ddd D MMM') }}
                        {{ \Carbon\Carbon::now()->year + 543 }}
                    </span>
                    <span class="text-muted">|</span>
                    <i class="bi bi-clock"></i>
                    <span id="topbarTime">{{ date('H:i') }}</span>
                </div>

                {{-- 🌟 1. ปุ่มกระดิ่งแจ้งเตือน (Notifications Dropdown) --}}
                <div class="dropdown">
                    <button class="btn border-0 position-relative rounded-circle d-flex align-items-center justify-content-center" type="button" id="notiDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="width: 42px; height: 42px; background: #f8fafc; transition: 0.2s;">
                        <i class="bi bi-bell-fill fs-5" style="color: #64748b;"></i>
                        @if($totalNotiCount > 0)
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-white" style="font-size: 0.65rem;">
                                {{ $totalNotiCount > 99 ? '99+' : $totalNotiCount }}
                            </span>
                        @endif
                    </button>
                    
                    <ul class="dropdown-menu dropdown-menu-end custom-dropdown-menu shadow p-0" aria-labelledby="notiDropdown" style="width: 320px; max-height: 400px; overflow-y: auto;">
                        <li class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-dark mb-0">การแจ้งเตือน</span>
                            <span class="badge bg-primary rounded-pill">{{ $totalNotiCount }} รายการ</span>
                        </li>
                        
                        <div class="list-group list-group-flush rounded-0">
                            {{-- แจ้งซ่อม --}}
                            @if($notiMaintenance->count() > 0)
                                <div class="px-3 py-2 bg-light border-bottom border-top small fw-bold text-warning"><i class="bi bi-tools me-1"></i> แจ้งซ่อม ({{ $notiMaintenance->count() }})</div>
                                @foreach($notiMaintenance->take(10) as $maint)
                                    <a href="{{ route('admin.maintenance.index') }}" class="list-group-item list-group-item-action py-2">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1 small fw-bold text-dark">ห้อง {{ $maint->room->room_number ?? '-' }}</h6>
                                            <small class="text-muted" style="font-size: 0.7rem;">{{ $maint->created_at->diffForHumans() }}</small>
                                        </div>
                                        <p class="mb-0 small text-muted text-truncate">{{ $maint->title }}</p>
                                    </a>
                                @endforeach
                            @endif

                            {{-- ผู้เช่าใหม่ --}}
                            @if($notiTenants->count() > 0)
                                <div class="px-3 py-2 bg-light border-bottom border-top small fw-bold text-primary"><i class="bi bi-person-plus-fill me-1"></i> ลงทะเบียนใหม่ ({{ $notiTenants->count() }})</div>
                                @foreach($notiTenants->take(10) as $t)
                                    <a href="{{ route('admin.registrations.show') }}" class="list-group-item list-group-item-action py-2">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1 small fw-bold text-dark">{{ $t->first_name }} {{ $t->last_name }}</h6>
                                            <small class="text-muted" style="font-size: 0.7rem;">{{ $t->created_at->diffForHumans() }}</small>
                                        </div>
                                        <p class="mb-0 small text-muted">รออนุมัติเข้าห้อง</p>
                                    </a>
                                @endforeach
                            @endif

                            {{-- สลิปจ่ายเงิน --}}
                            @if($notiPayments->count() > 0)
                                <div class="px-3 py-2 bg-light border-bottom border-top small fw-bold text-success"><i class="bi bi-cash-coin me-1"></i> แจ้งโอนเงิน ({{ $notiPayments->count() }})</div>
                                @foreach($notiPayments->take(10) as $p)
                                    <a href="{{ route('admin.payments.pendingInvoicesShow') }}" class="list-group-item list-group-item-action py-2">
                                        <div class="d-flex w-100 justify-content-between">
                                            @php
                                                // หาเลขห้องจาก Invoice หรือ Accounting (เงินมัดจำ)
                                                $pRoom = $p->invoice->tenant->room->room_number ?? $p->accounting_transactions->first()->tenant->room->room_number ?? '-';
                                            @endphp
                                            <h6 class="mb-1 small fw-bold text-dark">ห้อง {{ $pRoom }}</h6>
                                            <small class="text-muted" style="font-size: 0.7rem;">{{ $p->created_at->diffForHumans() }}</small>
                                        </div>
                                        <p class="mb-0 small text-muted">ยอดโอน: {{ number_format($p->amount_paid, 2) }} ฿</p>
                                    </a>
                                @endforeach
                            @endif

                            @if($totalNotiCount == 0)
                                <div class="text-center py-4 text-muted small">
                                    <i class="bi bi-bell-slash fs-4 d-block mb-2"></i>
                                    ไม่มีการแจ้งเตือนใหม่
                                </div>
                            @endif
                        </div>
                    </ul>
                </div>

                {{-- 🌟 2. ปุ่มเมนูโปรไฟล์ผู้ใช้ (User Dropdown) --}}
                <div class="dropdown">
                    <div class="topbar-user" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="text-end d-none d-sm-block">
                            <div class="topbar-user-name">{{ Auth::user()->firstname ?? 'ผู้ดูแลระบบ' }}</div>
                            <div class="topbar-user-role">{{ Auth::user()->role ?? 'Admin' }}</div>
                        </div>
                        <div class="topbar-user-avatar">
                            {{ mb_substr(Auth::user()->firstname ?? 'A', 0, 1) }}
                        </div>
                        <i class="bi bi-chevron-down text-muted small ms-1 d-none d-sm-block"></i>
                    </div>
                    
                    <ul class="dropdown-menu dropdown-menu-end custom-dropdown-menu shadow mt-2" aria-labelledby="userDropdown">
                        <li><h6 class="dropdown-header text-dark fw-bold">เมนูจัดการ</h6></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger fw-bold py-2 d-flex align-items-center" href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                <i class="bi bi-box-arrow-right me-2 fs-5"></i> ออกจากระบบ
                            </a>
                        </li>
                    </ul>
                </div>

            </div>
        </header>

        <div class="content-body">
            @yield('content')
        </div>

        <footer class="mt-auto py-3 text-center no-print">
            <span class="text-muted">&copy; {{ date('Y') }}
                {{ $apartmentInfo->name ?? 'ชื่ออพาร์ทเม้นท์' }}</span>
            made by Thanakorn Srisawat And Natthaphol SaeEung
        </footer>
    </div>

    <form id="logout-form" action="{{ route('admin.logout') }}" method="POST" class="d-none no-print">
        @csrf
    </form>

    {{-- === Scripts === --}}
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        // CSRF Token Setup
        $.ajaxSetup({
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        });
    </script>

    <script>
        const sidebar = document.getElementById('sidebar');
        const mainWrapper = document.getElementById('mainWrapper');
        const btnCollapse = document.getElementById('btnCollapse');
        const collapseIcon = document.getElementById('collapseIcon');
        const toggleSidebar = document.getElementById('toggleSidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const sidebarArea = document.getElementById('sidebarScrollArea');

        // 1. จำสถานะย่อ/ขยาย Sidebar ข้ามหน้า
        const collapsedState = localStorage.getItem('sidebarCollapsed');
        if (collapsedState === 'true') {
            sidebar.classList.add('collapsed');
            mainWrapper.classList.add('expanded');
            collapseIcon.classList.replace('bi-chevron-left', 'bi-chevron-right');
        }

        btnCollapse?.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            mainWrapper.classList.toggle('expanded');
            localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));

            if (sidebar.classList.contains('collapsed')) {
                collapseIcon.classList.replace('bi-chevron-left', 'bi-chevron-right');
            } else {
                collapseIcon.classList.replace('bi-chevron-right', 'bi-chevron-left');
            }
        });

        // 2. Mobile Sidebar Toggle
        toggleSidebar?.addEventListener('click', function() {
            sidebar.classList.toggle('active');
            sidebarOverlay.classList.toggle('active');
        });
        sidebarOverlay?.addEventListener('click', function() {
            sidebar.classList.remove('active');
            sidebarOverlay.classList.remove('active');
        });

        // 3. Live Clock (อัปเดตเวลาบน Topbar)
        function updateClock() {
            const now = new Date();
            const h = String(now.getHours()).padStart(2, '0');
            const m = String(now.getMinutes()).padStart(2, '0');
            const el = document.getElementById('topbarTime');
            if (el) el.textContent = h + ':' + m;
        }
        setInterval(updateClock, 30000);

        // 4. ระบบจำตำแหน่ง Scroll เฉพาะ Sidebar
        window.addEventListener("beforeunload", function() {
            if (sidebarArea) {
                localStorage.setItem('sidebarScrollPosition', sidebarArea.scrollTop);
            }
        });

        document.addEventListener("DOMContentLoaded", function() {
            const sidebarPos = localStorage.getItem('sidebarScrollPosition');
            if (sidebarPos && sidebarArea) {
                sidebarArea.scrollTop = parseInt(sidebarPos, 10);
            }
        });
    </script>

    <script>
        // SweetAlert Server-side Messages
        @if ($errors->any())
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด!',
                html: "{!! implode('<br>', $errors->all()) !!}",
                confirmButtonColor: '#6366f1'
            });
        @endif

        @if (session('error'))
            Swal.fire({
                icon: 'error',
                title: 'แจ้งเตือน',
                text: "{{ session('error') }}",
                confirmButtonColor: '#6366f1'
            });
        @endif

        @if (session('success'))
            Swal.fire({
                icon: 'success',
                title: 'สำเร็จ!',
                text: "{{ session('success') }}",
                timer: 2000,
                showConfirmButton: false
            });
        @endif

        // 🌟 5. Laravel Echo & Real-time Notifications (แบบรวมมิตร)
        document.addEventListener('DOMContentLoaded', function() {
            if (!window.Echo) return;
            
            // แจ้งซ่อม
            window.Echo.channel('admin-notifications')
                .listen('.new.maintenance.request', function(data) {
                    showAdminToast('แจ้งซ่อมใหม่', data.message || data.title, data.roomNumber, 'bi-tools', '#f59e0b', '{{ route('admin.maintenance.index') }}');
                });
                
            // (เตรียมเผื่อไว้) ผู้เช่าลงทะเบียนใหม่
            window.Echo.channel('admin-notifications')
                .listen('.new.tenant.request', function(data) {
                    showAdminToast('ลงทะเบียนเช่าใหม่', data.message, '-', 'bi-person-plus-fill', '#3b82f6', '{{ route('admin.registrations.show') }}');
                });

            // (เตรียมเผื่อไว้) แจ้งโอนเงิน
            window.Echo.channel('admin-notifications')
                .listen('.new.payment.request', function(data) {
                    showAdminToast('แจ้งโอนเงิน', data.message, data.roomNumber, 'bi-cash-coin', '#10b981', '{{ route('admin.payments.pendingInvoicesShow') }}');
                });
        });

        // ฟังก์ชันลอยแจ้งเตือนมุมขวา (Dynamic Toast)
        function showAdminToast(headerTitle, messageText, roomStr, iconClass, colorHex, linkUrl) {
            var id = 'amt_' + Date.now();
            var d = document.createElement('div');
            d.id = id;
            d.style.cssText =
                `position:fixed;top:80px;right:24px;z-index:99999;background:#fff;border-left:5px solid ${colorHex};border-radius:10px;box-shadow:0 8px 32px rgba(0,0,0,.18);padding:14px 18px;min-width:280px;max-width:360px;display:flex;gap:12px;align-items:flex-start;animation:amtIn .35s ease;cursor:pointer;font-family:inherit`;
            
            var msg = _e(messageText || '');
            var room = _e(roomStr || '-');
            
            d.innerHTML = `<div style="font-size:1.6rem;line-height:1;color:${colorHex}"><i class="bi ${iconClass}"></i></div>` +
                '<div style="flex:1">' +
                `<div style="font-weight:700;color:${colorHex};font-size:.9rem;margin-bottom:2px">${headerTitle}</div>` +
                '<div style="color:#1e293b;font-size:.88rem">' + msg + '</div>' +
                '<div style="color:#64748b;font-size:.78rem;margin-top:3px">ห้อง ' + room + '</div>' +
                '</div>' +
                '<button onclick="event.stopPropagation(); document.getElementById(\'' + id +
                '\').remove()" style="background:none;border:none;font-size:1.1rem;cursor:pointer;color:#94a3b8;padding:0;line-height:1" title="ปิด">✕</button>';
            
            d.addEventListener('click', function(e) {
                if (e.target.tagName !== 'BUTTON') {
                    window.location.href = linkUrl;
                }
            });
            
            document.body.appendChild(d);
            
            setTimeout(function() {
                if (document.getElementById(id)) {
                    d.style.animation = 'amtOut .4s ease forwards';
                    setTimeout(function() {
                        var el = document.getElementById(id);
                        if (el) el.remove();
                    }, 400);
                }
            }, 30000); // ลอยค้าง 30 วินาที
        }

        function _e(s) {
            return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }
    </script>

    @stack('scripts')
</body>

</html>