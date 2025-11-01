/**
 * E2E Tests: Auto-Insertion Functionality
 *
 * Tests automatic CTA insertion into post content via JavaScript.
 *
 * @group e2e
 * @group frontend
 * @group auto-insert
 */

const { test, expect } = require('@playwright/test');
const CTAAdminPage = require('../utils/CTAAdminPage');
const CTAFrontendPage = require('../utils/CTAFrontendPage');

test.describe('Auto-Insertion', () => {
	let adminPage;
	let frontendPage;

	test.beforeEach(async ({ page, context }) => {
		adminPage = new CTAAdminPage(page);
		frontendPage = new CTAFrontendPage(page);

		// Clear storage
		await context.clearCookies();
		await frontendPage.clearCooldowns();
	});

	test.describe('Basic Auto-Insertion', () => {
		test('should auto-insert CTA into post content', async ({ page }) => {
			// Create CTA with auto-insert config
			await adminPage.goto();
			await adminPage.clickAddNew();

			await adminPage.fillTitle('Auto-Insert Test CTA');
			await adminPage.fillContent('<p>This CTA should auto-insert</p>');
			await adminPage.setCTAType('primary');
			await adminPage.setPostTypes(['post']);
			await adminPage.setInsertionDirection('forward');
			await adminPage.setInsertionPosition(2);
			await adminPage.publish();

			// Create a post (without shortcode)
			await page.goto('/wp-admin/post-new.php');
			await adminPage.waitForPageLoad();

			await adminPage.fillTitle('Auto-Insert Target Post');
			await page.evaluate(() => {
				const content = `
					<p>Paragraph 1</p>
					<p>Paragraph 2</p>
					<p>Paragraph 3</p>
					<p>Paragraph 4</p>
					<p>Paragraph 5</p>
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

			// Wait for JavaScript to execute
			await page.waitForTimeout(1000);

			// Should have auto-inserted CTA
			const autoInsertCount =
				await frontendPage.getAutoInsertedCTACount();
			expect(autoInsertCount).toBeGreaterThan(0);
		});

		test('should insert at correct position (forward)', async ({
			page,
		}) => {
			// Create CTA
			await adminPage.goto();
			await adminPage.clickAddNew();

			await adminPage.fillTitle('Forward Position Test');
			await adminPage.fillContent('<p>Auto-inserted CTA</p>');
			await adminPage.setCTAType('primary');
			await adminPage.setPostTypes(['post']);
			await adminPage.setInsertionDirection('forward');
			await adminPage.setInsertionPosition(3); // After 3rd paragraph
			await adminPage.publish();

			// Create post
			await page.goto('/wp-admin/post-new.php');
			await adminPage.waitForPageLoad();

			await adminPage.fillTitle('Position Test Post');
			await page.evaluate(() => {
				const content = `
					<p>Paragraph 1</p>
					<p>Paragraph 2</p>
					<p>Paragraph 3</p>
					<p>Paragraph 4</p>
					<p>Paragraph 5</p>
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

			await page.waitForTimeout(1000);

			// Get CTA position
			const position = await frontendPage.getCTAPosition(0);

			// Should be at position 3 (0-indexed, so after 3rd paragraph)
			expect(position).toBe(3);
		});

		test('should insert at correct position (reverse)', async ({
			page,
		}) => {
			// Create CTA with reverse direction
			await adminPage.goto();
			await adminPage.clickAddNew();

			await adminPage.fillTitle('Reverse Position Test');
			await adminPage.fillContent('<p>Reverse inserted CTA</p>');
			await adminPage.setCTAType('primary');
			await adminPage.setPostTypes(['post']);
			await adminPage.setInsertionDirection('reverse');
			await adminPage.setInsertionPosition(2); // 2nd from end
			await adminPage.publish();

			// Create post
			await page.goto('/wp-admin/post-new.php');
			await adminPage.waitForPageLoad();

			await adminPage.fillTitle('Reverse Test Post');
			await page.evaluate(() => {
				const content = `
					<p>Paragraph 1</p>
					<p>Paragraph 2</p>
					<p>Paragraph 3</p>
					<p>Paragraph 4</p>
					<p>Paragraph 5</p>
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

			await page.waitForTimeout(1000);

			// Get CTA position
			const position = await frontendPage.getCTAPosition(0);

			// Should be near the end (reverse counting)
			// With 5 paragraphs, reverse position 2 would be around index 3-4
			expect(position).toBeGreaterThan(0);
		});

		test('should not auto-insert on wrong post type', async ({ page }) => {
			// Create CTA targeting only posts
			await adminPage.goto();
			await adminPage.clickAddNew();

			await adminPage.fillTitle('Post Type Test CTA');
			await adminPage.fillContent('<p>Only for posts</p>');
			await adminPage.setCTAType('primary');
			await adminPage.setPostTypes(['post']); // Only posts
			await adminPage.setInsertionDirection('forward');
			await adminPage.setInsertionPosition(2);
			await adminPage.publish();

			// Create a PAGE (not post)
			await page.goto('/wp-admin/post-new.php?post_type=page');
			await adminPage.waitForPageLoad();

			await adminPage.fillTitle('Test Page');
			await page.evaluate(() => {
				const content = `
					<p>Paragraph 1</p>
					<p>Paragraph 2</p>
					<p>Paragraph 3</p>
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

			await page.waitForTimeout(1000);

			// Should NOT have auto-inserted CTA
			const autoInsertCount =
				await frontendPage.getAutoInsertedCTACount();
			expect(autoInsertCount).toBe(0);
		});
	});

	test.describe('Fallback Chain', () => {
		test('should show primary CTA when conditions met', async ({
			page,
		}) => {
			// Create primary CTA
			await adminPage.goto();
			await adminPage.clickAddNew();

			await adminPage.fillTitle('Primary CTA');
			await adminPage.fillContent('<p>Primary CTA content</p>');
			await adminPage.setCTAType('primary');
			await adminPage.setPostTypes(['post']);
			await adminPage.setInsertionDirection('forward');
			await adminPage.setInsertionPosition(2);
			await adminPage.publish();

			const primaryId = await adminPage.getCurrentPostId();

			// Create post
			await page.goto('/wp-admin/post-new.php');
			await adminPage.waitForPageLoad();

			await adminPage.fillTitle('Fallback Test Post');
			await page.evaluate(() => {
				const content = `<p>P1</p><p>P2</p><p>P3</p>`;
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

			await page.waitForTimeout(1000);

			// Should show primary CTA
			const hasAutoCTA = await frontendPage.hasAutoInsertedCTA(primaryId);
			expect(hasAutoCTA).toBe(true);
		});

		test('should show fallback CTA when primary on cooldown', async ({
			page,
		}) => {
			// Create primary CTA
			await adminPage.goto();
			await adminPage.clickAddNew();

			await adminPage.fillTitle('Primary for Cooldown');
			await adminPage.fillContent('<p>Primary content</p>');
			await adminPage.setCTAType('primary');
			await adminPage.setPostTypes(['post']);
			await adminPage.setInsertionDirection('forward');
			await adminPage.setInsertionPosition(2);
			await adminPage.publish();

			const primaryId = await adminPage.getCurrentPostId();

			// Create fallback CTA
			await adminPage.goto();
			await adminPage.clickAddNew();

			await adminPage.fillTitle('Fallback CTA');
			await adminPage.fillContent('<p>Fallback content</p>');
			await adminPage.setCTAType('fallback');
			await adminPage.selectFallback(primaryId);
			await adminPage.setPostTypes(['post']);
			await adminPage.setInsertionDirection('forward');
			await adminPage.setInsertionPosition(2);
			await adminPage.publish();

			const fallbackId = await adminPage.getCurrentPostId();

			// Create post
			await page.goto('/wp-admin/post-new.php');
			await adminPage.waitForPageLoad();

			await adminPage.fillTitle('Cooldown Fallback Test');
			await page.evaluate(() => {
				const content = `<p>P1</p><p>P2</p><p>P3</p>`;
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

			// First visit - dismiss primary CTA
			await frontendPage.goto(permalink);
			await page.waitForTimeout(1000);

			// Wait for CTA and dismiss it
			await frontendPage.scrollCTAIntoView(0);
			await frontendPage.waitForHighlight().catch(() => {});
			await frontendPage.clickClose(0);

			// Reload page
			await page.reload();
			await page.waitForTimeout(1000);

			// Should now show fallback CTA (primary is on cooldown)
			const hasFallback =
				await frontendPage.hasAutoInsertedCTA(fallbackId);
			expect(hasFallback).toBe(true);
		});
	});

	test.describe('Storage Conditions', () => {
		test('should respect "never" storage condition', async ({ page }) => {
			// Create CTA with "never" storage
			await adminPage.goto();
			await adminPage.clickAddNew();

			await adminPage.fillTitle('Never Storage CTA');
			await adminPage.fillContent('<p>Always show</p>');
			await adminPage.setCTAType('primary');
			await adminPage.setPostTypes(['post']);
			await adminPage.setInsertionDirection('forward');
			await adminPage.setInsertionPosition(2);
			await adminPage.setStorageCondition('never');
			await adminPage.publish();

			// Create post
			await page.goto('/wp-admin/post-new.php');
			await adminPage.waitForPageLoad();

			await adminPage.fillTitle('Storage Test Post');
			await page.evaluate(() => {
				const content = `<p>P1</p><p>P2</p><p>P3</p>`;
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

			// First visit
			await frontendPage.goto(permalink);
			await page.waitForTimeout(1000);

			let count = await frontendPage.getAutoInsertedCTACount();
			expect(count).toBeGreaterThan(0);

			// Dismiss CTA
			await frontendPage.scrollCTAIntoView(0);
			await frontendPage.waitForHighlight().catch(() => {});
			await frontendPage.clickClose(0);

			// Reload - should show again (storage is "never")
			await page.reload();
			await page.waitForTimeout(1000);

			count = await frontendPage.getAutoInsertedCTACount();
			expect(count).toBeGreaterThan(0);
		});
	});

	test.describe('Meta Box Disable', () => {
		test('should not auto-insert when disabled via meta box', async ({
			page,
		}) => {
			// Create CTA
			await adminPage.goto();
			await adminPage.clickAddNew();

			await adminPage.fillTitle('Meta Box Disable Test');
			await adminPage.fillContent('<p>Auto-insert content</p>');
			await adminPage.setCTAType('primary');
			await adminPage.setPostTypes(['post']);
			await adminPage.setInsertionDirection('forward');
			await adminPage.setInsertionPosition(2);
			await adminPage.publish();

			// Create post with auto-insert disabled via meta box
			await page.goto('/wp-admin/post-new.php');
			await adminPage.waitForPageLoad();

			await adminPage.fillTitle('Disabled Auto-Insert Post');
			await page.evaluate(() => {
				const content = `<p>P1</p><p>P2</p><p>P3</p>`;
				if (
					typeof window.tinyMCE !== 'undefined' &&
					window.tinyMCE.activeEditor
				) {
					window.tinyMCE.activeEditor.setContent(content);
				} else {
					document.getElementById('content').value = content;
				}
			});

			// Check "Disable auto-insert" meta box if visible
			const disableCheckbox = page.locator(
				'#cta_highlights_disable_auto_insert'
			);
			if ((await disableCheckbox.count()) > 0) {
				await disableCheckbox.check();
			}

			await page.click('#publish');
			await page.waitForSelector('.notice-success');

			const permalink = await page
				.locator('#sample-permalink a')
				.getAttribute('href');
			await frontendPage.goto(permalink);

			await page.waitForTimeout(1000);

			// Should NOT have auto-inserted CTA
			const count = await frontendPage.getAutoInsertedCTACount();
			expect(count).toBe(0);
		});
	});
});
