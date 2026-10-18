const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { runInNewContext } = require('node:vm');
const source = readFileSync(require('node:path').join(__dirname, '../static/js/advertising.js'), 'utf8');

function element(blockId) {
  const attributes = { 'data-cooklook-yandex': blockId };
  return { id: '', connected: true, getAttribute: name => attributes[name], hasAttribute: name => name in attributes, setAttribute: (name, value) => { attributes[name] = value; } };
}
function setup(blocks = [], { existingLoader = false, ready = false } = {}) {
  let nodes = blocks.map(element);
  const events = {}, renders = [], scripts = existingLoader ? [{ src: 'https://yandex.ru/ads/system/context.js' }] : [];
  const window = {};
  const document = {
    scripts, head: { appendChild: script => scripts.push(script) }, createElement: () => ({}),
    querySelectorAll: () => nodes, getElementById: id => nodes.find(node => node.id === id),
    documentElement: { contains: node => node.connected }
  };
  const manager = { render: args => renders.push({ ...args }) };
  function sdkReady() {
    window.Ya = { Context: { AdvManager: manager } };
    const queue = window.yaContextCb || [];
    window.yaContextCb = { push: callback => callback() };
    queue.forEach(callback => callback());
  }
  if (ready) sdkReady();
  function jQuery(value) { if (typeof value === 'function') value(jQuery); else return { on: (event, callback) => { events[event] = callback; } }; }
  runInNewContext(source, { window, document, jQuery });
  return {
    renders, scripts, sdkReady, window,
    refresh: () => events['cooklook:recipes-updated'](),
    replace: blocks => { nodes.forEach(node => { node.connected = false; }); nodes = blocks.map(element); events['cooklook:recipes-updated'](); }
  };
}

const delayed = setup(['R-A-1-2', 'R-A-1-2', 'R-I-3-4']);
assert.equal(delayed.scripts.length, 1, 'Only one SDK may load for multiple placements');
assert.equal(delayed.scripts[0].async, true);
assert.equal(delayed.renders.length, 0, 'Wait for SDK');
delayed.refresh(); delayed.sdkReady(); delayed.refresh();
assert.equal(delayed.renders.length, 3, 'Repeated catalog events must not render an existing container twice');
assert.equal(new Set(delayed.renders.map(item => item.renderTo)).size, 3, 'Repeated blockId must use distinct containers');
delayed.replace(['R-A-1-2', 'R-A-1-2']);
assert.equal(delayed.renders.length, 5);
assert.equal(new Set(delayed.renders.map(item => item.renderTo)).size, 5, 'AJAX responses must never reuse prior renderTo IDs');
assert.equal(delayed.scripts.length, 1, 'Filter changes must reuse the SDK');

const stale = setup(['R-A-1-2']);
stale.replace(['R-A-5-6']); stale.sdkReady();
assert.deepEqual(stale.renders.map(item => item.blockId), ['R-A-5-6'], 'Detached containers must not render after SDK download');
const legacy = setup(['R-A-1-2'], { existingLoader: true });
assert.equal(legacy.scripts.length, 1, 'HEAD/FOOTER loader must not be duplicated');
legacy.sdkReady(); assert.equal(legacy.renders.length, 1);
const alreadyReady = setup(['R-A-1-2'], { ready: true });
assert.equal(alreadyReady.renders.length, 1); assert.equal(alreadyReady.scripts.length, 0);
const empty = setup();
assert.equal(empty.scripts.length, 0, 'Empty catalogs must not request advertising');
empty.replace(['R-A-1-2']); empty.sdkReady(); assert.equal(empty.renders.length, 1, 'Initially empty catalog must initialize later results');
const invalid = setup(['<script>evil()</script>', 'R-A-1-2"', '']);
assert.equal(invalid.scripts.length, 0); assert.equal(invalid.renders.length, 0);
console.log('PASS: one asynchronous loader, legacy SDK reuse, unique repeated blocks, AJAX replacement, stale containers, empty catalogs and invalid IDs.');
