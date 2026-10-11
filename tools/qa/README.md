# QA tooling

Isolated from the site's own dependencies (`npm install` here). Reports are written next to the scripts and git-ignored.

| Script | Purpose |
|---|---|
| `../serve.sh` | Throw-away QA site on :8123 (MySQL container + `QaDemoSeeder` content) |
| `../docker-app.sh` | Production-like nginx + PHP-FPM stack on :8125 (no-dev Composer, OPcache, Redis, MySQL on a Docker network) |
| `a11y.mjs` | axe-core WCAG 2.2 AA audit of 20 URLs × 2 viewports (+ menu-open and banner states) |
| `e2e.mjs` | Browser end-to-end: keyboard menu, consent, forms, 2FA login, block editor, publish workflow |
| `lighthouse.mjs` | Mobile Lighthouse scores and Web Vitals for key pages |
| `load.mjs` | autocannon load test; `QA_BUST=1` forces cache misses |

```sh
export QA_BASE=http://127.0.0.1:8125
node a11y.mjs && node e2e.mjs && node lighthouse.mjs
node load.mjs cached 200 20 ; QA_BUST=1 node load.mjs uncached 200 20
docker run --rm --network dbd-net -v "$PWD/zap":/zap/wrk:rw ghcr.io/zaproxy/zaproxy:stable zap-baseline.py -t http://dbd-app2:8080 -r zap.html -J zap.json -I
```

E2E note: the form rate limit is 5 posts/minute/IP, so wait a minute between consecutive e2e runs.
