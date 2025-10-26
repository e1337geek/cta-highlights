/**
 * WordPress Globals Mock
 *
 * Provides mock WordPress global variables for testing
 * Includes ctaHighlightsConfig, analytics functions, etc.
 */

/**
 * Create default ctaHighlightsConfig
 */
function createCtaHighlightsConfig(overrides = {}) {
	return {
		globalCooldown: 3600, // 1 hour
		templateCooldown: 86400, // 24 hours
		overlayColor: 'rgba(0, 0, 0, 0.7)',
		debug: false,
		...overrides
	};
}

/**
 * Mock Google Analytics gtag function
 */
function gtagMock() {
	if (!global.__gtagCalls) {
		global.__gtagCalls = [];
	}
	global.__gtagCalls.push(Array.from(arguments));
}

/**
 * Mock Google Analytics Universal ga function
 */
function gaMock() {
	if (!global.__gaCalls) {
		global.__gaCalls = [];
	}
	global.__gaCalls.push(Array.from(arguments));
}

/**
 * Setup WordPress environment for tests
 */
function setupWordPressEnv(config = {}) {
	// Setup config
	global.window = global.window || {};
	global.window.ctaHighlightsConfig = createCtaHighlightsConfig(config);

	// Setup analytics mocks
	global.gtag = gtagMock;
	global.ga = gaMock;

	// Reset analytics call tracking
	global.__gtagCalls = [];
	global.__gaCalls = [];
}

/**
 * Reset WordPress environment
 */
function resetWordPressEnv() {
	if (global.window) {
		delete global.window.ctaHighlightsConfig;
		delete global.window.ctaHighlightsManager;
		delete global.window.ctaAutoInsertManager;
	}
	delete global.gtag;
	delete global.ga;
	delete global.__gtagCalls;
	delete global.__gaCalls;
}

/**
 * Get tracked analytics calls
 */
function getAnalyticsCalls() {
	return {
		gtag: global.__gtagCalls || [],
		ga: global.__gaCalls || []
	};
}

module.exports = {
	createCtaHighlightsConfig,
	setupWordPressEnv,
	resetWordPressEnv,
	getAnalyticsCalls,
	gtagMock,
	gaMock
};
