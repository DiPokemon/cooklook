/* Read-only public HTTP sample; no credentials, cookies, WAF changes or verified-bot claims.
 * Usage: node tools/seo-ai-access.cjs https://cook-look.ru/ > docs/seo-ai-access.json
 */
'use strict';
const site = new URL(process.argv[2]);
if (site.protocol !== 'https:' || site.username || site.password) throw new Error('Use a public HTTPS origin without credentials.');
const origin = site.origin;
const guest = 'Mozilla/5.0 (compatible; CooklookSEOCheck/1.0)';
async function request(url, agent = guest) {
  for (let redirects = 0; redirects <= 3; redirects++) {
    const response = await fetch(url, { headers: { 'User-Agent': agent }, redirect: 'manual', signal: AbortSignal.timeout(20000) });
    if (response.status >= 300 && response.status < 400 && response.headers.has('location')) {
      await response.body?.cancel();
      const next = new URL(response.headers.get('location'), url);
      if (next.origin !== origin) throw new Error('Unexpected redirect outside the selected site.');
      url = next.href;
      continue;
    }
    const chunks = [];
    let bytes = 0;
    for await (const chunk of response.body) {
      bytes += chunk.length;
      if (bytes > 2000000) throw new Error('Response exceeds the 2 MB audit limit.');
      chunks.push(chunk);
    }
    return { url, status: response.status, redirects, xRobotsTag: response.headers.get('x-robots-tag'), body: Buffer.concat(chunks).toString('utf8') };
  }
  throw new Error('Too many redirects.');
}
function attributes(tag) {
  return Object.fromEntries(Array.from(tag.matchAll(/([\w-]+)\s*=\s*(["'])(.*?)\2/gs), match => [match[1].toLowerCase(), match[3]]));
}
function summarize(result, agent) {
  const metas = Array.from(result.body.matchAll(/<meta\b[^>]*>/gi), match => attributes(match[0]));
  const canonical = Array.from(result.body.matchAll(/<link\b[^>]*>/gi), match => attributes(match[0])).filter(item => item.rel === 'canonical').map(item => item.href);
  return {
    url: result.url, agent, status: result.status, redirects: result.redirects, x_robots_tag: result.xRobotsTag,
    robots: metas.filter(item => /^(robots|googlebot|bingbot)$/i.test(item.name || '')).map(item => ({ name: item.name, content: item.content })),
    canonical, h1_count: (result.body.match(/<h1\b/gi) || []).length,
    ingredients_in_html: /ingridients_list-item/.test(result.body), steps_in_html: /id=["']step_\d+/.test(result.body),
    data_nosnippet_count: (result.body.match(/\bdata-nosnippet(?=[\s=>])/gi) || []).length
  };
}
(async () => {
  const robots = await request(origin + '/robots.txt');
  const sitemap = await request(origin + '/recipe-sitemap.xml');
  if (robots.status !== 200 || sitemap.status !== 200) throw new Error('robots.txt or recipe sitemap unavailable.');
  const urls = Array.from(sitemap.body.matchAll(/<loc>([^<]+)<\/loc>/g), match => match[1]).filter(url => {
    const parsed = new URL(url);
    return parsed.origin === origin && parsed.pathname.startsWith('/recipe/');
  }).slice(0, 10);
  if (urls.length !== 10) throw new Error('Expected ten public recipe URLs in sitemap.');
  const report = { checked_at: new Date().toISOString(), site: origin, note: 'User-Agent probes use our ordinary IP, not verified crawler IPs. Search Console, server logs and editorial claims are not verified.', robots_txt: robots.body, pages: [], agent_probes: [] };
  for (const url of urls) report.pages.push(summarize(await request(url), guest));
  for (const agent of ['Googlebot', 'OAI-SearchBot', 'Claude-SearchBot', 'Claude-User', 'PerplexityBot', 'ChatGPT-User', 'Perplexity-User']) {
    report.agent_probes.push(summarize(await request(urls[0], agent), agent));
  }
  process.stdout.write(JSON.stringify(report, null, 2) + '\n');
})().catch(error => { process.stderr.write(error.message + '\n'); process.exitCode = 1; });
