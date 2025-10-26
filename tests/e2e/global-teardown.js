/**
 * Global Teardown for Playwright E2E Tests
 *
 * This file runs once after all tests to clean up:
 * 1. Remove temporary files
 * 2. Clean up test data (optional)
 * 3. Log test completion
 *
 * @see https://playwright.dev/docs/test-global-setup-teardown
 */

const fs = require('fs');
const path = require('path');

/**
 * Global teardown function
 *
 * @param {import('@playwright/test').FullConfig} config - Playwright config
 */
async function globalTeardown(config) {
	console.log('🧹 Global Teardown: Cleaning up...');

	// Optional: Remove auth state file
	// const authFile = path.join(__dirname, '.auth', 'admin.json');
	// if (fs.existsSync(authFile)) {
	// 	fs.unlinkSync(authFile);
	// 	console.log('✅ Removed authentication state file');
	// }

	// Optional: Clean up test screenshots/videos older than 7 days
	// This helps prevent accumulation of test artifacts
	// (Implementation would go here)

	console.log('✅ Global Teardown complete');
}

module.exports = globalTeardown;
