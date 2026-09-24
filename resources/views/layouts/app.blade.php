<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('permission-toolkit::messages.layout_title')) - Spatie Toolkit</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    <script>
        (function() {
            const savedTheme = localStorage.getItem('ptk-theme') || (window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark');
            if (savedTheme === 'light') {
                document.documentElement.setAttribute('data-theme', 'light');
            }
        })();
    </script>
    <style>
        :root {
            --bg-body: #0b0f19;
            --bg-card: #111827;
            --bg-card-hover: #1f2937;
            --bg-header: #171f2e;
            --border: #374151;
            --text-main: #f9fafb;
            --text-muted: #9ca3af;
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
        }
        [data-theme="light"] {
            --bg-body: #f8fafc;
            --bg-card: #ffffff;
            --bg-card-hover: #f1f5f9;
            --bg-header: #f8fafc;
            --border: #e2e8f0;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --success: #059669;
            --danger: #dc2626;
            --warning: #d97706;
        }
        [data-theme="light"] .badge-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        [data-theme="light"] .badge-danger { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        [data-theme="light"] .badge-warning { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        [data-theme="light"] .badge-info { background: #e0e7ff; color: #3730a3; border: 1px solid #c7d2fe; }
        [data-theme="light"] tr:hover td { background-color: rgba(79, 70, 229, 0.04); }
        [data-theme="light"] #matrixTable thead th,
        [data-theme="light"] #matrixTable thead th:first-child {
            background-color: #f1f5f9 !important;
            color: #1e293b !important;
        }
        [data-theme="light"] #matrixTable tbody td:first-child {
            background-color: #ffffff !important;
            color: #0f172a !important;
        }
        [data-theme="light"] .module-header-sticky {
            background: #ede9fe !important;
            color: #5b21b6 !important;
        }
        [data-theme="light"] .input-control {
            background-color: #ffffff;
            color: #0f172a;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        header {
            background-color: var(--bg-card);
            border-bottom: 1px solid var(--border);
            padding: 0.85rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 100;
        }
        .brand {
            font-weight: 700;
            font-size: 1.15rem;
            color: var(--text-main);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }
        .brand span {
            font-size: 0.75rem;
            background: #1e1b4b;
            color: #a5b4fc;
            padding: 0.2rem 0.5rem;
            border-radius: 9999px;
            border: 1px solid #4338ca;
        }
        nav.main-nav { display: flex; gap: 0.5rem; }
        nav.main-nav a {
            color: var(--text-muted);
            text-decoration: none;
            padding: 0.5rem 0.85rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.2s;
        }
        nav.main-nav a:hover, nav.main-nav a.active {
            color: var(--text-main);
            background-color: var(--bg-card-hover);
        }
        nav.main-nav a.active {
            border-bottom: 2px solid var(--primary);
            color: #a5b4fc;
        }
        main {
            flex: 1;
            padding: 1.25rem 2rem;
            max-width: 98vw;
            margin: 0 auto;
            width: 100%;
        }
        .card {
            background-color: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 0.5rem;
            padding: 1.25rem 1.5rem;
            margin-bottom: 1.5rem;
        }
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        .card-title {
            font-size: 1.15rem;
            font-weight: 600;
        }
        .badge {
            display: inline-block;
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .badge-success { background: #064e3b; color: #6ee7b7; border: 1px solid #047857; }
        .badge-danger { background: #7f1d1d; color: #fca5a5; border: 1px solid #b91c1c; }
        .badge-warning { background: #78350f; color: #fde68a; border: 1px solid #d97706; }
        .badge-info { background: #1e1b4b; color: #a5b4fc; border: 1px solid #4338ca; }
        .table-responsive { overflow: auto; }
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            text-align: left;
            font-size: 0.875rem;
        }
        th, td {
            padding: 0.65rem 1rem;
            border-bottom: 1px solid var(--border);
            border-right: 1px solid rgba(255,255,255,0.05);
        }
        th {
            background-color: #171f2e;
            color: var(--text-muted);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
        }
        tr:hover td { background-color: rgba(255, 255, 255, 0.03); }
        .btn {
            background-color: var(--primary);
            color: #fff;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            white-space: nowrap;
        }
        .btn:hover { background-color: var(--primary-hover); }
        .btn-secondary {
            background-color: #1f2937;
            border: 1px solid var(--border);
            color: var(--text-main);
        }
        .btn-secondary:hover {
            background-color: #374151;
        }
        .input-control {
            background: #171f2e;
            border: 1px solid var(--border);
            color: var(--text-main);
            padding: 0.5rem 0.75rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            width: 100%;
        }
        .input-control:focus {
            outline: none;
            border-color: var(--primary);
        }

        /* Fixed Pagination & Capped SVG */
        nav[role="navigation"] {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 1.5rem;
            font-size: 0.85rem;
            color: var(--text-muted);
        }
        nav[role="navigation"] svg {
            width: 1rem !important;
            height: 1rem !important;
            max-width: 1.25rem !important;
            max-height: 1.25rem !important;
            display: inline-block !important;
            vertical-align: middle !important;
        }
        nav[role="navigation"] .flex,
        nav[role="navigation"] div:last-child {
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }
        nav[role="navigation"] a,
        nav[role="navigation"] span {
            padding: 0.35rem 0.7rem;
            border-radius: 0.375rem;
            border: 1px solid var(--border);
            background: #171f2e;
            color: var(--text-main);
            text-decoration: none;
            font-size: 0.8rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        nav[role="navigation"] a:hover {
            background: var(--bg-card-hover);
            border-color: var(--primary);
        }
        nav[role="navigation"] span[aria-current="page"] {
            background: var(--primary) !important;
            border-color: var(--primary) !important;
            color: #ffffff !important;
            font-weight: 600;
        }
        nav[role="navigation"] span[aria-disabled="true"] {
            opacity: 0.4;
            cursor: not-allowed;
        }

        #toast-container {
            position: fixed;
            bottom: 1.5rem;
            right: 1.5rem;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .toast {
            background: #1e1b4b;
            color: #fff;
            padding: 0.75rem 1.25rem;
            border-radius: 0.375rem;
            border: 1px solid #4338ca;
            font-size: 0.875rem;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.5);
            animation: fadeIn 0.3s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
    @stack('styles')
</head>
<body>
    <header>
        <div style="display: flex; align-items: center; gap: 1.5rem;">
            <a href="{{ route('permission-toolkit.matrix') }}" class="brand">
                🛡️ <span>Permission Toolkit</span>
            </a>
        </div>
        <div style="display: flex; align-items: center; gap: 1.5rem;">
            <nav class="main-nav">
                <a href="{{ route('permission-toolkit.matrix') }}" class="{{ request()->routeIs('permission-toolkit.matrix') || request()->routeIs('permission-toolkit.index') ? 'active' : '' }}">
                    🔲 {{ __('permission-toolkit::messages.nav_matrix') }}
                </a>
                <a href="{{ route('permission-toolkit.users.index') }}" class="{{ request()->routeIs('permission-toolkit.users.*') ? 'active' : '' }}">
                    👥 {{ __('permission-toolkit::messages.nav_users') }}
                </a>
                <a href="{{ route('permission-toolkit.simulator') }}" class="{{ request()->routeIs('permission-toolkit.simulator') ? 'active' : '' }}">
                    🔍 {{ __('permission-toolkit::messages.nav_simulator') }}
                </a>
                <a href="{{ route('permission-toolkit.audit') }}" class="{{ request()->routeIs('permission-toolkit.audit') ? 'active' : '' }}">
                    📜 {{ __('permission-toolkit::messages.nav_audit') }}
                </a>
                <a href="{{ route('permission-toolkit.doctor') }}" class="{{ request()->routeIs('permission-toolkit.doctor') ? 'active' : '' }}">
                    🩺 {{ __('permission-toolkit::messages.nav_doctor') }}
                </a>
            </nav>

            <!-- Language Switcher -->
            <div class="lang-switcher" style="display: inline-flex; align-items: center; gap: 0.2rem; background: var(--bg-card-hover); border: 1px solid var(--border); border-radius: 0.375rem; padding: 0.2rem 0.35rem;">
                <a href="{{ route('permission-toolkit.locale', 'it') }}" 
                   style="text-decoration: none; font-size: 0.75rem; font-weight: 600; padding: 0.25rem 0.5rem; border-radius: 0.25rem; transition: all 0.2s; {{ app()->getLocale() === 'it' ? 'background: var(--primary); color: #ffffff;' : 'color: var(--text-muted);' }}"
                   title="Italiano">
                    🇮🇹 IT
                </a>
                <a href="{{ route('permission-toolkit.locale', 'en') }}" 
                   style="text-decoration: none; font-size: 0.75rem; font-weight: 600; padding: 0.25rem 0.5rem; border-radius: 0.25rem; transition: all 0.2s; {{ app()->getLocale() === 'en' ? 'background: var(--primary); color: #ffffff;' : 'color: var(--text-muted);' }}"
                   title="English">
                    🇬🇧 EN
                </a>
            </div>

            <!-- Theme Toggle Button -->
            <button type="button" 
                    id="theme-toggle-btn"
                    onclick="toggleTheme()"
                    title="Toggle Theme"
                    style="background: var(--bg-card-hover); border: 1px solid var(--border); color: var(--text-main); border-radius: 0.375rem; padding: 0.35rem 0.65rem; cursor: pointer; display: inline-flex; align-items: center; font-size: 0.9rem; transition: all 0.2s;">
                <span id="theme-toggle-icon">🌙</span>
            </button>
        </div>
    </header>

    <main>
        @yield('content')
    </main>

    <div id="toast-container"></div>

    <script>
        function updateThemeIcon() {
            const isLight = document.documentElement.getAttribute('data-theme') === 'light';
            const icon = document.getElementById('theme-toggle-icon');
            if (icon) icon.innerText = isLight ? '☀️' : '🌙';
        }
        function toggleTheme() {
            const current = document.documentElement.getAttribute('data-theme');
            const target = current === 'light' ? 'dark' : 'light';
            if (target === 'light') {
                document.documentElement.setAttribute('data-theme', 'light');
            } else {
                document.documentElement.removeAttribute('data-theme');
            }
            localStorage.setItem('ptk-theme', target);
            updateThemeIcon();
        }
        document.addEventListener('DOMContentLoaded', updateThemeIcon);

        function showToast(message, type = 'info') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = 'toast';
            toast.innerText = message;
            container.appendChild(toast);
            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
    </script>
    @stack('scripts')
</body>
</html>
