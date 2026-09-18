<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SNS Furniture')</title>
    <script>
        (function () {
            try {
                var t = localStorage.getItem('sns-theme');
                if (t === 'light' || t === 'dark') {
                    document.documentElement.setAttribute('data-theme', t);
                }
            } catch (e) {}
        })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=Fraunces:opsz,wght@9..144,600;9..144,700&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    <style>
        :root, html[data-theme="dark"] {
            --purple: #6c5ce7;
            --purple-mid: #452F80;
            --purple-dark: #0d0b21;
            --sidebar: #100e24;
            --panel: #16132e;
            --panel-2: #1a1634;
            --border: #2a2550;
            --text: #f4f2ff;
            --muted: #9b94b8;
            --red: #E13B30;
            --accent: #a78bfa;
            --lime: #a3e635;
            --lime-ink: #142010;
            --ok: #8dffc1;
            --warn: #fbbf24;
            --topbar: rgba(16, 14, 36, 0.85);
            --input-bg: #0f0d1f;
            --nav-hover: #1c1836;
            --nav-active: #3d3470;
            --shadow: 0 18px 40px rgba(0, 0, 0, 0.35);
            --btn-on-accent: #120f24;
            --status-present: #86efac;
            --status-absent: #fca5a5;
            --status-late: #fdba74;
            --status-half: #fde047;
            --status-leave: #93c5fd;
            --status-holiday: #c4b5fd;
            --note-bg: color-mix(in srgb, #3b82f6 18%, transparent);
            --note-text: #93c5fd;
            --note-border: color-mix(in srgb, #3b82f6 35%, transparent);
            --today-bg: color-mix(in srgb, #3b82f6 28%, transparent);
            --today-text: #93c5fd;
        }
        html[data-theme="light"] {
            --purple: #4c3fd4;
            --purple-mid: #5b4cdb;
            --purple-dark: #efeafc;
            --sidebar: #ffffff;
            --panel: #ffffff;
            --panel-2: #f4f0ff;
            --border: #b8aedc;
            --text: #160f33;
            --muted: #4f476c;
            --red: #b42318;
            --accent: #4c3fd4;
            --lime: #3f7c0c;
            --lime-ink: #ffffff;
            --ok: #166534;
            --warn: #9a3412;
            --topbar: rgba(255, 255, 255, 0.96);
            --input-bg: #ffffff;
            --nav-hover: #ebe4ff;
            --nav-active: #ddd3ff;
            --shadow: 0 14px 32px rgba(55, 40, 120, 0.12);
            --btn-on-accent: #ffffff;
            --status-present: #15803d;
            --status-absent: #b91c1c;
            --status-late: #c2410c;
            --status-half: #a16207;
            --status-leave: #1d4ed8;
            --status-holiday: #6d28d9;
            --note-bg: #eff6ff;
            --note-text: #1e40af;
            --note-border: #93c5fd;
            --today-bg: #dbeafe;
            --today-text: #1e40af;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "DM Sans", system-ui, -apple-system, Segoe UI, sans-serif;
            background: var(--purple-dark);
            color: var(--text);
            min-height: 100vh;
            transition: background 0.2s ease, color 0.2s ease;
        }
        .display-font { font-family: Fraunces, Georgia, serif; }
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
            color: var(--text);
            margin-bottom: 0.25rem;
            font-size: 0.92rem;
            opacity: 0.9;
        }
        .nav-link:hover { background: var(--nav-hover); text-decoration: none; opacity: 1; }
        .nav-link.active { background: var(--nav-active); color: var(--text); font-weight: 600; opacity: 1; }
        .sidebar-foot { margin-top: auto; padding: 0.75rem 0.5rem 0; border-top: 1px solid var(--border); }
        .main { flex: 1; display: flex; flex-direction: column; min-width: 0; }
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            padding: 0.9rem 1.5rem;
            border-bottom: 1px solid var(--border);
            background: var(--topbar);
            backdrop-filter: blur(8px);
        }
        .topbar-user { font-weight: 600; }
        .topbar-actions { display: flex; align-items: center; gap: 0.65rem; }
        .role-badge {
            display: inline-block;
            padding: 0.2rem 0.65rem;
            border-radius: 999px;
            background: var(--nav-active);
            color: var(--text);
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        .theme-toggle {
            width: 40px;
            height: 40px;
            border-radius: 999px;
            border: 1px solid var(--border);
            background: var(--panel);
            color: var(--text);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            padding: 0;
        }
        .theme-toggle:hover { border-color: var(--accent); }
        .theme-toggle svg { width: 18px; height: 18px; display: block; }
        html[data-theme="dark"] .icon-sun { display: none; }
        html[data-theme="light"] .icon-moon { display: none; }
        .content { padding: 1.5rem; flex: 1; }
        .content-wide { max-width: none; }
        .card {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 1.25rem;
            box-shadow: var(--shadow);
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            border: 0;
            border-radius: 10px;
            padding: 0.55rem 0.95rem;
            background: var(--accent);
            color: var(--btn-on-accent);
            font-weight: 700;
            cursor: pointer;
            font-size: 0.9rem;
            text-decoration: none;
        }
        .btn:hover { filter: brightness(1.05); text-decoration: none; }
        .btn.secondary { background: var(--purple); color: #fff; }
        .btn.ghost {
            background: var(--panel);
            color: var(--text);
            border: 1px solid var(--border);
        }
        html[data-theme="light"] .btn.ghost {
            background: #ffffff;
            color: #160f33;
            border-color: #8f82c4;
        }
        .btn.danger { background: var(--red); color: #fff; }
        .btn.lime { background: var(--lime); color: var(--lime-ink); }
        .btn.purple-outline {
            background: transparent;
            color: var(--accent);
            border: 1px solid var(--accent);
        }
        html[data-theme="light"] .btn.purple-outline {
            color: #4c3fd4;
            border-color: #4c3fd4;
            background: #ffffff;
        }
        html[data-theme="light"] .btn.secondary,
        html[data-theme="light"] .btn:not(.ghost):not(.purple-outline):not(.lime):not(.danger) {
            color: #ffffff;
        }
        html[data-theme="light"] .theme-toggle {
            background: #ffffff;
            color: #160f33;
            border-color: #8f82c4;
        }
        html[data-theme="light"] .nav-link.active {
            color: #160f33;
            background: #d8ceff;
        }
        html[data-theme="light"] .role-badge {
            color: #160f33;
            background: #d8ceff;
        }
        html[data-theme="light"] .badge {
            color: #4c3fd4;
            background: #ebe4ff;
        }
        html[data-theme="light"] .badge-danger { color: #b91c1c; }
        html[data-theme="light"] input,
        html[data-theme="light"] select,
        html[data-theme="light"] textarea {
            color: #160f33;
            background: #ffffff;
            border-color: #b8aedc;
        }
        html[data-theme="light"] ::placeholder { color: #7a7199; opacity: 1; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data th, table.data td { text-align: left; padding: 0.7rem 0.45rem; border-bottom: 1px solid var(--border); font-size: 0.92rem; color: var(--text); }
        table.data th { color: var(--muted); font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.04em; }
        .flash { background: color-mix(in srgb, var(--ok) 18%, transparent); color: var(--ok); padding: 0.75rem 1rem; border-radius: 10px; margin-bottom: 1rem; border: 1px solid color-mix(in srgb, var(--ok) 35%, transparent); }
        .errors { background: color-mix(in srgb, var(--red) 18%, transparent); color: #ffb4b4; padding: 0.75rem 1rem; border-radius: 10px; margin-bottom: 1rem; border: 1px solid color-mix(in srgb, var(--red) 35%, transparent); }
        html[data-theme="light"] .errors { color: var(--red); }
        label { display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.25rem; color: var(--text); }
        input, select, textarea {
            width: 100%;
            padding: 0.55rem 0.7rem;
            border: 1px solid var(--border);
            border-radius: 10px;
            margin-bottom: 0.85rem;
            background: var(--input-bg);
            color: var(--text);
        }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 0.85rem; }
        .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.85rem; }
        .muted { color: var(--muted); font-size: 0.85rem; }
        .badge {
            display: inline-block;
            padding: 0.15rem 0.5rem;
            border-radius: 999px;
            background: var(--nav-active);
            color: var(--accent);
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        .badge-ok { background: color-mix(in srgb, var(--lime) 22%, transparent); color: var(--lime); }
        .badge-warn { background: color-mix(in srgb, var(--warn) 22%, transparent); color: var(--warn); }
        .badge-danger { background: color-mix(in srgb, var(--red) 22%, transparent); color: #ff8a80; }
        .page-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; margin-bottom: 1.25rem; flex-wrap: wrap; }
        .page-head h1 { margin: 0; font-size: 1.75rem; font-weight: 700; }
        .page-head h1.display-font { font-size: 2rem; letter-spacing: -0.02em; }
        .toolbar { display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; }
        .filter-bar {
            display: flex; flex-wrap: wrap; gap: 0.65rem; align-items: center;
            margin-bottom: 1rem; padding: 0.85rem; border: 1px solid var(--border);
            border-radius: 14px; background: var(--panel);
        }
        .filter-bar input, .filter-bar select { margin: 0; min-width: 10rem; }
        .chip-row { display: flex; flex-wrap: wrap; gap: 0.4rem; }
        .chip {
            border: 1px solid var(--border); background: transparent; color: var(--muted);
            border-radius: 999px; padding: 0.35rem 0.75rem; cursor: pointer; font-size: 0.8rem; font-weight: 600;
        }
        .chip.active { background: color-mix(in srgb, var(--lime) 22%, transparent); color: var(--lime); border-color: color-mix(in srgb, var(--lime) 45%, transparent); }
        .avatar {
            width: 40px; height: 40px; border-radius: 999px; object-fit: cover;
            background: var(--nav-active); display: inline-flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 0.85rem; flex-shrink: 0;
        }
        .avatar.lg { width: 120px; height: 120px; border-radius: 18px; font-size: 2rem; }
        .emp-row { display: flex; align-items: center; gap: 0.75rem; }
        .emp-row strong { display: block; }
        .summary-strip {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(110px, 1fr)); gap: 0.75rem;
            padding: 1rem 1.15rem; border-radius: 16px; margin-bottom: 1rem;
            background: linear-gradient(90deg, color-mix(in srgb, var(--lime) 55%, #1a1634), var(--panel));
            border: 1px solid var(--border);
        }
        html[data-theme="light"] .summary-strip {
            background: linear-gradient(90deg, color-mix(in srgb, var(--lime) 35%, #fff), #fff);
        }
        .summary-strip .metric strong { display: block; font-size: 1.25rem; }
        .summary-strip .metric span { color: var(--muted); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.04em; }
        .legend { display: flex; flex-wrap: wrap; gap: 0.75rem 1rem; margin: 0.75rem 0 1rem; }
        .legend span { display: inline-flex; align-items: center; gap: 0.35rem; color: var(--muted); font-size: 0.8rem; }
        .dot { width: 8px; height: 8px; border-radius: 999px; display: inline-block; }
        .att-scroll { overflow-x: auto; border: 1px solid var(--border); border-radius: 16px; background: var(--panel); scroll-behavior: smooth; }
        .att-grid { min-width: 960px; border-collapse: separate; border-spacing: 0; }
        .att-grid th, .att-grid td { vertical-align: middle; }
        .att-grid th:first-child, .att-grid td:first-child {
            position: sticky; left: 0; z-index: 3; background: var(--panel);
            box-shadow: 4px 0 10px rgba(0,0,0,0.12); min-width: 180px;
        }
        .att-grid thead th:first-child { z-index: 4; }
        .att-grid th.today { background: var(--today-bg); color: var(--today-text); }
        .att-grid td.today-col { background: color-mix(in srgb, var(--today-bg) 55%, transparent); }
        .att-cell-wrap { position: relative; min-width: 92px; padding: 0.35rem !important; }
        .att-sessions { display: flex; flex-direction: column; gap: 0.28rem; }
        .att-session { position: relative; }
        .att-session-label {
            display: block; font-size: 0.58rem; letter-spacing: 0.06em; text-transform: uppercase;
            color: var(--muted); margin-bottom: 0.12rem; font-weight: 700;
        }
        .att-cell {
            width: 100%; min-height: 32px; border-radius: 8px; border: 1px solid var(--border);
            background: var(--panel-2); color: var(--text); cursor: pointer; font-size: 0.65rem;
            font-weight: 700; text-transform: capitalize; padding: 0.28rem 0.25rem; line-height: 1.15;
        }
        .att-cell:hover { border-color: var(--accent); }
        .att-cell.is-editable { box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--lime) 35%, transparent); }
        .att-cell.is-readonly { cursor: default; opacity: 0.95; }
        .att-cell.status-present { background: color-mix(in srgb, var(--status-present) 18%, transparent); color: var(--status-present); border-color: color-mix(in srgb, var(--status-present) 40%, transparent); }
        .att-cell.status-absent { background: color-mix(in srgb, var(--status-absent) 18%, transparent); color: var(--status-absent); border-color: color-mix(in srgb, var(--status-absent) 40%, transparent); }
        .att-cell.status-late { background: color-mix(in srgb, var(--status-late) 18%, transparent); color: var(--status-late); border-color: color-mix(in srgb, var(--status-late) 40%, transparent); }
        .att-cell.status-half_day { background: color-mix(in srgb, var(--status-half) 18%, transparent); color: var(--status-half); border-color: color-mix(in srgb, var(--status-half) 40%, transparent); }
        .att-cell.status-leave { background: color-mix(in srgb, var(--status-leave) 18%, transparent); color: var(--status-leave); border-color: color-mix(in srgb, var(--status-leave) 40%, transparent); }
        .att-cell.status-holiday { background: color-mix(in srgb, var(--status-holiday) 18%, transparent); color: var(--status-holiday); border-color: color-mix(in srgb, var(--status-holiday) 40%, transparent); }
        .att-menu {
            position: absolute; z-index: 20; top: calc(100% - 2px); left: 50%; transform: translateX(-50%);
            min-width: 128px; padding: 0.35rem; border-radius: 12px; border: 1px solid var(--border);
            background: var(--panel-2); box-shadow: var(--shadow);
        }
        .att-menu button, .att-menu .att-menu-item {
            display: block; width: 100%; text-align: left; border: 0; background: transparent;
            color: var(--text); padding: 0.4rem 0.55rem; border-radius: 8px; cursor: pointer;
            font-size: 0.75rem; font-weight: 600;
        }
        .att-menu button:hover, .att-menu .att-menu-item:hover { background: var(--nav-hover); }
        .status-present { color: var(--status-present); }
        .status-absent { color: var(--status-absent); }
        .status-late { color: var(--status-late); }
        .status-half_day { color: var(--status-half); }
        .status-leave { color: var(--status-leave); }
        .status-holiday { color: var(--status-holiday); }
        .stat-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 0.75rem; margin-top: 1rem; }
        .stat-card { background: var(--panel); border: 1px solid var(--border); border-radius: 14px; padding: 0.9rem 1rem; }
        .stat-card strong { display: block; font-size: 1.25rem; }
        .profile-shell {
            display: grid; grid-template-columns: 280px 1fr; gap: 1.25rem;
            background: var(--panel); border: 1px solid var(--border); border-radius: 22px; padding: 1.25rem;
            box-shadow: var(--shadow);
        }
        .profile-side { display: flex; flex-direction: column; gap: 0.85rem; }
        .profile-side h2 { margin: 0; font-family: Fraunces, Georgia, serif; font-size: 1.6rem; }
        .profile-role { color: var(--lime); font-weight: 700; }
        .profile-contact { display: flex; flex-direction: column; gap: 0.45rem; color: var(--muted); font-size: 0.9rem; }
        .profile-section { margin-bottom: 1.25rem; }
        .profile-section h3 {
            margin: 0 0 0.75rem; display: flex; align-items: center; gap: 0.5rem;
            font-size: 0.95rem; color: var(--text);
        }
        .profile-section h3 .ico {
            width: 28px; height: 28px; border-radius: 8px; background: color-mix(in srgb, var(--lime) 30%, transparent);
            color: var(--lime); display: inline-flex; align-items: center; justify-content: center; font-size: 0.8rem;
        }
        .salary-box {
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.75rem;
            background: var(--panel-2); border: 1px solid var(--border); border-radius: 14px; padding: 0.9rem;
        }
        .salary-box .val-lime { color: var(--lime); font-weight: 700; }
        .salary-box .val-red { color: var(--status-absent); font-weight: 700; }
        .pay-hero {
            display: grid; grid-template-columns: 1.4fr 0.8fr; gap: 1rem; margin-bottom: 1.25rem;
        }
        .pay-summary {
            border-radius: 18px; padding: 1.25rem;
            background: linear-gradient(120deg, color-mix(in srgb, var(--lime) 50%, #1a1634), var(--panel));
            border: 1px solid var(--border);
        }
        .pay-metrics { display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.85rem; margin-top: 0.85rem; }
        .pay-metrics strong { display: block; font-size: 1.15rem; }
        .pay-checklist { background: var(--panel); border: 1px solid var(--border); border-radius: 18px; padding: 1.1rem; }
        .pay-checklist li { list-style: none; margin: 0.45rem 0; color: var(--muted); }
        .pay-checklist ul { margin: 0; padding: 0; }
        .pay-line {
            background: var(--panel); border: 1px solid var(--border); border-radius: 16px;
            padding: 1rem 1.1rem; margin-bottom: 0.75rem;
        }
        .pay-line-grid { display: grid; grid-template-columns: 1.4fr repeat(5, 1fr); gap: 0.75rem; align-items: center; }
        .note-box {
            background: var(--note-bg); border: 1px solid var(--note-border);
            color: var(--note-text); border-radius: 12px; padding: 0.75rem 1rem; margin-bottom: 1rem; font-size: 0.88rem;
        }
        html[data-theme="light"] .summary-strip .metric strong,
        html[data-theme="light"] .stat-card strong,
        html[data-theme="light"] .page-head h1,
        html[data-theme="light"] .pay-metrics strong,
        html[data-theme="light"] .emp-row strong {
            color: #160f33;
        }
        html[data-theme="light"] .chip {
            color: #4f476c;
            border-color: #b8aedc;
            background: #ffffff;
        }
        html[data-theme="light"] .chip.active {
            color: #3f7c0c;
            border-color: #86a85a;
            background: #f0f7e6;
        }
        html[data-theme="light"] a { color: #4c3fd4; }
        html[data-theme="light"] .logout-dialog h3 { color: #160f33; }
        .doc-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 0.65rem; }
        .doc-card {
            border: 1px solid var(--border); border-radius: 12px; padding: 0.75rem; background: var(--panel-2);
            font-size: 0.85rem;
        }
        .doc-card .ok-dot { width: 8px; height: 8px; border-radius: 999px; background: var(--lime); display: inline-block; }
        .emp-form-panel {
            margin-bottom: 1.25rem;
            border: 1px solid var(--border);
            border-radius: 22px;
            background: var(--panel);
            box-shadow: var(--shadow);
            overflow: hidden;
        }
        .emp-form-head {
            display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem;
            padding: 1.15rem 1.25rem; border-bottom: 1px solid var(--border);
            background: linear-gradient(120deg, color-mix(in srgb, var(--lime) 18%, var(--panel)), var(--panel));
        }
        .emp-form-head h2 { margin: 0; font-family: Fraunces, Georgia, serif; font-size: 1.45rem; }
        .emp-form-body { padding: 1.15rem 1.25rem 1.35rem; }
        .emp-form-layout {
            display: grid; grid-template-columns: 220px 1fr; gap: 1.25rem; align-items: start;
        }
        .emp-form-photo {
            border: 1px dashed var(--border); border-radius: 18px; padding: 1rem;
            background: var(--panel-2); text-align: center;
        }
        .emp-form-photo .preview {
            width: 140px; height: 140px; border-radius: 18px; object-fit: cover; margin: 0 auto 0.75rem;
            background: var(--nav-active); display: flex; align-items: center; justify-content: center;
            font-size: 2rem; font-weight: 700; color: var(--muted); overflow: hidden;
        }
        .emp-form-photo .preview img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .emp-form-section {
            border: 1px solid var(--border); border-radius: 16px; padding: 1rem 1.05rem;
            margin-bottom: 0.9rem; background: var(--panel-2);
        }
        .emp-form-section:last-child { margin-bottom: 0; }
        .emp-form-section h3 {
            margin: 0 0 0.85rem; display: flex; align-items: center; gap: 0.5rem;
            font-size: 0.92rem; font-weight: 700;
        }
        .emp-form-section h3 .ico {
            width: 28px; height: 28px; border-radius: 8px;
            background: color-mix(in srgb, var(--lime) 30%, transparent); color: var(--lime);
            display: inline-flex; align-items: center; justify-content: center; font-size: 0.75rem;
        }
        .emp-form-actions {
            display: flex; justify-content: flex-end; gap: 0.65rem; flex-wrap: wrap;
            margin-top: 1.1rem; padding-top: 1rem; border-top: 1px solid var(--border);
        }
        .file-tile {
            border: 1px dashed var(--border); border-radius: 14px; padding: 0.85rem;
            background: var(--panel); min-height: 96px; display: flex; flex-direction: column; gap: 0.35rem;
        }
        .file-tile strong { font-size: 0.88rem; }
        .file-tile .hint { color: var(--muted); font-size: 0.75rem; }
        .file-tile input[type="file"] { margin: 0.35rem 0 0; font-size: 0.8rem; }
        @media (max-width: 900px) {
            .emp-form-layout { grid-template-columns: 1fr; }
        }
        @media (max-width: 900px) {
            .shell { flex-direction: column; }
            .sidebar {
                width: 100%;
                position: static;
                height: auto;
                max-height: none;
                overflow-y: visible;
            }
            .grid-2, .grid-3, .profile-shell, .pay-hero, .pay-line-grid, .salary-box { grid-template-columns: 1fr; }
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
            background: var(--panel-2);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 1.5rem;
            box-shadow: var(--shadow);
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
        @if (auth()->user()->hasPermission('finance', 'view') || auth()->user()->canViewFunding() || auth()->user()->canViewAllocations())
            <div class="nav-section">Finance</div>
            @if (auth()->user()->hasPermission('finance', 'view'))
                <a class="nav-link {{ request()->routeIs('invoices.*') ? 'active' : '' }}" href="{{ route('invoices.index') }}">Invoices</a>
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
            <a class="nav-link {{ request()->routeIs('finance.payroll*') ? 'active' : '' }}" href="{{ route('finance.payroll') }}">Pay Cycles</a>
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
            <div class="topbar-user">{{ '@'.$username }} {{ auth()->user()->full_name }}</div>
            <div class="topbar-actions">
                <span class="role-badge">{{ $roleLabel }}</span>
                <button type="button" class="theme-toggle" id="theme-toggle" aria-label="Toggle light mode" title="Toggle light / dark">
                    <svg class="icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 14.5A8.5 8.5 0 1 1 9.5 3 7 7 0 0 0 21 14.5z"/></svg>
                    <svg class="icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/></svg>
                </button>
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
    var toggle = document.getElementById('theme-toggle');
    if (toggle) {
        toggle.addEventListener('click', function () {
            var next = document.documentElement.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
            document.documentElement.setAttribute('data-theme', next);
            try { localStorage.setItem('sns-theme', next); } catch (e) {}
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
@stack('scripts')
</body>
</html>
