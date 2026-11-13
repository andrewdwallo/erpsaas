import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import { runInNewContext } from 'node:vm';

const script = readFileSync(new URL('../../resources/js/top-navigation.js', import.meta.url), 'utf8');

function createElement() {
    const classes = new Set();
    const listeners = new Map();

    return {
        classList: {
            add: (name) => classes.add(name),
            remove: (name) => classes.delete(name),
            contains: (name) => classes.has(name),
        },
        addEventListener: (name, callback) => listeners.set(name, callback),
        dispatch: (name) => listeners.get(name)?.(),
    };
}

function createPage(hasTopbar, hasSidebar) {
    const topbar = hasTopbar ? createElement() : null;
    const sidebar = hasSidebar ? createElement() : null;
    const documentListeners = new Map();
    const windowListeners = new Map();
    const window = {
        scrollY: 0,
        addEventListener: (name, callback) => windowListeners.set(name, callback),
    };
    const document = {
        querySelector: (selector) => ({
            '.fi-topbar > nav': topbar,
            '.fi-sidebar-header': sidebar,
        })[selector] ?? null,
        addEventListener: (name, callback) => documentListeners.set(name, callback),
    };

    runInNewContext(script, { document, window });

    return {
        topbar,
        sidebar,
        window,
        initialize: () => documentListeners.get('DOMContentLoaded')(),
        scroll: () => windowListeners.get('scroll')?.(),
    };
}

for (const [name, hasTopbar, hasSidebar] of [
    ['topbar and sidebar', true, true],
    ['topbar only', true, false],
    ['sidebar only', false, true],
    ['neither navigation element', false, false],
]) {
    test(`initializes and handles scrolling with ${name}`, () => {
        const page = createPage(hasTopbar, hasSidebar);
        assert.doesNotThrow(page.initialize);

        for (const scrollY of [0, 100, 0]) {
            page.window.scrollY = scrollY;
            assert.doesNotThrow(page.scroll);

            for (const element of [page.topbar, page.sidebar].filter(Boolean)) {
                assert.equal(element.classList.contains('topbar-scrolled'), scrollY > 0);
            }
        }
    });

    if (hasTopbar || hasSidebar) {
        test(`updates hover styling with ${name}`, () => {
            const page = createPage(hasTopbar, hasSidebar);
            page.initialize();
            const elements = [page.topbar, page.sidebar].filter(Boolean);

            for (const source of elements) {
                source.dispatch('mouseenter');
                for (const element of elements) {
                    assert.equal(element.classList.contains('topbar-hovered'), true);
                }

                source.dispatch('mouseleave');
                for (const element of elements) {
                    assert.equal(element.classList.contains('topbar-hovered'), false);
                }
            }
        });
    }
}
