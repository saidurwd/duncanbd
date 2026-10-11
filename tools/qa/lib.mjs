import puppeteer from 'puppeteer-core';
import { execSync } from 'node:child_process';
import crypto from 'node:crypto';

export const BASE = process.env.QA_BASE || 'http://127.0.0.1:8123';
export const CHROME = process.env.CHROME || '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';

export async function launch() {
    return puppeteer.launch({ executablePath: CHROME, headless: 'new', args: ['--no-sandbox', '--disable-gpu'] });
}

/** Collects console errors, page errors and CSP violations for a page. */
export function watch(page, sink) {
    page.on('console', (m) => {
        const t = m.text();
        if ((m.type() === 'error' && !/Failed to load resource/.test(t)) || /content security policy|refused to/i.test(t)) sink.push(`console.${m.type()}: ${t}`);
    });
    page.on('pageerror', (e) => sink.push(`pageerror: ${e.message}`));
    page.on('response', (r) => { if (r.status() >= 400 && !/favicon/.test(r.url())) sink.push(`HTTP ${r.status()}: ${r.url()}`); });
    page.on('requestfailed', (r) => {
        if (!/favicon|youtube-nocookie/.test(r.url())) sink.push(`requestfailed: ${r.url()} ${r.failure()?.errorText}`);
    });
}

export function sql(query) {
    return execSync(`docker exec dbd-mysql mysql -uroot -psecret duncanbd_qa -N -e ${JSON.stringify(query)} 2>/dev/null`).toString().trim();
}

// RFC 6238 TOTP (SHA-1, 6 digits, 30 s) so the E2E can complete real two-factor setup.
export function totp(secretBase32, offset = 0) {
    const alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    let bits = '';
    for (const c of secretBase32.replace(/=+$/, '').toUpperCase()) bits += alphabet.indexOf(c).toString(2).padStart(5, '0');
    const key = Buffer.from(bits.match(/.{8}/g).map((b) => parseInt(b, 2)));
    const counter = Math.floor(Date.now() / 30000) + offset;
    const buf = Buffer.alloc(8);
    buf.writeBigUInt64BE(BigInt(counter));
    const h = crypto.createHmac('sha1', key).update(buf).digest();
    const o = h[h.length - 1] & 0xf;
    return String(((h.readUInt32BE(o) & 0x7fffffff) % 1000000)).padStart(6, '0');
}

export const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
