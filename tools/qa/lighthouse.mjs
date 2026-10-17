// Lighthouse (mobile, default simulated throttling) for key pages; prints the four category scores + core metrics.
import fs from 'node:fs';
import lighthouse from 'lighthouse';
import * as chromeLauncher from 'chrome-launcher';
import { BASE, CHROME } from './lib.mjs';

const pages = (process.env.QA_URLS ? process.env.QA_URLS.split(',') : ['/', '/about-duncan/history-of-duncan', '/products/tea', '/news', '/documents/annual-reports', '/gallery', '/contact']);
const chrome = await chromeLauncher.launch({ chromePath: CHROME, chromeFlags: ['--headless=new', '--no-sandbox', '--disable-gpu'] });
const rows = [];
for (const path of pages) {
    const { lhr } = await lighthouse(BASE + path, { port: chrome.port, output: 'json', logLevel: 'error', onlyCategories: ['performance', 'accessibility', 'best-practices', 'seo'] });
    const score = (k) => Math.round(lhr.categories[k].score * 100);
    const a = lhr.audits;
    rows.push({
        path, perf: score('performance'), a11y: score('accessibility'), bp: score('best-practices'), seo: score('seo'),
        LCP: Math.round(a['largest-contentful-paint'].numericValue), CLS: +a['cumulative-layout-shift'].numericValue.toFixed(3),
        TBT: Math.round(a['total-blocking-time'].numericValue), TTFB: Math.round(a['server-response-time'].numericValue),
        KB: Math.round(a['total-byte-weight'].numericValue / 1024),
        failing: Object.values(lhr.audits).filter((x) => x.score !== null && x.score < 0.9 && x.scoreDisplayMode === 'binary').map((x) => x.id),
    });
    console.log(JSON.stringify(rows.at(-1)));
}
await chrome.kill();
fs.writeFileSync('lighthouse-results.json', JSON.stringify(rows, null, 2));
