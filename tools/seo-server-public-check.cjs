#!/usr/bin/env node
'use strict';
// Only public GETs to cook-look.ru. No raw logs, IPs, DNS bot checks or credentials.
const fs = require('node:fs');
const [source, output] = process.argv.slice(2);
if (!source || !output) throw new Error('Usage: node tools/seo-server-public-check.cjs SANITIZED_REPORT OUTPUT_JSON');
const paths = JSON.parse(fs.readFileSync(source, 'utf8')).live.map(row => row.path);
for (const p of paths) if (!/^\/[a-z0-9_./-]*$/.test(p) || p.startsWith('//')) throw new Error('Unexpected path');
async function main() {
  const results = []; let next = 0;
  await Promise.all(Array.from({ length: 2 }, async () => {
    while (next < paths.length) {
      const p = paths[next++], start = Date.now();
      try {
        const response = await fetch('https://cook-look.ru' + p, { redirect: 'manual', headers: { 'User-Agent': 'CookLook-SEO-Audit/1.0' }, signal: AbortSignal.timeout(15000) });
        const text = await response.text();
        results.push({ path: p, status: response.status, location: response.headers.get('location'), elapsed_ms: Date.now() - start, body_bytes: Buffer.byteLength(text), title: text.match(/<title[^>]*>([\s\S]*?)<\/title>/i)?.[1] || null });
      } catch (error) { results.push({ path: p, error: error.name, code: error.cause?.code }); }
    }
  }));
  const report = { checked_at: new Date().toISOString(), method: 'One public GET per URL, concurrency two, no cookies, redirects not followed; elapsed_ms includes transfer and is not origin server processing time.', results };
  fs.writeFileSync(output, JSON.stringify(report, null, 2) + '\n');
  console.log(JSON.stringify(report, null, 2));
}
main().catch(error => { console.error(error.message); process.exitCode = 1; });
