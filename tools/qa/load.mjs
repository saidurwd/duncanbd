// HTTP load test (autocannon). Usage: node load.mjs <label> [connections=200] [seconds=20]
import autocannon from 'autocannon';
import { BASE } from './lib.mjs';

const [label = 'run', connections = '200', duration = '20'] = process.argv.slice(2);
const paths = ['/', '/who-we-are', '/what-we-do', '/investors', '/news', '/documents/annual-reports', '/what-we-do/tea', '/what-we-do/tea/estates/kodala', '/careers/vacancies', '/sitemap.xml'];

// Warm-up so a cold cache does not skew a "cached" run.
for (const p of paths) await fetch(BASE + p);

// QA_BUST=1 appends a unique query string so every request misses the response cache (worst-case, "uncached" run).
let n = 0;
const bust = process.env.QA_BUST === '1';
const requests = paths.map((path) => ({ method: 'GET', path, ...(bust ? { setupRequest: (req) => ({ ...req, path: `${path}${path.includes('?') ? '&' : '?'}b=${Date.now()}${n++}` }) } : {}) }));
const result = await autocannon({ url: BASE, connections: Number(connections), duration: Number(duration), requests, timeout: 30 });
const non2xx = result.non2xx, errors = result.errors, timeouts = result.timeouts;
console.log(JSON.stringify({
    label, connections: Number(connections), duration: Number(duration),
    reqPerSec: Math.round(result.requests.average), total: result.requests.total,
    latencyMs: { avg: result.latency.average, p50: result.latency.p50, p90: result.latency.p90, p97_5: result.latency.p97_5, p99: result.latency.p99, max: result.latency.max },
    throughputMBs: +(result.throughput.average / 1048576).toFixed(2), non2xx, errors, timeouts,
}, null, 2));
