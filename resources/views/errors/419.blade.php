<!doctype html>
<html lang="uz">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sessiya muddati tugadi</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="auth-page">
    <main class="login-card error-card">
        <div class="brand auth-brand">
            <span class="brand-mark">T</span>
            <span><strong>Tutash hududlar reestri</strong><small>Tizim xabari</small></span>
        </div>

        <div class="error-icon" aria-hidden="true">419</div>
        <h1>Sessiya muddati tugadi</h1>
        <p>Forma uzoq vaqt ochiq qolgan yoki sahifa eski token bilan yuborilgan. Sahifani yangilang va qayta urinib ko‘ring.</p>

        <div class="error-actions">
            <button class="primary-button" type="button" onclick="window.location.reload()">Sahifani yangilash</button>
            @auth
                <a class="secondary-button" href="{{ route('requests.index') }}">Bosh sahifaga qaytish</a>
            @else
                <a class="secondary-button" href="{{ route('login') }}">Qayta kirish</a>
            @endauth
        </div>
    </main>
</body>
</html>
