/**
 * E2E Test Helpers
 *
 * Utility functions for Playwright E2E tests.
 */

/**
 * Wait for WordPress admin page to be fully loaded
 *
 * @param {import('@playwright/test').Page} page - Playwright page
 */
async function waitForAdminPage(page) {
	// Wait for admin bar (indicates WordPress is loaded) - use 'attached' not 'visible' as it may be hidden on some pages
	await page.waitForSelector('#wpadminbar', { state: 'attached' });

	// Wait for page to be loaded
	await page.waitForLoadState('domcontentloaded');

	// Wait for any loading spinners to disappear
	await page
		.waitForSelector('.spinner.is-active', {
			state: 'hidden',
			timeout: 5000,
		})
		.catch(() => {
			// Spinner might not exist, that's okay
		});
}

/**
 * Navigate to CTA Highlights admin page
 *
 * @param {import('@playwright/test').Page} page - Playwright page
 */
async function navigateToCTAAdmin(page) {
	await page.goto('/wp-admin/edit.php?post_type=cta_highlight');
	await waitForAdminPage(page);
}

/**
 * Create a new CTA via admin interface
 *
 * @param {import('@playwright/test').Page} page            - Playwright page
 * @param {Object}                          data            - CTA data
 * @param {string}                          data.title      - CTA title
 * @param {string}                          data.content    - CTA content
 * @param {string}                          [data.template] - Template name
 * @param {string}                          [data.ctaType]  - CTA type (primary or fallback)
 * @param {string}                          [data.status]   - Post status (publish or draft)
 * @return {Promise<string>} - CTA ID
 */
async function createCTA(page, data) {
	const {
		title = 'Test CTA',
		content = '<p>Test content</p>',
		template = 'default',
		ctaType = 'primary',
		status = 'publish',
	} = data;

	// Navigate to new CTA page
	await page.goto('/wp-admin/post-new.php?post_type=cta_highlight');
	await waitForAdminPage(page);

	// Fill in title
	await page.fill('#title', title);

	// Fill in content (handle both Classic Editor and Block Editor)
	const isBlockEditor = (await page.locator('.block-editor').count()) > 0;
	if (isBlockEditor) {
		// Block Editor
		await page.click('.block-editor-writing-flow');
		await page.keyboard.type(content);
	} else {
		// Classic Editor
		await page.evaluate((htmlContent) => {
			if (typeof tinyMCE !== 'undefined' && tinyMCE.activeEditor) {
				tinyMCE.activeEditor.setContent(htmlContent);
			} else {
				document.getElementById('content').value = htmlContent;
			}
		}, content);
	}

	// Set template (if meta box is visible)
	const templateSelect = page.locator('#cta_highlights_template');
	if ((await templateSelect.count()) > 0) {
		await templateSelect.selectOption(template);
	}

	// Set CTA type (if meta box is visible)
	const ctaTypeRadio = page.locator(
		`input[name="cta_highlights_type"][value="${ctaType}"]`
	);
	if ((await ctaTypeRadio.count()) > 0) {
		await ctaTypeRadio.check();
	}

	// Publish or save draft
	if (status === 'publish') {
		await page.click('#publish');
	} else {
		await page.click('#save-post');
	}

	// Wait for success message
	await page.waitForSelector('.notice-success', { timeout: 10000 });

	// Extract post ID from URL
	const url = page.url();
	const match = url.match(/post=(\d+)/);
	return match ? match[1] : null;
}

/**
 * Delete a CTA via admin interface
 *
 * @param {import('@playwright/test').Page} page  - Playwright page
 * @param {string}                          ctaId - CTA ID
 */
async function deleteCTA(page, ctaId) {
	// Navigate to edit page
	await page.goto(`/wp-admin/post.php?post=${ctaId}&action=edit`);
	await waitForAdminPage(page);

	// Click "Move to Trash"
	await page.click('#delete-action a.submitdelete');

	// Wait for deletion confirmation
	await page.waitForURL(/wp-admin\/edit\.php/, { timeout: 10000 });
}

/**
 * Create a test post with CTA shortcode
 *
 * @param {import('@playwright/test').Page} page          - Playwright page
 * @param {Object}                          data          - Post data
 * @param {string}                          data.title    - Post title
 * @param {string}                          data.content  - Post content with shortcode
 * @param {string}                          [data.status] - Post status
 * @return {Promise<Object>} - Post data {id, url}
 */
async function createPostWithShortcode(page, data) {
	const {
		title = 'Test Post',
		content = '[cta_highlights template="default" title="Subscribe"]Sign up now![/cta_highlights]',
		status = 'publish',
	} = data;

	// Navigate to new post
	await page.goto('/wp-admin/post-new.php');
	await waitForAdminPage(page);

	// Fill in title
	await page.fill('#title', title);

	// Fill in content with shortcode
	const isBlockEditor = (await page.locator('.block-editor').count()) > 0;
	if (isBlockEditor) {
		// Block Editor - add Shortcode block
		await page.click('.block-editor-writing-flow');
		await page.keyboard.type('/shortcode');
		await page.keyboard.press('Enter');
		await page.keyboard.type(content);
	} else {
		// Classic Editor
		await page.evaluate((htmlContent) => {
			if (typeof tinyMCE !== 'undefined' && tinyMCE.activeEditor) {
				tinyMCE.activeEditor.setContent(htmlContent);
			} else {
				document.getElementById('content').value = htmlContent;
			}
		}, content);
	}

	// Publish
	if (status === 'publish') {
		await page.click('#publish');
		await page.waitForSelector('.notice-success', { timeout: 10000 });
	}

	// Get post ID and URL
	const url = page.url();
	const match = url.match(/post=(\d+)/);
	const postId = match ? match[1] : null;

	// Get permalink
	const permalinkInput = page.locator('#sample-permalink a');
	const permalink = await permalinkInput.getAttribute('href');

	return {
		id: postId,
		url: permalink,
	};
}

/**
 * Clear localStorage in browser
 *
 * @param {import('@playwright/test').Page} page - Playwright page
 */
async function clearLocalStorage(page) {
	await page.evaluate(() => {
		window.localStorage.clear();
	});
}

/**
 * Clear cookies in browser
 *
 * @param {import('@playwright/test').Page} page - Playwright page
 */
async function clearCookies(page) {
	await page.context().clearCookies();
}

/**
 * Get localStorage data
 *
 * @param {import('@playwright/test').Page} page - Playwright page
 * @param {string}                          key  - Storage key
 * @return {Promise<any>} - Stored data
 */
async function getLocalStorageItem(page, key) {
	return await page.evaluate((storageKey) => {
		return window.localStorage.getItem(storageKey);
	}, key);
}

/**
 * Set localStorage data
 *
 * @param {import('@playwright/test').Page} page  - Playwright page
 * @param {string}                          key   - Storage key
 * @param {any}                             value - Value to store
 */
async function setLocalStorageItem(page, key, value) {
	await page.evaluate(
		({ storageKey, storageValue }) => {
			window.localStorage.setItem(storageKey, storageValue);
		},
		{ storageKey: key, storageValue: value }
	);
}

/**
 * Wait for element to be visible and stable
 *
 * @param {import('@playwright/test').Page} page     - Playwright page
 * @param {string}                          selector - Element selector
 * @param {Object}                          options  - Wait options
 */
async function waitForStableElement(page, selector, options = {}) {
	const element = page.locator(selector);
	await element.waitFor({ state: 'visible', ...options });

	// Wait for animations to complete
	await page.waitForTimeout(300);
}

/**
 * Check if element has accessibility issues
 *
 * @param {import('@playwright/test').Page} page     - Playwright page
 * @param {string}                          selector - Element selector
 * @return {Promise<Array>} - Array of accessibility violations
 */
async function checkAccessibility(page, selector = null) {
	// This would integrate with axe-core or similar
	// For now, basic checks
	const element = selector ? page.locator(selector) : page.locator('body');

	const issues = [];

	// Check for missing alt text on images
	const images = await element.locator('img:not([alt])').count();
	if (images > 0) {
		issues.push({ type: 'missing-alt', count: images });
	}

	// Check for form inputs without labels
	const unlabeledInputs = await element
		.locator('input:not([aria-label]):not([aria-labelledby])')
		.count();
	if (unlabeledInputs > 0) {
		issues.push({ type: 'unlabeled-input', count: unlabeledInputs });
	}

	return issues;
}

/**
 * Simulate keyboard navigation
 *
 * @param {import('@playwright/test').Page} page  - Playwright page
 * @param {string}                          key   - Key to press (Tab, Enter, Escape, etc.)
 * @param {number}                          times - Number of times to press
 */
async function pressKey(page, key, times = 1) {
	for (let i = 0; i < times; i++) {
		await page.keyboard.press(key);
		await page.waitForTimeout(100); // Small delay between key presses
	}
}

/**
 * Get currently focused element
 *
 * @param {import('@playwright/test').Page} page - Playwright page
 * @return {Promise<string>} - Selector of focused element
 */
async function getFocusedElement(page) {
	return await page.evaluate(() => {
		const el = document.activeElement;
		if (!el || el === document.body) return null;

		// Get selector for element
		let selector = el.tagName.toLowerCase();
		if (el.id) selector += `#${el.id}`;
		if (el.className) {
			const classes = el.className
				.split(' ')
				.filter((c) => c)
				.join('.');
			if (classes) selector += `.${classes}`;
		}

		return selector;
	});
}

/**
 * Take screenshot with timestamp
 *
 * @param {import('@playwright/test').Page} page - Playwright page
 * @param {string}                          name - Screenshot name
 */
async function takeScreenshot(page, name) {
	const timestamp = new Date().toISOString().replace(/:/g, '-');
	await page.screenshot({
		path: `tests/e2e/screenshots/${name}-${timestamp}.png`,
		fullPage: true,
	});
}

module.exports = {
	waitForAdminPage,
	navigateToCTAAdmin,
	createCTA,
	deleteCTA,
	createPostWithShortcode,
	clearLocalStorage,
	clearCookies,
	getLocalStorageItem,
	setLocalStorageItem,
	waitForStableElement,
	checkAccessibility,
	pressKey,
	getFocusedElement,
	takeScreenshot,
};
