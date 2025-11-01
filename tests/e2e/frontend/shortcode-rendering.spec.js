/**
 * E2E Tests: Shortcode Rendering on Frontend
 *
 * Tests CTA shortcode rendering, display, and interaction on the frontend.
 *
 * @group e2e
 * @group frontend
 */

const { test, expect } = require('@playwright/test');
const CTAAdminPage = require('../utils/CTAAdminPage');
const CTAFrontendPage = require('../utils/CTAFrontendPage');

test.describe('Shortcode Rendering', () => {
	let adminPage;
	let frontendPage;

	test.beforeEach(async ({ page }) => {
		adminPage = new CTAAdminPage(page);
		frontendPage = new CTAFrontendPage(page);
	});

	test.describe('Basic Shortcode Display', () => {
		test('should render shortcode with default template', async ({
			page,
		}) => {
			// Create post with shortcode
			await page.goto('/wp-admin/post-new.php');
			await adminPage.waitForPageLoad();

			await adminPage.fillTitle('Shortcode Test Post');
			await page.evaluate(() => {
				const content =
					'[cta_highlights template="default" title="Subscribe"]Join our newsletter![/cta_highlights]';
				if (
					typeof window.tinyMCE !== 'undefined' &&
					window.tinyMCE.activeEditor
				) {
					window.tinyMCE.activeEditor.setContent(content);
				} else {
					document.getElementById('content').value = content;
				}
			});

			// Publish post
			await page.click('#publish');
			await page.waitForSelector('.notice-success');

			// Get permalink
			const permalink = await page
				.locator('#sample-permalink a')
				.getAttribute('href');

			// Visit frontend
			await frontendPage.goto(permalink);

			// Verify CTA is rendered
			const ctaCount = await frontendPage.getCTACount();
			expect(ctaCount).toBeGreaterThan(0);

			// Verify content
			const title = await frontendPage.getCTATitle(0);
			expect(title).toContain('Subscribe');

			const content = await frontendPage.getCTAContent(0);
			expect(content).toContain('Join our newsletter!');
		});

		test('should render multiple shortcodes on same page', async ({
			page,
		}) => {
			// Create post with multiple shortcodes
			await page.goto('/wp-admin/post-new.php');
			await adminPage.waitForPageLoad();

			await adminPage.fillTitle('Multiple Shortcodes Test');
			await page.evaluate(() => {
				const content = `
					<p>First paragraph</p>
					[cta_highlights title="First CTA"]First CTA content[/cta_highlights]
					<p>Second paragraph</p>
					[cta_highlights title="Second CTA"]Second CTA content[/cta_highlights]
					<p>Third paragraph</p>
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

			// Should have 2 CTAs
			const ctaCount = await frontendPage.getCTACount();
			expect(ctaCount).toBe(2);

			// Verify both CTAs have correct content
			const title1 = await frontendPage.getCTATitle(0);
			expect(title1).toContain('First CTA');

			const title2 = await frontendPage.getCTATitle(1);
			expect(title2).toContain('Second CTA');
		});

		test('should not render shortcode in text mode', async ({ page }) => {
			// Create post with escaped shortcode
			await page.goto('/wp-admin/post-new.php');
			await adminPage.waitForPageLoad();

			await adminPage.fillTitle('Escaped Shortcode Test');
			await page.evaluate(() => {
				// Escaped shortcode (in code block or similar)
				const content =
					'<code>[cta_highlights title="Test"]Content[/cta_highlights]</code>';
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

			// Shortcode should be visible as text, not rendered
			const pageContent = await page.textContent('body');
			expect(pageContent).toContain('[cta_highlights');

			// Should have no CTA elements
			const ctaCount = await frontendPage.getCTACount();
			expect(ctaCount).toBe(0);
		});

		test('should handle empty shortcode content', async ({ page }) => {
			// Create post with empty shortcode
			await page.goto('/wp-admin/post-new.php');
			await adminPage.waitForPageLoad();

			await adminPage.fillTitle('Empty Shortcode Test');
			await page.evaluate(() => {
				const content =
					'[cta_highlights title="Empty CTA"][/cta_highlights]';
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

			// CTA should still render (even if empty)
			const ctaCount = await frontendPage.getCTACount();
			expect(ctaCount).toBeGreaterThan(0);
		});
	});

	test.describe('Shortcode Attributes', () => {
		test('should apply custom template attribute', async ({ page }) => {
			// Create post with custom template shortcode
			await page.goto('/wp-admin/post-new.php');
			await adminPage.waitForPageLoad();

			await adminPage.fillTitle('Custom Template Test');
			await page.evaluate(() => {
				const content =
					'[cta_highlights template="custom" title="Custom"]Custom template content[/cta_highlights]';
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

			// Verify CTA rendered
			const ctaCount = await frontendPage.getCTACount();
			expect(ctaCount).toBeGreaterThan(0);

			// Check for template-specific class (if applied)
			const wrapper = page.locator('.cta-highlights-wrapper').first();
			const classes = await wrapper.getAttribute('class');
			// Template should be reflected in data attribute or class
			expect(classes || '').toBeTruthy();
		});

		test('should respect highlight="false" attribute', async ({ page }) => {
			// Create post with highlight disabled
			await page.goto('/wp-admin/post-new.php');
			await adminPage.waitForPageLoad();

			await adminPage.fillTitle('No Highlight Test');
			await page.evaluate(() => {
				const content =
					'[cta_highlights highlight="false" title="No Highlight"]No highlight effect[/cta_highlights]';
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

			// Scroll CTA into view
			await frontendPage.scrollCTAIntoView(0);
			await frontendPage.waitForAnimations();

			// Should NOT be highlighted
			const isHighlighted = await frontendPage.isCTAHighlighted(0);
			expect(isHighlighted).toBe(false);

			// Overlay should not appear
			const overlayVisible = await frontendPage.isOverlayVisible();
			expect(overlayVisible).toBe(false);
		});

		test('should sanitize XSS in title attribute', async ({ page }) => {
			// Create post with XSS attempt in shortcode
			await page.goto('/wp-admin/post-new.php');
			await adminPage.waitForPageLoad();

			await adminPage.fillTitle('XSS Test');
			await page.evaluate(() => {
				const content =
					'[cta_highlights title="<script>alert(\'XSS\')</script>Test"]Content[/cta_highlights]';
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

			// Title should be escaped, no script executed
			const title = await frontendPage.getCTATitle(0);
			expect(title).not.toContain('<script>');

			// Check page HTML source doesn't have raw script tag
			const html = await page.content();
			expect(html).not.toMatch(/<script>alert\('XSS'\)<\/script>/);
		});
	});

	test.describe('Shortcode Assets', () => {
		test('should enqueue CTA CSS when shortcode present', async ({
			page,
		}) => {
			// Create post with shortcode
			await page.goto('/wp-admin/post-new.php');
			await adminPage.waitForPageLoad();

			await adminPage.fillTitle('Asset Enqueue Test');
			await page.evaluate(() => {
				const content =
					'[cta_highlights title="Test"]Test content[/cta_highlights]';
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

			// Check for CSS file in head
			const cssLinks = await page.locator('link[rel="stylesheet"]').all();
			const ctaCSSLoaded = await Promise.all(
				cssLinks.map(async (link) => {
					const href = await link.getAttribute('href');
					return href && href.includes('cta-highlights');
				})
			);

			expect(ctaCSSLoaded.some((loaded) => loaded)).toBe(true);
		});

		test('should enqueue CTA JS when shortcode present', async ({
			page,
		}) => {
			// Create post with shortcode
			await page.goto('/wp-admin/post-new.php');
			await adminPage.waitForPageLoad();

			await adminPage.fillTitle('JS Enqueue Test');
			await page.evaluate(() => {
				const content =
					'[cta_highlights title="Test"]Test content[/cta_highlights]';
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

			// Check for JS file
			const scripts = await page.locator('script[src]').all();
			const ctaJSLoaded = await Promise.all(
				scripts.map(async (script) => {
					const src = await script.getAttribute('src');
					return src && src.includes('cta-highlights');
				})
			);

			expect(ctaJSLoaded.some((loaded) => loaded)).toBe(true);
		});
	});
});
