<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SNS Furniture</title>
    <link rel="icon" type="image/png" href="{{ asset('sns-logo.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('sns-logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('sns-logo.png') }}">
    <style>
        body { margin:0; font-family: Georgia, 'Times New Roman', serif; min-height:100vh; display:flex; }
        .left {
            width: 42%;
            display:flex; flex-direction:column; align-items:center; justify-content:center;
            background:#fff; padding:2rem; text-align:center;
            background-image: radial-gradient(#452F80 0.6px, transparent 0.6px);
            background-size: 20px 20px;
            position: relative;
        }
        .left h1 { color:#24194D; margin:0.5rem 0 0; font-size:2.4rem; font-family: Georgia, serif; }
        .eyebrow { color:#452F80b3; letter-spacing:0.2em; text-transform:uppercase; font-size:0.7rem; font-weight:700; margin-top:1rem; font-family: system-ui, sans-serif; }
        .right {
            flex:1; display:flex; align-items:center; justify-content:center;
            background: linear-gradient(135deg, #332461, #452F80, #24194D);
            padding:2rem;
            clip-path: ellipse(120% 100% at 70% 50%);
        }
        .card {
            width:100%; max-width:420px; background:rgba(255,255,255,0.97);
            border-radius:24px; padding:2rem; box-shadow: 0 25px 50px rgba(0,0,0,0.25);
            font-family: system-ui, sans-serif;
        }
        .card h2 { margin-top:0; color:#24194D; font-family: Georgia, serif; font-size:1.8rem; }
        label { display:block; font-size:0.75rem; font-weight:700; color:#24194D; margin-bottom:0.3rem; }
        input[type=text], input[type=password] {
            width:100%; height:44px; border-radius:12px; border:1px solid #452F804d;
            padding:0 0.8rem; margin-bottom:0.9rem; box-sizing:border-box;
        }
        .password-field { position:relative; margin-bottom:0.9rem; }
        .password-field input {
            margin-bottom:0;
            padding-right:2.8rem;
        }
        .password-toggle {
            position:absolute;
            right:0.35rem;
            top:50%;
            transform:translateY(-50%);
            width:40px;
            height:40px;
            padding:0;
            border:0;
            border-radius:10px;
            background:transparent;
            color:#452F80;
            cursor:pointer;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            line-height:0;
            font-size:0;
        }
        .password-toggle:hover { background:#452F8014; color:#24194D; }
        .password-toggle svg {
            width:22px;
            height:22px;
            display:block;
            flex-shrink:0;
        }
        .password-toggle .icon-hide { display:none !important; }
        .password-toggle.is-visible .icon-show { display:none !important; }
        .password-toggle.is-visible .icon-hide { display:block !important; }
        button[type=submit] {
            width:100%; height:44px; border:0; border-radius:12px;
            background:#E13B30; color:#fff; font-weight:700; cursor:pointer;
        }
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
            font-family: system-ui, sans-serif;
        }
        .toast-popup {
            pointer-events: auto;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 0.9rem 1rem;
            border-radius: 12px;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.25);
            font-size: 0.92rem;
            line-height: 1.4;
            animation: toast-in 0.28s ease-out;
            transition: opacity 0.2s ease;
        }
        .toast-popup.is-hiding { opacity: 0; }
        .toast-popup.success { background: #e8f7ef; color: #146c43; border: 1px solid #b7e4c7; }
        .toast-popup.warn { background: #fff4e5; color: #9a5b00; border: 1px solid #f0d9a8; }
        .toast-popup.error { background: #fde8e8; color: #E13B30; border: 1px solid #f5c2c2; }
        .toast-popup .toast-body { flex: 1; min-width: 0; }
        .toast-popup .toast-close {
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
        @keyframes toast-in {
            from { opacity: 0; transform: translateY(-0.6rem); }
            to { opacity: 1; transform: translateY(0); }
        }
        .remember { display:flex; align-items:center; gap:0.4rem; margin-bottom:1rem; color:#24194D; font-size:0.9rem; }
        .logo {
            width:120px; height:120px; border-radius:24px; background:#fff;
            display:flex; align-items:center; justify-content:center;
            box-shadow: 0 10px 30px rgba(36,25,77,0.12);
            overflow:hidden;
        }
        .logo img { max-width:100%; max-height:100%; object-fit:contain; }
        .back {
            position:absolute; bottom:1.5rem; left:50%; transform:translateX(-50%);
            border:1px solid #cbbfea; border-radius:999px; padding:0.4rem 1rem;
            color:#452F80; font-size:0.85rem; text-decoration:none; font-family: system-ui, sans-serif;
        }
        @media (max-width: 800px) {
            body { flex-direction: column; }
            .left { width:100%; min-height:40vh; }
            .right { clip-path: none; }
        }
    </style>
</head>
<body>
@php
    $loginToasts = [];
    if (request('loggedOut')) {
        $loginToasts[] = ['type' => 'success', 'text' => 'Signed out successfully. See you soon!'];
    }
    if (request('sessionExpired')) {
        $loginToasts[] = ['type' => 'warn', 'text' => 'Your session expired due to inactivity. Please sign in again.'];
    }
    if (isset($errors) && $errors->any()) {
        $loginToasts[] = ['type' => 'error', 'text' => (string) $errors->first()];
    }
@endphp
<div class="toast-host" id="login-toasts" aria-live="polite"></div>
<div class="left">
    <div class="logo"><img src="{{ asset('sns-logo.png') }}" alt="SNS Furniture"></div>
    <div class="eyebrow">Staff Portal</div>
    <h1>SNS Furniture</h1>
    <a class="back" href="#">← Back to the website</a>
</div>
<div class="right">
    <div class="card">
        <h2>Sign in</h2>
        <p style="color:#452F80b3;margin-top:0">Enter your workspace credentials</p>
        <form method="POST" action="{{ route('login') }}">
            @csrf
            <label for="email">Username/Email</label>
            <input id="email" name="email" type="text" value="{{ old('email') }}" autocomplete="username" required>
            <label for="password">Password</label>
            <div class="password-field">
                <input id="password" name="password" type="password" autocomplete="current-password" required>
                <button type="button" class="password-toggle" id="toggle-password" aria-label="Show password" aria-pressed="false" title="Show password">
                    <svg class="icon-show" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                    </svg>
                    <svg class="icon-hide" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78 3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/>
                    </svg>
                </button>
            </div>
            <label class="remember">
                <input type="checkbox" name="remember_me" value="1" @checked(old('remember_me'))>
                Remember me
            </label>
            <button type="submit">Sign in to Workspace</button>
        </form>
    </div>
</div>
<script>
(function () {
    var host = document.getElementById('login-toasts');
    var initial = @json($loginToasts);
    function dismiss(el) {
        el.classList.add('is-hiding');
        setTimeout(function () { el.remove(); }, 200);
    }
    function showToast(type, text) {
        if (!host || !text) return;
        var el = document.createElement('div');
        el.className = 'toast-popup ' + (type || 'success');
        el.setAttribute('role', 'status');
        var body = document.createElement('div');
        body.className = 'toast-body';
        body.textContent = text;
        var close = document.createElement('button');
        close.type = 'button';
        close.className = 'toast-close';
        close.setAttribute('aria-label', 'Dismiss');
        close.innerHTML = '&times;';
        close.addEventListener('click', function () { dismiss(el); });
        el.appendChild(body);
        el.appendChild(close);
        host.appendChild(el);
        setTimeout(function () { if (el.parentNode) dismiss(el); }, 5000);
    }
    (initial || []).forEach(function (t) { showToast(t.type, t.text); });

    var input = document.getElementById('password');
    var btn = document.getElementById('toggle-password');
    if (!input || !btn) return;
    btn.addEventListener('click', function () {
        var showing = input.type === 'text';
        input.type = showing ? 'password' : 'text';
        btn.classList.toggle('is-visible', !showing);
        btn.setAttribute('aria-pressed', showing ? 'false' : 'true');
        btn.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
        btn.title = showing ? 'Show password' : 'Hide password';
        input.focus();
    });
})();
</script>
</body>
</html>
