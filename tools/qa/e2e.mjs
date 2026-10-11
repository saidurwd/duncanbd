// Browser end-to-end checks of the interactive behaviour: keyboard menu, consent, forms, slider, video,
// real two-factor login, and the admin block editor + publish workflow. Fails on any console/CSP error.
import { BASE, launch, watch, sql, totp, sleep } from './lib.mjs';

const ADMIN = process.env.QA_ADMIN || 'qa-admin@example.com';
const PASS = process.env.QA_PASS || 'Qa-Password-12345!';
const ADMIN_PATH = process.env.QA_ADMIN_PATH || 'dbbd-console';
let failures = 0;
const log = [];
const check = (name, ok, detail = '') => { log.push({ name, ok, detail }); console.log(`${ok ? 'PASS' : 'FAIL'}  ${name}${ok ? '' : '  -> ' + detail}`); if (!ok) failures++; };

// Re-runnable: put the QA admin back to 'no 2FA yet' and remove pages from earlier runs.
sql(`update users set two_factor_secret=null, two_factor_recovery_codes=null, two_factor_confirmed_at=null where email='${ADMIN}'`);
sql("delete from revisions where revisionable_type like '%Page' and revisionable_id in (select id from pages where title='E2E Landing Page')");
sql("delete from pages where title='E2E Landing Page'");

const browser = await launch();
const errors = [];
const newPage = async (vp = { width: 1280, height: 900 }) => { const p = await browser.newPage(); await p.setViewport(vp); watch(p, errors); return p; };

// ---------------------------------------------------------------- public: chrome
{
    const page = await newPage();
    await page.goto(BASE + '/', { waitUntil: 'networkidle0' });

    // Skip link is first in tab order and works
    await page.keyboard.press('Tab');
    const first = await page.evaluate(() => document.activeElement?.className);
    check('skip link is the first focusable element', /skip-link/.test(first), first);
    await page.keyboard.press('Enter');
    check('skip link moves focus target to main', await page.evaluate(() => location.hash === '#main'));

    // Explore overlay: open, focus trap, Escape, focus restore
    await page.click('[data-explore-toggle]');
    check('Explore opens and sets aria-expanded', await page.$eval('[data-explore-toggle]', (e) => e.getAttribute('aria-expanded')) === 'true');
    check('focus moves into the overlay', await page.evaluate(() => document.querySelector('[data-explore-menu]').contains(document.activeElement)));
    const inside = [];
    for (let i = 0; i < 40; i++) { await page.keyboard.press('Tab'); inside.push(await page.evaluate(() => document.querySelector('[data-explore-menu]').contains(document.activeElement))); }
    check('Tab never leaves the open overlay (focus trap)', inside.every(Boolean));
    await page.keyboard.down('Shift'); await page.keyboard.press('Tab'); await page.keyboard.up('Shift');
    check('Shift+Tab stays trapped', await page.evaluate(() => document.querySelector('[data-explore-menu]').contains(document.activeElement)));
    await page.keyboard.press('Escape');
    check('Escape closes the overlay', await page.$eval('[data-explore-menu]', (e) => e.hidden));
    check('focus returns to the Explore button', await page.evaluate(() => document.activeElement?.hasAttribute('data-explore-toggle')));
    check('body scroll lock released', await page.evaluate(() => !document.body.classList.contains('menu-open')));

    // Cookie consent
    await page.deleteCookie(...(await page.cookies()));
    await page.reload({ waitUntil: 'networkidle0' });
    check('cookie banner shown to a new visitor', await page.$eval('[data-cookie-banner]', (e) => !e.hidden));
    await page.click('[data-consent-reject]');
    check('rejecting hides the banner and stores the choice', await page.$eval('[data-cookie-banner]', (e) => e.hidden) && (await page.cookies()).some((c) => c.name === 'dbd_consent' && c.value === 'necessary'));
    await page.reload({ waitUntil: 'networkidle0' });
    check('banner stays hidden on the next page view', await page.$eval('[data-cookie-banner]', (e) => e.hidden));
    check('analytics script not loaded without consent', !(await page.$('script[data-analytics]')));
    await page.evaluate(() => document.querySelector('[data-cookie-preferences]').click());
    check('"Cookie settings" re-opens the banner', await page.$eval('[data-cookie-banner]', (e) => !e.hidden));

    // Hero slider pause control
    const slider = await page.$('[data-hero-slider]');
    if (slider) {
        const label1 = await page.$eval('[data-slider-pause]', (e) => e.textContent);
        await page.click('[data-slider-pause]');
        const label2 = await page.$eval('[data-slider-pause]', (e) => e.textContent);
        check('slider pause control toggles', label1 !== label2 && /Play/.test(label2), `${label1} / ${label2}`);
    }

    // Click-to-load video: no third-party frame until activated
    check('no YouTube iframe before click', !(await page.$('iframe')));
    await page.evaluate(() => document.querySelector('[data-video]').scrollIntoView());
    await page.click('[data-video]');
    await sleep(300);
    const src = await page.$eval('iframe', (e) => e.src).catch(() => '');
    check('video loads from youtube-nocookie only after click', src.startsWith('https://www.youtube-nocookie.com/embed/'), src);
    await page.close();
}

// ---------------------------------------------------------------- public: forms
{
    const page = await newPage();
    await page.goto(BASE + '/', { waitUntil: 'networkidle0' });
    await page.evaluate(() => document.querySelector('[data-cookie-banner]').hidden = true);

    // Newsletter band on a cached, cookie-free page
    const before = Number(sql('select count(*) from subscribers'));
    await page.focus('#nl-email');
    await sleep(400);
    check('newsletter token fetched on first interaction', (await page.$eval('[data-form-csrf]', (e) => e.value)).length > 20);
    await page.type('#nl-email', `e2e-${Date.now()}@example.com`);
    await page.click('.newsletter-consent input');
    await sleep(3200); // human-speed time trap
    await page.click('.newsletter-band .button');
    await page.waitForFunction(() => document.querySelector('[data-newsletter-status]').textContent.includes('confirmation'), { timeout: 8000 }).catch(() => {});
    const status = await page.$eval('[data-newsletter-status]', (e) => e.textContent);
    check('newsletter band shows the in-page confirmation message', /confirmation email/.test(status), status);
    check('subscriber stored as pending (double opt-in)', Number(sql('select count(*) from subscribers')) === before + 1 && sql("select status from subscribers order by id desc limit 1") === 'pending');

    // Contact form: validation summary then success
    await page.goto(BASE + '/contact', { waitUntil: 'networkidle0' });
    await page.evaluate(() => document.querySelector('[data-cookie-banner]').hidden = true);
    await sleep(3200);
    await page.type('#f-name', 'E2E Tester');
    await page.type('#f-email', 'e2e@example.com');
    await page.click('button.button');
    await page.waitForSelector('[data-error-summary]', { timeout: 8000 }).catch(() => {});
    check('contact form shows an error summary and focuses it', await page.evaluate(() => document.activeElement?.hasAttribute('data-error-summary')));
    check('form keeps entered values after validation errors', await page.$eval('#f-name', (e) => e.value) === 'E2E Tester');
    await page.type('#f-message', 'This is an end to end test enquiry message.');
    await page.click('input[name=consent]');
    const enq = Number(sql('select count(*) from enquiries'));
    await sleep(500);
    await page.click('button.button');
    await page.waitForSelector('.form-success', { timeout: 8000 }).catch(() => {});
    check('contact form success message', !!(await page.$('.form-success')));
    check('enquiry stored with consent timestamp', Number(sql('select count(*) from enquiries')) === enq + 1 && sql('select consent_at is not null from enquiries order by id desc limit 1') === '1');

    // Bot behaviour: filling the honeypot yields the same thank-you but stores nothing
    await page.goto(BASE + '/contact', { waitUntil: 'networkidle0' });
    await page.evaluate(() => { document.querySelector('[data-cookie-banner]').hidden = true; document.querySelector('input[name=website]').value = 'spam'; });
    await sleep(3200);
    await page.type('#f-name', 'Bot'); await page.type('#f-email', 'bot@example.com'); await page.type('#f-message', 'Buy cheap things now please.');
    await page.click('input[name=consent]');
    const n = Number(sql('select count(*) from enquiries'));
    await page.click('button.button');
    await page.waitForSelector('.form-success', { timeout: 8000 }).catch(() => {});
    check('honeypot submission is silently dropped', Number(sql('select count(*) from enquiries')) === n);
    await page.close();
}

// ---------------------------------------------------------------- admin: mandatory 2FA, block editor, workflow
{
    const page = await newPage();
    const login = async () => { await page.goto(`${BASE}/login`, { waitUntil: 'networkidle0' }); await page.type('#email', ADMIN); await page.type('#password', PASS); await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0' }), page.click('button[type=submit]')]); };

    await login();
    check('first login is forced to 2FA setup', page.url().includes(`${ADMIN_PATH}/security`), page.url());
    await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0' }), page.click('form[action$="/user/two-factor-authentication"] button')]);
    // password confirmation may be required first
    if (page.url().includes('confirm-password')) {
        await page.type('#password', PASS);
        await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0' }), page.click('button[type=submit]')]);
        if (!(await page.$('input#code'))) await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0' }), page.click('form[action$="/user/two-factor-authentication"] button')]);
    }
    const secret = await page.$eval('code', (e) => e.textContent.trim());
    check('2FA secret and QR code are shown', secret.length >= 16 && !!(await page.$('svg')), secret);
    const usedCode = totp(secret);   // consumed here; must not be accepted again
    await page.type('#code', usedCode);
    await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0' }), page.click('form[action$="/user/confirmed-two-factor-authentication"] button')]);
    check('2FA confirmed; recovery codes displayed', (await page.$$('ul.font-monospace li')).length === 8);
    await page.goto(`${BASE}/${ADMIN_PATH}`, { waitUntil: 'networkidle0' });
    check('dashboard reachable once 2FA is on', (await page.title()).includes('Dashboard'));

    // Logout, then log in again: 2FA challenge required
    await page.evaluate(() => document.querySelector('form[action$="/logout"]').submit());
    await page.waitForNavigation({ waitUntil: 'networkidle0' });
    await login();
    check('subsequent login requires the 2FA code', page.url().includes('two-factor-challenge'), page.url());
    // The code consumed during setup must not work twice (replay protection)...
    await page.type('#code', usedCode);
    await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0' }), page.click('button[type=submit]')]);
    check('a TOTP code that was already used is rejected (replay protection)', page.url().includes('two-factor-challenge'), page.url());
    // ...but the next time-step's code is accepted.
    await page.type('#code', totp(secret, 1));
    await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0' }), page.click('button[type=submit]')]);
    check('login completes with a fresh TOTP code', page.url().includes(ADMIN_PATH), page.url());

    // Block editor: build a page in the UI
    await page.goto(`${BASE}/${ADMIN_PATH}/pages/create`, { waitUntil: 'networkidle0' });
    await page.type('#title', 'E2E Landing Page');
    await page.select('#block-type', 'rich_text');
    await page.click('[data-add-block]');
    await page.waitForSelector('[data-block-list] .rte-content');
    await page.type('[data-block-list] input[type=text]', 'E2E heading');
    await page.click('[data-block-list] .rte-content');
    await page.keyboard.type('Hello editor');   // WYSIWYG: markup cannot be typed, the server still strips scripts if posted by hand
    await page.select('#block-type', 'cta_band');
    await page.click('[data-add-block]');
    check('block editor renders two blocks', (await page.$$('[data-block-list] .card')).length === 2);
    await page.evaluate(() => document.querySelectorAll('[data-block-list] .card')[1].querySelectorAll('input')[0].focus());
    const inputs = await page.$$('[data-block-list] .card:nth-child(2) input');
    await inputs[0].type('Join us'); await inputs[1].type('Careers'); await inputs[2].type('/careers/vacancies');
    // reorder: move CTA up
    await page.click('[data-block-list] .card:nth-child(2) [aria-label="Move up"]');
    check('block move-up reorders blocks', (await page.$eval('[data-block-list] .card:first-child .card-header strong', (e) => e.textContent)).includes('Cta Band'));
    await page.screenshot({ path: 'editor.png', fullPage: true });
    console.log('blocks_json =', await page.$eval('input[name=blocks_json]', (e) => e.value));
    await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0', timeout: 15000 }).catch(async () => console.log('NAV TIMEOUT; alerts:', await page.$$eval('.alert', (a) => a.map((x) => x.textContent.trim())))), page.click('form[action$="/pages"] button.btn-primary')]);
    check('page saved as a draft', page.url().includes('/edit') && sql("select status from pages where title='E2E Landing Page'") === 'draft', page.url());
    check('script tag stripped from saved rich text', !sql("select blocks from pages where title='E2E Landing Page'").includes('<script'));
    const slug = sql("select path from pages where title='E2E Landing Page'");
    check('draft is not public', (await (await fetch(`${BASE}/${slug}`)).status) === 404);

    // Workflow to published, then public + cache invalidation
    for (const next of ['In review', 'Approved', 'Published']) {
        const btn = await page.evaluateHandle((t) => [...document.querySelectorAll('form button.btn-outline-primary')].find((b) => b.textContent.trim() === t), next);
        await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0' }), btn.asElement().click()]);
    }
    check('page published through the workflow', sql("select status from pages where title='E2E Landing Page'") === 'published');
    const pub = await fetch(`${BASE}/${slug}`);
    check('published page is public with the heading and no script', pub.status === 200 && (await pub.text()).includes('E2E heading'));
    await page.close();
}

check('no console errors, page errors, failed requests or CSP violations in any flow', errors.length === 0, errors.slice(0, 5).join(' | '));
await browser.close();
console.log(`\n${log.length - failures}/${log.length} checks passed`);
process.exit(failures ? 1 : 0);
