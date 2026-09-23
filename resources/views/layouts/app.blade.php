<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Role & Permission Manager') - Spatie Toolkit</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    <style>
        :root {
            --bg-body: #0b0f19;
            --bg-card: #111827;
            --bg-card-hover: #1f2937;
            --border: #374151;
            --text-main: #f9fafb;
            --text-muted: #9ca3af;
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
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
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
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
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
        }
        .card {
            background-color: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.25rem;
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
        .table-responsive { overflow-x: auto; }
        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.875rem;
        }
        th, td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--border);
        }
        th {
            background-color: #171f2e;
            color: var(--text-muted);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
        }
        tr:hover td { background-color: rgba(255, 255, 255, 0.02); }
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
        <a href="{{ route('permission-toolkit.matrix') }}" class="brand">
            🛡️ <span>Permission Toolkit</span>
        </a>
        <nav class="main-nav">
            <a href="{{ route('permission-toolkit.matrix') }}" class="{{ request()->routeIs('permission-toolkit.matrix') ? 'active' : '' }}">
                🔲 Matrice Ruoli
            </a>
            <a href="{{ route('permission-toolkit.users.index') }}" class="{{ request()->routeIs('permission-toolkit.users.*') ? 'active' : '' }}">
                👥 Gestione Utenti
            </a>
            <a href="{{ route('permission-toolkit.simulator') }}" class="{{ request()->routeIs('permission-toolkit.simulator') ? 'active' : '' }}">
                🔍 Diagnostic Simulator
            </a>
            <a href="{{ route('permission-toolkit.audit') }}" class="{{ request()->routeIs('permission-toolkit.audit') ? 'active' : '' }}">
                📜 Audit Trail
            </a>
            <a href="{{ route('permission-toolkit.doctor') }}" class="{{ request()->routeIs('permission-toolkit.doctor') ? 'active' : '' }}">
                🩺 Integrity Doctor
            </a>
        </nav>
    </header>

    <main>
        @yield('content')
    </main>

    <div id="toast-container"></div>

    <script>
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
