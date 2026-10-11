// Cookie consent (Accept / Reject / Preferences). Analytics loads only after explicit consent.
// The choice lives in a first-party cookie so the cookie-free, cached HTML never needs a session.
const COOKIE = 'dbd_consent';
const banner = document.querySelector('[data-cookie-banner]');

const read = () => {
    const match = document.cookie.split('; ').find((c) => c.startsWith(`${COOKIE}=`));
    return match ? decodeURIComponent(match.split('=')[1]) : null;
};

const write = (value) => {
    const secure = location.protocol === 'https:' ? '; Secure' : '';
    document.cookie = `${COOKIE}=${encodeURIComponent(value)}; Max-Age=15552000; Path=/; SameSite=Lax${secure}`;
};

const loadAnalytics = () => {
    const id = document.body.dataset.analyticsId;
    if (!id || document.querySelector('script[data-analytics]')) return;

    const script = document.createElement('script');
    script.async = true;
    script.dataset.analytics = '';
    script.src = `https://www.googletagmanager.com/gtag/js?id=${encodeURIComponent(id)}`;
    document.head.append(script);

    window.dataLayer = window.dataLayer || [];
    window.gtag = function () { window.dataLayer.push(arguments); };
    window.gtag('js', new Date());
    window.gtag('config', id, { anonymize_ip: true });
};

const apply = (value) => {
    if (banner) banner.hidden = true;
    if (value === 'all') loadAnalytics();
};

const current = read();
if (current) {
    apply(current);
} else if (banner) {
    banner.hidden = false;
}

banner?.querySelector('[data-consent-accept]')?.addEventListener('click', () => { write('all'); apply('all'); });
banner?.querySelector('[data-consent-reject]')?.addEventListener('click', () => { write('necessary'); apply('necessary'); });
document.querySelectorAll('[data-cookie-preferences]').forEach((el) => el.addEventListener('click', (e) => {
    e.preventDefault();
    if (banner) { banner.hidden = false; banner.querySelector('button')?.focus(); }
}));
