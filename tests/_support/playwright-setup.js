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

		// Close any modals that might be blocking the Publish button
		// WordPress editor can show various modals (Welcome Guide, Feature Announcements, etc.)

		// Strategy 1: Close Welcome Guide (most common)
		const welcomeGuideClose = page.locator('button[aria-label="Close"]');
		if (await welcomeGuideClose.count() > 0) {
			try {
				await welcomeGuideClose.first().click({ timeout: 1000 });
				await page.waitForTimeout(300);
			} catch {
				// Welcome guide might not be present
			}
		}

		// Strategy 2: Close any modal with Close button in header
		const modalHeaderClose = page.locator('.components-modal__header button[aria-label*="Close"], .components-modal__header button[aria-label*="close"]');
		let attempts = 0;
		while (await modalHeaderClose.count() > 0 && attempts < 5) {
			try {
				await modalHeaderClose.first().click({ timeout: 1000 });
				await page.waitForTimeout(300);
				attempts++;
			} catch {
				break;
			}
		}

		// Strategy 3: Wait for all modal overlays to be removed
		try {
			await page.waitForFunction(() => {
				return document.querySelectorAll('.components-modal__screen-overlay').length === 0;
			}, { timeout: 3000 });
		} catch {
			// Timeout is OK - might not have had modals
		}

		// Strategy 4: Force remove any remaining overlays (nuclear option)
		await page.evaluate(() => {
			const overlays = document.querySelectorAll('.components-modal__screen-overlay');
			overlays.forEach(overlay => overlay.remove());
		});

		// Publish post - use role-based selector for better reliability
		const publishButton = page.getByRole('button', { name: /^Publish/i });
		await publishButton.click();

		// Confirm publish (if pre-publish panel appears)
		// Wait a bit for the panel to appear, then check for the final publish button
		await page.waitForTimeout(500);
		const confirmPublish = page.getByRole('button', { name: /^Publish/i }).last();
		try {
			await confirmPublish.click({ timeout: 2000 });
		} catch {
			// Button might not appear if pre-publish checks are disabled
		}

		// Wait for success notification
		await page.waitForSelector('.components-snackbar:has-text("published")', { timeout: 10000 });
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
