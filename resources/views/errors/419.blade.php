<!doctype html>
<html lang="uz">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
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
        <p>Forma uzoq vaqt ochiq qolgan yoki sahifa eski token bilan yuborilgan. Formaga qaytsangiz kiritilgan qiymatlar brauzerda yoki draftda saqlangan bo'lishi mumkin.</p>

        <div class="error-actions">
            <button class="primary-button" type="button" id="back-to-form">Formaga qaytish</button>
            <a class="secondary-button" href="{{ route('session.clear') }}">Sessiyani tozalash</a>
            <a class="ghost-button" href="{{ route('login') }}">Qayta kirish</a>
        </div>
    </main>

    <script>
        document.getElementById('back-to-form')?.addEventListener('click', function () {
            if (window.history.length > 1) {
                window.history.back();
                return;
            }

            window.location.href = @json(route('requests.create'));
        });
    </script>
</body>
</html>
