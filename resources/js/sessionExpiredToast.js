let sessionExpiredToastLock = false;

/**
 * Show a visible toast then reload so Laravel serves a fresh CSRF token and page.
 */
export function showSessionExpiredToastThenReload() {
    if (sessionExpiredToastLock || typeof document === 'undefined') {
        return;
    }
    sessionExpiredToastLock = true;

    const wrap = document.createElement('div');
    wrap.setAttribute('role', 'alert');
    wrap.setAttribute('aria-live', 'assertive');
    wrap.style.cssText = [
        'position:fixed',
        'left:50%',
        'top:max(12px, env(safe-area-inset-top, 0px))',
        'transform:translateX(-50%)',
        'z-index:99999',
        'width:calc(100vw - 24px)',
        'max-width:22rem',
        'box-sizing:border-box',
        'padding:14px 16px',
        'border-radius:12px',
        'background:#fffbeb',
        'border:1px solid #fcd34d',
        'box-shadow:0 10px 40px rgba(0,0,0,.12)',
        'font-family:system-ui,-apple-system,sans-serif',
    ].join(';');

    const title = document.createElement('p');
    title.textContent = 'Session expired';
    title.style.cssText = 'margin:0 0 4px;font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#b45309;';

    const body = document.createElement('p');
    body.textContent = 'Your session timed out or the page was open too long. Refreshing in a moment…';
    body.style.cssText = 'margin:0;font-size:14px;font-weight:500;line-height:1.45;color:#78350f;';

    wrap.appendChild(title);
    wrap.appendChild(body);
    document.body.appendChild(wrap);

    window.setTimeout(() => {
        window.location.reload();
    }, 2800);
}
