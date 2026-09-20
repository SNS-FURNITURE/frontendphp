<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SNS Furniture</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}?v=2" sizes="any">
    <link rel="icon" type="image/png" href="{{ asset('sns-logo.png') }}?v=2">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}?v=2">
    <link rel="apple-touch-icon" href="{{ asset('sns-logo.png') }}?v=2">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    <style>
        :root {
            --purple: #6c5ce7;
            --purple-mid: #452F80;
            --purple-dark: #0d0b21;
            --sidebar: #100e24;
            --panel: #16132e;
            --border: #2a2550;
            --text: #f4f2ff;
            --muted: #9b94b8;
            --red: #E13B30;
            --accent: #a78bfa;
        }
        * { box-sizing: border-box; }
        [x-cloak] { display: none !important; }
        html, body {
            height: 100%;
            margin: 0;
        }
        body {
            font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
            background: var(--purple-dark);
            color: var(--text);
            overflow: hidden;
        }
        a { color: var(--accent); text-decoration: none; }
        a:hover { text-decoration: underline; }
        .shell {
            display: flex;
            height: 100%;
            min-height: 0;
            overflow: hidden;
        }
        .sidebar {
            width: 240px;
            background: var(--sidebar);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            padding: 1.25rem 0.9rem;
            flex-shrink: 0;
            height: 100%;
            max-height: 100%;
            overflow-x: hidden;
            overflow-y: auto;
            overscroll-behavior: contain;
        }
        .brand-block { padding: 0 0.5rem 1.25rem; border-bottom: 1px solid var(--border); margin-bottom: 1rem; }
        .brand-name { color: #ff6b6b; font-weight: 800; font-size: 1.05rem; }
        .brand-sub { color: var(--muted); font-size: 0.75rem; margin-top: 0.15rem; }
        .nav-section { color: var(--muted); font-size: 0.65rem; letter-spacing: 0.12em; text-transform: uppercase; margin: 0.85rem 0.5rem 0.4rem; }
        .nav-link {
            display: block;
            padding: 0.55rem 0.75rem;
            border-radius: 10px;
            color: #d8d2f0;
            margin-bottom: 0.25rem;
            font-size: 0.92rem;
        }
        .nav-link:hover { background: #1c1836; text-decoration: none; }
        .nav-link.active { background: #3d3470; color: #fff; font-weight: 600; }
        .sidebar-foot { margin-top: auto; padding: 0.75rem 0.5rem 0; border-top: 1px solid var(--border); flex-shrink: 0; }
        .main {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
            min-height: 0;
            height: 100%;
            overflow: hidden;
            background: var(--purple-dark);
        }
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            padding: 0.9rem 1.5rem;
            border-bottom: 1px solid var(--border);
            background: rgba(16, 14, 36, 0.85);
            flex-shrink: 0;
        }
        .topbar-user { font-weight: 600; }
        .topbar-actions { display: flex; align-items: center; gap: 0.65rem; }
        .role-badge {
            display: inline-block;
            padding: 0.2rem 0.65rem;
            border-radius: 999px;
            background: #3d3470;
            color: #dcd6ff;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        .content {
            padding: 1.5rem;
            flex: 1;
            min-height: 0;
            overflow-y: auto;
            overscroll-behavior: contain;
            background: var(--purple-dark);
        }
        .content-wide { max-width: none; padding-top: 0; }
        .card {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 1.25rem;
        }
        .btn {
            display: inline-block;
            border: 0;
            border-radius: 10px;
            padding: 0.55rem 0.95rem;
            background: var(--accent);
            color: #120f24;
            font-weight: 700;
            cursor: pointer;
            font-size: 0.9rem;
        }
        .btn:hover { filter: brightness(1.05); text-decoration: none; }
        .btn.secondary { background: var(--purple); color: #fff; }
        .btn.ghost { background: transparent; color: #dcd6ff; border: 1px solid var(--border); }
        .btn.danger { background: var(--red); color: #fff; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data th, table.data td { text-align: left; padding: 0.7rem 0.45rem; border-bottom: 1px solid var(--border); font-size: 0.92rem; }
        table.data th { color: var(--muted); font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.04em; }
        .toast-host {
            position: fixed;
            top: 1.25rem;
            right: 1.25rem;
            z-index: 12000;
            display: flex;
            flex-direction: column;
            gap: 0.65rem;
            width: min(22rem, calc(100vw - 2rem));
            pointer-events: none;
        }
        .toast {
            pointer-events: auto;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 0.9rem 1rem;
            border-radius: 12px;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.45), 0 0 0 1px rgba(255, 255, 255, 0.06);
            backdrop-filter: blur(10px);
            font-size: 0.92rem;
            line-height: 1.4;
            animation: toast-in 0.28s ease-out;
        }
        .toast-success {
            background: rgba(20, 53, 42, 0.96);
            color: #8dffc1;
            border: 1px solid #1f5a44;
        }
        .toast-error {
            background: rgba(58, 21, 21, 0.96);
            color: #ffb4b4;
            border: 1px solid #6b2a2a;
        }
        .toast-body { flex: 1; min-width: 0; }
        .toast-close {
            flex-shrink: 0;
            background: transparent;
            border: 0;
            color: inherit;
            opacity: 0.7;
            cursor: pointer;
            font-size: 1.1rem;
            line-height: 1;
            padding: 0;
        }
        .toast-close:hover { opacity: 1; }
        @keyframes toast-in {
            from { opacity: 0; transform: translateY(-0.6rem) scale(0.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        @media (max-width: 640px) {
            .toast-host {
                top: auto;
                bottom: 1rem;
                left: 50%;
                right: auto;
                transform: translateX(-50%);
                width: min(22rem, calc(100vw - 1.5rem));
            }
        }
        label { display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.25rem; color: #d8d2f0; }
        input, select, textarea {
            width: 100%;
            padding: 0.55rem 0.7rem;
            border: 1px solid var(--border);
            border-radius: 10px;
            margin-bottom: 0.85rem;
            background: #0f0d1f;
            color: var(--text);
        }
        input[type="date"],
        input[type="datetime-local"],
        input[type="time"],
        input[type="month"],
        input[type="week"] {
            color-scheme: dark;
            min-height: 2.6rem;
            padding-right: 0.55rem;
        }
        input[type="date"]::-webkit-calendar-picker-indicator,
        input[type="datetime-local"]::-webkit-calendar-picker-indicator,
        input[type="time"]::-webkit-calendar-picker-indicator,
        input[type="month"]::-webkit-calendar-picker-indicator,
        input[type="week"]::-webkit-calendar-picker-indicator {
            cursor: pointer;
            opacity: 1;
            width: 1.15rem;
            height: 1.15rem;
            padding: 0.2rem;
            margin-left: 0.35rem;
            border-radius: 6px;
            background-color: #f97316;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23ffffff' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='3' y='4' width='18' height='18' rx='2'/%3E%3Cline x1='16' y1='2' x2='16' y2='6'/%3E%3Cline x1='8' y1='2' x2='8' y2='6'/%3E%3Cline x1='3' y1='10' x2='21' y2='10'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: center;
            background-size: 0.95rem 0.95rem;
            filter: none;
        }
        input[type="date"]::-webkit-calendar-picker-indicator:hover,
        input[type="datetime-local"]::-webkit-calendar-picker-indicator:hover,
        input[type="time"]::-webkit-calendar-picker-indicator:hover,
        input[type="month"]::-webkit-calendar-picker-indicator:hover,
        input[type="week"]::-webkit-calendar-picker-indicator:hover {
            background-color: #fb923c;
        }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 0.85rem; }
        .muted { color: var(--muted); font-size: 0.85rem; }
        .badge {
            display: inline-block;
            padding: 0.15rem 0.5rem;
            border-radius: 999px;
            background: #2a2550;
            color: var(--accent);
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        .page-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; margin-bottom: 1.25rem; flex-wrap: wrap; }
        .page-head h1 { margin: 0; font-size: 1.75rem; font-weight: 700; }
        .toolbar { display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; }
        .table-wrap { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .nav-toggle {
            display: none;
            border: 1px solid var(--border);
            background: #1c1836;
            color: #fff;
            border-radius: 10px;
            width: 2.5rem;
            height: 2.5rem;
            padding: 0;
            cursor: pointer;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            gap: 5px;
            flex-shrink: 0;
        }
        .nav-toggle-bar {
            display: block;
            width: 1.15rem;
            height: 2px;
            background: #f4f2ff;
            border-radius: 2px;
        }
        .sidebar-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(8, 6, 20, 0.55);
            z-index: 40;
        }
        .sidebar-backdrop.is-open { display: block; }
        @media (max-width: 1100px) {
            .grid-2 { grid-template-columns: 1fr; }
        }
        @media (max-width: 900px) {
            body { overflow: auto; height: auto; }
            .shell { flex-direction: column; height: auto; min-height: 100dvh; overflow: visible; }
            .nav-toggle { display: inline-flex; }
            .sidebar {
                position: fixed;
                top: 0;
                left: 0;
                z-index: 50;
                width: min(86vw, 300px);
                height: 100dvh;
                max-height: 100dvh;
                transform: translateX(-105%);
                transition: transform 0.2s ease;
                box-shadow: 12px 0 40px rgba(0,0,0,0.35);
                overflow-y: auto;
            }
            .sidebar.is-open { transform: translateX(0); }
            .main { height: auto; overflow: visible; width: 100%; }
            .topbar {
                padding: 0.75rem 1rem;
                position: sticky;
                top: 0;
                z-index: 30;
                backdrop-filter: blur(8px);
            }
            .topbar-user {
                font-size: 0.85rem;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
                max-width: 42vw;
            }
            .content { padding: 1rem; overflow: visible; min-height: 0; }
            .page-head h1 { font-size: 1.35rem; }
            .card { padding: 1rem; border-radius: 12px; overflow-x: auto; }
            .btn, .btn.ghost { min-height: 2.5rem; }
            table.data { min-width: 640px; }
            table.data th, table.data td { font-size: 0.85rem; padding: 0.55rem 0.35rem; }
            .role-badge { font-size: 0.65rem; }
        }
        @media (max-width: 560px) {
            .topbar-actions .btn { padding: 0.45rem 0.65rem; font-size: 0.8rem; }
            .content { padding: 0.75rem; }
            .page-head { gap: 0.75rem; }
            .page-head .btn { width: 100%; text-align: center; }
        }
        @media print {
            .sidebar, .topbar, .no-print, .logout-modal, .nav-toggle, .sidebar-backdrop, .toast-host, .sidebar-foot {
                display: none !important;
            }
            .shell, .main, .content {
                display: block !important;
                height: auto !important;
                overflow: visible !important;
                padding: 0 !important;
                margin: 0 !important;
                background: #fff !important;
            }
            body { background: #fff !important; color: #111 !important; }
        }
        .logout-modal {
            position: fixed;
            inset: 0;
            z-index: 1000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            background: rgba(8, 6, 20, 0.72);
            backdrop-filter: blur(4px);
        }
        .logout-modal.is-open { display: flex !important; }
        .logout-modal[hidden] { display: none !important; }
        .logout-dialog {
            width: 100%;
            max-width: 380px;
            background: #1a1634;
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 1.5rem;
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.45);
            animation: logout-pop 0.18s ease-out;
        }
        @keyframes logout-pop {
            from { opacity: 0; transform: translateY(8px) scale(0.98); }
            to { opacity: 1; transform: none; }
        }
        .logout-dialog h3 {
            margin: 0 0 0.4rem;
            font-size: 1.2rem;
            color: #fff;
        }
        .logout-dialog p {
            margin: 0 0 1.25rem;
            color: var(--muted);
            font-size: 0.92rem;
            line-height: 1.45;
        }
        .logout-actions {
            display: flex;
            gap: 0.65rem;
            justify-content: flex-end;
        }
        .logout-actions .btn { min-width: 96px; }
    </style>
    @stack('styles')
</head>
<body>
@auth
@php
    $roleLabel = auth()->user()->roles
        ->pluck('name')
        ->filter()
        ->map(fn ($n) => str_replace('_', ' ', (string) $n))
        ->map(fn ($n) => ucwords(strtolower($n)))
        ->unique()
        ->values()
        ->join(' · ') ?: 'No role';
    $username = auth()->user()->username ?: explode('@', auth()->user()->email)[0];
@endphp
<div class="shell">
    <div class="sidebar-backdrop no-print" id="sidebar-backdrop" hidden></div>
    <aside class="sidebar no-print" id="app-sidebar">
        <div class="brand-block">
            <div style="display:flex;align-items:center;gap:0.6rem">
                <img src="{{ asset('sns-logo.png') }}" alt="SNS" style="height:36px;width:auto;border-radius:8px;background:#fff;padding:2px">
                <div>
                    <div class="brand-name">SNS Furniture</div>
                    <div class="brand-sub">Work boards</div>
                </div>
            </div>
        </div>
        @if (auth()->user()->hasPermission('finance', 'view') || auth()->user()->hasPermission('finance', 'create') || auth()->user()->canViewFunding() || auth()->user()->canViewAllocations())
            <div class="nav-section">Finance</div>
            @if (auth()->user()->hasPermission('finance', 'view') || auth()->user()->hasPermission('finance', 'create'))
                <a class="nav-link {{ request()->routeIs('invoices.*') ? 'active' : '' }}" href="{{ route('invoices.index') }}">Invoices</a>
            @endif
            @if (auth()->user()->hasPermission('finance', 'view'))
                <a class="nav-link {{ request()->routeIs('payments.*') ? 'active' : '' }}" href="{{ route('payments.index') }}">Payments</a>
            @endif
            @if (auth()->user()->canViewFunding())
                <a class="nav-link {{ request()->routeIs('funding.*') ? 'active' : '' }}" href="{{ route('funding.index') }}">Funding</a>
            @endif
            @if (auth()->user()->canViewAllocations())
                <a class="nav-link {{ request()->routeIs('finance.allocations') ? 'active' : '' }}" href="{{ route('finance.allocations') }}">Allocations</a>
            @endif
        @endif
        @if (auth()->user()->canViewSales())
            <div class="nav-section">Sales</div>
            <a class="nav-link {{ request()->routeIs('sales.customers*') ? 'active' : '' }}" href="{{ route('sales.customers') }}">Customers</a>
            <a class="nav-link {{ request()->routeIs('orders.requests*') || request()->routeIs('sales.orders*') ? 'active' : '' }}" href="{{ route('orders.requests') }}">Order requests</a>
            <a class="nav-link {{ request()->routeIs('sales.quota') ? 'active' : '' }}" href="{{ route('sales.quota') }}">Quota</a>
        @endif
        @if (auth()->user()->canViewInventory())
            <div class="nav-section">Inventory</div>
            <a class="nav-link {{ request()->routeIs('inventory.items*') ? 'active' : '' }}" href="{{ route('inventory.items') }}">Items</a>
            <a class="nav-link {{ request()->routeIs('inventory.stock') ? 'active' : '' }}" href="{{ route('inventory.stock') }}">Stock</a>
            <a class="nav-link {{ request()->routeIs('inventory.low-stock') ? 'active' : '' }}" href="{{ route('inventory.low-stock') }}">Low stock</a>
            <a class="nav-link {{ request()->routeIs('inventory.movements*') ? 'active' : '' }}" href="{{ route('inventory.movements') }}">Movements</a>
            <a class="nav-link {{ request()->routeIs('material-requests.*') || request()->routeIs('inventory.material-requests') ? 'active' : '' }}" href="{{ route('material-requests.index') }}">Material requests</a>
            @if (auth()->user()->canViewDeliveries())
                <a class="nav-link {{ request()->routeIs('inventory.outbound') ? 'active' : '' }}" href="{{ route('inventory.outbound') }}">Outbound</a>
            @endif
        @endif
        @if (auth()->user()->canViewProduction())
            <div class="nav-section">Production</div>
            <a class="nav-link {{ request()->routeIs('production.index') || request()->routeIs('manufacturing.production-orders') ? 'active' : '' }}" href="{{ route('production.index') }}">Orders</a>
            <a class="nav-link {{ request()->routeIs('manufacturing.boms*') ? 'active' : '' }}" href="{{ route('manufacturing.boms') }}">BOMs</a>
        @endif
        @if (auth()->user()->canViewDeliveries())
            <div class="nav-section">Ops</div>
            <a class="nav-link {{ request()->routeIs('production.deliveries*') ? 'active' : '' }}" href="{{ route('production.deliveries') }}">Deliveries</a>
        @endif
        @if (auth()->user()->canViewDesigns() || auth()->user()->canViewMachinery() || auth()->user()->canViewProcurement())
            <div class="nav-section">Design &amp; make</div>
            @if (auth()->user()->canViewDesigns())
                <a class="nav-link {{ request()->routeIs('designs.*') ? 'active' : '' }}" href="{{ route('designs.index') }}">Designs</a>
            @endif
            @if (auth()->user()->canViewMachinery())
                <a class="nav-link {{ request()->routeIs('machinery.*') ? 'active' : '' }}" href="{{ route('machinery.index') }}">Machinery</a>
            @endif
            @if (auth()->user()->canViewProcurement())
                <a class="nav-link {{ request()->routeIs('procurement.*') ? 'active' : '' }}" href="{{ route('procurement.index') }}">Procurement</a>
            @endif
        @endif
        @if (auth()->user()->canViewProjects() || auth()->user()->canViewTasks())
            <div class="nav-section">Tasks</div>
            @if (auth()->user()->canViewProjects())
                <a class="nav-link {{ request()->routeIs('projects.*') ? 'active' : '' }}" href="{{ route('projects.index') }}">Projects</a>
            @endif
            @if (auth()->user()->canViewTasks())
                <a class="nav-link {{ request()->routeIs('tasks.*') ? 'active' : '' }}" href="{{ route('tasks.index') }}">Priority tasks</a>
            @endif
        @endif
        @if (auth()->user()->canPostReport() || auth()->user()->canViewAllReports())
            <div class="nav-section">Reports</div>
            @if (auth()->user()->canPostReport())
                <a class="nav-link {{ request()->routeIs('reports.index') || request()->routeIs('reports.store') ? 'active' : '' }}" href="{{ route('reports.index') }}">Post report</a>
            @endif
            @if (auth()->user()->canViewAllReports())
                <a class="nav-link {{ request()->routeIs('reports.library') ? 'active' : '' }}" href="{{ route('reports.library') }}">All reports</a>
            @endif
        @endif
        @if (auth()->user()->canViewBoards())
            <div class="nav-section">Boards</div>
            <a class="nav-link {{ request()->routeIs('boards.*') ? 'active' : '' }}" href="{{ route('boards.index') }}">Work boards</a>
        @endif
        @if (method_exists(auth()->user(), 'canViewHr') && auth()->user()->canViewHr() && \Illuminate\Support\Facades\Route::has('hr.employees'))
            <div class="nav-section">People</div>
            <a class="nav-link {{ request()->routeIs('hr.employees*') ? 'active' : '' }}" href="{{ route('hr.employees') }}">Employees</a>
            @if (\Illuminate\Support\Facades\Route::has('hr.attendance'))
                <a class="nav-link {{ request()->routeIs('hr.attendance*') ? 'active' : '' }}" href="{{ route('hr.attendance') }}">Attendance</a>
            @endif
            @if (\Illuminate\Support\Facades\Route::has('hr.leave'))
                <a class="nav-link {{ request()->routeIs('hr.leave*') ? 'active' : '' }}" href="{{ route('hr.leave') }}">Leave</a>
            @endif
            @if (\Illuminate\Support\Facades\Route::has('hr.org-chart'))
                <a class="nav-link {{ request()->routeIs('hr.org-chart') ? 'active' : '' }}" href="{{ route('hr.org-chart') }}">Org chart</a>
            @endif
        @endif
        @if (method_exists(auth()->user(), 'canViewPayroll') && auth()->user()->canViewPayroll() && \Illuminate\Support\Facades\Route::has('finance.payroll'))
            <a class="nav-link {{ request()->routeIs('finance.payroll*') ? 'active' : '' }}" href="{{ route('finance.payroll') }}">Payroll</a>
        @endif
        @if (method_exists(auth()->user(), 'canViewLeads') && auth()->user()->canViewLeads() && \Illuminate\Support\Facades\Route::has('leads.index'))
            <div class="nav-section">CRM</div>
            <a class="nav-link {{ request()->routeIs('leads.*') ? 'active' : '' }}" href="{{ route('leads.index') }}">Leads</a>
            @if (method_exists(auth()->user(), 'canViewDeals') && auth()->user()->canViewDeals() && \Illuminate\Support\Facades\Route::has('deals.index'))
                <a class="nav-link {{ request()->routeIs('deals.*') ? 'active' : '' }}" href="{{ route('deals.index') }}">Deals</a>
            @endif
        @endif
        @if (auth()->user()->isAdmin())
            <div class="nav-section">Admin</div>
            <a class="nav-link {{ request()->routeIs('admin.users*') ? 'active' : '' }}" href="{{ route('admin.users') }}">Users</a>
            <a class="nav-link {{ request()->routeIs('admin.audit') ? 'active' : '' }}" href="{{ route('admin.audit') }}">Audit log</a>
        @endif
        <div class="nav-section">Settings</div>
        <a class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}" href="{{ route('profile.edit') }}">My Profile</a>
        <div class="sidebar-foot">
            <button class="btn ghost" type="button" style="width:100%" data-logout-open>Sign out</button>
        </div>
    </aside>
    <div class="main">
        <header class="topbar no-print">
            <div style="display:flex;align-items:center;gap:0.65rem;min-width:0;flex:1">
                <button type="button" class="nav-toggle" id="nav-toggle" aria-label="Open menu" aria-controls="app-sidebar" aria-expanded="false">
                    <span class="nav-toggle-bar" aria-hidden="true"></span>
                    <span class="nav-toggle-bar" aria-hidden="true"></span>
                    <span class="nav-toggle-bar" aria-hidden="true"></span>
                </button>
                <div class="topbar-user">{{ '@'.$username }} {{ auth()->user()->full_name }}</div>
            </div>
            <div class="topbar-actions">
                <span class="role-badge">{{ $roleLabel }}</span>
                <button class="btn ghost" type="button" data-logout-open>Logout</button>
            </div>
        </header>
        <div class="content @yield('content_class')">
            @yield('content')
        </div>
    </div>
</div>

@php
    $erpToasts = [];
    if (session('status')) {
        $erpToasts[] = ['type' => 'success', 'text' => (string) session('status')];
    }
    if (isset($errors) && $errors->any()) {
        foreach ($errors->all() as $error) {
            $erpToasts[] = ['type' => 'error', 'text' => (string) $error];
        }
    }
@endphp
<div
    class="toast-host no-print"
    x-data="erpToasts(@js($erpToasts))"
    x-cloak
    aria-live="polite"
    aria-relevant="additions"
>
    <template x-for="t in toasts" :key="t.id">
        <div
            class="toast"
            :class="t.type === 'error' ? 'toast-error' : 'toast-success'"
            x-show="t.visible"
            x-transition.opacity.duration.200ms
            role="status"
        >
            <div class="toast-body" x-text="t.text"></div>
            <button type="button" class="toast-close" @click="dismiss(t.id)" aria-label="Dismiss">&times;</button>
        </div>
    </template>
</div>

<div class="logout-modal no-print" id="logout-modal" role="dialog" aria-modal="true" aria-labelledby="logout-title" hidden>
    <div class="logout-dialog">
        <h3 id="logout-title">Sign out?</h3>
        <p>You will need to sign in again to access invoices and payments.</p>
        <div class="logout-actions">
            <button type="button" class="btn ghost" data-logout-cancel>Cancel</button>
            <form method="POST" action="{{ route('logout') }}" style="margin:0">
                @csrf
                <button type="submit" class="btn danger">Sign out</button>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    var SIDEBAR_SCROLL_KEY = 'sns.sidebar.scrollTop';
    var sidebar = document.getElementById('app-sidebar') || document.querySelector('.sidebar');
    var backdrop = document.getElementById('sidebar-backdrop');
    var toggle = document.getElementById('nav-toggle');

    function closeSidebar() {
        if (sidebar) sidebar.classList.remove('is-open');
        if (backdrop) {
            backdrop.classList.remove('is-open');
            backdrop.hidden = true;
        }
        if (toggle) toggle.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
    }
    function openSidebar() {
        if (sidebar) sidebar.classList.add('is-open');
        if (backdrop) {
            backdrop.hidden = false;
            backdrop.classList.add('is-open');
        }
        if (toggle) toggle.setAttribute('aria-expanded', 'true');
        if (window.matchMedia('(max-width: 900px)').matches) {
            document.body.style.overflow = 'hidden';
        }
    }
    if (toggle) {
        toggle.addEventListener('click', function () {
            if (sidebar && sidebar.classList.contains('is-open')) closeSidebar();
            else openSidebar();
        });
    }
    if (backdrop) backdrop.addEventListener('click', closeSidebar);
    if (sidebar) {
        sidebar.querySelectorAll('a.nav-link').forEach(function (link) {
            link.addEventListener('click', function () {
                if (window.matchMedia('(max-width: 900px)').matches) closeSidebar();
            });
        });
    }
    window.addEventListener('resize', function () {
        if (!window.matchMedia('(max-width: 900px)').matches) closeSidebar();
    });

    if (sidebar) {
        var saved = sessionStorage.getItem(SIDEBAR_SCROLL_KEY);
        if (saved !== null) {
            var restore = function () {
                sidebar.scrollTop = parseInt(saved, 10) || 0;
            };
            restore();
            requestAnimationFrame(restore);
        }
        var persistSidebarScroll = function () {
            sessionStorage.setItem(SIDEBAR_SCROLL_KEY, String(sidebar.scrollTop));
        };
        sidebar.addEventListener('scroll', persistSidebarScroll, { passive: true });
        sidebar.querySelectorAll('a.nav-link').forEach(function (link) {
            link.addEventListener('click', persistSidebarScroll);
        });
    }

    var IDLE_MS = 30 * 60 * 1000;
    var timer;
    function reset() {
        clearTimeout(timer);
        timer = setTimeout(function () {
            window.location.href = @json(route('logout.idle'));
        }, IDLE_MS);
    }
    ['mousemove','mousedown','keydown','touchstart','scroll','click'].forEach(function (ev) {
        document.addEventListener(ev, reset, { passive: true });
    });
    reset();

    var modal = document.getElementById('logout-modal');
    if (!modal) return;
    function openLogout() {
        modal.hidden = false;
        modal.classList.add('is-open');
        var cancel = modal.querySelector('[data-logout-cancel]');
        if (cancel) cancel.focus();
    }
    function closeLogout() {
        modal.classList.remove('is-open');
        modal.hidden = true;
    }
    document.querySelectorAll('[data-logout-open]').forEach(function (btn) {
        btn.addEventListener('click', openLogout);
    });
    modal.querySelectorAll('[data-logout-cancel]').forEach(function (btn) {
        btn.addEventListener('click', closeLogout);
    });
    modal.addEventListener('click', function (e) {
        if (e.target === modal) closeLogout();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.classList.contains('is-open')) closeLogout();
    });
})();
</script>
@else
    @yield('content')
@endauth
<script>
function erpToasts(initial) {
    return {
        toasts: [],
        init() {
            (initial || []).forEach((m) => this.push(m.type || 'success', m.text || ''));
            window.addEventListener('erp-toast', (e) => {
                const d = e.detail || {};
                this.push(d.type || 'success', d.text || '');
            });
        },
        push(type, text) {
            const message = String(text || '').trim();
            if (!message) return;
            const id = Date.now() + Math.random();
            const item = { id: id, type: type === 'error' ? 'error' : 'success', text: message, visible: true };
            this.toasts.push(item);
            setTimeout(() => this.dismiss(id), 5000);
        },
        dismiss(id) {
            const item = this.toasts.find((t) => t.id === id);
            if (!item) return;
            item.visible = false;
            setTimeout(() => {
                this.toasts = this.toasts.filter((t) => t.id !== id);
            }, 220);
        },
    };
}
window.erpToast = function (text, type) {
    window.dispatchEvent(new CustomEvent('erp-toast', { detail: { text: text, type: type || 'success' } }));
};
</script>
@stack('scripts')
</body>
</html>
