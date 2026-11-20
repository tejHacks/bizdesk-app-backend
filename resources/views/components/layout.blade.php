@props(['title' => null])
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? "$title · " : '' }}BizDesk</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    <style>
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; margin-bottom: 18px; }
        .stat { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: 14px 16px; }
        .stat small { color: var(--muted); display: block; }
        .stat strong { font-size: 1.45rem; font-weight: 800; display: block; margin-top: 4px; overflow-wrap: anywhere; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
        .bars { display: flex; align-items: flex-end; gap: 8px; height: 150px; padding-top: 10px; }
        .bars div { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; height: 100%; }
        .bars span { width: 100%; background: var(--accent); border-radius: 6px 6px 2px 2px; min-height: 2px; opacity: .8; }
        .bars small { color: var(--muted); font-size: .75rem; margin-top: 4px; }
        table.list { width: 100%; border-collapse: collapse; font-size: .94rem; }
        table.list td, table.list th { text-align: left; padding: 8px 6px; border-bottom: 1px solid var(--border); }
        table.list td.num, table.list th.num { text-align: right; white-space: nowrap; }
        @media (max-width: 760px) { .grid-2 { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<header class="topbar">
    <div class="topbar-inner">
        <a href="{{ url('/') }}" class="brand"><span class="brand-mark">B</span><span>BizDesk</span></a>
        <nav class="topnav">
            @auth <a href="{{ route('dashboard') }}" class="active">Dashboard</a> @endauth
        </nav>
        @auth
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-sm">Log out</button></form>
        @endauth
    </div>
</header>
<main class="container">
    {{ $slot }}
</main>
<footer class="footer"><p>BizDesk API · Laravel {{ app()->version() }} · REST API at <code>/api/v1</code></p></footer>
</body>
</html>
