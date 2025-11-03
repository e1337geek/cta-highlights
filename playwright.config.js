/**
 * Playwright Configuration for CTA Highlights Plugin
 *
 * @see https://playwright.dev/docs/test-configuration
 */

const { defineConfig, devices } = require('@playwright/test');

/**
 * Read environment variables from file.
 * https://github.com/motdotla/dotenv
 */
// require('dotenv').config();

module.exports = defineConfig({
	// Test directory
	testDir: './tests/e2e',

	// Global setup
	globalSetup: require.resolve('./tests/_support/playwright-setup.js'),

	// Maximum time one test can run
	timeout: 60 * 1000,

	// Test timeout for each assertion
	expect: {
		timeout: 10000,
	},

	// Run tests in files in parallel
	fullyParallel: false,

	// Fail the build on CI if you accidentally left test.only in the source code
	forbidOnly: !!process.env.CI,

	// Retry on CI only
	retries: process.env.CI ? 2 : 0,

	// Opt out of parallel tests on CI
	workers: process.env.CI ? 1 : undefined,

	// Reporter to use
	reporter: [
		['html', { outputFolder: 'tests/_output/playwright-report' }],
		['json', { outputFile: 'tests/_output/test-results.json' }],
		['junit', { outputFile: 'tests/_output/junit.xml' }],
		['list'],
	],

	// Shared settings for all the projects below
	use: {
		// Base URL for tests
		baseURL: process.env.WP_BASE_URL || 'http://localhost:8888',

		// Load authentication state from setup
		storageState: 'tests/_output/.auth/admin.json',

		// Collect trace when retrying the failed test
		trace: 'on-first-retry',

		// Screenshot on failure
		screenshot: 'only-on-failure',

		// Video on failure
		video: 'retain-on-failure',

		// Maximum time for navigation
		navigationTimeout: 30000,

		// Maximum time for action
		actionTimeout: 10000,

		// Viewport
		viewport: { width: 1280, height: 720 },

		// Ignore HTTPS errors
		ignoreHTTPSErrors: true,

		// Locale
		locale: 'en-US',

		// Timezone
		timezoneId: 'America/New_York',
	},

	// Configure projects for major browsers
	projects: [
		{
			name: 'chromium',
			use: {
				...devices['Desktop Chrome'],
				// Use Chrome-specific settings
				launchOptions: {
					args: ['--disable-dev-shm-usage'],
				},
			},
		},

		{
			name: 'firefox',
			use: { ...devices['Desktop Firefox'] },
		},

		{
			name: 'webkit',
			use: { ...devices['Desktop Safari'] },
		},

		// Mobile viewports
		{
			name: 'mobile-chrome',
			use: { ...devices['Pixel 5'] },
		},

		{
			name: 'mobile-safari',
			use: { ...devices['iPhone 13'] },
		},

		// Tablet viewports
		{
			name: 'tablet',
			use: { ...devices['iPad Pro'] },
		},
	],

	// Web server configuration
	webServer: process.env.CI ? undefined : {
		command: 'npm run env:start',
		url: 'http://localhost:8888',
		timeout: 120 * 1000,
		reuseExistingServer: !process.env.CI,
	},

	// Output directory for test artifacts
	outputDir: 'tests/_output/test-artifacts',

	// Folder for test artifacts such as screenshots, videos, traces, etc.
	snapshotDir: 'tests/e2e/__snapshots__',
});
