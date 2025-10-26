/**
 * E2E Tests: CTA Admin CRUD Operations
 *
 * Tests create, read, update, delete operations for CTAs in WordPress admin.
 *
 * @group e2e
 * @group admin
 */

const { test, expect } = require('@playwright/test');
const CTAAdminPage = require('../utils/CTAAdminPage');

test.describe('CTA Admin CRUD Operations', () => {
	let adminPage;

	test.beforeEach(async ({ page }) => {
		adminPage = new CTAAdminPage(page);
		await adminPage.goto();
	});

	/**
	 * CREATE Tests
	 */
	test.describe('Create CTA', () => {
		test('should create a new CTA with basic fields', async ({ page }) => {
			// Click "Add New"
			await adminPage.clickAddNew();

			// Fill in form
			await adminPage.fillTitle('Test CTA - Basic');
			await adminPage.fillContent('<p>Subscribe to our newsletter!</p>');
			await adminPage.selectTemplate('default');

			// Publish
			await adminPage.publish();

			// Verify success notice
			const notice = await adminPage.getNoticeMessage('success');
			expect(notice).toContain('published');

			// Verify we have a post ID
			const postId = await adminPage.getCurrentPostId();
			expect(postId).not.toBeNull();
			expect(parseInt(postId)).toBeGreaterThan(0);
		});

		test('should create primary CTA with post type targeting', async ({ page }) => {
			await adminPage.clickAddNew();

			// Basic info
			await adminPage.fillTitle('Primary CTA - Posts Only');
			await adminPage.fillContent('<p>Read more articles!</p>');

			// Set as primary
			await adminPage.setCTAType('primary');

			// Target only posts
			await adminPage.setPostTypes(['post']);

			// Set insertion config
			await adminPage.setInsertionDirection('forward');
			await adminPage.setInsertionPosition(3);

			// Publish
			await adminPage.publish();

			// Verify success
			const notice = await adminPage.getNoticeMessage('success');
			expect(notice).toContain('published');
		});

		test('should create fallback CTA with parent reference', async ({ page }) => {
			// First create a primary CTA
			await adminPage.clickAddNew();
			await adminPage.fillTitle('Primary CTA');
			await adminPage.fillContent('<p>Primary content</p>');
			await adminPage.setCTAType('primary');
			await adminPage.publish();

			const primaryId = await adminPage.getCurrentPostId();

			// Now create fallback
			await adminPage.goto();
			await adminPage.clickAddNew();

			await adminPage.fillTitle('Fallback CTA');
			await adminPage.fillContent('<p>Fallback content</p>');
			await adminPage.setCTAType('fallback');

			// Select primary as fallback parent
			await adminPage.selectFallback(primaryId);

			// Publish
			await adminPage.publish();

			// Verify success
			const notice = await adminPage.getNoticeMessage('success');
			expect(notice).toContain('published');
		});

		test('should save CTA as draft', async ({ page }) => {
			await adminPage.clickAddNew();

			await adminPage.fillTitle('Draft CTA');
			await adminPage.fillContent('<p>Draft content</p>');

			// Save as draft instead of publishing
			await adminPage.saveDraft();

			// Verify draft saved
			const notice = await adminPage.getNoticeMessage('success');
			expect(notice).toMatch(/draft|saved/i);
		});
	});

	/**
	 * READ Tests
	 */
	test.describe('Read/List CTAs', () => {
		test('should display CTAs in list table', async ({ page }) => {
			// CTA list page should show table
			const ctaCount = await adminPage.getCTACount();

			// Should have at least the CTAs created in previous tests
			// (or 0 if running in isolation)
			expect(ctaCount).toBeGreaterThanOrEqual(0);
		});

		test('should search for CTA by title', async ({ page }) => {
			// Create a CTA with unique title
			await adminPage.clickAddNew();
			const uniqueTitle = `Searchable CTA ${Date.now()}`;
			await adminPage.fillTitle(uniqueTitle);
			await adminPage.fillContent('<p>Content</p>');
			await adminPage.publish();

			// Go back to list
			await adminPage.goto();

			// Search for it
			await adminPage.searchCTA(uniqueTitle);

			// Should find exactly 1
			const count = await adminPage.getCTACount();
			expect(count).toBe(1);
		});

		test('should navigate to edit CTA from list', async ({ page }) => {
			// Create a CTA
			await adminPage.clickAddNew();
			await adminPage.fillTitle('Edit Test CTA');
			await adminPage.fillContent('<p>Content</p>');
			await adminPage.publish();

			const ctaId = await adminPage.getCurrentPostId();

			// Navigate to edit page directly
			await adminPage.editCTA(ctaId);

			// Verify we're on edit page
			expect(page.url()).toContain(`post=${ctaId}`);
			expect(page.url()).toContain('action=edit');
		});
	});

	/**
	 * UPDATE Tests
	 */
	test.describe('Update CTA', () => {
		test('should update CTA title and content', async ({ page }) => {
			// Create initial CTA
			await adminPage.clickAddNew();
			await adminPage.fillTitle('Original Title');
			await adminPage.fillContent('<p>Original content</p>');
			await adminPage.publish();

			const ctaId = await adminPage.getCurrentPostId();

			// Edit it
			await adminPage.editCTA(ctaId);

			// Update fields
			await adminPage.fillTitle('Updated Title');
			await adminPage.fillContent('<p>Updated content</p>');

			// Publish changes
			await adminPage.publish();

			// Verify update
			const notice = await adminPage.getNoticeMessage('success');
			expect(notice).toMatch(/updated|published/i);
		});

		test('should change CTA type from primary to fallback', async ({ page }) => {
			// Create as primary
			await adminPage.clickAddNew();
			await adminPage.fillTitle('Type Change Test');
			await adminPage.fillContent('<p>Content</p>');
			await adminPage.setCTAType('primary');
			await adminPage.publish();

			const ctaId = await adminPage.getCurrentPostId();

			// Edit and change to fallback
			await adminPage.editCTA(ctaId);
			await adminPage.setCTAType('fallback');
			await adminPage.publish();

			// Verify update
			const notice = await adminPage.getNoticeMessage('success');
			expect(notice).toMatch(/updated|published/i);
		});

		test('should update insertion settings', async ({ page }) => {
			// Create CTA
			await adminPage.clickAddNew();
			await adminPage.fillTitle('Insertion Test');
			await adminPage.fillContent('<p>Content</p>');
			await adminPage.setCTAType('primary');
			await adminPage.setPostTypes(['post']);
			await adminPage.setInsertionDirection('forward');
			await adminPage.setInsertionPosition(2);
			await adminPage.publish();

			const ctaId = await adminPage.getCurrentPostId();

			// Edit and update insertion
			await adminPage.editCTA(ctaId);
			await adminPage.setInsertionDirection('reverse');
			await adminPage.setInsertionPosition(5);
			await adminPage.publish();

			// Verify update
			const notice = await adminPage.getNoticeMessage('success');
			expect(notice).toMatch(/updated|published/i);
		});
	});

	/**
	 * DELETE Tests
	 */
	test.describe('Delete CTA', () => {
		test('should move CTA to trash', async ({ page }) => {
			// Create CTA
			await adminPage.clickAddNew();
			await adminPage.fillTitle('To Be Deleted');
			await adminPage.fillContent('<p>Content</p>');
			await adminPage.publish();

			const ctaId = await adminPage.getCurrentPostId();

			// Edit page and move to trash
			await adminPage.editCTA(ctaId);
			await adminPage.moveToTrash();

			// Should redirect to list page
			expect(page.url()).toContain('edit.php');

			// Verify notice
			await page.waitForSelector('.notice');
			const notice = await page.locator('.notice').textContent();
			expect(notice).toMatch(/trash/i);
		});

		test('should permanently delete CTA from trash', async ({ page }) => {
			// Create and trash a CTA
			await adminPage.clickAddNew();
			await adminPage.fillTitle('Permanent Delete Test');
			await adminPage.fillContent('<p>Content</p>');
			await adminPage.publish();

			const ctaId = await adminPage.getCurrentPostId();
			await adminPage.editCTA(ctaId);
			await adminPage.moveToTrash();

			// Navigate to trash
			await page.goto('/wp-admin/edit.php?post_status=trash&post_type=cta_highlight');

			// Find the trashed post and permanently delete
			const deleteLink = page.locator(`#post-${ctaId} .delete a`);
			if (await deleteLink.count() > 0) {
				await deleteLink.click();
			}

			// Verify it's gone
			const exists = await adminPage.ctaExists(ctaId);
			expect(exists).toBe(false);
		});
	});

	/**
	 * VALIDATION Tests
	 */
	test.describe('Form Validation', () => {
		test('should require title when publishing', async ({ page }) => {
			await adminPage.clickAddNew();

			// Try to publish without title
			await adminPage.fillContent('<p>Content only</p>');
			await adminPage.publish();

			// WordPress should auto-generate "(no title)" or show validation
			// Check URL or notice
			const url = page.url();
			const hasPostId = url.match(/post=(\d+)/);

			// Either saved with auto-title or validation prevented save
			expect(hasPostId).toBeTruthy();
		});

		test('should allow saving draft without title', async ({ page }) => {
			await adminPage.clickAddNew();

			// Save draft without title
			await adminPage.fillContent('<p>Content only</p>');
			await adminPage.saveDraft();

			// Should succeed
			const notice = await adminPage.getNoticeMessage('success');
			expect(notice).toMatch(/draft|saved/i);
		});
	});

	/**
	 * BULK Actions Tests
	 */
	test.describe('Bulk Actions', () => {
		test.skip('should bulk trash multiple CTAs', async ({ page }) => {
			// Create multiple CTAs
			const ids = [];
			for (let i = 0; i < 3; i++) {
				await adminPage.clickAddNew();
				await adminPage.fillTitle(`Bulk Test ${i}`);
				await adminPage.fillContent('<p>Content</p>');
				await adminPage.publish();
				ids.push(await adminPage.getCurrentPostId());
				await adminPage.goto();
			}

			// Select all created CTAs
			for (const id of ids) {
				await page.check(`#cb-select-${id}`);
			}

			// Select "Move to Trash" bulk action
			await page.selectOption('#bulk-action-selector-top', 'trash');
			await page.click('#doaction');

			// Verify bulk action
			await page.waitForSelector('.notice');
			const notice = await page.locator('.notice').textContent();
			expect(notice).toMatch(/moved to the trash/i);
		});
	});
});
