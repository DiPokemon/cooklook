const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { join } = require('node:path');
const { runInNewContext } = require('node:vm');

const script = readFileSync(join(__dirname, '../static/js/recipe-manager.js'), 'utf8');
const ajaxUrl = '/wp-admin/admin-ajax.php';
const location = { pathname: '/wp-admin/admin.php', search: '?page=recipe-manager' };

function element(tag) {
	const properties = {};
	const children = [];
	const handlers = {};
	let content = '';
	let input = '';

	return {
		tag,
		child(name) { return children.find(child => child.tag === name); },
		text(value) {
			if (value === undefined) return content + children.map(child => child.text()).join('');
			this.empty();
			content = String(value);
			return this;
		},
		val(value) {
			if (value === undefined) return input;
			input = String(value);
			return this;
		},
		empty() {
			content = '';
			children.length = 0;
			return this;
		},
		append(child) {
			children.push(child);
			return this;
		},
		appendTo(parent) {
			parent.append(this);
			return this;
		},
		prop(name, value) {
			if (value === undefined) return properties[name];
			properties[name] = value;
			return this;
		},
		attr(name, value) { return this.prop(name, value); },
		on(name, callback) {
			handlers[name] = callback;
			return this;
		},
		trigger(name) {
			assert.notEqual(properties.disabled, true, 'An enabled control is required for this action');
			assert.notEqual(properties.hidden, true, 'A visible control is required for this action');
			handlers[name].call({ reportValidity: () => true }, { preventDefault() {} });
		}
	};
}

function openManager(storage = new Map(), { drafts = 10, published = 21, lastPublication } = {}) {
	const nodes = new Map();
	const requests = [];
	const sessionStorage = {
		getItem: key => storage.get(key) ?? null,
		setItem: (key, value) => storage.set(key, String(value)),
		removeItem: key => storage.delete(key)
	};

	function jQuery(selector, attributes = {}) {
		if (typeof selector === 'function') return selector(jQuery);
		if (selector.startsWith('<')) {
			const node = element(selector.slice(1, -1));
			for (const [name, value] of Object.entries(attributes)) {
				if (name === 'text') node.text(value);
				else node.prop(name, value);
			}
			return node;
		}
		if (selector.includes(',')) {
			const selected = selector.split(',').map(part => jQuery(part.trim()));
			return {
				empty() { selected.forEach(node => node.empty()); return this; },
				prop(...args) { selected.forEach(node => node.prop(...args)); return this; }
			};
		}
		if (selector === '#recipe-title a') return jQuery('#recipe-title').child('a');
		if (!nodes.has(selector)) nodes.set(selector, element());
		return nodes.get(selector);
	}

	const form = jQuery('#editable-steps-form');
	form.find = selector => {
		assert.equal(selector, 'button');
		return jQuery('#save-steps, #skip-recipe, #retry-recipe, #restart-recipe-pass');
	};
	form.serializeArray = () => [{ name: 'recipe_id', value: jQuery('#current-recipe-id').val() }];

	jQuery.post = (url, payload, _callback, dataType) => {
		assert.equal(url, ajaxUrl);
		assert.equal(dataType, 'json');
		const data = Array.isArray(payload) ? Object.fromEntries(payload.map(({ name, value }) => [name, value])) : payload;
		assert.equal(data.nonce, 'test-nonce');
		let onDone;
		let onFail;
		let onAlways;
		requests.push({
			data,
			succeed(response) { onDone(response); onAlways(); },
			fail(xhr = {}) { onFail(xhr); onAlways(); }
		});
		return {
			done(callback) { onDone = callback; return this; },
			fail(callback) { onFail = callback; return this; },
			always(callback) { onAlways = callback; return this; }
		};
	};

	jQuery('#manager-draft-count').text(drafts);
	jQuery('#manager-published-count').text(published);
	jQuery('#manager-publication, #manager-retry-action, #manager-restart-action').prop('hidden', true);
	runInNewContext(script, {
		window: { location, sessionStorage },
		jQuery,
		cooklookManager: { ajax_url: ajaxUrl, nonce: 'test-nonce', user_id: 7, last_publication: lastPublication }
	});

	return {
		chooseDraft(afterId) {
			const request = requests.shift();
			assert.equal(request?.data.action, 'get_random_draft_recipe');
			assert.equal(Number(request.data.after_id), afterId);
			return request;
		},
		saveDraft(recipeId) {
			form.trigger('submit');
			const request = requests.shift();
			assert.equal(request?.data.action, 'save_recipe_steps');
			assert.equal(Number(request.data.recipe_id), recipeId);
			return request;
		},
		click(selector) { jQuery(selector).trigger('click'); },
		node: jQuery,
		checkIdle() { assert.equal(requests.length, 0); }
	};
}

function draft(ID, title) {
	return {
		success: true,
		data: {
			ID, title, edit_link: '/wp-admin/post.php?post=' + ID,
			original_steps: ['Bake.'], steps: ['Bake.'], ingredients: ['Flour 300 g'], time: 30, prep: 5
		}
	};
}

function checkSkipAndResume() {
	const storage = new Map();
	const page = openManager(storage);
	page.chooseDraft(0).succeed(draft(42, 'Пирожки с яблоками'));
	assert.match(page.node('#recipe-title').text(), /\b42\b/);
	page.click('#skip-recipe');
	page.chooseDraft(42).fail();
	assert.equal(page.node('#manager-retry-action').prop('hidden'), false);
	assert.equal(page.node('#skip-recipe').prop('disabled'), true);
	assert.notEqual(page.node('#manager-message').text(), '');
	assert.equal(page.node('#manager-draft-count').text(), '10');
	assert.equal(page.node('#manager-published-count').text(), '21');

	page.click('#retry-recipe');
	page.chooseDraft(42).succeed(draft(42, 'Пирожки с яблоками'));
	assert.equal(page.node('#recipe-title').text(), '', 'A stale response must not reopen a skipped draft');
	assert.equal(page.node('#skip-recipe').prop('disabled'), true);
	assert.equal(page.node('#manager-retry-action').prop('hidden'), false);

	const reloaded = openManager(storage);
	const endMessage = 'Текущий проход завершён. Подходящие черновики остались.';
	reloaded.chooseDraft(42).succeed({ success: true, data: { pass_complete: true, message: endMessage } });
	assert.equal(reloaded.node('#manager-message').text(), endMessage);
	assert.equal(reloaded.node('#manager-restart-action').prop('hidden'), false);
	assert.equal(reloaded.node('#manager-retry-action').prop('hidden'), true);
	reloaded.checkIdle();

	// An end-of-pass response preserves the position. Only explicit restart resets it.
	const finalPage = openManager(storage);
	finalPage.chooseDraft(42).succeed({ success: true, data: { pass_complete: true, message: endMessage } });
	finalPage.click('#restart-recipe-pass');
	finalPage.chooseDraft(0).succeed(draft(42, 'Пирожки с яблоками'));
}

function checkPublicationAndResume() {
	const storage = new Map();
	const page = openManager(storage);
	page.chooseDraft(0).succeed(draft(42, 'Пирожки с яблоками'));
	page.saveDraft(42).succeed({ success: true, data: 'Рецепт опубликован.' });
	const receipt = page.node('#manager-publication').text();
	assert.match(receipt, /\b42\b/);
	assert.match(receipt, /Пирожки с яблоками/);
	assert.equal(page.node('#manager-draft-count').text(), '9');
	assert.equal(page.node('#manager-published-count').text(), '22');
	page.chooseDraft(42).fail();
	assert.equal(page.node('#manager-publication').text(), receipt);
	assert.notEqual(page.node('#manager-message').text(), '');

	const reloaded = openManager(storage, { drafts: 9, published: 22 });
	assert.equal(reloaded.node('#manager-publication').text(), receipt);
	reloaded.chooseDraft(42).succeed(draft(43, 'Хлеб'));
	reloaded.saveDraft(43).fail();
	assert.equal(reloaded.node('#manager-publication').text(), receipt);
	assert.equal(reloaded.node('#manager-published-count').text(), '22');
	reloaded.checkIdle();
	openManager(storage).chooseDraft(42);
}

function checkServerPublicationReceipt() {
	const lastPublication = 'Предыдущая попытка. Рецепт опубликован. ID 43 — Хлеб.';
	const options = { drafts: 9, published: 22, lastPublication };
	const storage = new Map([['cooklook-manager-publication:/wp-admin/admin.php:7', 'Рецепт опубликован. ID 42 — Пирожки.']]);
	for (const page of [openManager(new Map(), options), openManager(storage, options)]) {
		assert.equal(page.node('#manager-publication').text(), lastPublication);
		assert.equal(page.node('#manager-publication').prop('hidden'), false);
		assert.equal(page.node('#manager-draft-count').text(), '9');
		assert.equal(page.node('#manager-published-count').text(), '22');
		page.chooseDraft(0).succeed(draft(44, 'Кекс'));
		assert.match(page.node('#recipe-title').text(), /\b44\b/);
		page.checkIdle();
	}

	const withoutReceipt = openManager(storage, { lastPublication: '' });
	assert.equal(withoutReceipt.node('#manager-publication').prop('hidden'), true);
}

function checkTerminalStates() {
	const empty = openManager();
	const emptyMessage = 'Нет подходящих черновиков.';
	empty.chooseDraft(0).succeed({ success: false, data: emptyMessage });
	assert.equal(empty.node('#manager-message').text(), emptyMessage);
	assert.equal(empty.node('#manager-retry-action').prop('hidden'), true);
	assert.equal(empty.node('#manager-restart-action').prop('hidden'), true);
	empty.checkIdle();

	const forbidden = openManager();
	const forbiddenMessage = 'Недостаточно прав.';
	forbidden.chooseDraft(0).fail({ status: 403, responseJSON: { success: false, data: forbiddenMessage } });
	assert.equal(forbidden.node('#manager-message').text(), forbiddenMessage);
	assert.equal(forbidden.node('#manager-retry-action').prop('hidden'), true);
	assert.equal(forbidden.node('#manager-restart-action').prop('hidden'), true);
	forbidden.checkIdle();
}

checkSkipAndResume();
checkPublicationAndResume();
checkServerPublicationReceipt();
checkTerminalStates();
console.log('PASS: recipe queue resumes after failed loads, resets on request, and preserves confirmed publications.');
