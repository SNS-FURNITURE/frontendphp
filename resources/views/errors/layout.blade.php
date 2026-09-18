<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · SNS Furniture</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0d0b21;
            --panel: #16132e;
            --line: #2a2550;
            --ink: #f4f2ff;
            --muted: #9b94b8;
            --brand: #362870;
            --brand-mid: #452F80;
            --accent: #E13B30;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100vh; display: grid; place-items: center;
            font-family: Outfit, system-ui, sans-serif; color: var(--ink);
            background:
                radial-gradient(ellipse at top, rgba(54, 40, 112, 0.45), transparent 55%),
                var(--bg);
            padding: 1.5rem;
        }
        .card {
            width: min(440px, 100%); background: var(--panel);
            border: 1px solid var(--line); border-radius: 20px;
            padding: 2rem 1.75rem; text-align: center;
        }
        .code {
            display: inline-block; font-family: Fraunces, Georgia, serif;
            font-size: 3rem; color: var(--accent); line-height: 1; margin: 0 0 0.5rem;
        }
        h1 {
            font-family: Fraunces, Georgia, serif; font-size: 1.55rem;
            margin: 0 0 0.65rem; color: var(--ink);
        }
        p { margin: 0 0 1.35rem; color: var(--muted); line-height: 1.55; }
        .actions { display: flex; flex-wrap: wrap; gap: 0.65rem; justify-content: center; }
        a.btn {
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: 999px; padding: 0.7rem 1.15rem; font-weight: 700;
            font-size: 0.92rem; text-decoration: none;
        }
        a.primary { background: var(--brand-mid); color: #fff; }
        a.primary:hover { background: var(--brand); }
        a.ghost { border: 1px solid var(--line); color: var(--ink); }
        a.ghost:hover { border-color: var(--accent); color: #ff6b6b; }
        .logo { width: 56px; height: 56px; border-radius: 14px; background: #fff; margin: 0 auto 1rem; overflow: hidden; }
        .logo img { width: 100%; height: 100%; object-fit: contain; }
    </style>
</head>
<body>
    <div class="card">
        <div class="logo"><img src="{{ asset('sns-logo.png') }}" alt="SNS Furniture"></div>
        <div class="code">@yield('code')</div>
        <h1>@yield('heading')</h1>
        <p>@yield('message')</p>
        <div class="actions">
            @yield('actions')
        </div>
    </div>
</body>
</html>
