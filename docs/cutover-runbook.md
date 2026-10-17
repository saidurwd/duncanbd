# Cut-over runbook (Phase 9)

Goal: move duncanbd.com from the old site to the new one with no loss of content and a rehearsed way back. Names in
**bold** are roles from the plan (§16); fill in the people. All commands run on the new production server unless stated.
Prerequisites: everything in [go-live-checklist.md](go-live-checklist.md) is ticked and signed off.

## T-7 days: freeze and rehearse
- [ ] Content owner confirms the figures and wording flagged in [go-live-content.md](go-live-content.md); legal pages written and published
- [ ] Staging is a copy of production config; run the full QA set against it (`tools/qa`: a11y, e2e, lighthouse) and a ZAP baseline
- [ ] Rehearse once: restore the latest backup onto a clean server, `deploy/deploy.sh deploy <tag>`, then `deploy/deploy.sh rollback`
- [ ] Re-crawl the old site (`python3 tools/legacy/crawl.py`) and diff against the last crawl so nothing published there is missed
- [ ] Confirm who can change DNS, who approves, who is on call (names + phone numbers below)

| Role | Name | Phone |
|---|---|---|
| Cut-over lead (Product Owner) | | |
| DevOps / DNS | | |
| Content owner | | |
| Security reviewer | | |
| Sponsor (go / no-go) | | |

## T-48 hours
- [ ] Lower the DNS TTL of duncanbd.com and www to 300 s
- [ ] Announce a content freeze on the old site (Corporate Affairs); no edits after this point
- [ ] Take a full backup of the old site and its database (kept for 12 months)

## T-0: cut-over (suggest a quiet window, e.g. Friday evening Dhaka time)
1. [ ] Final content sync: re-crawl, `php artisan db:seed --class=LegacyContentSeeder` on **staging**, review diffs, then on production with `LEGACY_RESET=1` **only if production has no edits to keep**; otherwise apply changes by hand in the admin
2. [ ] `deploy/deploy.sh deploy <release-tag>`; confirm `php artisan about` shows `Debug Mode OFF`
3. [ ] `php artisan responsecache:clear`, then warm the cache (`curl` the sitemap URLs)
4. [ ] Smoke test on the production origin by hosts-file override, before DNS changes: home, a page from each menu section, search, contact form (check the email arrives), newsletter sign-up, the Annual Report download, admin login + two-factor
5. [ ] **Go / no-go** with the Sponsor
6. [ ] Switch DNS (Cloudflare) to the new origin; keep the old server running untouched
7. [ ] Verify from outside the office network: https works, redirect www → apex, `securityheaders.com` grade A, certificate valid, real client IPs appear in the logs
8. [ ] Submit `https://duncanbd.com/sitemap.xml` to Google Search Console and Bing Webmaster Tools
9. [ ] Tell the Camellia group communications team the final URL; ask them to update their link

## Rollback (decision within the first 2 hours)
Triggers: site down > 10 minutes, data or email loss, a security finding rated high, the Sponsor asks.
1. Point DNS back to the old server (TTL is 300 s, so recovery is about 5 minutes)
2. If only the new release is bad, `deploy/deploy.sh rollback` instead (under a second)
3. Record what happened; fix on staging; reschedule

## After cut-over
- [ ] Raise the DNS TTL back to normal after 48 hours of stability
- [ ] Keep the old server for 4 weeks (read-only), then decommission and archive its backup
- [ ] Hand over: admin manual ([admin-manual.md](admin-manual.md)), this runbook, architecture plan, credentials vault entries, backup password escrow
