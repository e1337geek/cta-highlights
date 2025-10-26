/**
 * Page Object Model for CTA Frontend Interface
 *
 * Encapsulates interactions with CTA elements on the frontend.
 */

class CTAFrontendPage {
	/**
	 * Constructor
	 *
	 * @param {import('@playwright/test').Page} page - Playwright page
	 */
	constructor(page) {
		this.page = page;

		// Selectors
		this.selectors = {
			// CTA elements
			ctaWrapper: '.cta-highlights-wrapper',
			ctaContent: '.cta-highlights-content',
			ctaTitle: '.cta-highlights-title',
			ctaInner: '.cta-highlights-inner',
			closeButton: '.cta-highlights-close',

			// Highlight effect
			overlay: '.cta-highlights-overlay',
			highlightedCTA: '.cta-highlights-wrapper.highlighted',

			// Auto-inserted CTAs
			autoInsertedCTA: '.cta-highlights-wrapper[data-auto-inserted="true"]',

			// ARIA and accessibility
			ariaLive: '[aria-live]',
			focusTrap: '[data-focus-trap]',

			// Post content
			postContent: '.entry-content, .post-content, article',
		};
	}

	/**
	 * Navigate to a post/page URL
	 *
	 * @param {string} url - Post/page URL
	 */
	async goto(url) {
		await this.page.goto(url);
		await this.waitForPageLoad();
	}

	/**
	 * Wait for page to load
	 */
	async waitForPageLoad() {
		await this.page.waitForLoadState('networkidle');
		await this.page.waitForSelector('body', { state: 'visible' });
	}

	/**
	 * Get all CTA elements on page
	 *
	 * @returns {Promise<Array>} - Array of CTA locators
	 */
	async getCTAs() {
		return await this.page.locator(this.selectors.ctaWrapper).all();
	}

	/**
	 * Get CTA count on page
	 *
	 * @returns {Promise<number>} - Number of CTAs
	 */
	async getCTACount() {
		return await this.page.locator(this.selectors.ctaWrapper).count();
	}

	/**
	 * Check if CTA is visible
	 *
	 * @param {number} index - CTA index (0-based)
	 * @returns {Promise<boolean>} - True if visible
	 */
	async isCTAVisible(index = 0) {
		const cta = this.page.locator(this.selectors.ctaWrapper).nth(index);
		return await cta.isVisible();
	}

	/**
	 * Get CTA title text
	 *
	 * @param {number} index - CTA index (0-based)
	 * @returns {Promise<string>} - Title text
	 */
	async getCTATitle(index = 0) {
		const title = this.page.locator(this.selectors.ctaWrapper).nth(index).locator(this.selectors.ctaTitle);
		return await title.textContent();
	}

	/**
	 * Get CTA content text
	 *
	 * @param {number} index - CTA index (0-based)
	 * @returns {Promise<string>} - Content text
	 */
	async getCTAContent(index = 0) {
		const content = this.page.locator(this.selectors.ctaWrapper).nth(index).locator(this.selectors.ctaContent);
		return await content.textContent();
	}

	/**
	 * Scroll CTA into view
	 *
	 * @param {number} index - CTA index (0-based)
	 */
	async scrollCTAIntoView(index = 0) {
		const cta = this.page.locator(this.selectors.ctaWrapper).nth(index);
		await cta.scrollIntoViewIfNeeded();
		// Wait for scroll to complete
		await this.page.waitForTimeout(500);
	}

	/**
	 * Wait for highlight effect to activate
	 *
	 * @param {number} timeout - Timeout in milliseconds
	 */
	async waitForHighlight(timeout = 5000) {
		await this.page.waitForSelector(this.selectors.highlightedCTA, {
			state: 'visible',
			timeout
		});
	}

	/**
	 * Check if CTA is highlighted
	 *
	 * @param {number} index - CTA index (0-based)
	 * @returns {Promise<boolean>} - True if highlighted
	 */
	async isCTAHighlighted(index = 0) {
		const cta = this.page.locator(this.selectors.ctaWrapper).nth(index);
		const classes = await cta.getAttribute('class');
		return classes.includes('highlighted');
	}

	/**
	 * Check if overlay is visible
	 *
	 * @returns {Promise<boolean>} - True if overlay visible
	 */
	async isOverlayVisible() {
		const overlay = this.page.locator(this.selectors.overlay);
		return await overlay.isVisible();
	}

	/**
	 * Click close button
	 *
	 * @param {number} index - CTA index (0-based)
	 */
	async clickClose(index = 0) {
		const closeBtn = this.page.locator(this.selectors.ctaWrapper).nth(index).locator(this.selectors.closeButton);
		await closeBtn.click();
	}

	/**
	 * Wait for CTA to be dismissed
	 *
	 * @param {number} index - CTA index (0-based)
	 */
	async waitForDismissal(index = 0) {
		const cta = this.page.locator(this.selectors.ctaWrapper).nth(index);
		await cta.waitFor({ state: 'hidden', timeout: 5000 });
	}

	/**
	 * Check localStorage for cooldown
	 *
	 * @param {string} key - Storage key
	 * @returns {Promise<any>} - Stored value
	 */
	async getStoredCooldown(key) {
		return await this.page.evaluate((storageKey) => {
			return window.localStorage.getItem(storageKey);
		}, key);
	}

	/**
	 * Clear localStorage cooldowns
	 */
	async clearCooldowns() {
		await this.page.evaluate(() => {
			const keys = [];
			for (let i = 0; i < window.localStorage.length; i++) {
				const key = window.localStorage.key(i);
				if (key.startsWith('cta_highlights_')) {
					keys.push(key);
				}
			}
			keys.forEach(key => window.localStorage.removeItem(key));
		});
	}

	/**
	 * Get auto-inserted CTA count
	 *
	 * @returns {Promise<number>} - Number of auto-inserted CTAs
	 */
	async getAutoInsertedCTACount() {
		return await this.page.locator(this.selectors.autoInsertedCTA).count();
	}

	/**
	 * Check if auto-inserted CTA exists
	 *
	 * @param {string} ctaId - CTA ID
	 * @returns {Promise<boolean>} - True if exists
	 */
	async hasAutoInsertedCTA(ctaId) {
		const selector = `.cta-highlights-wrapper[data-cta-id="${ctaId}"][data-auto-inserted="true"]`;
		return await this.page.locator(selector).count() > 0;
	}

	/**
	 * Get position of CTA in content
	 *
	 * @param {number} index - CTA index (0-based)
	 * @returns {Promise<number>} - Position index
	 */
	async getCTAPosition(index = 0) {
		return await this.page.evaluate((idx) => {
			const content = document.querySelector('.entry-content, .post-content, article');
			const cta = document.querySelectorAll('.cta-highlights-wrapper')[idx];

			if (!content || !cta) return -1;

			const children = Array.from(content.children);
			return children.indexOf(cta);
		}, index);
	}

	/**
	 * Check ARIA attributes
	 *
	 * @param {number} index - CTA index (0-based)
	 * @returns {Promise<Object>} - ARIA attributes
	 */
	async getARIAAttributes(index = 0) {
		const cta = this.page.locator(this.selectors.ctaWrapper).nth(index);

		return {
			role: await cta.getAttribute('role'),
			ariaLabel: await cta.getAttribute('aria-label'),
			ariaLabelledBy: await cta.getAttribute('aria-labelledby'),
			ariaDescribedBy: await cta.getAttribute('aria-describedby'),
			ariaLive: await cta.getAttribute('aria-live'),
			ariaModal: await cta.getAttribute('aria-modal'),
		};
	}

	/**
	 * Get focused element
	 *
	 * @returns {Promise<string>} - Focused element selector
	 */
	async getFocusedElement() {
		return await this.page.evaluate(() => {
			const el = document.activeElement;
			if (!el || el === document.body) return null;

			let selector = el.tagName.toLowerCase();
			if (el.id) selector += `#${el.id}`;
			if (el.className) {
				const classes = el.className.split(' ').filter(c => c).join('.');
				if (classes) selector += `.${classes}`;
			}

			return selector;
		});
	}

	/**
	 * Press keyboard key
	 *
	 * @param {string} key - Key name (Tab, Escape, Enter, etc.)
	 */
	async pressKey(key) {
		await this.page.keyboard.press(key);
		await this.page.waitForTimeout(100);
	}

	/**
	 * Tab to next element
	 *
	 * @param {number} times - Number of tabs
	 */
	async tab(times = 1) {
		for (let i = 0; i < times; i++) {
			await this.pressKey('Tab');
		}
	}

	/**
	 * Check if element is focusable
	 *
	 * @param {string} selector - Element selector
	 * @returns {Promise<boolean>} - True if focusable
	 */
	async isFocusable(selector) {
		return await this.page.evaluate((sel) => {
			const el = document.querySelector(sel);
			if (!el) return false;

			const tabindex = el.getAttribute('tabindex');
			if (tabindex && parseInt(tabindex) < 0) return false;

			const focusableSelectors = [
				'a[href]',
				'button:not([disabled])',
				'input:not([disabled])',
				'select:not([disabled])',
				'textarea:not([disabled])',
				'[tabindex]:not([tabindex="-1"])'
			];

			return focusableSelectors.some(focusSel => el.matches(focusSel));
		}, selector);
	}

	/**
	 * Get all focusable elements within CTA
	 *
	 * @param {number} index - CTA index (0-based)
	 * @returns {Promise<number>} - Count of focusable elements
	 */
	async getFocusableElementsCount(index = 0) {
		const cta = this.page.locator(this.selectors.ctaWrapper).nth(index);

		return await cta.evaluate((element) => {
			const focusableSelectors = [
				'a[href]',
				'button:not([disabled])',
				'input:not([disabled])',
				'select:not([disabled])',
				'textarea:not([disabled])',
				'[tabindex]:not([tabindex="-1"])'
			];

			return element.querySelectorAll(focusableSelectors.join(', ')).length;
		});
	}

	/**
	 * Wait for animations to complete
	 */
	async waitForAnimations() {
		await this.page.waitForTimeout(500);
	}

	/**
	 * Take screenshot
	 *
	 * @param {string} name - Screenshot name
	 */
	async screenshot(name) {
		const timestamp = new Date().toISOString().replace(/:/g, '-');
		await this.page.screenshot({
			path: `tests/e2e/screenshots/${name}-${timestamp}.png`,
			fullPage: true
		});
	}
}

module.exports = CTAFrontendPage;
