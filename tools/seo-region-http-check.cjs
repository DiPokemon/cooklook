/* Local guest GET checks only. No login, recipe writes, browser view events or cache flush.
 * node tools/seo-region-http-check.cjs
 */
'use strict';
const fs = require('node:fs');
const http = require('node:http');
const assert = require('node:assert/strict');
const baseline = require('../docs/seo-region-profile-before.json');
const integration = require('../docs/seo-region-integration.json');
const origin = 'http://cooklook';
const decode = s => s.replace(/&amp;/g, '&').replace(/&quot;/g, '"').replace(/&#0*39;|&#x27;/g, "'").replace(/&lt;/g, '<').replace(/&gt;/g, '>');
const attrs = s => Object.fromEntries([...s.matchAll(/([\w:-]+)\s*=\s*(?:"([^"]*)"|'([^']*)')/g)].map(m => [m[1], decode(m[2] ?? m[3])]));
const cards = html => [...html.matchAll(/<a\b[^>]*>/g)].map(m => attrs(m[0])).filter(a => a.class?.split(/\s+/).includes('recipe_title')).map(a => a.href);
function request(address) {
  const url = new URL(address, origin);
  assert.equal(url.origin, origin, 'Only the local site is supported');
  return new Promise((resolve, reject) => {
    const req = http.get({ hostname: '127.0.0.1', port: 80, path: url.pathname + url.search,
      headers: { Host: 'cooklook', 'User-Agent': 'CooklookSEOCheck/1.0', 'Accept-Encoding': 'identity' }, agent: false }, res => {
      const chunks = []; let bytes = 0;
      res.on('data', chunk => { bytes += chunk.length; if (bytes > 4_000_000) res.destroy(new Error('Response exceeds limit')); else chunks.push(chunk); });
      res.on('error', reject);
      res.on('end', () => resolve({ status: res.statusCode, location: res.headers.location, body: Buffer.concat(chunks).toString('utf8') }));
    });
    req.setTimeout(15000, () => req.destroy(new Error('Request timed out')));
    req.on('error', reject);
  });
}
function options(html) {
  const select = [...html.matchAll(/<select\b[^>]*>[\s\S]*?<\/select>/g)].find(m => attrs(m[0]).id === 'recipe_region')?.[0];
  assert.ok(select, 'Region dropdown missing');
  return [...select.matchAll(/<option\b([^>]*)>([\s\S]*?)<\/option>/g)].map(m => ({ ...attrs(m[1]), text: decode(m[2]).trim(), selected: /\bselected(?:\s|=|$)/.test(m[1]) }));
}
(async () => {
  const home = await request('/recipes/');
  assert.equal(home.status, 200);
  const expectedRegions = baseline.regions.filter(v => v && v !== '0');
  const directory = options(home.body).filter(o => o.value !== '');
  assert.deepEqual(directory.map(o => o.value), expectedRegions);
  assert.deepEqual(directory.map(o => o.text), expectedRegions);
  const config = JSON.parse(home.body.match(/var filter_obj = (\{[^\n]*\});/)?.[1] || 'null');
  assert.ok(config?.nonce && config.ajax_url, 'Catalog AJAX configuration missing');
  const cases = [];
  for (const item of integration.cases) {
    for (const page of [...new Set([1, item.pages])]) {
      const path = page > 1 ? `/recipes/page/${page}/` : '/recipes/';
      const url = new URL(path, origin); url.search = 'region=' + encodeURIComponent(item.region);
      const full = await request(url.href);
      assert.equal(full.status, 200, `Filtered page failed: ${url.href} -> ${full.location || full.status}`);
      assert.deepEqual(options(full.body).filter(o => o.value !== '').map(o => o.value), expectedRegions);
      assert.equal(options(full.body).find(o => o.selected)?.value, item.region);
      const links = cards(full.body);
      assert.equal(links.length, Math.min(11, item.recipes - (page - 1) * 11));
      const ajax = new URL(config.ajax_url);
      for (const [key, value] of Object.entries({ action: 'filter_recipes', nonce: config.nonce, region: item.region, paged: page, page_url: url.href })) ajax.searchParams.set(key, value);
      const response = await request(ajax.href);
      assert.equal(response.status, 200);
      const result = JSON.parse(response.body);
      assert.equal(result.success, true);
      assert.deepEqual(cards(result.data.html), links, 'AJAX and server-rendered cards differ');
      cases.push({ region: item.region, page, status: full.status, cards: links.length, selected_region_correct: true, ajax_cards_equal: true });
    }
  }
  const invalid = await request('/recipes/?region=Cooklook-Nonexistent-Region-Check');
  assert.equal(invalid.status, 404);
  const ajaxInvalid = new URL(config.ajax_url);
  ajaxInvalid.search = new URLSearchParams({ action: 'filter_recipes', nonce: config.nonce, region: 'Cooklook-Nonexistent-Region-Check' });
  const invalidResponse = await request(ajaxInvalid.href);
  assert.equal(invalidResponse.status, 404);
  assert.equal(JSON.parse(invalidResponse.body).success, false);
  const report = { checked_at: new Date().toISOString(), site: origin, method: 'Sequential unauthenticated GETs on the local catalog and read-only filter endpoint. No browser JavaScript or view events. All nine regions; first and last pages.', visible_regions: directory.length, directory_values_labels_order_equal: true, invalid_ssr_status: invalid.status, invalid_ajax_status: invalidResponse.status, cases };
  fs.writeFileSync('docs/seo-region-http-check.json', JSON.stringify(report, null, 2) + '\n');
  console.log(`PASS: ${directory.length} regions, ${cases.length} SSR/AJAX page pairs, unknown region returns 404 in both routes.`);
})().catch(error => { console.error(error); process.exitCode = 1; });
