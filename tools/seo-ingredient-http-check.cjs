/* Sequential local guest checks; refresh the ingredient integration/profile reports first.
 * node tools/seo-ingredient-http-check.cjs
 */
'use strict';
const fs = require('node:fs');
const http = require('node:http');
const assert = require('node:assert/strict');
const integration = require('../docs/seo-ingredient-integration.json');
const selection = require('../docs/seo-ingredient-profile-before.json').cases.selected.request;
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
function params(url, values) {
  for (const [key, value] of Object.entries(values)) {
    if (Array.isArray(value)) value.forEach(item => url.searchParams.append(key + '[]', item));
    else url.searchParams.set(key, value);
  }
  return url;
}
function config(html, name) { return JSON.parse(html.match(new RegExp('var ' + name + ' = (\\{[^\\n]*\\});'))?.[1] || 'null'); }
function selected(html, field) {
  const select = [...html.matchAll(/<select\b[^>]*>[\s\S]*?<\/select>/g)].find(m => attrs(m[0]).id === field)?.[0];
  assert.ok(select, 'Ingredient field missing: ' + field);
  const options = [...select.matchAll(/<option\b([^>]*)>/g)];
  assert.ok(options.every(m => /\bselected(?:\s|=|$)/.test(m[1])), 'Unselected directory options rendered');
  return options.map(m => attrs(m[1]).value).sort();
}
(async () => {
  const initial = await request('/recipes/');
  assert.equal(initial.status, 200);
  const searchConfig = config(initial.body, 'cooklookIngredients');
  assert.ok(searchConfig?.nonce, 'Lookup config missing');
  const endpointChecks = [];
  async function lookup(data, status = 200) {
    if (data.page !== undefined) { data.ingredient_page = data.page; delete data.page; }
    const response = await request(params(new URL(searchConfig.ajax_url), { action: 'cooklook_search_ingredients', nonce: searchConfig.nonce, ...data }));
    assert.equal(response.status, status, JSON.stringify(data));
    const json = JSON.parse(response.body);
    assert.equal(json.success, status === 200);
    endpointChecks.push({ input: Object.fromEntries(Object.entries(data).filter(([key]) => key !== 'nonce')), status, results: json.data?.results?.length ?? null, bytes: Buffer.byteLength(response.body) });
    return json.data;
  }
  for (const item of integration.cases) {
    const ids = [];
    for (let page = 1; page <= item.pages; page++) {
      const response = await lookup({ term: item.term, page });
      assert.ok(response.results.length <= 20);
      assert.equal(response.pagination.more, page < item.pages);
      ids.push(...response.results.map(r => r.id));
    }
    assert.equal(ids.length, item.matches); assert.equal(new Set(ids).size, ids.length);
  }
  for (const term of ['', 'я']) assert.equal((await lookup({ term })).results.length, 0);
  await lookup({ term: ['milk'] }, 400);
  await lookup({ term: 'milk', page: ['1'] }, 400);
  await lookup({ term: 'milk', page: 101 }, 400);
  await lookup({ term: 'я'.repeat(101) }, 400);
  await lookup({ term: 'milk', nonce: 'invalid' }, 403);
  const fields = ['include_ingredients', 'exclude_ingredients'];
  const scenarios = [
    ['blank', {}], ['include', { include_ingredients: [selection.include_ingredients[0]] }],
    ['exclude', { exclude_ingredients: selection.exclude_ingredients }], ['combined', selection],
    ['empty-selection', { include_ingredients: [integration.http_cases.empty] }],
    ['time', { ...selection, sort_by: 'recipe_time' }], ['views', { ...selection, sort_by: 'recipe_views' }]
  ];
  const results = [];
  async function checkCatalog(label, url, values) {
    const full = await request(url);
    assert.equal(full.status, 200, label);
    for (const field of fields) assert.deepEqual(selected(full.body, field), [...(values[field] || [])].sort(), label + ' SSR selection');
    const catalog = config(full.body, 'filter_obj');
    const response = await request(params(new URL(catalog.ajax_url), { ...values, action: 'filter_recipes', nonce: catalog.nonce,
      paged: new URL(full.url).pathname.includes('/page/2/') ? 2 : 1, current_category: catalog.current_category, current_tag: catalog.current_tag, page_url: full.url }));
    assert.equal(response.status, 200, label + ' AJAX');
    const json = JSON.parse(response.body); assert.equal(json.success, true);
    assert.deepEqual(cards(json.data.html), cards(full.body), label + ' SSR/AJAX recipe order');
    results.push({ case: label, url: full.url, cards: cards(full.body).length, selected_only_html: true, ajax_equal: true });
    return full;
  }
  for (const [label, values] of scenarios) {
    const full = await checkCatalog(label, params(new URL('/recipes/', origin), values), values);
    const page2 = [...full.body.matchAll(/<a\b[^>]*>2<\/a>/g)].map(m => attrs(m[0])).find(a => a.class?.includes('page-numbers'))?.href;
    if (page2 && ['include', 'exclude', 'combined'].includes(label)) await checkCatalog(label + '-page2', page2, values);
  }
  for (const taxonomy of ['recipe_category', 'recipe_tags']) {
    const link = [...initial.body.matchAll(/<a\b[^>]*>/g)].map(m => attrs(m[0])).find(a => a.href?.includes('/' + taxonomy + '/'))?.href;
    assert.ok(link, 'Taxonomy link missing');
    const values = { include_ingredients: [selection.include_ingredients[0]] };
    await checkCatalog(taxonomy, params(new URL(link), values), values);
  }
  for (const values of [{ include_ingredients: ['missing-ingredient-check'] }, { include_ingredients: [selection.include_ingredients[0]], exclude_ingredients: [selection.include_ingredients[0]] }]) {
    assert.equal((await request(params(new URL('/recipes/', origin), values))).status, 404);
  }
  fs.writeFileSync('docs/seo-ingredient-http-check.json', JSON.stringify({ checked_at: new Date().toISOString(), site: origin,
    method: 'Sequential local guest GETs. Lookup limits/encoding/errors; SSR selected options and AJAX recipe parity, recipe page 2 and taxonomy context. No browser view events.',
    lookup_checks: endpointChecks, catalog_checks: results, invalid_filters_status: 404 }, null, 2) + '\n');
  console.log(`PASS: ${endpointChecks.length} lookup checks and ${results.length} SSR/AJAX pairs; invalid filters return 404.`);
})().catch(error => { console.error(error); process.exitCode = 1; });
