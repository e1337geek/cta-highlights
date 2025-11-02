/**
 * CTA Highlights Tests
 *
 * MEDIUM PRIORITY: JavaScript Tests for Core CTA Functionality
 *
 * This test suite covers the cta-highlights.js file which handles:
 * - StorageManager (localStorage/cookie fallback)
 * - CTAHighlight (highlight effect, overlay, focus trap, accessibility)
 * - Cooldown logic
 * - Intersection observer integration
 *
 * @package
 */

const LocalStorageMock = require('./__mocks__/localStorage');
const IntersectionObserverMock = require('./__mocks__/intersectionObserver');
const {
	setupWordPressEnv,
	resetWordPressEnv,
	getAnalyticsCalls,
} = require('./__mocks__/wordpress');

// Import the classes from the JavaScript file
const { StorageManager, CTAHighlight } = require('../../assets/js/cta-highlights.js');

/**
 * Helper to initialize CTAHighlight with config
 */
function initCTAHighlight(config = {}) {
	const defaultConfig = {
		globalCooldown: 3600,
		templateCooldown: 86400,
		overlayColor: 'rgba(0, 0, 0, 0.7)',
		debug: false,
		...config,
	};

	// Set up global config
	global.ctaHighlightsConfig = defaultConfig;
	window.ctaHighlightsConfig = defaultConfig;

	// Create and initialize
	const manager = new CTAHighlight(defaultConfig);
	manager.init();

	return manager;
}

describe('CTA Highlights - Storage Manager', () => {
	beforeEach(() => {
		// Reset mocks
		global.resetAllMocks();
		setupWordPressEnv();
	});

	afterEach(() => {
		global.resetAllMocks();
		resetWordPressEnv();
	});

	// =============================================================
	// COOKIE OPERATIONS
	// =============================================================

	describe('Cookie Operations', () => {
		test('sets a cookie with expiry', () => {
			// Execute the script to get StorageManager
			

			const manager = new StorageManager();
			manager.setCookie('test_key', 'test_value', 3600);

			expect(document.cookie).toContain('test_key=test_value');
			expect(document.cookie).toContain('path=/');
			expect(document.cookie).toContain('SameSite=Lax');
		});

		test('gets a cookie value', () => {
			
			const manager = new StorageManager();

			// Set cookie manually
			document.cookie = 'test_key=test_value;path=/';

			const value = manager.getCookie('test_key');
			expect(value).toBe('test_value');
		});

		test('returns null for non-existent cookie', () => {
			
			const manager = new StorageManager();

			const value = manager.getCookie('nonexistent');
			expect(value).toBeNull();
		});

		test('removes a cookie', () => {
			
			const manager = new StorageManager();

			document.cookie = 'test_key=test_value;path=/';
			manager.removeCookie('test_key');

			expect(document.cookie).toContain('Thu, 01 Jan 1970');
		});
	});

	// =============================================================
	// LOCALSTORAGE OPERATIONS
	// =============================================================

	describe('localStorage Operations', () => {
		test('sets cooldown in localStorage', () => {
			
			const manager = new StorageManager();

			manager.set('cta_highlights_global', 3600);

			expect(
				localStorage.getItem('cta_highlights_global')
			).not.toBeNull();

			const data = JSON.parse(
				localStorage.getItem('cta_highlights_global')
			);
			expect(data).toHaveProperty('timestamp');
			expect(data).toHaveProperty('expiryTime');
		});

		test('falls back to cookies when localStorage fails', () => {
			global.__testLocalStorage.__setDisabled(true);


			const manager = new StorageManager();

			manager.set('cta_highlights_global', 3600);

			// Should be in cookies instead
			expect(document.cookie).toContain('cta_highlights_global');
		});

		test('checks if cooldown is active', () => {
			
			const manager = new StorageManager();

			// Set a cooldown that expires in the future
			const futureTime = Date.now() + 10000;
			const data = JSON.stringify({
				timestamp: Date.now(),
				expiryTime: futureTime,
			});
			localStorage.setItem('test_cooldown', data);

			expect(manager.isCooldownActive('test_cooldown')).toBe(true);
		});

		test('returns false for expired cooldown', () => {
			
			const manager = new StorageManager();

			// Set a cooldown that already expired
			const pastTime = Date.now() - 10000;
			const data = JSON.stringify({
				timestamp: Date.now() - 20000,
				expiryTime: pastTime,
			});
			localStorage.setItem('test_cooldown', data);

			expect(manager.isCooldownActive('test_cooldown')).toBe(false);
		});

		test('cleans up expired cooldowns', () => {
			
			const manager = new StorageManager();

			// Set expired cooldown
			const pastTime = Date.now() - 10000;
			const data = JSON.stringify({
				timestamp: Date.now() - 20000,
				expiryTime: pastTime,
			});
			localStorage.setItem('test_cooldown', data);

			manager.isCooldownActive('test_cooldown');

			// Should be removed
			expect(localStorage.getItem('test_cooldown')).toBeNull();
		});

		test('handles corrupted cooldown data', () => {
			
			const manager = new StorageManager();

			localStorage.setItem('test_cooldown', 'invalid json');

			expect(manager.isCooldownActive('test_cooldown')).toBe(false);
			expect(localStorage.getItem('test_cooldown')).toBeNull();
		});

		test('removes from both storages', () => {
			
			const manager = new StorageManager();

			localStorage.setItem('test_key', 'value');
			document.cookie = 'test_key=value;path=/';

			manager.removeFromBothStorages('test_key');

			expect(localStorage.getItem('test_key')).toBeNull();
		});
	});

	// =============================================================
	// FALLBACK BEHAVIOR
	// =============================================================

	describe('Storage Fallback Behavior', () => {
		test('falls back to cookies when localStorage quota exceeded', () => {
			global.__testLocalStorage.__setQuotaExceeded(true);


			const manager = new StorageManager();

			manager.set('cta_highlights_global', 3600);

			// Should be in cookies
			expect(document.cookie).toContain('cta_highlights_global');
		});

		test('reads from cookies when not in localStorage', () => {
			
			const manager = new StorageManager();

			// Only set in cookie
			const data = JSON.stringify({
				timestamp: Date.now(),
				expiryTime: Date.now() + 10000,
			});
			document.cookie = `test_cooldown=${data};path=/`;

			expect(manager.isCooldownActive('test_cooldown')).toBe(true);
		});
	});
});

describe('CTA Highlights - CTAHighlight Class', () => {
	beforeEach(() => {
		// Reset mocks
		global.resetAllMocks();

		// Setup DOM
		document.body.innerHTML = '';

		setupWordPressEnv({ debug: false });
	});

	afterEach(() => {
		global.resetAllMocks();
		resetWordPressEnv();
		document.body.innerHTML = '';
	});

	// =============================================================
	// INITIALIZATION
	// =============================================================

	describe('Initialization', () => {
		test('initializes when highlight CTAs exist', () => {
			document.body.innerHTML =
				'<div class="cta-highlights-wrapper" data-highlight="true"></div>';

			initCTAHighlight();

			expect(
				document.querySelector('.cta-highlights-overlay')
			).not.toBeNull();
			expect(
				document.querySelector('.cta-highlights-close')
			).not.toBeNull();
		});

		test('does not initialize when no highlight CTAs', () => {
			document.body.innerHTML =
				'<div class="cta-highlights-wrapper" data-highlight="false"></div>';

			initCTAHighlight();

			expect(
				document.querySelector('.cta-highlights-overlay')
			).toBeNull();
		});

		test('does not initialize when global cooldown active', () => {
			const futureTime = Date.now() + 10000;
			const data = JSON.stringify({
				timestamp: Date.now(),
				expiryTime: futureTime,
			});
			localStorage.setItem('cta_highlights_global', data);

			document.body.innerHTML =
				'<div class="cta-highlights-wrapper" data-highlight="true"></div>';

			initCTAHighlight();

			expect(
				document.querySelector('.cta-highlights-overlay')
			).toBeNull();
		});
	});

	// =============================================================
	// OVERLAY AND CLOSE BUTTON
	// =============================================================

	describe('Overlay and Close Button Creation', () => {
		test('creates overlay with correct attributes', () => {
			document.body.innerHTML =
				'<div class="cta-highlights-wrapper" data-highlight="true"></div>';

			initCTAHighlight();

			const overlay = document.querySelector('.cta-highlights-overlay');
			expect(overlay).not.toBeNull();
			expect(overlay.getAttribute('aria-hidden')).toBe('true');
			expect(overlay.getAttribute('role')).toBe('presentation');
		});

		test('applies custom overlay color from config', () => {
			setupWordPressEnv({ overlayColor: 'rgba(255, 0, 0, 0.5)' });

			document.body.innerHTML =
				'<div class="cta-highlights-wrapper" data-highlight="true"></div>';

			initCTAHighlight({ overlayColor: 'rgba(255, 0, 0, 0.5)' });

			const overlay = document.querySelector('.cta-highlights-overlay');
			expect(overlay.style.backgroundColor).toBe('rgba(255, 0, 0, 0.5)');
		});

		test('creates close button with correct attributes', () => {
			document.body.innerHTML =
				'<div class="cta-highlights-wrapper" data-highlight="true"></div>';

			initCTAHighlight();

			const closeBtn = document.querySelector('.cta-highlights-close');
			expect(closeBtn).not.toBeNull();
			expect(closeBtn.getAttribute('aria-label')).toBe('Close highlight');
			expect(closeBtn.getAttribute('type')).toBe('button');
			// JSDOM decodes HTML entities, so &times; becomes ×
			expect(closeBtn.innerHTML).toBe('×');
		});
	});

	// =============================================================
	// INTERSECTION OBSERVER
	// =============================================================

	describe('Intersection Observer', () => {
		test('creates intersection observer for CTA', () => {
			document.body.innerHTML =
				'<div class="cta-highlights-wrapper" data-highlight="true" data-template="default"></div>';

			initCTAHighlight();

			expect(
				global.__intersectionObserverInstances.length
			).toBeGreaterThan(0);
		});

		test('does not observe CTA when template cooldown active', () => {
			const futureTime = Date.now() + 10000;
			const data = JSON.stringify({
				timestamp: Date.now(),
				expiryTime: futureTime,
			});
			localStorage.setItem('cta_highlights_template_default', data);

			document.body.innerHTML =
				'<div class="cta-highlights-wrapper" data-highlight="true" data-template="default"></div>';

			initCTAHighlight();

			// Should not create observer
			expect(global.__intersectionObserverInstances.length).toBe(0);
		});
	});

	// =============================================================
	// COOLDOWN LOGIC
	// =============================================================

	describe('Cooldown Logic', () => {
		test('sets global cooldown on activation', () => {
			setupWordPressEnv({ globalCooldown: 7200 });

			document.body.innerHTML = `
				<div class="cta-highlights-wrapper" data-highlight="true" data-template="default" data-duration="5">
					<p>Test CTA</p>
				</div>
			`;

			initCTAHighlight({ globalCooldown: 7200 });

			// Trigger intersection
			const observer = global.__intersectionObserverInstances[0];
			const cta = document.querySelector('.cta-highlights-wrapper');

			// Mock getBoundingClientRect
			cta.getBoundingClientRect = jest.fn(() => ({
				top: 0,
				bottom: 100,
				left: 0,
				right: 100,
				width: 100,
				height: 100,
			}));

			observer.__trigger(true, cta);

			expect(
				localStorage.getItem('cta_highlights_global')
			).not.toBeNull();
		});

		test('sets template cooldown on activation', () => {
			setupWordPressEnv({ templateCooldown: 86400 });

			document.body.innerHTML = `
				<div class="cta-highlights-wrapper" data-highlight="true" data-template="banner" data-duration="5">
					<p>Test CTA</p>
				</div>
			`;

			initCTAHighlight();

			const observer = global.__intersectionObserverInstances[0];
			const cta = document.querySelector('.cta-highlights-wrapper');

			cta.getBoundingClientRect = jest.fn(() => ({
				top: 0,
				bottom: 100,
				height: 100,
			}));

			observer.__trigger(true, cta);

			expect(
				localStorage.getItem('cta_highlights_template_banner')
			).not.toBeNull();
		});
	});

	// =============================================================
	// FOCUS TRAP
	// =============================================================

	describe('Focus Trap', () => {
		test('traps focus within CTA', () => {
			document.body.innerHTML = `
				<div class="cta-highlights-wrapper" data-highlight="true" data-template="default" data-duration="5">
					<a href="#" id="link1">Link 1</a>
					<a href="#" id="link2">Link 2</a>
				</div>
			`;

			initCTAHighlight();

			const observer = global.__intersectionObserverInstances[0];
			const cta = document.querySelector('.cta-highlights-wrapper');

			cta.getBoundingClientRect = jest.fn(() => ({
				top: 0,
				bottom: 100,
				height: 100,
			}));

			observer.__trigger(true, cta);

			// First link should be focused
			expect(document.activeElement.id).toBe('link1');
		});
	});

	// =============================================================
	// ACCESSIBILITY
	// =============================================================

	describe('Accessibility', () => {
		test('sets aria-modal on activation', () => {
			document.body.innerHTML = `
				<div class="cta-highlights-wrapper" data-highlight="true" data-template="default" data-duration="5">
					<p>Test</p>
				</div>
			`;

			initCTAHighlight();

			const observer = global.__intersectionObserverInstances[0];
			const cta = document.querySelector('.cta-highlights-wrapper');

			cta.getBoundingClientRect = jest.fn(() => ({
				top: 0,
				bottom: 100,
				height: 100,
			}));

			observer.__trigger(true, cta);

			expect(cta.getAttribute('aria-modal')).toBe('true');
		});

		test('announces to screen readers on activation', () => {
			document.body.innerHTML = `
				<div class="cta-highlights-wrapper" data-highlight="true" data-template="default" data-duration="5">
					<p>Test</p>
				</div>
			`;

			initCTAHighlight();

			const observer = global.__intersectionObserverInstances[0];
			const cta = document.querySelector('.cta-highlights-wrapper');

			cta.getBoundingClientRect = jest.fn(() => ({
				top: 0,
				bottom: 100,
				height: 100,
			}));

			observer.__trigger(true, cta);

			// Check for screen reader announcement element
			const announcement = document.querySelector(
				'.cta-highlights-sr-only'
			);
			expect(announcement).not.toBeNull();
			expect(announcement.getAttribute('aria-live')).toBe('polite');
		});
	});
});

describe('CTA Highlights - Integration', () => {
	beforeEach(() => {
		// Reset mocks
		global.resetAllMocks();
		document.body.innerHTML = '';
		setupWordPressEnv();
	});

	afterEach(() => {
		global.resetAllMocks();
		resetWordPressEnv();
	});

	test('complete activation and dismissal flow', () => {
		document.body.innerHTML = `
			<div class="cta-highlights-wrapper" data-highlight="true" data-template="default" data-duration="5">
				<p>Test CTA</p>
				<a href="#">Click</a>
			</div>
		`;

		initCTAHighlight();

		const overlay = document.querySelector('.cta-highlights-overlay');
		const closeBtn = document.querySelector('.cta-highlights-close');
		const cta = document.querySelector('.cta-highlights-wrapper');

		cta.getBoundingClientRect = jest.fn(() => ({
			top: 0,
			bottom: 100,
			height: 100,
		}));

		// Activate
		const observer = global.__intersectionObserverInstances[0];
		observer.__trigger(true, cta);

		expect(overlay.classList.contains('active')).toBe(true);
		expect(closeBtn.classList.contains('active')).toBe(true);
		expect(cta.classList.contains('cta-highlights-active')).toBe(true);

		// Dismiss via close button
		closeBtn.click();

		expect(overlay.classList.contains('active')).toBe(false);
		expect(closeBtn.classList.contains('active')).toBe(false);
		expect(cta.classList.contains('cta-highlights-active')).toBe(false);
	});
});
