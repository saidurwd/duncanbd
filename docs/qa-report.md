# Phase 8 — QA, Security & Performance Report

Run date: 11 October 2026 · Build: `main` + Phase 8 changes · Environment: local Docker (MySQL 8.4, Redis 7,
nginx + PHP-FPM 8.4 with OPcache, no-dev optimised Composer install) with seeded demo content
(`php artisan db:seed --class=QaDemoSeeder`). Tooling is reproducible from `tools/qa/` and `deploy/`.

**Verdict: ready for UAT and an independent penetration test. Not yet ready for go-live** — the items under
"Not done / open" must be closed first. Nothing found in this phase is a known open defect.

## 1. Summary

| Area | Target (plan §11/§14) | Result |
|---|---|---|
| Automated tests | 70 % of domain/action code | **158 tests pass**; line coverage **88.1 %** (pcov); also green on **MySQL 8.4** |
| Static analysis | Larastan level 6 | Clean (0 errors) |
| Browser end-to-end | Keyboard menu, consent, forms, login + 2FA, editor, publish | **40 / 40 checks pass**, zero console / CSP / network errors |
| Accessibility (axe-core, WCAG 2.2 A/AA + best practice) | Zero serious/critical | **0 violations** on 20 URLs × desktop + phone, incl. open menu and cookie banner states |
| Lighthouse (mobile) | ≥ 90 / 100 / 100 / 100 | Performance **98–100**, Accessibility **100**, Best practices **100**, SEO **100** |
| Core Web Vitals (lab) | LCP < 2.5 s, CLS < 0.1 | LCP 1.5–2.2 s, CLS ≤ 0.014, TBT 0 ms |
| Page weight | Home < 1.5 MB | 242 KB (demo images; re-measure with real photography) |
| Security scan (OWASP ZAP baseline) | No high/critical | **0 FAIL**, 56 PASS; remaining warnings triaged below |
| Dependencies | `composer audit`, `npm audit` | No advisories |
| Backup / restore drill | Restore tested | Encrypted backup 2 s; full restore 2 s; all 13 tables' row counts and a stored document match byte-for-byte |
| Deploy / rollback drill | Rollback < 1 min | Release deploy 8–13 s; rollback **< 1 s** |
| Load (200 concurrent, p95 < 1 s) | See §5 | Cached pages: met at 50 concurrent, borderline at 200. Uncached: **not met at 200** on this rig → rely on page cache + CDN; see §5 |

## 2. Defects found by this phase (all fixed, with regression tests)

| # | Found by | Defect | Fix |
|---|---|---|---|
| 1 | E2E | Hero slider pause button toggled itself back (focus event paused, click then resumed) | Focus handler ignores the pause button |
| 2 | E2E | After a validation error the form got a *fresh* time-trap value; fixing one field quickly was silently dropped as "bot" | The original timestamp is kept in old input |
| 3 | E2E | Page-builder URL fields only accepted absolute URLs (browser `type=url` and server rule), so editors could not link to `/investors` | Accept `https://…` or site-relative `/path`; still reject `javascript:`, `data:`, `//host` |
| 4 | Profiling | Every request re-read settings from the cache ~14 times and ran a schema-introspection query | Per-request memoisation, lazy mail configuration. In-process page time 157 ms → 24 ms |
| 5 | Profiling | Response cache's CSRF replacer started a session on cacheable pages | Replacer removed (public pages carry no token) |
| 6 | ZAP | No security headers on 404 / 419 / 500 responses (route-group middleware never ran) | Headers + CSP are global middleware, set with `set()` so never duplicated |
| 7 | ZAP | `frame-ancestors` missing from CSP; JS-readable `XSRF-TOKEN` cookie; no COOP/CORP | Added `frame-ancestors 'self'`, removed the cookie, added COOP `same-origin` and CORP `same-site` |
| 8 | ZAP | Missing document file on disk returned a 500 error page | 404 + logged report (public and admin routes) |
| 9 | Lighthouse | Pages without an editor-written description had no meta description (SEO 92) | Description = editor text → first paragraph of the page → site default (new Settings field) |
| 10 | Container run | `Number::fileSize` needs the `intl` extension (500 on a minimal PHP image) | Own helper, no extension needed |
| 11 | Browser console | CSP blocked the inline SVG arrows on admin `<select>` | `img-src data:` (images cannot execute script) |
| 12 | Architecture review | Behind Cloudflare/nginx every visitor would share the proxy's IP (broken rate limits, `http` URLs) | `TRUSTED_PROXIES` setting wired into the framework |
| 13 | MySQL run | Tables used `utf8mb4_unicode_ci`, plan says `utf8mb4_0900_ai_ci` | Collation set for MySQL (MariaDB keeps `unicode_ci`) |
| 14 | Review | Backups would have included the whole project and had absolute paths | User data only (documents, CVs, media), AES-256, repo-relative paths |

## 3. Accessibility

Automated (axe-core 4.10, tags `wcag2a wcag2aa wcag21a wcag21aa wcag22aa best-practice`): **0 violations** across
home, section pages, news list/detail, documents list/detail, business and estate pages, careers list/detail/apply,
contact, newsletter, search, sitemap, legal and the 404 page; desktop (1280 px) and phone (390 px); plus the open
Explore overlay and dismissed-banner states. Browser tests also verify: skip link first in tab order, focus moves
into the overlay, focus trap (Tab and Shift+Tab never escape), Escape closes and returns focus, error summary
receives focus, slider pause control works, no auto-playing media, reduced-motion handling in code.

**Not covered by automation (required before the Accessibility Statement is published):** manual screen-reader
walkthrough (NVDA + Firefox, VoiceOver + Safari/iOS, TalkBack), 200 % / 400 % zoom and reflow review, high-contrast
mode, voice-control, and a review of real photography alt text. Automated tools find roughly a third of WCAG issues.

## 4. Security

Method: OWASP ZAP baseline (passive spider + passive rules) against the production-like stack, before and after fixes;
code review of authentication, authorisation, uploads, downloads, forms and headers; dependency audits; the automated
regression tests in `tests/Feature/Security` and `tests/Feature/Ops`.

Controls verified by tests or scans: mandatory TOTP 2FA (setup forced, replay of a used code rejected), lockout and
rate limits, deny-by-default roles (including investor vs general documents), CSRF on every form, honeypot + time
trap + optional Turnstile, uploads (allow-listed MIME and extension, random stored names, private disk, SVG
rejected, EXIF/payload stripped by re-encoding conversions), CVs and documents never reachable by URL, signed
single-use style links for newsletter actions, HTML sanitised with an allow-list, JSON-LD escaped, CSV exports
formula-safe, strict CSP (no inline script/style, no eval), security headers on every response, no stack traces
with `APP_DEBUG=false`, no sensitive files or debug routes exposed.

ZAP result after fixes: **0 FAIL, 11 WARN, 56 PASS.** Triage:

| ZAP alert | Assessment |
|---|---|
| CSP: failure to define directive with no fallback (`frame-ancestors`) | Header **does** contain `frame-ancestors 'self'` (verified with curl and a test); ZAP still reports it — treated as a false positive |
| Multiple X-Frame-Options / Referrer-Policy | Caused by the test image's nginx adding the same headers as the app. `deploy/nginx.conf` does not duplicate them |
| X-Content-Type-Options, Permissions-Policy, CORP missing on `/build/*` and some static files | nginx serves these without PHP. Fixed in `deploy/nginx.conf` (`add_header … always`) — **verify on the real server** |
| COEP missing | Intentionally not set: `require-corp` would block the YouTube embed. Accepted risk, low |
| Big redirect (login/forms redirect with a body) | Laravel standard; contains no sensitive data |
| Session management / authentication request / modern web app / non-storable content | Informational |
| User-controllable HTML attribute (search box echoes the query) | Value is HTML-escaped (tested with a `<script>` query) |

**Not done:** the independent penetration test the plan requires before go-live; an *active* ZAP scan with
authenticated contexts (admin); fuzzing of the upload endpoints with real-world malformed files; review of the
Cloudflare WAF rules; TLS configuration scan (needs the real domain).

## 5. Performance and load

Lab Lighthouse (mobile profile, simulated slow 4G): see §1. Server-side numbers below come from a **laptop
(12 vCPU Docker VM) with the load generator on the same machine**, so treat them as relative, not as capacity
promises. Content: 10 representative public URLs, demo data.

| Scenario | Concurrency | Requests/s | p50 | p95-ish | Errors |
|---|---|---|---|---|---|
| Cached pages (Redis response cache) | 50 | 308 | 145 ms | 379 ms (p97.5) | 0 |
| Cached pages | 200 | 336 | 523 ms | 1.0 s (p97.5) | 0 |
| Every request a cache miss (unique query string) | 50 | 114 | 377 ms | 948 ms (p97.5) | 0 |
| Every request a cache miss | 200 | 99 | 1.7 s | 4.2 s (p97.5) | 0 |

Reading: the plan's target (200 concurrent, p95 < 1 s *uncached*) is **not met** for a worst case where nothing is
cached; it is met for cached traffic at 50 concurrent and borderline at 200. This is expected for PHP-FPM with a
framework boot per request. Mitigations already designed in: guest response cache (invalidated on publish),
Cloudflare caching of HTML for anonymous visitors, immutable hashed assets. Recommended before go-live:

1. Configure a Cloudflare *Cache Rule* for HTML GETs without cookies (public pages set none), purge on publish.
2. Re-run `tools/qa/load.mjs` against the real server size (plan: 4 vCPU / 8 GB) from a separate machine.
3. If origin traffic ever needs more, Laravel Octane removes the per-request boot cost (measured ≈ 8 ms for a bare
   request vs ≈ 24 ms handling a page), at the price of long-running-process discipline.

Measurements also found and removed real inefficiencies (defects 4–5). Page weight and LCP must be re-measured
with the real photography (the demo images are small generated JPEGs).

## 6. Disaster recovery and release drills

* **Backup** (`php artisan backup:run`): 600 KB archive in 2.1 s containing the database dump plus
  `storage/app/private` (documents, CVs) and `storage/app/public` (media). AES-256: a reader without the password is
  refused. Archive paths are repo-relative.
* **Restore:** unpack, import the dump into an empty MySQL 8.4 database in 2 s; row counts of users, pages, posts,
  documents, versions, media, subscribers, enquiries, settings, activity log, menu items and redirects all match;
  a restored document file is byte-identical to the live one.
* **Deploy / rollback** (`deploy/deploy.sh`, run on Linux): two releases deployed (13 s, 8 s), traffic switched by
  atomic symlink, smoke test gate, rollback in under a second, shared `.env` and `storage` untouched,
  migrations not undone (hence the backward-compatible-migration rule).
* Still to prove on the real infrastructure: restoring onto a *new server* from the off-site copy, and the quarterly
  restore test cadence.

## 7. Browser and device coverage

Verified: Chromium (Google Chrome, current) at 1280 px and 390 px emulation. **Not verified:** Safari (macOS and iOS),
Firefox, Edge, Android Chrome, real devices. The code uses only widely supported features (native `<dialog>`, CSS grid,
`fetch`), but the plan's matrix still needs a manual or cloud-device pass.

## 8. Not done / open (must close before go-live)

| Item | Owner | Why it matters |
|---|---|---|
| Independent penetration test, fixes verified | Head of IT | Plan §10 and Phase 8 exit criteria |
| UAT with Corporate Affairs, Company Secretary, HR + editor training | Product owner | Phase 8 exit criteria; the demo seeder gives UAT a realistic site |
| Manual screen-reader / zoom / keyboard audit → publish Accessibility Statement | UX + QA | Automated axe is necessary, not sufficient |
| Cross-browser / device pass (Safari, Firefox, Edge, iOS, Android) | QA | Only Chrome verified |
| Re-run load test on production-size hardware, with Cloudflare cache rule | DevOps | This report used a laptop |
| First GitHub Actions run (lint, Larastan, tests, audits, build) | Dev | CI workflow is committed but has never executed |
| Real SMTP + SPF/DKIM/DMARC; send test of all four email templates | IT | Templates render (tested); deliverability untested |
| 27 legacy pages (login-protected) migrated; copy, figures and image alt text reviewed | Content owner | See `docs/content-migration.md` |
| Staging + production servers, Cloudflare, TLS, error tracking (Sentry/Flare), uptime monitor | DevOps | Phase 2 infrastructure items still open |
| Off-site backup disk (S3-compatible) configured; first scheduled backup + monitor email observed | DevOps | Local archive only so far |
