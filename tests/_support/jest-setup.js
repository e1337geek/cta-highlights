/**
 * Jest Setup File
 *
 * This file runs before each test file. Use it to setup global mocks,
 * polyfills, and test utilities.
 *
 * @see https://jestjs.io/docs/configuration#setupfiles-array
 */

// Mock localStorage with enhanced features for testing
class LocalStorageMock {
	constructor() {
		this.store = {};
		this.quotaExceeded = false;
		this.disabled = false;
	}

	clear() {
		if (this.disabled) {
			throw new Error('localStorage is disabled');
		}
		this.store = {};
	}

	getItem(key) {
		if (this.disabled) {
			throw new Error('localStorage is disabled');
		}
		return this.store[key] || null;
	}

	setItem(key, value) {
		if (this.disabled) {
			throw new Error('localStorage is disabled');
		}
		if (this.quotaExceeded) {
			const error = new Error('QuotaExceededError');
			error.name = 'QuotaExceededError';
			throw error;
		}
		this.store[key] = String(value);
	}

	removeItem(key) {
		if (this.disabled) {
			throw new Error('localStorage is disabled');
		}
		delete this.store[key];
	}

	get length() {
		return Object.keys(this.store).length;
	}

	key(index) {
		const keys = Object.keys(this.store);
		return keys[index] || null;
	}

	// Test helpers
	__setQuotaExceeded(value) {
		this.quotaExceeded = value;
	}

	__setDisabled(value) {
		this.disabled = value;
	}

	__reset() {
		// console.log('[LocalStorageMock] __reset() called, clearing store:', Object.keys(this.store));
		this.store = {};
		this.quotaExceeded = false;
		this.disabled = false;
	}
}

// Create shared instances and expose them globally for test access
const localStorageInstance = new LocalStorageMock();
const sessionStorageInstance = new LocalStorageMock();

// Expose instances for direct test access (these won't be reset by Jest)
global.__testLocalStorage = localStorageInstance;
global.__testSessionStorage = sessionStorageInstance;

// Attach to both global and window to ensure compatibility
global.localStorage = localStorageInstance;
global.sessionStorage = sessionStorageInstance;

// JSDOM uses window as primary object, so set it there too
if (typeof window !== 'undefined') {
	window.localStorage = localStorageInstance;
	window.sessionStorage = sessionStorageInstance;
}

// Mock document.cookie with proper attribute support
class CookieStorageMock {
	constructor() {
		this.cookies = {};
	}

	set(cookieString) {
		// Parse cookie string: "name=value;expires=...;path=/;SameSite=Lax"
		const parts = cookieString.split(';').map(part => part.trim());
		const [nameValue] = parts;
		const [name, value] = nameValue.split('=');

		if (!name) return;

		// Extract attributes
		const attributes = {};
		for (let i = 1; i < parts.length; i++) {
			const [key, val] = parts[i].split('=');
			attributes[key.toLowerCase()] = val || true;
		}

		// Store cookie with its attributes
		this.cookies[name] = {
			value: value || '',
			attributes,
			fullString: cookieString
		};
	}

	get() {
		// Return all cookies in the format browsers use
		return Object.entries(this.cookies)
			.map(([name, data]) => {
				// Build the cookie string with attributes
				const parts = [`${name}=${data.value}`];

				if (data.attributes.expires) {
					parts.push(`expires=${data.attributes.expires}`);
				}
				if (data.attributes.path) {
					parts.push(`path=${data.attributes.path}`);
				}
				if (data.attributes.samesite) {
					parts.push(`SameSite=${data.attributes.samesite}`);
				}

				return parts.join(';');
			})
			.join('; ');
	}

	clear() {
		this.cookies = {};
	}
}

const cookieStorage = new CookieStorageMock();

// Override document.cookie getter/setter
Object.defineProperty(document, 'cookie', {
	get() {
		return cookieStorage.get();
	},
	set(value) {
		cookieStorage.set(value);
	},
	configurable: true
});

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

// Mock IntersectionObserver with test helpers
global.IntersectionObserver = class IntersectionObserver {
	constructor(callback, options = {}) {
		this.callback = callback;
		this.options = options;
		this.observedElements = new Set();

		// Store instance for test access
		if (!global.__intersectionObserverInstances) {
			global.__intersectionObserverInstances = [];
		}
		global.__intersectionObserverInstances.push(this);
	}

	observe(element) {
		this.observedElements.add(element);
	}

	unobserve(element) {
		this.observedElements.delete(element);
	}

	disconnect() {
		this.observedElements.clear();
	}

	// Test helper: manually trigger intersection
	__trigger(isIntersecting, element = null) {
		const elementsToTrigger = element
			? [element]
			: Array.from(this.observedElements);

		const entries = elementsToTrigger.map((el) => ({
			target: el,
			isIntersecting,
			intersectionRatio: isIntersecting ? 1.0 : 0,
			boundingClientRect: el.getBoundingClientRect(),
			intersectionRect: isIntersecting
				? el.getBoundingClientRect()
				: { top: 0, bottom: 0, left: 0, right: 0, width: 0, height: 0 },
			rootBounds: null,
			time: Date.now(),
		}));

		this.callback(entries, this);
	}

	// Test helper: reset instances
	static __reset() {
		global.__intersectionObserverInstances = [];
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

// Mock getComputedStyle - must return a CSSStyleDeclaration-like object
Object.defineProperty(window, 'getComputedStyle', {
	writable: true,
	configurable: true,
	value: jest.fn((element) => ({
		getPropertyValue: jest.fn((prop) => {
			if (prop === 'background-color' || prop === 'backgroundColor') {
				return 'rgb(255, 255, 255)';
			}
			return '';
		}),
		backgroundColor: 'rgb(255, 255, 255)',
	}))
});

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
	debug: true,
};

// Helper to reset mocks between tests
global.resetAllMocks = () => {
	jest.clearAllMocks();

	// Reset localStorage - use the shared instance that has the test helpers
	if (global.__testLocalStorage) {
		global.__testLocalStorage.__reset();

		// IMPORTANT: Re-assign to ALL possible references
		// Jest's resetMocks: true might clear these, so we restore them
		global.localStorage = global.__testLocalStorage;
		if (typeof window !== 'undefined') {
			window.localStorage = global.__testLocalStorage;
			// Also define as a property on window to ensure it's accessible
			Object.defineProperty(window, 'localStorage', {
				value: global.__testLocalStorage,
				writable: true,
				configurable: true
			});
		}
	}

	// Reset sessionStorage
	if (global.__testSessionStorage) {
		global.__testSessionStorage.__reset();
		global.sessionStorage = global.__testSessionStorage;
		if (typeof window !== 'undefined') {
			window.sessionStorage = global.__testSessionStorage;
			Object.defineProperty(window, 'sessionStorage', {
				value: global.__testSessionStorage,
				writable: true,
				configurable: true
			});
		}
	}

	// Reset cookies
	cookieStorage.clear();

	// Reset IntersectionObserver instances
	if (global.__intersectionObserverInstances) {
		global.__intersectionObserverInstances = [];
	}

	// Re-establish window.getComputedStyle (always, because resetMocks: true might break it)
	if (typeof window !== 'undefined') {
		Object.defineProperty(window, 'getComputedStyle', {
			writable: true,
			configurable: true,
			value: jest.fn((element) => ({
				getPropertyValue: jest.fn((prop) => {
					if (prop === 'background-color' || prop === 'backgroundColor') {
						return 'rgb(255, 255, 255)';
					}
					return '';
				}),
				backgroundColor: 'rgb(255, 255, 255)',
			}))
		});
	}
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
