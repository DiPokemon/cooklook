/* Bounded guest crawl of Cooklook. No login, cookies, parallel load or cache purge.
 * node tools/seo-measure.cjs http://cooklook/ docs/seo-measure-local.json
 * node tools/seo-measure.cjs https://cook-look.ru/ docs/seo-measure-production.json
 * HTTP timing uses fresh connections, identity encoding and no browser assets.
 */
'use strict';
const fs = require('node:fs');
const http = require('node:http');
const https = require('node:https');
const { performance } = require('node:perf_hooks');
const site = new URL(process.argv[2]);
if (!['http://cooklook', 'https://cook-look.ru'].includes(site.origin) || site.username || site.password) {
  throw new Error('Only the configured local and public Cooklook origins are supported.');
}
const local = site.hostname === 'cooklook';
const origin = site.origin;
const output = process.argv[3];
if (!output) throw new Error('Specify the JSON report path.');
const pause = () => new Promise(resolve => setTimeout(resolve, local ? 50 : 350));
const round = n => Math.round(n * 100) / 100;
const decode = s => s.replace(/&#(?:0*38|x26);/g, '&').replace(/&amp;/g, '&').replace(/&quot;/g, '"').replace(/&#(?:0*39|x27);/g, "'").replace(/&lt;/g, '<').replace(/&gt;/g, '>');
const plain = s => decode(s.replace(/<[^>]+>/g, ' ')).replace(/\s+/g, ' ').trim();
const attrs = s => Object.fromEntries([...s.matchAll(/([\w:-]+)\s*=\s*(?:"([^"]*)"|'([^']*)'|([^\s>]+))/g)].map(m => [m[1].toLowerCase(), decode(m[2] ?? m[3] ?? m[4])]));
function request(address, method = 'GET', redirects = []) {
  const url = new URL(address);
  if (url.origin !== origin) return Promise.reject(new Error('Off-origin request refused: ' + url.origin));
  return new Promise((resolve, reject) => {
    const start = performance.now();
    const options = { method, agent: false, hostname: local ? '127.0.0.1' : url.hostname,
      port: url.port || (url.protocol === 'https:' ? 443 : 80), path: url.pathname + url.search,
      headers: { Host: url.host, 'User-Agent': 'CooklookSEOCheck/1.0', 'Accept-Encoding': 'identity' } };
    const req = (url.protocol === 'https:' ? https : http).request(options, res => {
      const ttfb = performance.now() - start;
      const chunks = []; let size = 0;
      res.on('data', chunk => { size += chunk.length; if (size > 8000000) res.destroy(new Error('Response exceeds 8 MB')); else chunks.push(chunk); });
      res.on('error', reject);
      res.on('end', async () => {
        if (res.statusCode >= 300 && res.statusCode < 400 && res.headers.location) {
          if (redirects.length >= 4) return reject(new Error('Redirect limit exceeded'));
          try { return resolve(await request(new URL(res.headers.location, url).href, method, [...redirects, { url: url.href, status: res.statusCode, ttfb_ms: round(ttfb) }])); } catch (e) { return reject(e); }
        }
        resolve({ url: url.href, status: res.statusCode, redirects, ttfb_ms: round(ttfb), total_ms: round(performance.now() - start), bytes: size,
          headers: res.headers, body: Buffer.concat(chunks).toString('utf8') });
      });
    });
    req.setTimeout(30000, () => req.destroy(new Error('Request timeout')));
    req.on('error', reject); req.end();
  });
}
const locs = xml => [...xml.matchAll(/<loc>([^<]+)<\/loc>/g)].map(m => decode(m[1]));
const metric = r => ({ url: r.url, status: r.status, redirects: r.redirects, ttfb_ms: r.ttfb_ms, total_ms: r.total_ms, html_bytes: r.bytes });
function summarize(r, kind) {
  const html = r.body;
  const metas = [...html.matchAll(/<meta\b[^>]*>/gi)].map(m => attrs(m[0]));
  const links = [...html.matchAll(/<link\b[^>]*>/gi)].map(m => attrs(m[0]));
  const nodes = []; const errors = [];
  const visit = x => { if (!x || typeof x !== 'object') return; if (x['@type']) nodes.push(x); Object.values(x).forEach(v => { if (Array.isArray(v)) v.forEach(visit); else if (v && typeof v === 'object') visit(v); }); };
  for (const match of html.matchAll(/<script\b([^>]*)>([\s\S]*?)<\/script>/gi)) {
    if (attrs(match[1]).type !== 'application/ld+json') continue;
    try { visit(JSON.parse(match[2])); } catch (e) { errors.push(e.message); }
  }
  const typed = type => nodes.filter(n => [].concat(n['@type']).includes(type));
  const images = [...html.matchAll(/<img\b[^>]*>/gi)].map(m => attrs(m[0]));
  const recipes = typed('Recipe').map(n => ({ id: n['@id'] || null, name: n.name, image: n.image,
    critical_missing: ['name', 'image'].filter(k => !n[k] || ![].concat(n[k]).length),
    ingredient_count: [].concat(n.recipeIngredient || []).length, step_count: [].concat(n.recipeInstructions || []).length,
    author: n.author, nutrition_present: Boolean(n.nutrition), main_entity_of_page: n.mainEntityOfPage || null,
    broken_step_anchors: [].concat(n.recipeInstructions || []).filter(s => s.url && new URL(s.url, r.url).hash && !html.includes('id="' + new URL(s.url, r.url).hash.slice(1) + '"')).map(s => s.url) }));
  const breadcrumbs = typed('BreadcrumbList').map(n => ({ count: (n.itemListElement || []).length,
    ordered: (n.itemListElement || []).every((x, i) => x.position === i + 1 && Boolean(x.name)),
    missing_intermediate_urls: (n.itemListElement || []).slice(0, -1).filter(x => !x.item || (typeof x.item === 'object' && !x.item['@id'] && !x.item.url)).map(x => x.position) }));
  const scripts = [...html.matchAll(/<script\b[^>]*>/gi)].map(m => attrs(m[0]).src).filter(Boolean);
  return { ...metric(r), kind, title: plain((html.match(/<title[^>]*>([\s\S]*?)<\/title>/i) || [,''])[1]),
    h1: [...html.matchAll(/<h1\b[^>]*>([\s\S]*?)<\/h1>/gi)].map(m => plain(m[1])),
    description: metas.filter(m => m.name === 'description').map(m => m.content),
    social: Object.fromEntries(['og:title', 'og:description', 'twitter:title', 'twitter:description'].map(key => [key, metas.filter(m => m.property === key || m.name === key).map(m => m.content)])),
    webpages: typed('WebPage').map(n => ({ id: n['@id'] || null, name: n.name || '', description: n.description || '' })),
    canonical: links.filter(l => l.rel === 'canonical').map(l => l.href),
    robots: metas.filter(m => /^(robots|googlebot|bingbot)$/i.test(m.name || '')).map(m => m.content),
    x_robots_tag: r.headers['x-robots-tag'] || null, cache_control: r.headers['cache-control'] || null,
    jsonld_errors: errors, recipes, breadcrumbs, website_count: typed('WebSite').length,
    ingredients_in_html: html.includes('ingridients_list-item'), steps_in_html: /id=["']step_\d+/.test(html),
    php_diagnostics: /(?:<b>(?:Warning|Notice|Fatal error)<\/b>|PHP (?:Warning|Fatal error)|Uncaught (?:Error|Exception))/.test(html),
    local_url_leak: !local && /https?:\/\/(?:cooklook|localhost|127\.0\.0\.1)(?:[\/:"'\s])/.test(html),
    images: { total: images.length, missing_dimensions: images.filter(i => !i.width || !i.height).length, missing_alt: images.filter(i => !Object.hasOwn(i, 'alt')).length },
    stylesheets: links.filter(l => l.rel === 'stylesheet').map(l => l.href), scripts };
}
async function main() {
  const report = { checked_at: new Date().toISOString(), site: origin,
    scope: local ? 'All recipe URLs from the local sitemap; sampled other templates and negative cases.' : 'All sitemap files, stratified public URL sample; not a full crawl of every production page.',
    method: 'Sequential guest GETs, fresh TCP/TLS per request, identity encoding, no cookies; ordinary recipe view counters may increment. Times are lab HTTP measurements from this workstation, not field CWV.',
    robots: null, sitemaps: [], pages: [], timings: [], image_checks: [], errors: [] };
  const save = () => fs.writeFileSync(output, JSON.stringify(report, null, 2) + '\n');
  const robots = await request(origin + '/robots.txt');
  report.robots = { ...metric(robots), text: robots.body };
  const index = await request(origin + '/sitemap_index.xml');
  if (robots.status !== 200 || index.status !== 200 || !index.body.includes('<sitemapindex')) throw new Error('robots.txt or sitemap index unavailable.');
  report.sitemap_index = metric(index);
  const selected = new Map();
  for (const path of ['/', '/recipes/', '/recipes/page/2/', '/account/', '/my-favorites/', '/?s=каша', '/recipes/?sort_by=recipe_time', '/recipes/?sort_by=recipe_views', '/recipes/?recipe_category=999999999', '/recipes/?include_ingredients=seo-audit-nonexistent', '/recipes/page/999999/', '/seo-audit-nonexistent/']) selected.set(new URL(path, origin).href, 'template_or_negative_case');
  const maps = locs(index.body);
  if (maps.length > 32) throw new Error('More than 32 sitemaps; review the crawl scope before continuing.');
  for (const url of maps) {
    await pause();
    try {
      const map = await request(url);
      const entries = [...map.body.matchAll(/<url>([\s\S]*?)<\/url>/g)].map(m => ({ url: locs(m[1])[0], lastmod: (m[1].match(/<lastmod>([^<]+)<\/lastmod>/) || [,''])[1] })).filter(x => x.url);
      report.sitemaps.push({ ...metric(map), urls: entries.length, foreign_hosts: entries.filter(x => new URL(x.url).origin !== origin).length,
        future_lastmod: entries.filter(x => x.lastmod && Date.parse(x.lastmod) > Date.now() + 86400000).map(x => x.url),
        service_urls: entries.filter(x => /\/(?:account|my-favorites|rcl-postedit)\//.test(x.url)).map(x => x.url) });
      const recipeMap = /\/recipe-sitemap\d*\.xml$/.test(url);
      let sample = entries;
      if (recipeMap && !local) sample = url.endsWith('/recipe-sitemap.xml') ? entries.filter(x => /\/recipe\//.test(x.url)).slice(0, 10) : entries.slice(0, 1);
      else if (!recipeMap) sample = entries.slice(0, 2);
      for (const entry of sample) if (new URL(entry.url).origin === origin) selected.set(entry.url, recipeMap && /\/recipe\//.test(entry.url) ? 'recipe' : 'sitemap_sample');
    } catch (e) { report.errors.push({ url, error: e.message }); }
    save();
  }
  if (selected.size > (local ? 250 : 100)) throw new Error('Page sample exceeds the audit limit; review the crawl scope.');
  let count = 0;
  for (const [url, kind] of selected) {
    await pause();
    try { report.pages.push(summarize(await request(url), kind)); }
    catch (e) { report.errors.push({ url, error: e.message }); }
    if (++count % 10 === 0) process.stderr.write('Crawled ' + count + '/' + selected.size + '\n');
    save();
  }
  const firstRecipe = report.pages.find(p => p.kind === 'recipe' && p.status === 200 && p.recipes.length);
  for (const url of [origin + '/', origin + '/recipes/', firstRecipe?.url].filter(Boolean)) {
    const runs = [];
    for (let i = 0; i < 3; i++) { await pause(); try { runs.push(metric(await request(url))); } catch (e) { report.errors.push({ url, error: e.message }); } }
    const values = runs.map(r => r.ttfb_ms).sort((a,b) => a-b);
    report.timings.push({ url, runs, median_ttfb_ms: values.length ? values[Math.floor(values.length / 2)] : null });
    save();
  }
  const images = [...new Set(report.pages.filter(p => p.kind === 'recipe' && p.recipes.length).slice(0, 10).flatMap(p => p.recipes.flatMap(r => [].concat(r.image || []).map(i => typeof i === 'string' ? i : i.url).filter(Boolean))))];
  for (const url of images.slice(0, 20)) {
    await pause();
    try { const r = await request(url, 'HEAD'); report.image_checks.push({ url, status: r.status, redirects: r.redirects, content_type: r.headers['content-type'] || null, bytes: Number(r.headers['content-length'] || 0) }); }
    catch (e) { report.errors.push({ url, error: e.message }); }
  }
  report.completed_at = new Date().toISOString(); save();
  process.stdout.write(JSON.stringify({ output, pages: report.pages.length, sitemap_urls: report.sitemaps.reduce((a,m) => a+m.urls,0), errors: report.errors.length }) + '\n');
}
main().catch(e => { process.stderr.write(e.message + '\n'); process.exitCode = 1; });
