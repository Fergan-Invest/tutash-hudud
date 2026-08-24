document.addEventListener('DOMContentLoaded', () => {
    const passwordInput = document.getElementById('password');
    const passwordToggle = document.querySelector('[data-password-toggle]');

    if (! passwordInput || ! passwordToggle) {
        return;
    }

    passwordToggle.addEventListener('click', () => {
        const willShow = passwordInput.type === 'password';
        passwordInput.type = willShow ? 'text' : 'password';
        passwordToggle.setAttribute('aria-pressed', String(willShow));
        passwordToggle.setAttribute('aria-label', willShow ? 'Parolni yashirish' : 'Parolni ko‘rsatish');
        passwordInput.focus();
    });
});
