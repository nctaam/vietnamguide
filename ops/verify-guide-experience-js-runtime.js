'use strict';

const fs = require('fs');
const vm = require('vm');

if (process.argv.length !== 3 || !fs.existsSync(process.argv[2])) {
  process.stderr.write('Usage: node verify-guide-experience-js-runtime.js <guide-experience.js>\n');
  process.exit(2);
}

function assert(condition, message) {
  if (!condition) {
    throw new Error(message);
  }
}

function createClassList() {
  const values = new Set();
  return {
    contains(value) { return values.has(value); },
    toggle(value, active) {
      if (active) values.add(value);
      else values.delete(value);
      return active;
    },
  };
}

function createLink(hash) {
  const attributes = new Map([['href', hash]]);
  return {
    classList: createClassList(),
    getAttribute(name) { return attributes.has(name) ? attributes.get(name) : null; },
    setAttribute(name, value) { attributes.set(name, String(value)); },
    removeAttribute(name) { attributes.delete(name); },
    hasAttribute(name) { return attributes.has(name); },
  };
}

const sections = [{ id: 'first' }, { id: 'second' }];
const links = [createLink('#first'), createLink('#second')];
let observerCallback = null;
let scrollCallback = null;
let guideRect = { top: 0, bottom: 2400, height: 2400 };

function MockIntersectionObserver(callback) {
  observerCallback = callback;
  this.observe = function () {};
}

const guide = {
  querySelectorAll(selector) {
    if (selector.indexOf('.vg-guide-toc') >= 0) return links;
    return [];
  },
  getBoundingClientRect() { return guideRect; },
  style: { setProperty() {} },
};

const windowObject = {
  IntersectionObserver: MockIntersectionObserver,
  innerHeight: 900,
  addEventListener(name, callback) {
    if (name === 'scroll') scrollCallback = callback;
  },
  requestAnimationFrame(callback) { callback(); },
};

const context = {
  Array,
  Boolean,
  Math,
  decodeURIComponent,
  document: {
    querySelector() { return guide; },
    getElementById(id) { return sections.find((section) => section.id === id) || null; },
  },
  IntersectionObserver: MockIntersectionObserver,
  window: windowObject,
};

vm.runInNewContext(fs.readFileSync(process.argv[2], 'utf8'), context, { filename: process.argv[2] });
assert(typeof observerCallback === 'function', 'IntersectionObserver callback was not registered.');

observerCallback([{ isIntersecting: true, boundingClientRect: { top: 100 }, target: sections[0] }]);
assert(links[0].classList.contains('is-active'), 'First guide link was not activated.');
assert(links[0].getAttribute('aria-current') === 'location', 'Active guide link did not receive aria-current="location".');
assert(!links[1].hasAttribute('aria-current'), 'Inactive guide link unexpectedly received aria-current.');

observerCallback([{ isIntersecting: true, boundingClientRect: { top: 120 }, target: sections[1] }]);
assert(!links[0].classList.contains('is-active'), 'Previously active guide link retained is-active.');
assert(!links[0].hasAttribute('aria-current'), 'Previously active guide link retained aria-current.');
assert(links[1].classList.contains('is-active'), 'Second guide link was not activated.');
assert(links[1].getAttribute('aria-current') === 'location', 'Second active guide link did not receive aria-current="location".');

guideRect = { top: -1600, bottom: 850, height: 2450 };
assert(typeof scrollCallback === 'function', 'Scroll callback was not registered.');
scrollCallback();
assert(links[1].getAttribute('aria-current') === 'location', 'Near-end activation lost aria-current synchronization.');

process.stdout.write('Guide JavaScript runtime fixture passed.\n');
