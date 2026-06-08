const navToggle = document.querySelector('[data-nav-toggle]');
const nav = document.querySelector('[data-nav]');
if (navToggle && nav) {
    navToggle.addEventListener('click', () => {
        nav.classList.toggle('open');
    });
}
const registerForm = document.querySelector('[data-register-form]');
if (registerForm) {
    const password = registerForm.querySelector('[name="password"]');
    const confirmation = registerForm.querySelector('[name="password_confirm"]');
    const error = registerForm.querySelector('[data-password-error]');
    registerForm.addEventListener('submit', (event) => {
        if (password.value !== confirmation.value) {
            event.preventDefault();
            error.hidden = false;
            confirmation.focus();
        }
    });
}