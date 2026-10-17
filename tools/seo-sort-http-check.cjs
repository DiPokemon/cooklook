/* Local guest SSR/AJAX sorting checks; no login, writes or browser view events.
 * node tools/seo-sort-http-check.cjs
 * Refresh seo-sort-profile-after.json first if recipes have changed.
 */
'use strict';
const fs = require('node:fs');
const http = require('node:http');
const assert = require('node:assert/strict');
const profile = require('../docs/seo-sort-profile-after.json');
const origin = 'http://cooklook';
const decode = s => s.replace(/&amp;/g, '&').replace(/&quot;/g, '"').replace(/&#0*39;|&#x27;/g, "'").replace(/&lt;/g, '<').replace(/&gt;/g, '>');
const attrs = s => Object.fromEntries([...s.matchAll(/([\w:-]+)\s*=\s*(?:"([^"]*)"|'([^']*)')/g)].map(m => [m[1], decode(m[2] ?? m[3])]));
const cards = html => [...html.matchAll(/<a\b[^>]*>/g)].map(m => attrs(m[0])).filter(a => a.class?.split(/\s+/).includes('recipe_title')).map(a => a.href);
function request(address, redirects = []) {
  const url = new URL(address, origin);
  assert.equal(url.origin, origin, 'Only the local site is supported');
  return new Promise((resolve, reject) => {
    const req = http.get({ hostname: '127.0.0.1', port: 80, path: url.pathname + url.search,
      headers: { Host: 'cooklook', 'User-Agent': 'CooklookSEOCheck/1.0', 'Accept-Encoding': 'identity' }, agent: false }, res => {
      const chunks = []; let bytes = 0;
      res.on('data', chunk => { bytes += chunk.length; if (bytes > 4_000_000) res.destroy(new Error('Response exceeds limit')); else chunks.push(chunk); });
      res.on('error', reject);
      res.on('end', () => {
        if (res.statusCode >= 300 && res.statusCode < 400 && res.headers.location) {
          if (redirects.length >= 4) return reject(new Error('Redirect limit exceeded'));
          return request(new URL(res.headers.location, url), [...redirects, res.statusCode]).then(resolve, reject);
        }
        resolve({ status: res.statusCode, url: url.href, redirects, body: Buffer.concat(chunks).toString('utf8') });
      });
    });
    req.setTimeout(15000, () => req.destroy(new Error('Request timed out')));
    req.on('error', reject);
  });
}
function selectedSort(html, id) {
  const select = [...html.matchAll(/<select\b[^>]*>[\s\S]*?<\/select>/g)].find(m => attrs(m[0]).id === id)?.[0];
  assert.ok(select, 'Sort selector missing: ' + id);
  const options = [...select.matchAll(/<option\b([^>]*)>/g)];
  return attrs((options.find(m => /\bselected(?:\s|=|$)/.test(m[1])) || options[0])[1]).value;
}
(async () => {
  const results = [];
  for (const item of profile.cases) {
    const page = item.request.paged || 1;
    const url = new URL(page > 1 ? `/recipes/page/${page}/` : '/recipes/', origin);
    for (const [key, value] of Object.entries(item.request)) {
      if (key !== 'paged' && !(key === 'sort_by' && value === 'date')) url.searchParams.set(key, Array.isArray(value) ? value.join(',') : value);
    }
    const full = await request(url);
    assert.equal(full.status, 200, item.case);
    assert.equal(selectedSort(full.body, 'sort_by'), item.request.sort_by);
    assert.equal(selectedSort(full.body, 'sort_by-mobile'), item.request.sort_by);
    const links = cards(full.body);
    assert.equal(links.length, item.ids.length, item.case + ' card count');
    const config = JSON.parse(full.body.match(/var filter_obj = (\{[^\n]*\});/)?.[1] || 'null');
    assert.ok(config?.nonce && config.ajax_url, 'Catalog configuration missing');
    const ajax = new URL(config.ajax_url);
    const data = { ...item.request, action: 'filter_recipes', nonce: config.nonce, paged: page, page_url: full.url };
    for (const [key, value] of Object.entries(data)) ajax.searchParams.set(key, Array.isArray(value) ? value.join(',') : value);
    const response = await request(ajax);
    assert.equal(response.status, 200, item.case + ' AJAX');
    const result = JSON.parse(response.body);
    assert.equal(result.success, true);
    assert.deepEqual(cards(result.data.html), links, item.case + ' SSR/AJAX order');
    results.push({ case: item.case, url: full.url, status: full.status, redirects: full.redirects, cards: links.length, desktop_mobile_sort_correct: true, ajax_order_equal: true });
  }
  const invalid = await request('/recipes/?sort_by=invalid-sort');
  assert.equal(invalid.status, 404);
  fs.writeFileSync('docs/seo-sort-http-check.json', JSON.stringify({ checked_at: new Date().toISOString(), site: origin,
    method: 'Sequential local guest GETs; all 24 profiled combinations, SSR vs AJAX, desktop/mobile selected sort. Read-only filter endpoint, no browser JavaScript or view requests.', cases: results, invalid_sort_status: invalid.status }, null, 2) + '\n');
  console.log(`PASS: ${results.length} SSR/AJAX pairs; desktop/mobile sort selection; invalid sort returns 404.`);
})().catch(error => { console.error(error); process.exitCode = 1; });
