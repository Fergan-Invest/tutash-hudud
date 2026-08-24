<!doctype html>
<html lang="uz">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kirish - Tutash hududlar reestri</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    <script src="{{ asset('js/login.js') }}?v={{ filemtime(public_path('js/login.js')) }}" defer></script>
</head>
<body class="auth-page">
    <form class="login-card compact-login-card" method="POST" action="{{ route('login.store') }}">
        @csrf
        <div class="brand auth-brand">
            <span class="brand-mark">T</span>
            <span><strong>Tutash hududlar reestri</strong><small>Tizimga kirish</small></span>
        </div>

        <label>Email
            <input name="email" type="email" value="{{ old('email') }}" placeholder="login@example.local" required autofocus>
        </label>
        @error('email')<p class="field-error">{{ $message }}</p>@enderror

        <label>Parol
            <span class="password-field">
                <input id="password" name="password" type="password" placeholder="Parolni kiriting" required>
                <button class="password-toggle" type="button" data-password-toggle aria-label="Parolni ko‘rsatish" aria-controls="password" aria-pressed="false">
                    <svg class="eye-open" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                    <svg class="eye-closed" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="m3 3 18 18M10.6 10.7a2 2 0 0 0 2.7 2.7M9.9 4.2A10.8 10.8 0 0 1 12 4c6.5 0 10 8 10 8a17 17 0 0 1-2.1 3.2M6.6 6.6C3.7 8.5 2 12 2 12s3.5 8 10 8a9.8 9.8 0 0 0 4.1-.9"/>
                    </svg>
                </button>
            </span>
        </label>
        @error('password')<p class="field-error">{{ $message }}</p>@enderror

        <label class="check-row"><input name="remember" type="checkbox" value="1"> Eslab qolish</label>
        <button class="primary-button full-button" type="submit">Tizimga kirish</button>
    </form>
</body>
</html>
