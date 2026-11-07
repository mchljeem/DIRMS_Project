// Independent accessible show/hide password control; no password is stored in browser.
(() => {
  'use strict';
  document.addEventListener('click', event => {
    const button = event.target.closest('[data-toggle-password]');
    if (!button) return;
    const input = document.getElementById(button.dataset.togglePassword);
    if (!input) return;
    const visible = input.type === 'password';
    input.type = visible ? 'text' : 'password';
    button.setAttribute('aria-pressed', visible ? 'true' : 'false');
    button.setAttribute('aria-label', visible ? 'Hide password' : 'Show password');
    button.title = visible ? 'Hide password' : 'Show password';
    button.textContent = visible ? '🙈' : '👁';
    input.focus({preventScroll:true});
  });
})();
