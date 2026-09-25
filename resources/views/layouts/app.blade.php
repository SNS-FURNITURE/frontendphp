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
    <script>
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
        }
    </script>
    <style>
        :root {
            --purple: #6c5ce7;
            --purple-mid: #a78bfa;
            --purple-dark: #f8fafc;
            --sidebar: #ffffff;
            --panel: #ffffff;
            --border: #e2e8f0;
            --text: #0f172a;
            --muted: #64748b;
            --red: #ef4444;
            --accent: #4f46e5;
            --nav-hover: #f1f5f9;
            --topbar-bg: rgba(255, 255, 255, 0.85);
            --role-bg: #e0e7ff;
            --role-text: #4338ca;
            --btn-text: #ffffff;
            --toast-succ-bg: #dcfce7;
            --toast-succ-txt: #166534;
            --toast-succ-bd: #bbf7d0;
            --toast-err-bg: #fee2e2;
            --toast-err-txt: #991b1b;
            --toast-err-bd: #fecaca;
            --toast-warn-bg: #fef3c7;
            --toast-warn-txt: #92400e;
            --toast-warn-bd: #fde68a;
            --input-bg: #ffffff;
            --color-scheme: var(--color-scheme);
            --badge-bg: #e0e7ff;
            --nav-toggle-bg: #ffffff;
            --backdrop: rgba(15, 23, 42, 0.4);
            --dialog-bg: #ffffff;
            --btn-shadow: 0 1px 2px rgba(0,0,0,0.05);
            --card-shadow: 0 1px 3px rgba(0,0,0,0.05);
            --cal-icon: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23ffffff' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='3' y='4' width='18' height='18' rx='2'/%3E%3Cline x1='16' y1='2' x2='16' y2='6'/%3E%3Cline x1='8' y1='2' x2='8' y2='6'/%3E%3Cline x1='3' y1='10' x2='21' y2='10'/%3E%3C/svg%3E");
            --cal-bg: var(--accent);
        }
        [data-theme="dark"] {
            --purple-dark: #0d0b21;
            --sidebar: #100e24;
            --panel: #16132e;
            --border: #2a2550;
            --text: #f4f2ff;
            --muted: #9b94b8;
            --accent: #a78bfa;
            --nav-hover: #1c1836;
            --topbar-bg: rgba(16, 14, 36, 0.85);
            --role-bg: #3d3470;
            --role-text: #dcd6ff;
            --btn-text: #120f24;
            --toast-succ-bg: rgba(20, 53, 42, 0.96);
            --toast-succ-txt: #8dffc1;
            --toast-succ-bd: #1f5a44;
            --toast-err-bg: rgba(58, 21, 21, 0.96);
            --toast-err-txt: #ffb4b4;
            --toast-err-bd: #6b2a2a;
            --toast-warn-bg: rgba(58, 45, 14, 0.96);
            --toast-warn-txt: #fcd34d;
            --toast-warn-bd: #78350f;
            --input-bg: #0f0d1f;
            --color-scheme: dark;
            --badge-bg: #2a2550;
            --nav-toggle-bg: #1c1836;
            --backdrop: rgba(8, 6, 20, 0.72);
            --dialog-bg: #1a1634;
            --btn-shadow: none;
            --card-shadow: none;
            --cal-icon: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23ffffff' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='3' y='4' width='18' height='18' rx='2'/%3E%3Cline x1='16' y1='2' x2='16' y2='6'/%3E%3Cline x1='8' y1='2' x2='8' y2='6'/%3E%3Cline x1='3' y1='10' x2='21' y2='10'/%3E%3C/svg%3E");
            --cal-bg: #f97316;
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
            padding: 0 0.9rem;
            flex-shrink: 0;
            height: 100%;
            max-height: 100%;
            overflow-x: hidden;
            overflow-y: auto;
            overscroll-behavior: contain;
        }
        .brand-block {
            position: sticky;
            top: 0;
            z-index: 10;
            background: var(--sidebar);
            padding: 1.25rem 0.5rem 1.25rem;
            border-bottom: 1px solid var(--border);
            margin-bottom: 1rem;
        }
        .brand-name { color: #ff6b6b; font-weight: 800; font-size: 1.05rem; }
        .brand-sub { color: var(--muted); font-size: 0.75rem; margin-top: 0.15rem; }
        .nav-section { color: var(--muted); font-size: 0.65rem; letter-spacing: 0.12em; text-transform: uppercase; margin: 0.85rem 0.5rem 0.4rem; }
        .nav-link {
            display: block;
            padding: 0.55rem 0.75rem;
            border-radius: 8px;
            color: var(--text);
            margin-bottom: 0.25rem;
            font-size: 0.92rem;
            font-weight: 500;
        }
        .nav-link:hover { background: var(--nav-hover); text-decoration: none; }
        .nav-link.active { background: var(--purple); color: #fff; font-weight: 600; }
        .sidebar-foot { margin-top: auto; padding: 0.75rem 0.5rem 1.25rem; border-top: 1px solid var(--border); flex-shrink: 0; }
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
            background: var(--topbar-bg);
            flex-shrink: 0;
        }
        .topbar-user { font-weight: 600; }
        .topbar-actions { display: flex; align-items: center; gap: 0.65rem; }
        .app-back-btn {
            appearance: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.375rem;
            height: 2.375rem;
            padding: 0;
            margin: 0;
            font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
            font-size: 1.125rem;
            font-weight: 600;
            line-height: 1;
            letter-spacing: -0.04em;
            color: var(--text);
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 11px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);
            cursor: pointer;
            transition: color 0.15s ease, background 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease, transform 0.15s ease;
        }
        .app-back-btn:hover {
            color: var(--accent);
            background: color-mix(in srgb, var(--accent) 10%, var(--panel));
            border-color: color-mix(in srgb, var(--accent) 28%, var(--border));
            box-shadow: 0 4px 14px rgba(15, 23, 42, 0.08);
            transform: translateX(-1px);
        }
        .app-back-btn:active {
            transform: translateX(-1px) scale(0.96);
        }
        .app-back-btn:focus-visible {
            outline: 2px solid color-mix(in srgb, var(--accent) 45%, transparent);
            outline-offset: 2px;
        }
        .role-badge {
            display: inline-block;
            padding: 0.2rem 0.65rem;
            border-radius: 999px;
            background: var(--role-bg);
            color: var(--role-text);
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
            border-radius: 12px;
            padding: 1.25rem;
            box-shadow: var(--card-shadow);
        }
        .btn {
            display: inline-block;
            border: 0;
            border-radius: 8px;
            padding: 0.55rem 0.95rem;
            background: var(--accent);
            color: var(--btn-text);
            font-weight: 600;
            cursor: pointer;
            font-size: 0.9rem;
            box-shadow: var(--btn-shadow);
        }
        .btn:hover { filter: brightness(1.05); text-decoration: none; }
        .btn.secondary { background: var(--purple); color: #fff; }
        .btn.ghost { background: transparent; color: var(--text); border: 1px solid var(--border); box-shadow: none; }
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
            background: var(--toast-succ-bg);
            color: var(--toast-succ-txt);
            border: 1px solid var(--toast-succ-bd);
        }
        .toast-error {
            background: var(--toast-err-bg);
            color: var(--toast-err-txt);
            border: 1px solid var(--toast-err-bd);
        }
        .toast-warn {
            background: var(--toast-warn-bg);
            color: var(--toast-warn-txt);
            border: 1px solid var(--toast-warn-bd);
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
        label { display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.35rem; color: var(--text); }
        input, select, textarea {
            width: 100%;
            padding: 0.55rem 0.7rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            margin-bottom: 0.85rem;
            background: var(--input-bg);
            color: var(--text);
            box-shadow: inset 0 1px 2px rgba(0,0,0,0.02);
        }
        input[type="date"],
        input[type="datetime-local"],
        input[type="time"],
        input[type="month"],
        input[type="week"] {
            color-scheme: var(--color-scheme);
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
            background-image: var(--cal-icon);
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
            background: var(--badge-bg);
            color: var(--accent);
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        .page-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; margin-bottom: 1.25rem; flex-wrap: wrap; }
        .page-head h1 { margin: 0; font-size: 1.75rem; font-weight: 700; color: var(--text); }
        .toolbar { display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; }
        .table-wrap { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; display: block; }
        .nav-toggle {
            display: none;
            border: 1px solid var(--border);
            background: var(--nav-toggle-bg);
            color: var(--text);
            border-radius: 8px;
            width: 2.5rem;
            height: 2.5rem;
            padding: 0;
            cursor: pointer;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            gap: 4px;
            flex-shrink: 0;
        }
        .nav-toggle-bar {
            display: block;
            width: 1.15rem;
            height: 2px;
            background: var(--text);
            border-radius: 2px;
        }
        .sidebar-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: var(--backdrop);
            z-index: 40;
            backdrop-filter: blur(2px);
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
        @media (max-width: 400px) {
            .topbar { padding: 0.6rem 0.75rem; gap: 0.5rem; }
            .topbar-user { font-size: 0.75rem; max-width: 35vw; }
            .role-badge { display: none; }
            .topbar-actions .btn { font-size: 0.75rem; padding: 0.4rem 0.5rem; }
            .nav-toggle { width: 2.2rem; height: 2.2rem; }
            .page-head h1 { font-size: 1.25rem; }
        }
        @media print {
            .sidebar, .topbar, .no-print, .logout-modal, #erp-confirm-modal, .nav-toggle, .sidebar-backdrop, .toast-host, .sidebar-foot {
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
            background: var(--backdrop);
            backdrop-filter: blur(4px);
        }
        .logout-modal.is-open { display: flex !important; }
        .logout-modal[hidden] { display: none !important; }
        .logout-dialog {
            width: 100%;
            max-width: 380px;
            background: var(--dialog-bg);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
            animation: logout-pop 0.18s ease-out;
        }
        @keyframes logout-pop {
            from { opacity: 0; transform: translateY(8px) scale(0.98); }
            to { opacity: 1; transform: none; }
        }
        .logout-dialog h3 {
            margin: 0 0 0.4rem;
            font-size: 1.2rem;
            color: var(--text);
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
        ->pluck('formatted_name')
        ->filter()
        ->unique()
        ->values()
        ->join(' · ') ?: 'No role';
    $showAppBack = !View::hasSection('hide_back')
        && !request()->routeIs('*.index')
        && !request()->routeIs('profile.edit');
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
            <a class="nav-link {{ request()->routeIs('invoices.*') ? 'active' : '' }}" href="{{ route('invoices.index') }}">Orders</a>
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
                @if ($showAppBack)
                    <button type="button" class="app-back-btn" id="app-back-btn" aria-label="Go back" title="Go back"><span aria-hidden="true">&lt;</span></button>
                @endif
                <button type="button" class="nav-toggle" id="nav-toggle" aria-label="Open menu" aria-controls="app-sidebar" aria-expanded="false">
                    <span class="nav-toggle-bar" aria-hidden="true"></span>
                    <span class="nav-toggle-bar" aria-hidden="true"></span>
                    <span class="nav-toggle-bar" aria-hidden="true"></span>
                </button>
                <div class="topbar-user">{{ auth()->user()->full_name }}</div>
            </div>
            <div class="topbar-actions">
                <button type="button" class="btn ghost" id="theme-toggle" aria-label="Toggle Theme" style="padding: 0.35rem 0.5rem;" title="Toggle Dark/Light Mode">
                    <svg id="theme-icon-dark" style="display:none; width: 18px; height: 18px;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
                    </svg>
                    <svg id="theme-icon-light" style="display:block; width: 18px; height: 18px;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
                    </svg>
                </button>
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
            :class="t.type === 'error' ? 'toast-error' : (t.type === 'warn' ? 'toast-warn' : 'toast-success')"
            x-show="t.visible"
            x-transition.opacity.duration.200ms
            role="status"
        >
            <div class="toast-body" x-html="t.text"></div>
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

<div class="logout-modal no-print" id="erp-confirm-modal" role="dialog" aria-modal="true" aria-labelledby="erp-confirm-title" hidden>
    <div class="logout-dialog">
        <h3 id="erp-confirm-title">Confirm</h3>
        <p id="erp-confirm-message"></p>
        <div class="logout-actions">
            <button type="button" class="btn ghost" data-erp-confirm-cancel>Cancel</button>
            <button type="button" class="btn" id="erp-confirm-ok-btn">Confirm</button>
        </div>
    </div>
</div>

<script>
(function () {
    
    var savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark') {
        document.documentElement.setAttribute('data-theme', 'dark');
    }
    
    var appBackBtn = document.getElementById('app-back-btn');
    if (appBackBtn) {
        if (window.history.length <= 1) {
            appBackBtn.hidden = true;
        }
        appBackBtn.addEventListener('click', function () {
            if (window.history.length > 1) {
                window.history.back();
            }
        });
    }

    var themeToggle = document.getElementById('theme-toggle');
    if (themeToggle) {
        var iconDark = document.getElementById('theme-icon-dark');
        var iconLight = document.getElementById('theme-icon-light');
        
        function updateIcons() {
            var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
            if (isDark) {
                iconDark.style.display = 'block';
                iconLight.style.display = 'none';
            } else {
                iconDark.style.display = 'none';
                iconLight.style.display = 'block';
            }
        }
        // Run on next tick so DOM is ready
        setTimeout(updateIcons, 0);
        
        themeToggle.addEventListener('click', function() {
            if (document.documentElement.getAttribute('data-theme') === 'dark') {
                document.documentElement.removeAttribute('data-theme');
                localStorage.setItem('theme', 'light');
            } else {
                document.documentElement.setAttribute('data-theme', 'dark');
                localStorage.setItem('theme', 'dark');
            }
            updateIcons();
        });
    }

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
            const toastType = type === 'error' ? 'error' : (type === 'warn' ? 'warn' : 'success');
            const item = { id: id, type: toastType, text: message, visible: true };
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

window.erpConfirm = function (message, options) {
    options = options || {};
    return new Promise(function (resolve) {
        var modal = document.getElementById('erp-confirm-modal');
        if (!modal) {
            resolve(false);
            return;
        }

        var titleEl = document.getElementById('erp-confirm-title');
        var msgEl = document.getElementById('erp-confirm-message');
        var okBtn = document.getElementById('erp-confirm-ok-btn');
        var cancelBtn = modal.querySelector('[data-erp-confirm-cancel]');

        titleEl.textContent = options.title || 'Confirm';
        msgEl.textContent = String(message || '');
        okBtn.textContent = options.okLabel || 'Confirm';
        okBtn.className = options.danger ? 'btn danger' : 'btn';

        function cleanup() {
            modal.hidden = true;
            modal.classList.remove('is-open');
            okBtn.removeEventListener('click', onOk);
            cancelBtn.removeEventListener('click', onCancel);
            modal.removeEventListener('click', onBackdrop);
            document.removeEventListener('keydown', onKey);
        }

        function onOk() { cleanup(); resolve(true); }
        function onCancel() { cleanup(); resolve(false); }
        function onBackdrop(e) { if (e.target === modal) { onCancel(); } }
        function onKey(e) {
            if (e.key === 'Escape') { onCancel(); }
        }

        okBtn.addEventListener('click', onOk);
        cancelBtn.addEventListener('click', onCancel);
        modal.addEventListener('click', onBackdrop);
        document.addEventListener('keydown', onKey);

        modal.hidden = false;
        modal.classList.add('is-open');
        if (cancelBtn) { cancelBtn.focus(); }
    });
};

window.alert = function (message) {
    window.erpToast(String(message || ''), 'warn');
};

function erpIsDeleteForm(form) {
    var method = form.querySelector('input[name="_method"]');
    return method && String(method.value).toUpperCase() === 'DELETE';
}

function erpRemoveDeleteTarget(form) {
    var selector = form.getAttribute('data-erp-remove') || 'closest:tr';
    var target = null;

    if (selector.indexOf('closest:') === 0) {
        target = form.closest(selector.slice(8));
    } else if (selector.charAt(0) === '#') {
        target = document.querySelector(selector);
    }

    if (target) {
        target.style.transition = 'opacity 0.15s ease';
        target.style.opacity = '0';
        setTimeout(function () {
            target.remove();
        }, 150);
    }

    var also = form.getAttribute('data-erp-remove-also');
    if (also) {
        document.querySelectorAll(also).forEach(function (el) {
            el.remove();
        });
    }
}

function erpMaybeShowEmptyState(form) {
    var tbody = form.closest('tbody');
    if (!tbody || tbody.querySelectorAll('tr').length > 0) {
        return;
    }

    var cols = tbody.closest('table') ? tbody.closest('table').querySelectorAll('thead th').length : 1;
    var msg = tbody.getAttribute('data-erp-empty-message') || 'No items';
    tbody.innerHTML = '<tr><td colspan="' + cols + '" class="muted" style="text-align:center;padding:2rem">' + msg + '</td></tr>';
}

function erpPerformInstantDelete(form) {
    var tokenEl = document.querySelector('meta[name="csrf-token"]');
    var headers = {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    };
    if (tokenEl) {
        headers['X-CSRF-TOKEN'] = tokenEl.content;
    }

    var btn = form.querySelector('[type="submit"]');
    if (btn) {
        btn.disabled = true;
    }

    fetch(form.action, {
        method: 'POST',
        body: new FormData(form),
        headers: headers,
        credentials: 'same-origin',
    })
        .then(function (res) {
            return res.json().catch(function () {
                return {};
            }).then(function (data) {
                if (!res.ok) {
                    throw data;
                }
                return data;
            });
        })
        .then(function (data) {
            erpRemoveDeleteTarget(form);
            erpMaybeShowEmptyState(form);
            window.erpToast(data.message || 'Deleted.', 'success');
        })
        .catch(function (err) {
            var msg = err.message
                || (err.errors && Object.values(err.errors).flat()[0])
                || 'Could not delete.';
            window.erpToast(String(msg), 'error');
        })
        .finally(function () {
            if (btn) {
                btn.disabled = false;
            }
        });
}

document.addEventListener('submit', function (e) {
    var form = e.target;
    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    var msg = form.getAttribute('data-erp-confirm');
    var instantDelete = erpIsDeleteForm(form) || form.hasAttribute('data-erp-instant-delete');

    if (!msg && !instantDelete) {
        return;
    }

    if (!instantDelete && form.dataset.erpConfirmed === '1') {
        delete form.dataset.erpConfirmed;
        return;
    }

    e.preventDefault();
    e.stopPropagation();

    function proceed() {
        if (instantDelete) {
            erpPerformInstantDelete(form);
            return;
        }

        form.dataset.erpConfirmed = '1';
        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
        } else {
            form.submit();
        }
    }

    if (msg) {
        var danger = form.hasAttribute('data-erp-confirm-danger');
        var title = form.getAttribute('data-erp-confirm-title') || (danger ? 'Are you sure?' : 'Confirm');
        var okLabel = form.getAttribute('data-erp-confirm-ok') || (danger ? 'Delete' : 'Confirm');

        window.erpConfirm(msg, { title: title, okLabel: okLabel, danger: danger }).then(function (ok) {
            if (ok) {
                proceed();
            }
        });
    } else {
        proceed();
    }
}, true);
</script>
@stack('scripts')

@auth
{{-- ============================================================
     ENTERPRISE SESSION GUARD
     - 30-min idle → auto logout (matches server SESSION_LIFETIME)
     - Warning dialog at 25 min of idle (5-min countdown)
     - Tab/browser close → session cookie cleared via sessionStorage flag
     - Activity events reset the idle timer
     ============================================================ --}}
<script>
(function () {
    'use strict';

    var IDLE_LIMIT_MS   = 30 * 60 * 1000;   // 30 minutes — must match SESSION_LIFETIME
    var WARN_BEFORE_MS  = 5  * 60 * 1000;   // show warning 5 min before expiry
    var WARN_AT_MS      = IDLE_LIMIT_MS - WARN_BEFORE_MS;  // 25 min
    var LOGOUT_URL      = '{{ route("logout") }}';
    var CSRF_TOKEN      = document.querySelector('meta[name="csrf-token"]')
                            ? document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            : '';

    // ── Tab-close detection ──────────────────────────────────────────────────
    // sessionStorage is TAB-scoped: it disappears the moment the tab closes.
    // We use a TWO-KEY approach:
    //   sns_erp_nav  = set in beforeunload (i.e. navigating away within the app)
    //   sns_erp_active = presence means "we already ran the guard this session"
    //
    // On a fresh browser open: neither key exists → force logout + redirect to login.
    // On page navigation within the app: sns_erp_nav is set → normal continuation.
    var SESSION_KEY = 'sns_erp_active';
    var NAV_KEY     = 'sns_erp_nav';

    var navigatingInternally = sessionStorage.getItem(NAV_KEY);
    var wasActive            = sessionStorage.getItem(SESSION_KEY);

    // Clear nav flag immediately so the next check is clean
    sessionStorage.removeItem(NAV_KEY);

    if (!navigatingInternally && !wasActive) {
        // True fresh open (browser closed and reopened, or brand-new tab)
        // — fire a silent server-side logout to invalidate stale cookie
        var token = CSRF_TOKEN;
        if (token && navigator.sendBeacon) {
            navigator.sendBeacon(LOGOUT_URL, new URLSearchParams({ _token: token, _method: 'POST' }));
        }
        sessionStorage.setItem(SESSION_KEY, '1');
        // Redirect to login
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function () {
                window.location.href = '{{ route("login") }}';
            });
        } else {
            window.location.href = '{{ route("login") }}';
        }
        return;
    }

    // Mark session as active for this tab
    sessionStorage.setItem(SESSION_KEY, '1');

    // Before any navigation/link-click within the ERP, stamp the nav key
    // so the next page load knows it was an internal navigation (not a fresh open)
    window.addEventListener('beforeunload', function () {
        sessionStorage.setItem(NAV_KEY, '1');
    });

    // ── Build the warning dialog ─────────────────────────────────────────────
    var overlay = document.createElement('div');
    overlay.id  = 'sns-idle-overlay';
    overlay.setAttribute('aria-modal', 'true');
    overlay.setAttribute('role', 'alertdialog');
    overlay.setAttribute('aria-labelledby', 'sns-idle-title');
    overlay.style.cssText = [
        'display:none',
        'position:fixed',
        'inset:0',
        'z-index:99999',
        'background:rgba(0,0,0,0.65)',
        'backdrop-filter:blur(4px)',
        '-webkit-backdrop-filter:blur(4px)',
        'align-items:center',
        'justify-content:center',
    ].join(';');

    var box = document.createElement('div');
    box.style.cssText = [
        'background:var(--dialog-bg,#fff)',
        'color:var(--text,#0f172a)',
        'border:1px solid var(--border,#e2e8f0)',
        'border-radius:14px',
        'padding:2rem 2.25rem',
        'max-width:380px',
        'width:90%',
        'text-align:center',
        'box-shadow:0 20px 60px rgba(0,0,0,0.35)',
        'animation:sns-idle-in .2s ease',
    ].join(';');

    box.innerHTML = [
        '<div style="font-size:2.4rem;margin-bottom:.75rem">⏳</div>',
        '<h2 id="sns-idle-title" style="margin:0 0 .5rem;font-size:1.15rem;font-weight:700">',
            'Still there?',
        '</h2>',
        '<p style="margin:0 0 1.5rem;color:var(--muted,#64748b);font-size:.9rem">',
            'You\'ll be signed out in <strong id="sns-idle-countdown">5:00</strong> due to inactivity.',
        '</p>',
        '<button id="sns-idle-stay"',
            ' style="',
                'background:var(--purple,#6c5ce7);',
                'color:#fff;',
                'border:none;',
                'border-radius:8px;',
                'padding:.6rem 1.6rem;',
                'font-size:.95rem;',
                'font-weight:600;',
                'cursor:pointer;',
                'width:100%;',
            '"',
        '>Stay signed in</button>',
    ].join('');

    overlay.appendChild(box);

    // Inject keyframe animation
    var style = document.createElement('style');
    style.textContent = '@keyframes sns-idle-in{from{opacity:0;transform:scale(.95)}to{opacity:1;transform:scale(1)}}';
    document.head.appendChild(style);

    document.addEventListener('DOMContentLoaded', function () {
        document.body.appendChild(overlay);
    });

    // ── State ────────────────────────────────────────────────────────────────
    var idleTimer     = null;
    var warnTimer     = null;
    var countTimer    = null;
    var warnShown     = false;
    var lastActivity  = Date.now();

    function resetIdleTimer() {
        lastActivity = Date.now();
        clearTimeout(idleTimer);
        clearTimeout(warnTimer);
        if (warnShown) hideWarning();

        // Schedule: show warning at 25 min
        warnTimer = setTimeout(showWarning, WARN_AT_MS);
        // Schedule: hard logout at 30 min (fallback if user ignores dialog)
        idleTimer = setTimeout(doLogout, IDLE_LIMIT_MS);
    }

    // ── Warning dialog controls ──────────────────────────────────────────────
    function showWarning() {
        warnShown = true;
        overlay.style.display = 'flex';
        overlay.focus();
        startCountdown(WARN_BEFORE_MS / 1000);
    }

    function hideWarning() {
        warnShown = false;
        overlay.style.display = 'none';
        clearInterval(countTimer);
    }

    function startCountdown(secondsLeft) {
        var el = document.getElementById('sns-idle-countdown');
        function tick() {
            if (!el) return;
            var m = Math.floor(secondsLeft / 60);
            var s = Math.floor(secondsLeft % 60);
            el.textContent = m + ':' + (s < 10 ? '0' : '') + s;
            if (secondsLeft <= 0) { doLogout(); return; }
            secondsLeft--;
        }
        tick();
        countTimer = setInterval(tick, 1000);
    }

    // ── Logout ───────────────────────────────────────────────────────────────
    function doLogout() {
        clearTimeout(idleTimer);
        clearTimeout(warnTimer);
        clearInterval(countTimer);
        sessionStorage.removeItem(SESSION_KEY);
        // POST to Laravel logout route (requires CSRF)
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = LOGOUT_URL + '?idle=1';
        var csrf = document.createElement('input');
        csrf.type = 'hidden'; csrf.name = '_token'; csrf.value = CSRF_TOKEN;
        form.appendChild(csrf);
        document.body.appendChild(form);
        form.submit();
    }

    // ── Activity events ──────────────────────────────────────────────────────
    var EVENTS = ['mousemove','mousedown','keydown','touchstart','scroll','click','wheel'];
    EVENTS.forEach(function (ev) {
        document.addEventListener(ev, resetIdleTimer, { passive: true, capture: true });
    });

    // Stay-signed-in button
    document.addEventListener('click', function (e) {
        if (e.target && e.target.id === 'sns-idle-stay') {
            resetIdleTimer();
            // Ping server to renew session (a HEAD request resets the Laravel session TTL)
            fetch(window.location.href, { method: 'HEAD', credentials: 'same-origin' }).catch(function(){});
        }
    });

    // Page visibility: if user hides tab for 30+ min and comes back, log out
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') {
            var idle = Date.now() - lastActivity;
            if (idle >= IDLE_LIMIT_MS) {
                doLogout();
            }
        }
    });

    // Start the timer
    resetIdleTimer();
})();
</script>
@endauth

</body>
</html>
