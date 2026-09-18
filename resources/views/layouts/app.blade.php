<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SNS Furniture')</title>
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
        body {
            margin: 0;
            font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
            background: var(--purple-dark);
            color: var(--text);
            min-height: 100vh;
        }
        a { color: var(--accent); text-decoration: none; }
        a:hover { text-decoration: underline; }
        .shell { display: flex; min-height: 100vh; }
        .sidebar {
            width: 240px;
            background: var(--sidebar);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            padding: 1.25rem 0.9rem;
            flex-shrink: 0;
            position: sticky;
            top: 0;
            align-self: flex-start;
            height: 100vh;
            max-height: 100vh;
            overflow-x: hidden;
            overflow-y: auto;
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
        .sidebar-foot { margin-top: auto; padding: 0.75rem 0.5rem 0; border-top: 1px solid var(--border); }
        .main { flex: 1; display: flex; flex-direction: column; min-width: 0; }
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            padding: 0.9rem 1.5rem;
            border-bottom: 1px solid var(--border);
            background: rgba(16, 14, 36, 0.85);
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
        .content { padding: 1.5rem; flex: 1; }
        .content-wide { max-width: none; }
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
        .flash { background: #14352a; color: #8dffc1; padding: 0.75rem 1rem; border-radius: 10px; margin-bottom: 1rem; border: 1px solid #1f5a44; }
        .errors { background: #3a1515; color: #ffb4b4; padding: 0.75rem 1rem; border-radius: 10px; margin-bottom: 1rem; border: 1px solid #6b2a2a; }
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
        @media (max-width: 900px) {
            .shell { flex-direction: column; }
            .sidebar {
                width: 100%;
                position: static;
                height: auto;
                max-height: none;
                overflow-y: visible;
            }
            .grid-2 { grid-template-columns: 1fr; }
        }
        @media print {
            .sidebar, .topbar, .no-print, .logout-modal { display: none !important; }
            .content { padding: 0; }
            body { background: #fff; color: #111; }
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
    $roleNames = auth()->user()->roles->pluck('name')->map(fn ($n) => strtolower($n))->all();
    $roleLabel = 'Staff';
    if (in_array('admin', $roleNames, true)) $roleLabel = 'Admin';
    elseif (in_array('finance', $roleNames, true)) $roleLabel = 'Finance';
    elseif (in_array('company_manager', $roleNames, true)) $roleLabel = 'Company Manager';
    elseif (in_array('marketing_manager', $roleNames, true)) $roleLabel = 'Marketing Manager';
    elseif (in_array('advisor', $roleNames, true) || in_array('supervisor', $roleNames, true)) $roleLabel = 'Advisor';
    $username = auth()->user()->username ?: explode('@', auth()->user()->email)[0];
@endphp
<div class="shell">
    <aside class="sidebar no-print">
        <div class="brand-block">
            <div style="display:flex;align-items:center;gap:0.6rem">
                <img src="{{ asset('sns-logo.png') }}" alt="SNS" style="height:36px;width:auto;border-radius:8px;background:#fff;padding:2px">
                <div>
                    <div class="brand-name">SNS Furniture</div>
                    <div class="brand-sub">Work boards</div>
                </div>
            </div>
        </div>
        <div class="nav-section">Finance</div>
        <a class="nav-link {{ request()->routeIs('invoices.*') ? 'active' : '' }}" href="{{ route('invoices.index') }}">Invoices</a>
        <a class="nav-link {{ request()->routeIs('payments.*') ? 'active' : '' }}" href="{{ route('payments.index') }}">Payments</a>
        @if (auth()->user()->canViewFunding())
            <a class="nav-link {{ request()->routeIs('funding.*') ? 'active' : '' }}" href="{{ route('funding.index') }}">Funding</a>
        @endif
        @if (auth()->user()->canViewAllocations())
            <a class="nav-link {{ request()->routeIs('finance.allocations') ? 'active' : '' }}" href="{{ route('finance.allocations') }}">Allocations</a>
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
            <div class="topbar-user">{{ '@'.$username }} {{ auth()->user()->full_name }}</div>
            <div class="topbar-actions">
                <span class="role-badge">{{ $roleLabel }}</span>
                <button class="btn ghost" type="button" data-logout-open>Logout</button>
            </div>
        </header>
        <div class="content @yield('content_class')">
            @if (session('status'))
                <div class="flash no-print">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="errors no-print">
                    <ul style="margin:0;padding-left:1.1rem">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @yield('content')
        </div>
    </div>
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
@stack('scripts')
</body>
</html>
