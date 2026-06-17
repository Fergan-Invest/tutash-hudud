<!doctype html>
<html lang="uz">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Tutash hududlar reestri')</title>
    <link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    <script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}" defer></script>
</head>
<body>
<div class="app-shell">
    <div class="sidebar-backdrop" data-sidebar-close hidden></div>
    <aside class="sidebar" id="app-sidebar">
        <a class="brand" href="{{ route('requests.index') }}">
            <span class="brand-mark">T</span>
            <span><strong>Tutash hududlar reestri</strong><small>Tadbirkor kabineti</small></span>
        </a>

        <nav class="nav-list" aria-label="Asosiy menyu">
            <a class="nav-link {{ request()->routeIs('requests.index') ? 'active' : '' }}" href="{{ route('requests.index') }}">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h6v6h-6z"/></svg>
                <span>Bosh sahifa</span>
            </a>
            <a class="nav-link {{ request()->routeIs('requests.monitoring') ? 'active' : '' }}" href="{{ route('requests.monitoring') }}">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19V5"/><path d="M4 19h16"/><path d="M8 16V9"/><path d="M12 16V7"/><path d="M16 16v-4"/></svg>
                <span>Monitoring</span>
            </a>
            @can('create', App\Models\RegistryRequest::class)
                <a class="nav-link {{ request()->routeIs('requests.create') ? 'active' : '' }}" href="{{ route('requests.create') }}">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6.5 12 3l8 3.5v11L12 21l-8-3.5z"/><path d="M8 9.5h8M8 13h5"/></svg>
                    <span>Yangi ariza</span>
                </a>
            @endcan
            <a class="nav-link {{ request()->routeIs('addresses.*') ? 'active' : '' }}" href="{{ route('addresses.index') }}">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h9l3 3v15H6z"/><path d="M14 3v4h4M9 12h6M9 16h6"/></svg>
                <span>Manzillar</span>
            </a>
            <a class="nav-link {{ request()->routeIs('users.online') ? 'active' : '' }}" href="{{ route('users.online') }}">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 3 10 18H2z"/><path d="M12 9v5M12 17h.01"/></svg>
                <span>Online foydalanuvchilar</span>
            </a>
        </nav>
    </aside>

    <div class="content">
        <header class="topbar">
            <div class="topbar-title">
                <button class="menu-button" type="button" aria-label="Menyuni ochish" aria-controls="app-sidebar" aria-expanded="false"><span></span><span></span><span></span></button>
                <span class="breadcrumb">@yield('breadcrumb', 'Kadastr uchastkalari')</span>
            </div>

            <div class="topbar-actions">
                @yield('topbar-actions')
                <a class="report-link" href="mailto:invest.abdusattorov@gmail.com">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"/><path d="M8 9h8M8 13h5"/></svg>
                    <span>Xatolik haqida xabar berish</span>
                </a>
                <button class="bell-button" type="button" aria-label="Bildirishnomalar">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M10 21h4"/></svg>
                </button>
                <div class="user-card">
                    <span class="user-avatar">{{ mb_substr(auth()->user()->name, 0, 1) }}</span>
                    <span>{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="link-button" type="submit">Chiqish</button></form>
                </div>
            </div>
        </header>

        @if(session('success'))
            <div class="alert success">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="alert danger validation-summary">
                <strong>Ma'lumotlarda xatolik bor. Quyidagi maydonlarni tekshiring:</strong>
                <ul>
                    @foreach($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <main>@yield('content')</main>
    </div>
</div>
</body>
</html>
