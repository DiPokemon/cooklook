const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

// Run the real form controller; substitute only the browser and AJAX boundaries.
function submitComment(response, currentUrl) {
	const elements = new Map();
	const navigation = [];
	let submit, complete;
	function $(selector) {
		if (typeof selector === 'function') { selector($); return; }
		if (!elements.has(selector)) elements.set(selector, { props: {}, value: 'New comment' });
		const element = elements.get(selector);
		return {
			length: 1,
			on(event, callback) { submit = callback; return this; },
			prop(key, value) { element.props[key] = value; return this; },
			text(value) { element.text = value; return this; },
			val(value) { element.value = value; return this; },
			find: $,
			serializeArray() { return [{ name: 'comment', value: 'New comment' }]; },
			trigger() { return this; }
		};
	}
	$.ajax = () => ({
		done(callback) { complete = callback; return this; },
		fail() { return this; },
		always() { return this; }
	});
	const location = {
		href: currentUrl,
		assign(url) { navigation.push(['navigate', url]); this.href = url; },
		reload() { navigation.push(['reload']); }
	};
	vm.runInNewContext(fs.readFileSync(require.resolve('../static/js/ajax-comment.js'), 'utf8'), {
		jQuery: $, URL, window: { location },
		ajax_comment_params: { ajaxurl: '/ajax', nonce: 'test' }
	});
	submit.call({ reportValidity: () => true }, { preventDefault() {} });
	complete(response);
	return { navigation, elements };
}

const current = 'http://cooklook/recipe/example/';
for (const hash of ['', '#comment-1', '#comment-2']) {
	const result = submitComment({ success: true, data: { approved: true, url: current + '#comment-2' } }, current + hash);
	assert.deepEqual(result.navigation, [['navigate', current + '#comment-2'], ['reload']], 'A same-page comment needs fresh server HTML, even when its hash changes');
}

const paginated = current + 'comment-page-2/#comment-2';
const nextPage = submitComment({ success: true, data: { approved: true, url: paginated } }, current);
assert.deepEqual(nextPage.navigation, [['navigate', paginated]], 'A paginated comment must navigate without reloading the old page');

const pending = submitComment({ success: true, data: { approved: false, message: 'На модерации.' } }, current);
assert.deepEqual(pending.navigation, []);
assert.equal(pending.elements.get('#comment').value, '');
assert.equal(pending.elements.get('#comment-success-message').text, 'На модерации.');
assert.equal(pending.elements.get('#comment-success-message').props.hidden, false);

console.log('PASS: approved comments refresh same-page HTML, follow pagination, and keep moderation responses on the form.');
