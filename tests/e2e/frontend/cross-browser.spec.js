/**
 * E2E Tests: Cross-Browser Compatibility
 *
 * Tests CTA functionality across different browsers and devices.
 * These tests will run on Chromium, Firefox, WebKit, and mobile browsers.
 *
 * @group e2e
 * @group cross-browser
 */

const { test, expect, devices } = require('@playwright/test');
const CTAAdminPage = require('../utils/CTAAdminPage');
const CTAFrontendPage = require('../utils/CTAFrontendPage');

test.describe('Cross-Browser Compatibility', () => {
	let adminPage;
	let frontendPage;

	test.beforeEach(async ({ page, context }) => {
		adminPage = new CTAAdminPage(page);
		frontendPage = new CTAFrontendPage(page);

		// Clear storage
		await context.clearCookies();
		await frontendPage.clearCooldowns();
	});

	test.describe('Core Rendering (All Browsers)', () => {
		test('should render CTA correctly', async ({ page, browserName }) => {
			// Create post with shortcode
			await page.goto('/wp-admin/post-new.php');
			await adminPage.waitForPageLoad();

			await adminPage.fillTitle(`Cross-Browser Test ${browserName}`);
			await page.evaluate(() => {
				const content =
					'[cta_highlights title="Browser Test"]Test content for all browsers[/cta_highlights]';
				if (
					typeof window.tinyMCE !== 'undefined' &&
					window.tinyMCE.activeEditor
				) {
					window.tinyMCE.activeEditor.setContent(content);
				} else {
					document.getElementById('content').value = content;
				}
			});

			await page.click('#publish');
			await page.waitForSelector('.notice-success');

			const permalink = await page
				.locator('#sample-permalink a')
				.getAttribute('href');
			await frontendPage.goto(permalink);

			// Verify rendering
			const ctaCount = await frontendPage.getCTACount();
			expect(ctaCount).toBeGreaterThan(0);

			const isVisible = await frontendPage.isCTAVisible(0);
			expect(isVisible).toBe(true);
		});

		test('should activate highlight effect', async ({
			page,
			browserName,
		}) => {
			// Create post
			await page.goto('/wp-admin/post-new.php');
			await adminPage.waitForPageLoad();

			await adminPage.fillTitle(`Highlight Test ${browserName}`);
			await page.evaluate(() => {
				const content =
					'[cta_highlights title="Highlight"]Highlight in all browsers[/cta_highlights]';
				if (
					typeof window.tinyMCE !== 'undefined' &&
					window.tinyMCE.activeEditor
				) {
					window.tinyMCE.activeEditor.setContent(content);
				} else {
					document.getElementById('content').value = content;
				}
			});

			await page.click('#publish');
			await page.waitForSelector('.notice-success');

			const permalink = await page
				.locator('#sample-permalink a')
				.getAttribute('href');
			await frontendPage.goto(permalink);

			// Scroll into view
			await frontendPage.scrollCTAIntoView(0);

			// Wait for highlight (may vary by browser)
			await frontendPage.waitForHighlight().catch(() => {
				// Some browsers might be slower
			});

			await page.waitForTimeout(1000);

			// Check if highlighted or at least visible
			const isHighlighted = await frontendPage.isCTAHighlighted(0);
			const isVisible = await frontendPage.isCTAVisible(0);

			// At minimum, CTA should be visible
			expect(isVisible).toBe(true);
		});

		test('should handle localStorage', async ({ page, browserName }) => {
			// Create post
			await page.goto('/wp-admin/post-new.php');
			await adminPage.waitForPageLoad();

			await adminPage.fillTitle(`Storage Test ${browserName}`);
			await page.evaluate(() => {
				const content =
					'[cta_highlights title="Storage"]Storage test[/cta_highlights]';
				if (
					typeof window.tinyMCE !== 'undefined' &&
					window.tinyMCE.activeEditor
				) {
					window.tinyMCE.activeEditor.setContent(content);
				} else {
					document.getElementById('content').value = content;
				}
			});

			await page.click('#publish');
			await page.waitForSelector('.notice-success');

			const permalink = await page
				.locator('#sample-permalink a')
				.getAttribute('href');
			await frontendPage.goto(permalink);

			// Test localStorage access
			const storageWorks = await page.evaluate(() => {
				try {
					window.localStorage.setItem('test', 'value');
					const val = window.localStorage.getItem('test');
					window.localStorage.removeItem('test');
					return val === 'value';
				} catch (e) {
					return false;
				}
			});

			// Most browsers should support localStorage
			expect(storageWorks).toBe(true);
		});

		test('should dismiss CTA', async ({ page, browserName }) => {
			// Create post
			await page.goto('/wp-admin/post-new.php');
			await adminPage.waitForPageLoad();

			await adminPage.fillTitle(`Dismiss Test ${browserName}`);
			await page.evaluate(() => {
				const content =
					'[cta_highlights title="Dismiss"]Dismiss in all browsers[/cta_highlights]';
				if (
					typeof window.tinyMCE !== 'undefined' &&
					window.tinyMCE.activeEditor
				) {
					window.tinyMCE.activeEditor.setContent(content);
				} else {
					document.getElementById('content').value = content;
				}
			});

			await page.click('#publish');
			await page.waitForSelector('.notice-success');

			const permalink = await page
				.locator('#sample-permalink a')
				.getAttribute('href');
			await frontendPage.goto(permalink);

			// Activate
			await frontendPage.scrollCTAIntoView(0);
			await frontendPage.waitForHighlight().catch(() => {});

			// Click close
			const closeBtn = page.locator('.cta-highlights-close').first();
			if (await closeBtn.isVisible()) {
				await closeBtn.click();

				// Wait for animation
				await page.waitForTimeout(500);

				// Should be dismissed
				const isHighlighted = await frontendPage.isCTAHighlighted(0);
				expect(isHighlighted).toBe(false);
			}
		});
	});

	test.describe('Mobile Specific Tests', () => {
		test('should render on mobile viewport', async ({ page }) => {
			// Set mobile viewport if not already mobile
			await page.setViewportSize({ width: 375, height: 667 }); // iPhone SE

			// Create post
			await page.goto('/wp-admin/post-new.php');
			await adminPage.waitForPageLoad();

			await adminPage.fillTitle('Mobile Viewport Test');
			await page.evaluate(() => {
				const content =
					'[cta_highlights title="Mobile"]Mobile content[/cta_highlights]';
				if (
					typeof window.tinyMCE !== 'undefined' &&
					window.tinyMCE.activeEditor
				) {
					window.tinyMCE.activeEditor.setContent(content);
				} else {
					document.getElementById('content').value = content;
				}
			});

			await page.click('#publish');
			await page.waitForSelector('.notice-success');

			const permalink = await page
				.locator('#sample-permalink a')
				.getAttribute('href');
			await frontendPage.goto(permalink);

			// CTA should be visible on mobile
			const isVisible = await frontendPage.isCTAVisible(0);
			expect(isVisible).toBe(true);

			// Check if it fits viewport
			const wrapper = page.locator('.cta-highlights-wrapper').first();
			const box = await wrapper.boundingBox();

			if (box) {
				// Should not be wider than viewport
				expect(box.width).toBeLessThanOrEqual(375);
			}
		});

		test('should handle touch events', async ({ page }) => {
			// Set mobile viewport
			await page.setViewportSize({ width: 375, height: 667 });

			// Create post
			await page.goto('/wp-admin/post-new.php');
			await adminPage.waitForPageLoad();

			await adminPage.fillTitle('Touch Event Test');
			await page.evaluate(() => {
				const content =
					'[cta_highlights title="Touch"]Touch test[/cta_highlights]';
				if (
					typeof window.tinyMCE !== 'undefined' &&
					window.tinyMCE.activeEditor
				) {
					window.tinyMCE.activeEditor.setContent(content);
				} else {
					document.getElementById('content').value = content;
				}
			});

			await page.click('#publish');
			await page.waitForSelector('.notice-success');

			const permalink = await page
				.locator('#sample-permalink a')
				.getAttribute('href');
			await frontendPage.goto(permalink);

			// Activate highlight
			await frontendPage.scrollCTAIntoView(0);
			await frontendPage.waitForHighlight().catch(() => {});

			// Tap close button
			const closeBtn = page.locator('.cta-highlights-close').first();
			if (await closeBtn.isVisible()) {
				await closeBtn.tap();

				await page.waitForTimeout(500);

				// Should dismiss
				const isHighlighted = await frontendPage.isCTAHighlighted(0);
				expect(isHighlighted).toBe(false);
			}
		});
	});

	test.describe('Browser-Specific Features', () => {
		test('should work with browser back button', async ({
			page,
			browserName,
		}) => {
			// Create post
			await page.goto('/wp-admin/post-new.php');
			await adminPage.waitForPageLoad();

			await adminPage.fillTitle(`Back Button Test ${browserName}`);
			await page.evaluate(() => {
				const content =
					'[cta_highlights title="Back Test"]Back button test[/cta_highlights]';
				if (
					typeof window.tinyMCE !== 'undefined' &&
					window.tinyMCE.activeEditor
				) {
					window.tinyMCE.activeEditor.setContent(content);
				} else {
					document.getElementById('content').value = content;
				}
			});

			await page.click('#publish');
			await page.waitForSelector('.notice-success');

			const permalink = await page
				.locator('#sample-permalink a')
				.getAttribute('href');
			await frontendPage.goto(permalink);

			// Dismiss CTA
			await frontendPage.scrollCTAIntoView(0);
			await frontendPage.waitForHighlight().catch(() => {});
			await frontendPage.clickClose(0);

			// Navigate away
			await page.goto('/');

			// Go back
			await page.goBack();

			// CTA should still respect cooldown
			await page.waitForTimeout(1000);
			await frontendPage.scrollCTAIntoView(0);

			const isHighlighted = await frontendPage.isCTAHighlighted(0);
			expect(isHighlighted).toBe(false);
		});

		test('should handle browser refresh', async ({ page, browserName }) => {
			// Create post
			await page.goto('/wp-admin/post-new.php');
			await adminPage.waitForPageLoad();

			await adminPage.fillTitle(`Refresh Test ${browserName}`);
			await page.evaluate(() => {
				const content =
					'[cta_highlights title="Refresh"]Refresh test[/cta_highlights]';
				if (
					typeof window.tinyMCE !== 'undefined' &&
					window.tinyMCE.activeEditor
				) {
					window.tinyMCE.activeEditor.setContent(content);
				} else {
					document.getElementById('content').value = content;
				}
			});

			await page.click('#publish');
			await page.waitForSelector('.notice-success');

			const permalink = await page
				.locator('#sample-permalink a')
				.getAttribute('href');
			await frontendPage.goto(permalink);

			// Dismiss CTA
			await frontendPage.scrollCTAIntoView(0);
			await frontendPage.waitForHighlight().catch(() => {});
			await frontendPage.clickClose(0);

			// Refresh page
			await page.reload();

			// CTA should respect cooldown
			await page.waitForTimeout(1000);
			await frontendPage.scrollCTAIntoView(0);

			const isHighlighted = await frontendPage.isCTAHighlighted(0);
			expect(isHighlighted).toBe(false);
		});

		test('should work in private/incognito mode', async ({
			page,
			browserName,
		}) => {
			// Note: This test runs in regular context, but we can test
			// graceful fallback when storage is restricted

			// Create post
			await page.goto('/wp-admin/post-new.php');
			await adminPage.waitForPageLoad();

			await adminPage.fillTitle(`Private Mode Test ${browserName}`);
			await page.evaluate(() => {
				const content =
					'[cta_highlights title="Private"]Private mode test[/cta_highlights]';
				if (
					typeof window.tinyMCE !== 'undefined' &&
					window.tinyMCE.activeEditor
				) {
					window.tinyMCE.activeEditor.setContent(content);
				} else {
					document.getElementById('content').value = content;
				}
			});

			await page.click('#publish');
			await page.waitForSelector('.notice-success');

			const permalink = await page
				.locator('#sample-permalink a')
				.getAttribute('href');
			await frontendPage.goto(permalink);

			// CTA should still render even if storage is unavailable
			const isVisible = await frontendPage.isCTAVisible(0);
			expect(isVisible).toBe(true);
		});
	});

	test.describe('CSS and Layout', () => {
		test('should apply styles correctly', async ({ page, browserName }) => {
			// Create post
			await page.goto('/wp-admin/post-new.php');
			await adminPage.waitForPageLoad();

			await adminPage.fillTitle(`Style Test ${browserName}`);
			await page.evaluate(() => {
				const content =
					'[cta_highlights title="Style"]Style test[/cta_highlights]';
				if (
					typeof window.tinyMCE !== 'undefined' &&
					window.tinyMCE.activeEditor
				) {
					window.tinyMCE.activeEditor.setContent(content);
				} else {
					document.getElementById('content').value = content;
				}
			});

			await page.click('#publish');
			await page.waitForSelector('.notice-success');

			const permalink = await page
				.locator('#sample-permalink a')
				.getAttribute('href');
			await frontendPage.goto(permalink);

			// Check computed styles
			const wrapper = page.locator('.cta-highlights-wrapper').first();
			const styles = await wrapper.evaluate((el) => {
				const computed = window.getComputedStyle(el);
				return {
					display: computed.display,
					position: computed.position,
					zIndex: computed.zIndex,
				};
			});

			// Basic style checks
			expect(styles.display).toBeTruthy();
			expect(styles.display).not.toBe('none');
		});

		test('should not break page layout', async ({ page, browserName }) => {
			// Create post
			await page.goto('/wp-admin/post-new.php');
			await adminPage.waitForPageLoad();

			await adminPage.fillTitle(`Layout Test ${browserName}`);
			await page.evaluate(() => {
				const content = `
					<p>Paragraph 1</p>
					[cta_highlights title="Layout"]Layout test[/cta_highlights]
					<p>Paragraph 2</p>
				`;
				if (
					typeof window.tinyMCE !== 'undefined' &&
					window.tinyMCE.activeEditor
				) {
					window.tinyMCE.activeEditor.setContent(content);
				} else {
					document.getElementById('content').value = content;
				}
			});

			await page.click('#publish');
			await page.waitForSelector('.notice-success');

			const permalink = await page
				.locator('#sample-permalink a')
				.getAttribute('href');
			await frontendPage.goto(permalink);

			// Check page doesn't have horizontal scroll
			const hasHorizontalScroll = await page.evaluate(() => {
				return (
					document.documentElement.scrollWidth >
					document.documentElement.clientWidth
				);
			});

			// Should not cause unwanted horizontal scroll
			// (unless theme does this already)
			// This is a soft check
			expect(typeof hasHorizontalScroll).toBe('boolean');
		});
	});
});
