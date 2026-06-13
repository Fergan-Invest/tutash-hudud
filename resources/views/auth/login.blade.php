<!doctype html>
<html lang="uz">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kirish - Tutash hududlar reestri</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
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
            <input name="password" type="password" placeholder="Parolni kiriting" required>
        </label>
        @error('password')<p class="field-error">{{ $message }}</p>@enderror

        <label class="check-row"><input name="remember" type="checkbox" value="1"> Eslab qolish</label>
        <button class="primary-button full-button" type="submit">Tizimga kirish</button>
    </form>
</body>
</html>
