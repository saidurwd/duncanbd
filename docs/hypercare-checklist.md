# Hypercare: the four weeks after go-live

Exit criteria (plan §15): 4 weeks stable, uptime ≥ 99.9 %, no Sev-1 incidents, then support moves to business as usual.

## Every day (10 minutes, IT on-call)
- [ ] Uptime monitor: any downtime? (target ≥ 99.9 %, i.e. under 43 minutes a month)
- [ ] Error tracking (Sentry/Flare): new or spiking errors; `storage/logs` size normal
- [ ] Admin → Redirects & 404s: top 404 paths. Create a redirect or fix a link for anything repeated
- [ ] Admin → Enquiries and Careers: new items are arriving and being answered; the "new" count is not growing unattended
- [ ] Queue and scheduler alive: `php artisan queue:monitor redis:default --max=100`; the `pages:publish-scheduled` job ran in the last minute
- [ ] Backups: yesterday's archive present on the off-site disk; `backup:monitor` email was not a failure notice
- [ ] Search Console: crawl errors and indexing coverage (days 1–14)

## Every week
- [ ] Core Web Vitals (field data in Search Console / CrUX) vs targets: LCP < 2.5 s, INP < 200 ms, CLS < 0.1
- [ ] Re-run `tools/qa/lighthouse.mjs` and `a11y.mjs` against production
- [ ] `composer audit` and `npm audit` on the deployed release; apply security patches (monthly window)
- [ ] Review the audit log (Admin → dashboard, Recent activity) for unexpected admin actions or repeated failed sign-ins
- [ ] Confirm every admin has two-factor on; deactivate leavers (Admin → Users & roles)
- [ ] Review feedback from the content owner; list content fixes (typos, outdated figures)

## Severity and response
| Level | Example | Response |
|---|---|---|
| Sev-1 | Site down, data leak, admin compromise | Page on-call now; consider rollback; Sponsor informed within 1 hour |
| Sev-2 | Forms not delivering mail, search broken, a section returns errors | Fix within 1 working day |
| Sev-3 | Wrong text, a broken link, cosmetic issue | Next weekly batch |

## Week-4 review
Uptime figure, incidents and fixes, Core Web Vitals, number of enquiries/applications/subscribers received, open defects, a
restore test from the off-site backup, then close the project and move to the monthly maintenance routine
(security patches, quarterly accessibility and performance review, annual penetration test, Laravel upgrade before Q1 2028).
