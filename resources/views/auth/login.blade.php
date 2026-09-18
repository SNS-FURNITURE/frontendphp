<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in · SNS Furniture</title>
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
            width:36px;
            height:36px;
            padding:0;
            border:0;
            border-radius:8px;
            background:transparent;
            color:#452F80;
            cursor:pointer;
            display:inline-flex;
            align-items:center;
            justify-content:center;
        }
        .password-toggle:hover { background:#452F8012; }
        .password-toggle svg { width:20px; height:20px; display:block; }
        .password-toggle .icon-hide { display:none; }
        .password-toggle.is-visible .icon-show { display:none; }
        .password-toggle.is-visible .icon-hide { display:block; }
        button[type=submit] {
            width:100%; height:44px; border:0; border-radius:12px;
            background:#E13B30; color:#fff; font-weight:700; cursor:pointer;
        }
        .error { color:#E13B30; font-size:0.85rem; margin-bottom:0.6rem; }
        .toast { background:#e8f7ef; color:#146c43; padding:0.65rem 0.85rem; border-radius:10px; margin-bottom:0.85rem; font-size:0.85rem; }
        .toast.warn { background:#fff4e5; color:#9a5b00; }
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
        @if (request('loggedOut'))
            <div class="toast">Signed out successfully. See you soon!</div>
        @endif
        @if (request('sessionExpired'))
            <div class="toast warn">Your session expired due to inactivity. Please sign in again.</div>
        @endif
        @if ($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif
        <form method="POST" action="{{ route('login') }}">
            @csrf
            <label for="email">Username/Email</label>
            <input id="email" name="email" type="text" value="{{ old('email') }}" autocomplete="username" required>
            <label for="password">Password</label>
            <div class="password-field">
                <input id="password" name="password" type="password" autocomplete="current-password" required>
                <button type="button" class="password-toggle" id="toggle-password" aria-label="Show password" aria-pressed="false" title="Show password">
                    <svg class="icon-show" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                    <svg class="icon-hide" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"></path>
                        <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"></path>
                        <path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"></path>
                        <line x1="1" y1="1" x2="23" y2="23"></line>
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
