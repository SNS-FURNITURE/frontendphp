import re

with open('resources/views/layouts/app.blade.php', 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Update :root and add [data-theme='dark']
root_css = '''        :root {
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
            --input-bg: #ffffff;
            --color-scheme: light;
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
        }'''
content = re.sub(r'        :root \{[\s\S]*?--accent: #4f46e5;\n        \}', root_css, content)

# 2. Replace hardcoded values with variables
content = content.replace('background: #f1f5f9;', 'background: var(--nav-hover);')
content = content.replace('background: rgba(255, 255, 255, 0.85);', 'background: var(--topbar-bg);')
content = content.replace('background: #e0e7ff;\n            color: #4338ca;', 'background: var(--role-bg);\n            color: var(--role-text);')
content = content.replace('box-shadow: 0 1px 3px rgba(0,0,0,0.05);', 'box-shadow: var(--card-shadow);')
content = content.replace('color: #ffffff;\n            font-weight: 600;\n            cursor: pointer;\n            font-size: 0.9rem;\n            box-shadow: 0 1px 2px rgba(0,0,0,0.05);', 'color: var(--btn-text);\n            font-weight: 600;\n            cursor: pointer;\n            font-size: 0.9rem;\n            box-shadow: var(--btn-shadow);')
content = content.replace('background: #dcfce7;\n            color: #166534;\n            border: 1px solid #bbf7d0;', 'background: var(--toast-succ-bg);\n            color: var(--toast-succ-txt);\n            border: 1px solid var(--toast-succ-bd);')
content = content.replace('background: #fee2e2;\n            color: #991b1b;\n            border: 1px solid #fecaca;', 'background: var(--toast-err-bg);\n            color: var(--toast-err-txt);\n            border: 1px solid var(--toast-err-bd);')
content = content.replace('background: #ffffff;\n            color: var(--text);\n            box-shadow: inset 0 1px 2px rgba(0,0,0,0.02);', 'background: var(--input-bg);\n            color: var(--text);\n            box-shadow: inset 0 1px 2px rgba(0,0,0,0.02);')
content = content.replace('color-scheme: light;', 'color-scheme: var(--color-scheme);')
content = content.replace('background-color: var(--accent);', 'background-color: var(--cal-bg);')
content = content.replace('''background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23ffffff' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='3' y='4' width='18' height='18' rx='2'/%3E%3Cline x1='16' y1='2' x2='16' y2='6'/%3E%3Cline x1='8' y1='2' x2='8' y2='6'/%3E%3Cline x1='3' y1='10' x2='21' y2='10'/%3E%3C/svg%3E");''', 'background-image: var(--cal-icon);')
content = content.replace('background: #e0e7ff;\n            color: var(--accent);', 'background: var(--badge-bg);\n            color: var(--accent);')
content = content.replace('background: #ffffff;\n            color: var(--text);\n            border-radius: 8px;', 'background: var(--nav-toggle-bg);\n            color: var(--text);\n            border-radius: 8px;')
content = content.replace('background: rgba(15, 23, 42, 0.4);\n            z-index: 40;\n            backdrop-filter: blur(2px);', 'background: var(--backdrop);\n            z-index: 40;\n            backdrop-filter: blur(2px);')
content = content.replace('background: rgba(15, 23, 42, 0.4);\n            backdrop-filter: blur(4px);', 'background: var(--backdrop);\n            backdrop-filter: blur(4px);')
content = content.replace('background: #ffffff;\n            border: 1px solid var(--border);\n            border-radius: 12px;\n            padding: 1.5rem;\n            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);', 'background: var(--dialog-bg);\n            border: 1px solid var(--border);\n            border-radius: 12px;\n            padding: 1.5rem;\n            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);')

# 3. Add toggle button
toggle_btn = '''<button type="button" class="btn ghost" id="theme-toggle" aria-label="Toggle Theme" style="padding: 0.35rem 0.5rem;" title="Toggle Dark/Light Mode">
                    <svg id="theme-icon-dark" style="display:none; width: 18px; height: 18px;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
                    </svg>
                    <svg id="theme-icon-light" style="display:block; width: 18px; height: 18px;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
                    </svg>
                </button>
                <span class="role-badge">'''
content = content.replace('<span class="role-badge">', toggle_btn)

# 4. Add JS to handle theme toggle
js_script = '''
    var savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark') {
        document.documentElement.setAttribute('data-theme', 'dark');
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

    var SIDEBAR_SCROLL_KEY = 'sns.sidebar.scrollTop';'''

content = content.replace("var SIDEBAR_SCROLL_KEY = 'sns.sidebar.scrollTop';", js_script)

# 5. Fix HTML initial theme load flicker by adding a small script in <head>
theme_head_script = '''<script>
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
        }
    </script>
    <style>'''
content = content.replace('<style>', theme_head_script, 1)

# Write it back
with open('resources/views/layouts/app.blade.php', 'w', encoding='utf-8') as f:
    f.write(content)
