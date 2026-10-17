/* Test our ingredient controller, leaving HTTP and Select2 rendering to browser checks. */
'use strict';
const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const fields = [
  { options: [], values: [], events: {}, input: { value: '' } },
  { options: [], values: [], events: {}, input: { value: '' } }
];
const popular = { events: {}, slug: 'milk', text: ' Молоко & сливки ' };
const clear = { events: {} }, other = { events: {} };
let settings;
class Collection {
  constructor(items) { this.items = items; this.length = items.length; }
  select2(value) { if (typeof value === 'object') settings = value; return this; }
  each(callback) { this.items.forEach(item => callback.call(item)); return this; }
  on(event, callback) { this.items.forEach(item => { item.events[event] = callback; }); return this; }
  val(value) {
    if (value === undefined) return this.items[0].values ?? this.items[0].value;
    this.items.forEach(item => { item.values = value.filter(v => item.options.some(o => o.value === v)); }); return this;
  }
  next() { return new Collection(this.items.map(item => item.input)); }
  find(selector) { return selector === '.select2-search__field' ? this : new Collection(this.items.flatMap(item => item.options)); }
  filter(callback) { return new Collection(this.items.filter(item => callback.call(item))); }
  append(option) { this.items[0].options.push(option); return this; }
  data() { return this.items[0].slug; }
  text() { return this.items[0].text; }
  trigger() { return this; }
}
function $(target) {
  if (typeof target === 'function') return target($);
  if (target === '#include_ingredients, #exclude_ingredients') return new Collection(fields);
  if (target === '#include_ingredients') return new Collection([fields[0]]);
  if (target === '.popular_ingridients-item') return new Collection([popular]);
  if (target === '.clear_ingridients') return new Collection([clear]);
  return new Collection([typeof target === 'object' ? target : other]);
}
const config = { ajax_url: '/wp-admin/admin-ajax.php', nonce: 'catalog-nonce' };
const requests = [];
$.ajax = options => {
  const request = { options, status: 200,
    done(callback) { this.resolve = callback; return this; },
    fail(callback) { this.reject = callback; return this; },
    abort() { this.status = 0; this.reject(); }
  };
  requests.push(request); return request;
};
const context = { jQuery: $, cooklookIngredients: config, document: { body: { events: {} }, events: {} },
  Option: function (text, value) { this.text = text; this.value = value; } };
vm.runInNewContext(fs.readFileSync('static/js/filter-ingridients.js', 'utf8'), context);
assert.equal(settings.minimumInputLength, 2);
assert.equal(settings.maximumSelectionLength, 20);
assert.equal(settings.ajax.delay, 300);
assert.equal(settings.ajax.timeout, 15000);
assert.equal(settings.ajax.data({ term: 'мол', page: 3 }).ingredient_page, 3);
assert.equal(settings.ajax.data({ term: 'мол', page: 3 }).page, undefined);
assert.equal(settings.ajax.data({ term: 'мол' }).nonce, config.nonce);
assert.equal(settings.ajax.data({ term: 'мол' }).term, 'мол');
const result = { results: [{ id: 'milk', text: '<img src=x>' }], pagination: { more: true } };
assert.equal(settings.ajax.processResults({ success: true, data: result }), result);
const click = item => item.events.click.call(item, { preventDefault() {} });
click(popular);
assert.deepEqual(fields[0].values, ['milk']);
assert.equal(fields[0].options[0].text, 'Молоко & сливки');
click(popular);
assert.equal(fields[0].options.length, 1, 'Repeated chip duplicated option');
click(clear);
assert.equal(fields[0].values.length, 0);
click(popular);
assert.deepEqual(Array.from(fields[0].values), ['milk'], 'Chip cannot reselect a cleared option');
assert.equal(fields[0].options.length, 1);
fields[1].options = [{ value: 'nuts', text: 'Орехи' }]; fields[1].values = ['nuts'];
click(clear);
assert.equal(fields[1].values.length, 0, 'Clear left exclusions selected');
fields[0].options = Array.from({ length: 20 }, (_, i) => ({ value: `item-${i}` }));
fields[0].values = fields[0].options.map(o => o.value);
click(popular);
assert.equal(fields[0].values.length, 20, 'Popular chip bypassed server selection limit');
assert.equal(fields[0].options.length, 20);
let successes = 0, failures = 0;
const options = { data: { term: 'мол' } };
const success = () => successes++, failure = () => failures++;
fields[1].input.value = 'м';
settings.ajax.transport(options, success, failure).abort();
assert.equal(requests.length, 0, 'Delayed obsolete search still reached the server');
fields[1].input.value = 'мол';
settings.ajax.transport(options, success, failure);
fields[1].input.value = '';
requests[0].resolve(result); requests[0].reject();
assert.equal(successes + failures, 0, 'Old result/error replaced the short-input prompt');
fields[1].input.value = 'мол';
const active = settings.ajax.transport(options, success, failure);
assert.equal(active, requests[1], 'Select2 cannot cancel the returned request');
requests[1].resolve(result); requests[1].reject();
assert.equal(successes, 1); assert.equal(failures, 1);
console.log('PASS: remote lookup, popular choices, clearing/limits, obsolete delayed requests and stale responses.');
