/**
 * Global Setup for Playwright E2E Tests
 *
 * This file runs once before all tests to:
 * 1. Authenticate as WordPress admin
 * 2. Save authentication state for reuse across tests
 * 3. Set up test environment
 *
 * @see https://playwright.dev/docs/test-global-setup-teardown
 */

const { chromium } = require('@playwright/test');
const path = require('path');
const fs = require('fs');

/**
 * WordPress credentials
 */
const WP_BASE_URL = process.env.WP_BASE_URL || 'http://localhost:8888';
const WP_ADMIN_USER = process.env.WP_ADMIN_USER || 'admin';
const WP_ADMIN_PASSWORD = process.env.WP_ADMIN_PASSWORD || 'password';

/**
 * Auth state file path
 */
const AUTH_FILE = path.join(__dirname, '.auth', 'admin.json');

/**
 * Global setup function
 *
 * @param {import('@playwright/test').FullConfig} config - Playwright config
 */
async function globalSetup(config) {
	console.log('🚀 Global Setup: Authenticating as WordPress admin...');

	// Ensure .auth directory exists
	const authDir = path.dirname(AUTH_FILE);
	if (!fs.existsSync(authDir)) {
		fs.mkdirSync(authDir, { recursive: true });
	}

	// Launch browser
	const browser = await chromium.launch();
	const page = await browser.newPage();

	try {
		// Navigate to WordPress login
		await page.goto(`${WP_BASE_URL}/wp-login.php`);

		// Fill in credentials
		await page.fill('#user_login', WP_ADMIN_USER);
		await page.fill('#user_pass', WP_ADMIN_PASSWORD);

		// Submit login form
		await page.click('#wp-submit');

		// Wait for navigation to admin dashboard
		await page.waitForURL(/wp-admin/, { timeout: 30000 });

		// Verify we're logged in by checking for admin bar
		const adminBar = await page.locator('#wpadminbar').count();
		if (adminBar === 0) {
			throw new Error('Login failed: Admin bar not found');
		}

		// Save authentication state
		await page.context().storageState({ path: AUTH_FILE });

		console.log('✅ Authentication successful. State saved to:', AUTH_FILE);
	} catch (error) {
		console.error('❌ Authentication failed:', error.message);
		throw error;
	} finally {
		await browser.close();
	}

	console.log('✅ Global Setup complete');
}

module.exports = globalSetup;
