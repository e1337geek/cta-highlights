/**
 * Playwright Global Setup
 *
 * This file runs once before all Playwright tests. Use it to setup the
 * WordPress environment, create test users, and prepare test data.
 *
 * @see https://playwright.dev/docs/test-global-setup-teardown
 */

const { chromium } = require('@playwright/test');
const path = require('path');
const fs = require('fs');

module.exports = async (config) => {
	console.log('Setting up Playwright test environment...');

	const baseURL = config.use?.baseURL || 'http://localhost:8888';

	// Launch browser for setup
	const browser = await chromium.launch();
	const context = await browser.newContext();
	const page = await context.newPage();

	try {
		// Check if WordPress is running
		const response = await page.goto(baseURL, { waitUntil: 'domcontentloaded' });

		if (!response || !response.ok()) {
			throw new Error(
				`WordPress is not running at ${baseURL}. Run 'npm run env:start' first.`
			);
		}

		console.log('✓ WordPress is running');

		// Login as admin
		await loginAsAdmin(page, baseURL);
		console.log('✓ Logged in as admin');

		// Activate plugin if not already active
		await activatePlugin(page, baseURL);
		console.log('✓ Plugin activated');

		// Create test data
		await createTestData(page, baseURL);
		console.log('✓ Test data created');

		// Save authentication state for reuse
		await saveAuthState(context);
		console.log('✓ Authentication state saved');

		console.log('Playwright setup complete!\n');
	} catch (error) {
		console.error('Setup failed:', error);
		throw error;
	} finally {
		await browser.close();
	}
};

/**
 * Login as WordPress admin
 */
async function loginAsAdmin(page, baseURL) {
	await page.goto(`${baseURL}/wp-login.php`);

	// Check if already logged in
	const isLoggedIn = await page.locator('#wpadminbar').count() > 0;

	if (isLoggedIn) {
		return;
	}

	// Login
	await page.fill('#user_login', 'admin');
	await page.fill('#user_pass', 'password');
	await page.click('#wp-submit');

	// Wait for redirect to dashboard
	await page.waitForURL('**/wp-admin/**');
}

/**
 * Activate the CTA Highlights plugin
 */
async function activatePlugin(page, baseURL) {
	await page.goto(`${baseURL}/wp-admin/plugins.php`);

	// Check if already active
	const isActive = await page
		.locator('tr[data-slug="cta-highlights"]')
		.locator('text=Deactivate')
		.count() > 0;

	if (isActive) {
		return;
	}

	// Activate plugin
	const activateLink = page
		.locator('tr[data-slug="cta-highlights"]')
		.locator('text=Activate');

	if (await activateLink.count() > 0) {
		await activateLink.click();
		await page.waitForSelector('.notice-success');
	}
}

/**
 * Create test data (posts, CTAs, etc.)
 */
async function createTestData(page, baseURL) {
	// Create a test post if it doesn't exist
	await page.goto(`${baseURL}/wp-admin/post-new.php`);

	// Check if we're on the new post page
	const titleInput = page.locator('#title, .editor-post-title__input');

	if (await titleInput.count() > 0) {
		// Close welcome guide if it appears
		const closeGuide = page.locator('button[aria-label="Close dialog"]');
		if (await closeGuide.count() > 0) {
			await closeGuide.click();
		}

		// Add test post title
		await titleInput.fill('E2E Test Post for CTA Highlights');

		// Add test content with CTA shortcode
		const contentArea = page.locator('.block-editor-default-block-appender__content');

		if (await contentArea.count() > 0) {
			await contentArea.click();
			await page.keyboard.type('This is a test post for E2E testing.');
			await page.keyboard.press('Enter');
			await page.keyboard.type('[cta_highlights template="default" cta_title="Test CTA"]');
			await page.keyboard.type('Click here to test the CTA!');
			await page.keyboard.type('[/cta_highlights]');
		}

		// Publish post
		await page.click('button:has-text("Publish")');

		// Confirm publish (if pre-publish panel appears)
		const confirmPublish = page.locator('.editor-post-publish-panel button:has-text("Publish")');
		if (await confirmPublish.count() > 0) {
			await confirmPublish.click();
		}

		await page.waitForSelector('.components-snackbar:has-text("published")');
	}

	// Create test CTA auto-insert (if the page exists)
	const ctaAdminURL = `${baseURL}/wp-admin/admin.php?page=cta-auto-insert`;
	const response = await page.goto(ctaAdminURL);

	if (response && response.ok()) {
		// Check if there are existing CTAs
		const hasCTAs = await page.locator('.cta-list-table tbody tr').count() > 0;

		if (!hasCTAs) {
			// Create a test CTA
			await page.click('a:has-text("Add New")');
			await page.fill('#cta-name', 'E2E Test CTA');

			// Fill in content (if rich editor exists)
			const contentEditor = page.locator('#cta-content-editor');
			if (await contentEditor.count() > 0) {
				await contentEditor.fill('[cta_highlights]E2E Test Content[/cta_highlights]');
			}

			// Set status to active
			await page.check('input[name="status"][value="active"]');

			// Save
			await page.click('button[type="submit"]:has-text("Save")');
			await page.waitForSelector('.notice-success');
		}
	}
}

/**
 * Save authentication state for test reuse
 */
async function saveAuthState(context) {
	const authFile = path.join(__dirname, '..', '_output', 'auth-state.json');

	// Ensure directory exists
	const dir = path.dirname(authFile);
	if (!fs.existsSync(dir)) {
		fs.mkdirSync(dir, { recursive: true });
	}

	// Save state
	await context.storageState({ path: authFile });
}
