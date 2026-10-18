#!/usr/bin/env node
'use strict';

// Offline, read-only log audit. Never persist client IPs, query values or raw logs.
const fs = require('node:fs');
const path = require('node:path');
const zlib = require('node:zlib');
const [input, output] = process.argv.slice(2);
if (!input || !output) throw new Error('Usage: node tools/seo-server-log-audit.cjs INPUT_DIR OUTPUT_JSON');
const inc = (map, key, n = 1) => {
  // Query keys and URL segments are untrusted; inherited property names are valid keys.
  Object.defineProperty(map, key, { value: (Object.hasOwn(map, key) ? map[key] : 0) + n, writable: true, enumerable: true, configurable: true });
};
const top = (map, limit = 25) => Object.entries(map).sort((a, b) => b[1] - a[1]).slice(0, limit);
const months = Object.fromEntries('Jan Feb Mar Apr May Jun Jul Aug Sep Oct Nov Dec'.split(' ').map((m, i) => [m, String(i + 1).padStart(2, '0')]));
const files = [];
function walk(dir) {
  for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
    const file = path.join(dir, entry.name);
    if (entry.isDirectory()) walk(file);
    else if (/^cook-look\.ru\.(access|error)\.log(?:\.\d+\.gz)?$/.test(entry.name)) files.push(file);
  }
}
walk(input);
const errorRules = [
  ['upstream_buffered_to_disk', /upstream response is buffered to a temporary file/],
  ['upstream_timeout', /upstream timed out/],
  ['php_fatal', /PHP Fatal error/], ['php_warning', /PHP Warning/], ['php_notice', /PHP Notice/],
  ['upstream_connect_failed', /connect\(\) failed/],
  ['nginx_shared_memory_allocation', /shm_add_|ngx_slab_alloc/],
  ['fcgid_process_table_lock', /mod_fcgid: can't lock process table/],
  ['fcgid_client_connection', /mod_fcgid: ap_pass_brigade failed/],
  ['upstream_temp_file_partial_write', /pwritev\(\).*has written only/],
  ['server_no_space_left', /No space left on device/],
  ['fcgid_read_failed', /error reading data from FastCGI server/],
  ['script_ended_before_headers', /End of script output before headers/],
  ['wordpress_cron_event_error', /Cron/],
  ['request_body_buffered_to_disk', /a client request body is buffered/],
  ['modsecurity_block', /ModSecurity: Access denied/],
  ['apache_internal_redirect_limit', /AH00124|internal redirects/],
  ['cgi_directory_probe', /AH01265/],
  ['file_not_found', /No such file or directory|File does not exist|script .* not found|AH01264/],
  ['directory_index_forbidden', /directory index .* forbidden|AH01276/],
  ['request_body_too_large', /client intended to send too large body/],
  ['access_forbidden', /access forbidden|AH01797|AH01630/],
  ['ssl_handshake', /SSL_do_handshake/],
  ['upstream_closed', /upstream prematurely closed/],
  ['upstream_header_too_large', /upstream sent too big header/],
];
const result = { checked_at: new Date().toISOString(), method: 'Offline analysis of supplied access/error logs. User-Agent claims are not verified crawler identities. Query values and client IPs are excluded. No request-duration field in combined access format; no TTFB inference. No automatic deduplication of legitimate repeated requests.', files: [], access: { total: 0, unparsed: 0, invalid_request_lines: 0, status: {}, daily: {}, clients: {}, path_groups: {}, query_keys: {}, query_requests: 0, facet_requests: 0, robots: {}, five_xx_paths: {}, public_404: {}, public_404_internal_referrer: {}, public_404_by_day: {}, crawlers: {}, response_sizes: {} }, errors: { total: 0, unparsed: 0, levels: {}, types: {}, daily: {}, paths_by_type: {}, ranges: {}, examples: {} } };
function client(ua) {
  if (/Lighthouse|Chrome-Lighthouse|PageSpeed|HeadlessChrome/i.test(ua)) return 'diagnostic_browser';
  for (const [name, pattern] of [['Googlebot', /Googlebot/i], ['GoogleOther', /GoogleOther/i], ['GoogleInspectionTool', /Google-InspectionTool/i], ['Yandex', /Yandex/i], ['Bingbot', /bingbot/i], ['OpenAI-search', /OAI-SearchBot/i], ['OpenAI-training', /GPTBot/i], ['ChatGPT-User', /ChatGPT-User/i], ['Claude', /ClaudeBot|Claude-SearchBot|Claude-User|anthropic-ai/i], ['Perplexity', /Perplexity/i], ['PetalBot', /PetalBot/i], ['Meta', /meta-external|facebookexternalhit/i], ['Ahrefs', /Ahrefs/i], ['Semrush', /Semrush/i], ['Applebot', /Applebot/i]]) if (pattern.test(ua)) return name;
  return /bot|spider|crawl|slurp/i.test(ua) ? 'other_bot_claim' : 'other_client';
}
function urlInfo(target) {
  try {
    const u = new URL(target, 'https://cook-look.ru');
    const p = u.pathname;
    const publicPath = p === '/' || /^\/(?:recipe|recipes|recipe_category|recipe_tags|page)(?:\/|$)/.test(p) || /^\/(?:robots\.txt|[^/]*sitemap[^/]*\.xml|policy\/?|favicon\.ico)$/.test(p);
    const group = p === '/' ? 'home' : p.split('/').filter(Boolean)[0] || 'other';
    return { path: publicPath ? p : '[' + group.replace(/[^a-zA-Z0-9_.-]/g, '').slice(0, 60) + ']', group, keys: [...u.searchParams.keys()].map(k => k.replace(/[^a-zA-Z0-9_\[\]-]/g, '').slice(0, 60)), publicPath };
  } catch { return { path: '[invalid_url]', group: 'invalid_url', keys: [], publicPath: false }; }
}
for (const file of files.sort()) {
  let buf = fs.readFileSync(file);
  if (file.endsWith('.gz')) buf = zlib.gunzipSync(buf);
  const info = { name: path.basename(file), kind: path.basename(file).includes('.access.') ? 'access' : 'error', lines: 0, parsed: 0, first: null, last: null };
  for (const line of buf.toString('utf8').split(/\r?\n/)) {
    if (!line) continue;
    info.lines++;
    if (info.kind === 'access') {
      const a = result.access; a.total++;
      let m = line.match(/^\S+ \S+ \S+ \[(\d{2})\/(\w{3})\/(\d{4}):(\d{2}:\d{2}:\d{2}) ([+-]\d{4})\] "(\S+) (.*?) HTTP\/[^"]+" (\d{3}) (\d+|-) "([^"]*)" "(.*)"$/);
      if (!m) {
        const raw = line.match(/^\S+ \S+ \S+ \[(\d{2})\/(\w{3})\/(\d{4}):(\d{2}:\d{2}:\d{2}) ([+-]\d{4})\] ".*" (\d{3}) (\d+|-) "([^"]*)" "(.*)"$/);
        if (!raw) { a.unparsed++; continue; }
        m = [raw[0], ...raw.slice(1, 6), 'INVALID', '/invalid-request-line', ...raw.slice(6)]; a.invalid_request_lines++;
      }
      const date = `${m[3]}-${months[m[2]]}-${m[1]}`, stamp = `${date} ${m[4]} ${m[5]}`;
      info.parsed++; info.first = !info.first || stamp < info.first ? stamp : info.first; info.last = !info.last || stamp > info.last ? stamp : info.last;
      const status = m[8], size = Number(m[9]) || 0, who = client(m[11]), u = urlInfo(m[7]);
      if (u.keys.length) u.path += '?keys=' + [...new Set(u.keys)].sort().join(',');
      inc(a.status, status); inc(a.clients, who); inc(a.path_groups, u.group);
      const day = a.daily[date] ||= { requests: 0, status: {}, clients: {}, facets: 0 }; day.requests++; inc(day.status, status); inc(day.clients, who);
      if (u.keys.length) a.query_requests++;
      for (const k of new Set(u.keys)) inc(a.query_keys, k);
      const facet = u.keys.some(k => /^(?:sort_by|recipe_region|region|category_id|subcategory_id|recipe_category|recipe_subcategory|include_ingredients|exclude_ingredients|filter)/.test(k));
      if (facet) { a.facet_requests++; day.facets++; }
      if (status.startsWith('5')) inc(a.five_xx_paths, `${status} ${u.path}`);
      if (status === '404' && u.publicPath) {
        inc(a.public_404, u.path); inc(a.public_404_by_day, date);
        if (/^https?:\/\/(?:www\.)?cook-look\.ru(?:\/|$)/.test(m[10])) inc(a.public_404_internal_referrer, u.path);
      }
      if (who !== 'other_client' && who !== 'diagnostic_browser') {
        const c = a.crawlers[who] ||= { requests: 0, status: {}, facets: 0, query_requests: 0, public_404: {}, five_xx: {}, robots: {}, groups: {} };
        c.requests++; inc(c.status, status); inc(c.groups, u.group); if (facet) c.facets++; if (u.keys.length) c.query_requests++;
        if (status === '404' && u.publicPath) inc(c.public_404, u.path);
        if (status.startsWith('5')) inc(c.five_xx, `${status} ${u.path}`);
        if (u.path === '/robots.txt' || /sitemap.*\.xml$/.test(u.path)) inc(c.robots, `${status} ${u.path}`);
      }
      if (u.path === '/robots.txt' || /sitemap.*\.xml$/.test(u.path)) inc(a.robots, `${status} ${u.path}`);
      if (status === '200' && ['recipe', 'recipes', 'recipe_category', 'recipe_tags', 'home'].includes(u.group)) {
        const s = a.response_sizes[u.group] ||= { count: 0, total_bytes: 0, max_bytes: 0, over_1MiB: 0 }; s.count++; s.total_bytes += size; s.max_bytes = Math.max(s.max_bytes, size); if (size > 1048576) s.over_1MiB++;
      }
    } else {
      const e = result.errors; e.total++;
      let m = line.match(/^(\d{4}\/\d{2}\/\d{2}) (\d{2}:\d{2}:\d{2}) \[(\w+)\]/);
      if (!m) {
        const apache = line.match(/^\[\w+ (\w+)\s+(\d+) (\d{2}:\d{2}:\d{2})(?:\.\d+)? (\d{4})\] \[(?:[^:\]]*:)?(\w+)\]/);
        if (apache) m = [apache[0], `${apache[4]}/${months[apache[1]]}/${apache[2].padStart(2, '0')}`, apache[3], apache[5]];
      }
      if (!m) { e.unparsed++; continue; }
      const date = m[1].replaceAll('/', '-'), stamp = `${date} ${m[2]}`;
      info.parsed++; info.first = !info.first || stamp < info.first ? stamp : info.first; info.last = !info.last || stamp > info.last ? stamp : info.last;
      const type = errorRules.find(([, pattern]) => pattern.test(line))?.[0] || 'other';
      inc(e.levels, m[3]); inc(e.types, type); const d = e.daily[date] ||= {}; inc(d, type);
      const range = e.ranges[type] ||= { first: stamp, last: stamp }; if (stamp < range.first) range.first = stamp; if (stamp > range.last) range.last = stamp;
      const request = line.match(/request: "\S+ (.*?) HTTP\/[^\"]+"/); if (request) inc(e.paths_by_type[type] ||= {}, urlInfo(request[1]).path);
      // Examples contain only the fixed diagnostic clause before client/upstream data.
      if ((e.examples[type] ||= []).length < 3) {
        let message = line.replace(/^.*?\*\d+ /, '').replace(/^.*?\[client [^\]]+\]\s*/, '').replace(/\[client [^\]]+\]/g, '[client]').split(/, client:|, server:|, request:|, upstream:|, host:|, referer:/)[0];
        message = message.replace(/"[^"]*"|'[^']*'/g, '[quoted]').replace(/\b\d+(?:\.\d+){3}\b/g, '[IP]').replace(/\/(?:[^\s,:;]+\/?)+/g, '[path]').slice(0, 250);
        if (!e.examples[type].includes(message)) e.examples[type].push(message);
      }
    }
  }
  result.files.push(info);
}
for (const key of ['five_xx_paths', 'public_404', 'public_404_internal_referrer']) result.access[key] = top(result.access[key], 40);
for (const c of Object.values(result.access.crawlers)) { c.public_404 = top(c.public_404); c.five_xx = top(c.five_xx); }
for (const key of Object.keys(result.errors.paths_by_type)) result.errors.paths_by_type[key] = top(result.errors.paths_by_type[key]);
fs.writeFileSync(output, JSON.stringify(result, null, 2) + '\n');
console.log(JSON.stringify({ files: result.files, access: { total: result.access.total, unparsed: result.access.unparsed, status: result.access.status, clients: result.access.clients, query_requests: result.access.query_requests, facet_requests: result.access.facet_requests, five_xx_paths: result.access.five_xx_paths, public_404: result.access.public_404.slice(0, 12) }, errors: { total: result.errors.total, unparsed: result.errors.unparsed, types: result.errors.types, examples: result.errors.examples } }, null, 2));
