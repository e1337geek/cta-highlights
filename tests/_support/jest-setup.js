/**
 * Jest Setup File
 *
 * This file runs before each test file. Use it to setup global mocks,
 * polyfills, and test utilities.
 *
 * @see https://jestjs.io/docs/configuration#setupfiles-array
 */

// Mock WordPress globals
global.wp = {
	hooks: {
		addAction: jest.fn(),
		addFilter: jest.fn(),
		doAction: jest.fn(),
		applyFilters: jest.fn(),
	},
};

global.jQuery = jest.fn(() => ({
	on: jest.fn(),
	off: jest.fn(),
	trigger: jest.fn(),
	find: jest.fn(() => ({
		length: 0,
	})),
}));

global.$ = global.jQuery;

// Mock IntersectionObserver
global.IntersectionObserver = class IntersectionObserver {
	constructor(callback, options) {
		this.callback = callback;
		this.options = options;
	}

	observe() {
		// Mock observe
	}

	unobserve() {
		// Mock unobserve
	}

	disconnect() {
		// Mock disconnect
	}
};

// Mock MutationObserver
global.MutationObserver = class MutationObserver {
	constructor(callback) {
		this.callback = callback;
	}

	observe() {
		// Mock observe
	}

	disconnect() {
		// Mock disconnect
	}
};

// Mock requestAnimationFrame
global.requestAnimationFrame = (callback) => {
	return setTimeout(callback, 0);
};

global.cancelAnimationFrame = (id) => {
	clearTimeout(id);
};

// Mock matchMedia
Object.defineProperty(window, 'matchMedia', {
	writable: true,
	value: jest.fn().mockImplementation((query) => ({
		matches: false,
		media: query,
		onchange: null,
		addListener: jest.fn(),
		removeListener: jest.fn(),
		addEventListener: jest.fn(),
		removeEventListener: jest.fn(),
		dispatchEvent: jest.fn(),
	})),
});

// Mock scrollTo
window.scrollTo = jest.fn();

// Mock getComputedStyle
window.getComputedStyle = jest.fn(() => ({
	getPropertyValue: jest.fn(() => ''),
}));

// Suppress console errors in tests (optional)
// Uncomment if you want cleaner test output
// global.console = {
//   ...console,
//   error: jest.fn(),
//   warn: jest.fn(),
// };

// Setup ctaHighlightsConfig (simulating wp_localize_script)
global.ctaHighlightsConfig = {
	globalCooldown: 3600,
	templateCooldown: 86400,
	overlayColor: 'rgba(0, 0, 0, 0.7)',
	debug: false,
};

// Setup ctaAutoInsertData (for auto-insertion tests)
global.ctaAutoInsertData = {
	fallbackChain: [],
	contentSelector: '.entry-content',
	debug: false,
};

// Helper to reset mocks between tests
global.resetAllMocks = () => {
	jest.clearAllMocks();
	localStorage.clear();
	sessionStorage.clear();
};

// Helper to create mock DOM elements
global.createMockElement = (tag, attributes = {}, children = []) => {
	const element = document.createElement(tag);

	Object.entries(attributes).forEach(([key, value]) => {
		if (key === 'className') {
			element.className = value;
		} else if (key === 'innerHTML') {
			element.innerHTML = value;
		} else {
			element.setAttribute(key, value);
		}
	});

	children.forEach((child) => {
		if (typeof child === 'string') {
			element.appendChild(document.createTextNode(child));
		} else {
			element.appendChild(child);
		}
	});

	return element;
};

// Helper to wait for async operations
global.waitFor = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

// Helper to trigger DOM events
global.triggerEvent = (element, eventType, detail = {}) => {
	const event = new CustomEvent(eventType, { detail, bubbles: true });
	element.dispatchEvent(event);
};
