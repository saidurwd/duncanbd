# Go-live checklist (expands plan Appendix B)

Tick each line with evidence (screenshot, command output, ticket). Items marked **(QA)** were verified in the
Phase 8 environment and must be re-verified on production.

## Application
- [ ] `APP_ENV=production`, `APP_DEBUG=false`, unique `APP_KEY`, `php artisan about` shows caches built **(QA)**
- [ ] Every admin user has 2FA; default, test and demo accounts removed; ≤ 2 Super Admins
- [ ] `ADMIN_PATH` changed, `ADMIN_IP_ALLOWLIST` set, `/<ADMIN_PATH>` is 404 from outside **(QA)**
- [ ] `TRUSTED_PROXIES` set; a request through Cloudflare logs the real visitor IP
- [ ] `storage:link` exists; `storage/app/private` is not web-accessible (`/storage/private/...` is 404) **(QA)**
- [ ] Queue workers (Supervisor) and scheduler (cron) running; a queued email is delivered
- [ ] Staging `noindex` **not** carried to production; `robots.txt` and `sitemap.xml` correct **(QA)**
- [ ] Legal pages published: Privacy notice, Cookie policy, Terms, Accessibility statement (after the manual audit)

## Security
- [ ] TLS valid (Cloudflare Full Strict + origin cert), HSTS on, securityheaders.com grade A
- [ ] Headers present on HTML, static files and error pages **(QA)**: CSP incl. `frame-ancestors`, nosniff, Referrer-Policy, Permissions-Policy, COOP/CORP
- [ ] Independent penetration test complete; no open high/critical findings
- [ ] ZAP baseline on production URL: 0 FAIL
- [ ] `composer audit` and `npm audit` clean on the release being deployed
- [ ] Cloudflare WAF, bot fight mode and rate-limit rules enabled; Turnstile keys set (optional)

## Data and recovery
- [ ] First `backup:run` succeeded, archive encrypted, a copy on the **off-site** disk **(QA drill)**
- [ ] Restore rehearsed onto a clean server from the off-site copy within the last 7 days
- [ ] `backup:monitor` emails reach IT on-call; backup password stored in the vault
- [ ] Rollback rehearsed on staging (`deploy/deploy.sh rollback`) **(QA drill)**
- [ ] Data-retention job (`privacy:prune`) scheduled; retention periods approved by Legal

## Content and consent
- [ ] All pages, figures, estates, people and documents proof-read and signed off by the content owner
- [ ] Image alt text reviewed (imported images reuse their filenames); decorative images flagged
- [ ] Annual report and AGM notices published with correct financial year and dates; Company Secretary sign-off
- [ ] Cookie banner: analytics fires only after Accept **(QA)**; consent wording approved
- [ ] Contact routing inboxes set per category and HR inbox; each tested

## Performance and accessibility
- [ ] Cloudflare cache rule for anonymous HTML + purge on publish tested; origin load test on production hardware
- [ ] Lighthouse mobile ≥ 90 / 100 / 100 / 100 on home and one page of each template with real photography
- [ ] Manual screen-reader, zoom/reflow and keyboard pass completed; axe clean **(QA)**
- [ ] Cross-browser/device matrix completed (Chrome, Edge, Safari, Firefox, iOS Safari, Android Chrome)

## Operations
- [ ] Uptime monitor and error tracking (Sentry/Flare) alert IT; Laravel Pulse reachable to admins only
- [ ] SPF, DKIM, DMARC set for the sending domain
- [ ] DNS TTL lowered 48 h before cut-over; Camellia group notified of the final URL
- [ ] Runbook, admin manual and credentials vault handed over

## Sign-offs
- [ ] Sponsor · [ ] Product Owner · [ ] Content Owner · [ ] Company Secretary · [ ] HR (careers) · [ ] Security reviewer
