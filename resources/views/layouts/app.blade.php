<!doctype html>
<html lang="uz">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Tutash hududlar reestri')</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" defer></script>
    <script src="{{ asset('js/app.js') }}" defer></script>
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <a class="brand" href="{{ route('requests.index') }}">
            <span class="brand-mark">T</span>
            <span><strong>Tutash hududlar reestri</strong><small>Farg‘ona viloyati</small></span>
        </a>
        <nav class="nav-list">
            <a class="nav-link {{ request()->routeIs('requests.index') ? 'active' : '' }}" href="{{ route('requests.index') }}">Reestr</a>
            @can('create', App\Models\RegistryRequest::class)
                <a class="nav-link {{ request()->routeIs('requests.create') ? 'active' : '' }}" href="{{ route('requests.create') }}">Yangi ariza</a>
            @endcan
            <a class="nav-link {{ request()->routeIs('addresses.index') ? 'active' : '' }}" href="{{ route('addresses.index') }}">Manzillar</a>
            <a class="nav-link" href="{{ route('users.online') }}">Online foydalanuvchilar</a>
        </nav>
    </aside>

    <div class="content">
        <header class="topbar">
            <div>
                <button class="menu-button" type="button" aria-label="Menyuni ochish"><span></span><span></span><span></span></button>
                <span class="breadcrumb">@yield('breadcrumb', 'Reestr')</span>
            </div>
            <div class="user-card">
                <span class="user-avatar">{{ mb_substr(auth()->user()->name, 0, 1) }}</span>
                <span>{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="link-button" type="submit">Chiqish</button></form>
            </div>
        </header>

        @if(session('success'))
            <div class="alert success">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="alert danger">Ma’lumotlarda xatolik bor. Maydonlarni tekshiring.</div>
        @endif

        <main>@yield('content')</main>
    </div>
</div>
</body>
</html>
