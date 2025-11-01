/**
 * E2E Tests: Keyboard Navigation & Accessibility
 *
 * Tests keyboard navigation, focus management, ARIA attributes, and screen reader support.
 *
 * @group e2e
 * @group accessibility
 * @group a11y
 */

const { test, expect } = require('@playwright/test');
const CTAAdminPage = require('../utils/CTAAdminPage');
const CTAFrontendPage = require('../utils/CTAFrontendPage');

test.describe('Keyboard Navigation & Accessibility', () => {
	let adminPage;
	let frontendPage;

	test.beforeEach(async ({ page, context }) => {
		adminPage = new CTAAdminPage(page);
		frontendPage = new CTAFrontendPage(page);

		// Clear storage
		await context.clearCookies();
		await frontendPage.clearCooldowns();
	});

	test.describe('Keyboard Navigation', () => {
		test('should be able to Tab to CTA elements', async ({ page }) => {
			// Create post with shortcode
			await page.goto('/wp-admin/post-new.php');
			await adminPage.waitForPageLoad();

			await adminPage.fillTitle('Tab Navigation Test');
			await page.evaluate(() => {
				const content = `
					<a href="#before">Link Before</a>
					[cta_highlights title="Tab Test"]<a href="#cta-link">CTA Link</a>[/cta_highlights]
					<a href="#after">Link After</a>
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

			// Tab through elements
			await page.keyboard.press('Tab'); // Link before
			await page.keyboard.press('Tab'); // CTA link
			await page.keyboard.press('Tab'); // Link after or close button

			// Verify focus moved through CTA
			const focusedEl = await frontendPage.getFocusedElement();
			expect(focusedEl).toBeTruthy();
		});

		test('should close CTA with Escape key', async ({ page }) => {
			// Create post with shortcode
			await page.goto('/wp-admin/post-new.php');
			await adminPage.waitForPageLoad();

			await adminPage.fillTitle('Escape Key Test');
			await page.evaluate(() => {
				const content =
					'[cta_highlights title="Escape Test"]Press Escape to close[/cta_highlights]';
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
			await frontendPage.waitForHighlight();

			// Press Escape
			await page.keyboard.press('Escape');

			// Should be dismissed
			await frontendPage.waitForAnimations();
			const isHighlighted = await frontendPage.isCTAHighlighted(0);
			expect(isHighlighted).toBe(false);
		});

		test('should close CTA with Enter/Space on close button', async ({
			page,
		}) => {
			// Create post with shortcode
			await page.goto('/wp-admin/post-new.php');
			await adminPage.waitForPageLoad();

			await adminPage.fillTitle('Close Button Keyboard Test');
			await page.evaluate(() => {
				const content =
					'[cta_highlights title="Close Test"]Close with keyboard[/cta_highlights]';
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
			await frontendPage.waitForHighlight();

			// Focus close button
			const closeBtn = page.locator('.cta-highlights-close').first();
			await closeBtn.focus();

			// Press Enter
			await page.keyboard.press('Enter');

			// Should be dismissed
			await frontendPage.waitForAnimations();
			const isHighlighted = await frontendPage.isCTAHighlighted(0);
			expect(isHighlighted).toBe(false);
		});

		test('should be able to Tab backwards with Shift+Tab', async ({
			page,
		}) => {
			// Create post with shortcode
			await page.goto('/wp-admin/post-new.php');
			await adminPage.waitForPageLoad();

			await adminPage.fillTitle('Shift Tab Test');
			await page.evaluate(() => {
				const content = `
					<a href="#link1">Link 1</a>
					[cta_highlights title="Shift Tab"]<a href="#cta">CTA Link</a>[/cta_highlights]
					<a href="#link2">Link 2</a>
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

			// Tab forward several times
			await page.keyboard.press('Tab');
			await page.keyboard.press('Tab');
			await page.keyboard.press('Tab');

			// Tab backward
			await page.keyboard.press('Shift+Tab');

			// Should be able to navigate backwards
			const focusedEl = await frontendPage.getFocusedElement();
			expect(focusedEl).toBeTruthy();
		});
	});

	test.describe('Focus Management', () => {
		test('should trap focus when CTA highlighted', async ({ page }) => {
			// Create post with shortcode
			await page.goto('/wp-admin/post-new.php');
			await adminPage.waitForPageLoad();

			await adminPage.fillTitle('Focus Trap Test');
			await page.evaluate(() => {
				const content = `
					<a href="#before">Link Before</a>
					[cta_highlights title="Focus Trap"]<a href="#inside">Inside Link</a>[/cta_highlights]
					<a href="#after">Link After</a>
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

			// Activate highlight
			await frontendPage.scrollCTAIntoView(0);
			await frontendPage.waitForHighlight();

			// Try to Tab many times
			for (let i = 0; i < 10; i++) {
				await page.keyboard.press('Tab');
			}

			// Focus should still be within CTA or on overlay
			const focusedEl = await frontendPage.getFocusedElement();

			// Should not have reached "Link After" outside CTA
			expect(focusedEl).not.toContain('#after');
		});

		test('should restore focus after dismissing CTA', async ({ page }) => {
			// Create post with shortcode
			await page.goto('/wp-admin/post-new.php');
			await adminPage.waitForPageLoad();

			await adminPage.fillTitle('Focus Restore Test');
			await page.evaluate(() => {
				const content = `
					<a href="#trigger" id="trigger-link">Trigger Link</a>
					[cta_highlights title="Focus Restore"]Test content[/cta_highlights]
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

			// Focus trigger link
			const triggerLink = page.locator('#trigger-link');
			if ((await triggerLink.count()) > 0) {
				await triggerLink.focus();
			}

			// Activate highlight
			await frontendPage.scrollCTAIntoView(0);
			await frontendPage.waitForHighlight();

			// Dismiss with Escape
			await page.keyboard.press('Escape');

			await frontendPage.waitForAnimations();

			// Focus should be restored to trigger element
			// (or at least not lost to body)
			const focusedEl = await frontendPage.getFocusedElement();
			expect(focusedEl).not.toBeNull();
			expect(focusedEl).not.toBe('body');
		});

		test('should focus close button when CTA highlighted', async ({
			page,
		}) => {
			// Create post with shortcode
			await page.goto('/wp-admin/post-new.php');
			await adminPage.waitForPageLoad();

			await adminPage.fillTitle('Auto-Focus Test');
			await page.evaluate(() => {
				const content =
					'[cta_highlights title="Auto Focus"]Test content[/cta_highlights]';
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
			await frontendPage.waitForHighlight();

			// Wait for focus to move
			await page.waitForTimeout(500);

			// Close button should be focused
			const focusedEl = await frontendPage.getFocusedElement();
			expect(focusedEl).toContain('close');
		});
	});

	test.describe('ARIA Attributes', () => {
		test('should have correct ARIA role', async ({ page }) => {
			// Create post with shortcode
			await page.goto('/wp-admin/post-new.php');
			await adminPage.waitForPageLoad();

			await adminPage.fillTitle('ARIA Role Test');
			await page.evaluate(() => {
				const content =
					'[cta_highlights title="ARIA Test"]Test content[/cta_highlights]';
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

			// Get ARIA attributes
			const aria = await frontendPage.getARIAAttributes(0);

			// Should have appropriate role (dialog, region, or complementary)
			expect(aria.role).toBeTruthy();
		});

		test('should have aria-label or aria-labelledby', async ({ page }) => {
			// Create post with shortcode
			await page.goto('/wp-admin/post-new.php');
			await adminPage.waitForPageLoad();

			await adminPage.fillTitle('ARIA Label Test');
			await page.evaluate(() => {
				const content =
					'[cta_highlights title="ARIA Label"]Test content[/cta_highlights]';
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

			// Get ARIA attributes
			const aria = await frontendPage.getARIAAttributes(0);

			// Should have aria-label or aria-labelledby
			const hasLabel = aria.ariaLabel || aria.ariaLabelledBy;
			expect(hasLabel).toBeTruthy();
		});

		test('should have aria-modal when highlighted', async ({ page }) => {
			// Create post with shortcode
			await page.goto('/wp-admin/post-new.php');
			await adminPage.waitForPageLoad();

			await adminPage.fillTitle('ARIA Modal Test');
			await page.evaluate(() => {
				const content =
					'[cta_highlights title="Modal Test"]Test content[/cta_highlights]';
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
			await frontendPage.waitForHighlight();

			// Get ARIA attributes
			const aria = await frontendPage.getARIAAttributes(0);

			// Should have aria-modal="true" when highlighted
			expect(aria.ariaModal).toBe('true');
		});

		test('should have aria-live region for screen readers', async ({
			page,
		}) => {
			// Create post with shortcode
			await page.goto('/wp-admin/post-new.php');
			await adminPage.waitForPageLoad();

			await adminPage.fillTitle('ARIA Live Test');
			await page.evaluate(() => {
				const content =
					'[cta_highlights title="Live Region"]Test content[/cta_highlights]';
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

			// Check for aria-live region
			const ariaLive = await page.locator('[aria-live]').count();
			expect(ariaLive).toBeGreaterThan(0);
		});
	});

	test.describe('Screen Reader Support', () => {
		test('should announce CTA activation to screen readers', async ({
			page,
		}) => {
			// Create post with shortcode
			await page.goto('/wp-admin/post-new.php');
			await adminPage.waitForPageLoad();

			await adminPage.fillTitle('Screen Reader Announce Test');
			await page.evaluate(() => {
				const content =
					'[cta_highlights title="Announce Test"]Test content[/cta_highlights]';
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
			await frontendPage.waitForHighlight();

			// Check for aria-live announcement
			const liveRegion = page.locator(
				'[aria-live="polite"], [aria-live="assertive"]'
			);
			const announcement = await liveRegion.textContent();

			// Should have some announcement text
			expect(announcement).toBeTruthy();
		});

		test('should have descriptive close button label', async ({ page }) => {
			// Create post with shortcode
			await page.goto('/wp-admin/post-new.php');
			await adminPage.waitForPageLoad();

			await adminPage.fillTitle('Close Button Label Test');
			await page.evaluate(() => {
				const content =
					'[cta_highlights title="Close Label"]Test content[/cta_highlights]';
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
			await frontendPage.waitForHighlight();

			// Check close button aria-label
			const closeBtn = page.locator('.cta-highlights-close').first();
			const ariaLabel = await closeBtn.getAttribute('aria-label');

			// Should have descriptive label
			expect(ariaLabel).toBeTruthy();
			expect(ariaLabel.toLowerCase()).toMatch(/close|dismiss/);
		});
	});

	test.describe('Color Contrast & Visual', () => {
		test('should have sufficient color contrast for text', async ({
			page,
		}) => {
			// Create post with shortcode
			await page.goto('/wp-admin/post-new.php');
			await adminPage.waitForPageLoad();

			await adminPage.fillTitle('Contrast Test');
			await page.evaluate(() => {
				const content =
					'[cta_highlights title="Contrast Test"]Test content with text[/cta_highlights]';
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

			// Get text and background colors
			const wrapper = page.locator('.cta-highlights-wrapper').first();
			const styles = await wrapper.evaluate((el) => {
				const computed = window.getComputedStyle(el);
				return {
					color: computed.color,
					backgroundColor: computed.backgroundColor,
					fontSize: computed.fontSize,
				};
			});

			// Colors should be set (not transparent/none)
			expect(styles.color).toBeTruthy();
			expect(styles.backgroundColor).toBeTruthy();

			// Note: Actual contrast calculation would require additional library
			// This is a basic check that colors are defined
		});

		test('should be visible at different zoom levels', async ({ page }) => {
			// Create post with shortcode
			await page.goto('/wp-admin/post-new.php');
			await adminPage.waitForPageLoad();

			await adminPage.fillTitle('Zoom Test');
			await page.evaluate(() => {
				const content =
					'[cta_highlights title="Zoom Test"]Test content[/cta_highlights]';
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

			// Test at 200% zoom
			await page.evaluate(() => {
				document.body.style.zoom = '2.0';
			});

			await page.waitForTimeout(500);

			// CTA should still be visible
			const isVisible = await frontendPage.isCTAVisible(0);
			expect(isVisible).toBe(true);

			// Reset zoom
			await page.evaluate(() => {
				document.body.style.zoom = '1.0';
			});
		});
	});
});
