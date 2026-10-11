// WCAG 2.2 AA automated audit (axe-core) of the public pages at desktop and phone widths,
// including the Explore overlay and cookie banner states.
import fs from 'node:fs';
import { createRequire } from 'node:module';
import { BASE, launch, sleep } from './lib.mjs';

const require = createRequire(import.meta.url);
const axeSource = fs.readFileSync(require.resolve('axe-core/axe.min.js'), 'utf8');

const urls = ['/', '/who-we-are', '/what-we-do', '/investors', '/sustainability', '/news', '/news/' + new Date().getFullYear() + '/demo-story-1',
    '/documents', '/documents/annual-reports', '/what-we-do/tea', '/what-we-do/tea/estates/kodala', '/careers/vacancies', '/careers/vacancies/assistant-estate-manager',
    '/careers/vacancies/assistant-estate-manager/apply', '/contact', '/newsletter', '/search?q=tea', '/sitemap', '/privacy-notice', '/does-not-exist'];
const viewports = { desktop: { width: 1280, height: 900 }, mobile: { width: 390, height: 844, isMobile: true, deviceScaleFactor: 2 } };
const tags = ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa', 'best-practice'];

const browser = await launch();
const results = [];
for (const [vpName, vp] of Object.entries(viewports)) {
    const page = await browser.newPage();
    await page.setViewport(vp);
    for (const path of urls) {
        for (const state of ['default', ...(path === '/' ? ['menu-open', 'banner-dismissed'] : [])]) {
            await page.deleteCookie(...(await page.cookies()));   // every state starts like a first-time visitor (banner visible)
            await page.goto(BASE + path, { waitUntil: 'networkidle0' });
            if (state === 'menu-open') { await page.click('[data-explore-toggle]'); await sleep(200); }
            if (state === 'banner-dismissed') { await page.click('[data-consent-reject]'); await sleep(200); }
            await page.evaluate(axeSource);
            const r = await page.evaluate((t) => window.axe.run(document, { runOnly: { type: 'tag', values: t } }), tags);
            for (const v of r.violations) {
                results.push({ vp: vpName, path, state, id: v.id, impact: v.impact, help: v.help, nodes: v.nodes.length, sample: v.nodes[0]?.target?.join(' '), html: v.nodes[0]?.html?.slice(0, 140) });
            }
        }
    }
    await page.close();
}
await browser.close();

fs.writeFileSync('a11y-results.json', JSON.stringify(results, null, 2));
const byImpact = results.reduce((a, r) => ((a[r.impact] = (a[r.impact] || 0) + 1), a), {});
console.log(`axe: ${results.length} violation(s) across ${urls.length} URLs x ${Object.keys(viewports).length} viewports`, byImpact);
const seen = new Set();
for (const r of results) {
    const key = `${r.id}|${r.sample}`;
    if (seen.has(key)) continue;
    seen.add(key);
    console.log(`- [${r.impact}] ${r.id} (${r.vp}, ${r.path}, ${r.state}) x${r.nodes}: ${r.help}\n    ${r.sample}\n    ${r.html}`);
}
process.exit(results.some((r) => ['critical', 'serious'].includes(r.impact)) ? 1 : 0);
