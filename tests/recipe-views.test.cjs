const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { runInNewContext } = require('node:vm');
const source = readFileSync(require('node:path').join(__dirname, '../static/js/recipe-views.js'), 'utf8');
const flush = () => new Promise(resolve => setImmediate(resolve));
function target(properties = {}) {
	const events = new Map();
	return Object.assign(properties, {
		addEventListener(name, callback) { if (!events.has(name)) events.set(name, new Set()); events.get(name).add(callback); },
		removeEventListener(name, callback) { events.get(name)?.delete(callback); },
		emit(name) { for (const callback of [...(events.get(name) || [])]) callback(); }
	});
}
function setup(options = {}) {
	const requests = [];
	const document = target({ readyState: options.loaded ? 'complete' : 'loading', visibilityState: options.hidden ? 'hidden' : 'visible', prerendering: !!options.prerender });
	const window = target({ location: new URL('https://example.test/subsite/recipe/42/'), fetch: async (url, init) => {
		requests.push({ url, init });
		if (options.networkFailure) throw new Error('Network failed after sending');
		return { status: options.status || (options.expired && requests.length === 1 ? 403 : 200), json: async () => ({ success: false, data: { code: options.code || 'invalid_nonce', nonce: 'fresh' } }) };
	} });
	const config = { recipe_id: 42, ajax_url: options.endpoint || 'https://example.test/subsite/wp-admin/admin-ajax.php', nonce: 'initial' };
	const context = { window, document, cooklookRecipeView: config, URL, URLSearchParams };
	runInNewContext(source, context);
	return { window, document, requests, repeatScript: () => runInNewContext(source, context) };
}
(async () => {
	const normal = setup();
	normal.document.emit('visibilitychange');
	assert.equal(normal.requests.length, 0, 'Must wait for load');
	normal.window.emit('load');
	await flush();
	normal.window.emit('load'); normal.document.emit('visibilitychange'); normal.window.emit('pageshow'); normal.repeatScript();
	assert.equal(normal.requests.length, 1, 'Duplicate events/script or bfcache restore must not double count');
	const request = normal.requests[0];
	assert.equal(request.init.method, 'POST');
	assert.equal(request.init.credentials, 'same-origin');
	assert.equal(request.init.mode, 'same-origin');
	assert.equal(request.init.cache, 'no-store');
	assert.equal(request.init.keepalive, true);
	assert.equal(request.init.body.get('recipe_id'), '42');
	assert.equal(request.init.body.get('nonce'), 'initial');
	assert.equal(setup({ loaded: true }).requests.length, 1, 'Late script must count a loaded visible page');
	const hidden = setup({ hidden: true });
	hidden.window.emit('load');
	assert.equal(hidden.requests.length, 0, 'Background tab must not count yet');
	hidden.document.visibilityState = 'visible'; hidden.document.emit('visibilitychange');
	assert.equal(hidden.requests.length, 1);
	const prerender = setup({ loaded: true, prerender: true });
	assert.equal(prerender.requests.length, 0);
	prerender.document.prerendering = false; prerender.document.emit('prerenderingchange');
	assert.equal(prerender.requests.length, 1);
	assert.equal(setup({ loaded: true, endpoint: 'https://evil.test/collect' }).requests.length, 0, 'Foreign endpoint must not receive tokens');
	const stale = setup({ loaded: true, expired: true });
	await flush();
	assert.equal(stale.requests.length, 2, 'Expired cached nonce must refresh once');
	assert.equal(stale.requests[1].init.body.get('nonce'), 'fresh');
	for (const options of [{ status: 403 }, { status: 403, code: 'invalid_origin' }, { status: 409 }, { status: 500 }, { networkFailure: true }]) {
		const test = setup({ loaded: true, ...options });
		await flush();
		assert.equal(test.requests.length, options.status === 403 && !options.code ? 2 : 1, 'Unexpected retry');
	}
	console.log('PASS: views wait for load/visibility, skip prerender until activation, send once, refresh stale tokens once and never retry ambiguous failures.');
})().catch(error => { console.error(error); process.exitCode = 1; });
