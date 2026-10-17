const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

// Exercise the actual catalog controller without a browser, network or dependencies.
const elements = new Map();
const requests = [];
const history = [];
const document = {};
function element(key) {
	if (!elements.has(key)) elements.set(key, { key, value: null, props: {}, handlers: {}, attrs: {}, html: '' });
	return elements.get(key);
}
function wrap(items) {
	return {
		items, length: items.length,
		val(value) { if (arguments.length === 0) return items[0].value; items.forEach(x => x.value = value); return this; },
		select2() { return this; },
		trigger(name) { items.forEach(x => { if (x.handlers[name]) x.handlers[name].call(x, { preventDefault() {} }); }); return this; },
		on(name, selector, callback) { items.forEach(x => x.handlers[name + (typeof selector === 'string' ? ':' + selector : '')] = callback || selector); return this; },
		prop(name, value) { items.forEach(x => x.props[name] = value); return this; },
		attr(name, value) { if (arguments.length === 1) return items[0].attrs[name]; items.forEach(x => x.attrs[name] = value); return this; },
		removeAttr(name) { items.forEach(x => delete x.attrs[name]); return this; },
		empty() { items.forEach(x => { x.html = ''; x.value = null; }); return this; },
		append(option) { items.forEach(x => { if (x.value === null) x.value = option.value; }); return this; },
		html(value) { items.forEach(x => x.html = value); return this; },
		replaceWith(value) { items.forEach(x => x.html = value); return this; },
		last() { return wrap(items.slice(-1)); },
		text(value) { items.forEach(x => x.text = value); return this; },
		insertBefore() { return this; }, addClass() { return this; }, removeClass() { return this; }, css() { return this; }
	};
}
function $(value) {
	if (typeof value === 'function') { value($); return; }
	if (value === document) return wrap([element('document')]);
	if (typeof value === 'object') return wrap([value]);
	return wrap(value.split(',').map(key => element(key.trim())));
}
$.extend = Object.assign;
function request(options) {
	const callbacks = {};
	const result = {
		options, aborted: false,
		done(fn) { callbacks.done = fn; return this; },
		fail(fn) { callbacks.fail = fn; return this; },
		always(fn) { callbacks.always = fn; return this; },
		abort() { this.aborted = true; if (callbacks.fail) callbacks.fail({}, 'abort'); if (callbacks.always) callbacks.always(); },
		resolve(data) { if (callbacks.done) callbacks.done(data); if (callbacks.always) callbacks.always(); }
	};
	requests.push(result);
	return result;
}
$.ajax = request;
$.getJSON = (url, data) => request({ url, data });
const context = {
	jQuery: $, document, URL, Number, String, Array, Object,
	Option: function (text, value) { this.value = value; },
	window: { location: { href: 'https://example.test/subsite/recipes/?paged=3', reload() {} }, history: { pushState(_, __, url) { history.push(url); } }, addEventListener() {} },
	filter_obj: { ajax_url: '/subsite/wp-admin/admin-ajax.php', nonce: 'test', current_category: 9, current_tag: 'soup', base_url: 'https://example.test/subsite/recipes/', filters: { category_id: 1, subcategory_id: 2, region: 'Русская', include_ingredients: ['milk', 'egg'], exclude_ingredients: ['nuts'], sort_by: 'date' } }
};
vm.runInNewContext(fs.readFileSync(require('node:path').join(__dirname, '../static/js/filters.js'), 'utf8'), context);
assert.equal(requests.length, 0, 'server-rendered filters avoid a redundant initial request');
assert.equal(element('#recipe_subcategory').value, '2', 'subcategory restored after asynchronous load');
element('#sort_by-mobile').value = 'recipe_time';
element('#sort_by-mobile').handlers.change.call(element('#sort_by-mobile'));
let current = requests.at(-1);
assert.equal(current.options.data.sort_by, 'recipe_time');
assert.equal(current.options.data.subcategory_id, '2');
assert.equal(current.options.data.current_category, 9);
assert.equal(current.options.data.current_tag, 'soup');
assert.equal(current.options.data.region, 'Русская');
assert.deepEqual(Array.from(current.options.data.include_ingredients), ['egg', 'milk']);
current.resolve({ success: true, data: { html: 'filtered', pagination: 'pages' } });
assert.equal(element('#response').html, 'filtered');
assert.match(history.at(-1), /subsite\/recipes\//);
assert.match(history.at(-1), /include_ingredients%5B%5D=milk/);
assert(!history.at(-1).includes('nonce='));
const click = element('document').handlers['click:.pagination a'];
click.call({ href: 'https://example.test/subsite/recipes/page/4/' }, { preventDefault() {} });
assert.equal(requests.at(-1).options.data.paged, 4, 'pretty permalink pagination is understood');
const stale = requests.at(-1);
element('#recipe-filter').handlers.submit({ preventDefault() {} });
const latest = requests.at(-1);
assert(stale.aborted, 'previous request canceled');
stale.resolve({ success: true, data: { html: 'stale' } });
assert.notEqual(element('#response').html, 'stale', 'stale response ignored');
latest.resolve({ success: true, data: { html: 'latest', pagination: '' } });
assert.equal(element('#response').html, 'latest');
element('#recipe_category').value = 'all';
element('#recipe_subcategory').value = 'all';
element('#recipe_region').value = '';
element('#include_ingredients').value = [];
element('#exclude_ingredients').value = [];
element('#recipe-filter').handlers.submit({ preventDefault() {} });
assert.equal(requests.at(-1).options.data.category_id, 'all', 'reset still sends a request');
context.filter_obj.base_url = 'https://example.test/subsite/recipe_tags/%D0%BC%D0%BE%D0%BB%D0%BE%D0%BA%D0%BE/?include_ingredients%5B0%5D=milk&include_ingredients%5B7%5D=old&exclude_ingredients%5B0%5D=nuts&exclude_ingredients%5Bbad%5D=old&include_ingredients=old&sort_by=recipe_time&paged=9&region=old&utm_source=kept';
element('#sort_by').value = 'date';
element('#recipe-filter').handlers.submit({ preventDefault() {} });
current = requests.at(-1);
let clean = new URL(current.options.data.page_url);
assert.deepEqual(Array.from(clean.searchParams.keys()), ['utm_source'], 'reset removes indexed, scalar, empty and default filter keys');
assert.match(clean.pathname, /recipe_tags\/%D0%BC/, 'Cyrillic archive path preserved');
current.resolve({ success: true, data: { html: 'reset', pagination: '' } });
assert.equal(history.at(-1), clean.href, 'history records the same clean state as the request');
element('#include_ingredients').value = ['%d0%bc%d0%be%d0%bb%d0%be%d0%ba%d0%be', 'egg', 'egg', ''];
element('#exclude_ingredients').value = ['nuts', 'nuts'];
element('#recipe-filter').handlers.submit({ preventDefault() {} });
current = requests.at(-1);
clean = new URL(current.options.data.page_url);
assert.deepEqual(clean.searchParams.getAll('include_ingredients[]'), Array.from(current.options.data.include_ingredients), 'AJAX and reload have the same normalized ingredients');
assert.equal(clean.searchParams.getAll('include_ingredients[]').length, 2);
assert.deepEqual(clean.searchParams.getAll('exclude_ingredients[]'), ['nuts']);
assert(!clean.href.includes('milk') && !clean.href.includes('old'), 'replacing selections never retains legacy values');
for (const trailing of ['', '/']) {
	context.filter_obj.base_url = 'https://example.test/subsite/index.php/recipes' + trailing;
	context.filter_obj.pagination = { base: context.filter_obj.base_url + '%_%', format: (trailing ? '' : '/') + 'page/%#%' + trailing };
	click.call({ href: 'https://example.test/subsite/index.php/recipes/page/4' + trailing }, { preventDefault() {} });
	let url = new URL(requests.at(-1).options.data.page_url);
	assert.equal(url.pathname, '/subsite/index.php/recipes/page/4' + trailing);
	assert(!url.searchParams.has('paged'), 'pretty URL must not use query pagination');
	element('#recipe-filter').handlers.submit({ preventDefault() {} });
	url = new URL(requests.at(-1).options.data.page_url);
	assert.equal(url.pathname, '/subsite/index.php/recipes' + trailing, 'filter submission returns to first page');
}
context.filter_obj.base_url = 'https://example.test/subsite/?post_type=recipe';
context.filter_obj.pagination = { base: context.filter_obj.base_url + '%_%', format: '&paged=%#%' };
click.call({ href: 'https://example.test/subsite/?post_type=recipe&paged=3' }, { preventDefault() {} });
let plain = new URL(requests.at(-1).options.data.page_url);
assert.equal(plain.searchParams.get('post_type'), 'recipe');
assert.equal(plain.searchParams.get('paged'), '3');
element('#recipe-filter').handlers.submit({ preventDefault() {} });
assert(!new URL(requests.at(-1).options.data.page_url).searchParams.has('paged'));
context.filter_obj.base_url = 'https://example.test/subsite/?recipe_category=soup';
context.filter_obj.pagination = { base: context.filter_obj.base_url + '%_%', format: '&paged=%#%' };
context.filter_obj.route_query = { recipe_category: 'soup' };
click.call({ href: 'https://example.test/subsite/?recipe_category=soup&paged=2' }, { preventDefault() {} });
plain = new URL(requests.at(-1).options.data.page_url);
assert.equal(plain.searchParams.get('recipe_category'), 'soup', 'plain taxonomy route survives facet reset');
assert.equal(plain.searchParams.get('paged'), '2');
context.filter_obj.page_metadata = {
	first: {title:'Catalog',description:'Catalog description',breadcrumbs:'First page breadcrumbs'},
	paged: {title:'Catalog — page %page%',description:'Page %page%. Catalog description',breadcrumbs:'Paged breadcrumbs'},
	page_label:'Page %s'
};
requests.at(-1).resolve({success:true,data:{html:'page 2',pagination:'pages'}});
assert.equal(document.title, 'Catalog — page 2');
assert.equal(element('.page-header__breadcrumbs .breadcrumbs__current').text, 'Page 2');
assert.equal(element('.archive_description').props.hidden, true);
element('#recipe-filter').handlers.submit({preventDefault(){}});
requests.at(-1).resolve({success:true,data:{html:'page 1',pagination:'pages'}});
assert.equal(document.title, 'Catalog');
assert.equal(element('.page-header__breadcrumbs').html, 'First page breadcrumbs');
assert.equal(element('.archive_description').props.hidden, false);
console.log('PASS: catalog controller preserves plain/pretty URLs, filters, history, mobile sorting and cancels stale responses.');
