/**
 * Page Object Model for CTA Admin Interface
 *
 * Encapsulates interactions with WordPress admin for CTA Highlights.
 */

class CTAAdminPage {
	/**
	 * Constructor
	 *
	 * @param {import('@playwright/test').Page} page - Playwright page
	 */
	constructor(page) {
		this.page = page;

		// Selectors
		this.selectors = {
			// Admin navigation
			adminBar: '#wpadminbar',
			adminMenu: '#adminmenu',
			ctaMenuItem: 'a[href="edit.php?post_type=cta_highlight"]',

			// CTA list page
			addNewButton: '.page-title-action',
			ctaTable: '.wp-list-table',
			ctaRow: '.type-cta_highlight',
			bulkActions: '#bulk-action-selector-top',
			applyButton: '#doaction',

			// CTA edit page
			titleInput: '#title',
			contentArea: '#content',
			blockEditor: '.block-editor',
			publishButton: '#publish',
			saveDraftButton: '#save-post',
			trashLink: '#delete-action a.submitdelete',

			// Meta boxes
			templateSelect: '#cta_highlights_template',
			ctaTypePrimary:
				'input[name="cta_highlights_type"][value="primary"]',
			ctaTypeFallback:
				'input[name="cta_highlights_type"][value="fallback"]',
			fallbackSelect: '#cta_highlights_fallback_id',
			postTypesCheckbox: 'input[name="cta_highlights_post_types[]"]',
			categoriesCheckbox: 'input[name="cta_highlights_categories[]"]',
			insertionDirection: '#cta_highlights_insertion_direction',
			insertionPosition: '#cta_highlights_insertion_position',
			storageCondition: '#cta_highlights_storage_condition',

			// Notices
			successNotice: '.notice-success',
			errorNotice: '.notice-error',
			warningNotice: '.notice-warning',
		};
	}

	/**
	 * Navigate to CTA list page
	 */
	async goto() {
		await this.page.goto('/wp-admin/edit.php?post_type=cta_highlight');
		await this.waitForPageLoad();
	}

	/**
	 * Wait for WordPress admin page to load
	 */
	async waitForPageLoad() {
		// Wait for admin bar to be attached (don't require visible, as it may be hidden on some pages)
		await this.page.waitForSelector(this.selectors.adminBar, {
			state: 'attached',
		});
		// Wait for page to be loaded
		await this.page.waitForLoadState('domcontentloaded');
		// Wait for any active spinners to finish
		await this.page
			.waitForSelector('.spinner.is-active', {
				state: 'hidden',
				timeout: 5000,
			})
			.catch(() => {});
	}

	/**
	 * Click "Add New" button
	 */
	async clickAddNew() {
		await this.page.click(this.selectors.addNewButton);
		await this.waitForPageLoad();
	}

	/**
	 * Fill in CTA title
	 *
	 * @param {string} title - CTA title
	 */
	async fillTitle(title) {
		const isBlockEditor =
			(await this.page.locator(this.selectors.blockEditor).count()) > 0;

		if (isBlockEditor) {
			// Block Editor - use the aria-label selector
			const titleSelector = '.editor-post-title__input, [aria-label="Add title"]';
			await this.page.click(titleSelector);
			await this.page.fill(titleSelector, title);
		} else {
			// Classic Editor
			await this.page.fill(this.selectors.titleInput, title);
		}
	}

	/**
	 * Fill in CTA content
	 *
	 * @param {string} content - CTA content (HTML)
	 */
	async fillContent(content) {
		const isBlockEditor =
			(await this.page.locator(this.selectors.blockEditor).count()) > 0;

		if (isBlockEditor) {
			// Block Editor
			await this.page.click('.block-editor-writing-flow');
			await this.page.keyboard.type(content);
		} else {
			// Classic Editor - set via TinyMCE or textarea
			await this.page.evaluate((htmlContent) => {
				if (
					typeof window.tinyMCE !== 'undefined' &&
					window.tinyMCE.activeEditor
				) {
					window.tinyMCE.activeEditor.setContent(htmlContent);
				} else {
					document.getElementById('content').value = htmlContent;
				}
			}, content);
		}
	}

	/**
	 * Select template
	 *
	 * @param {string} template - Template name
	 */
	async selectTemplate(template) {
		if (
			(await this.page.locator(this.selectors.templateSelect).count()) > 0
		) {
			await this.page.selectOption(
				this.selectors.templateSelect,
				template
			);
		}
	}

	/**
	 * Set CTA type
	 *
	 * @param {string} type - 'primary' or 'fallback'
	 */
	async setCTAType(type) {
		const selector =
			type === 'primary'
				? this.selectors.ctaTypePrimary
				: this.selectors.ctaTypeFallback;

		if ((await this.page.locator(selector).count()) > 0) {
			await this.page.check(selector);
		}
	}

	/**
	 * Select fallback CTA
	 *
	 * @param {string} fallbackId - Fallback CTA ID
	 */
	async selectFallback(fallbackId) {
		if (
			(await this.page.locator(this.selectors.fallbackSelect).count()) > 0
		) {
			await this.page.selectOption(
				this.selectors.fallbackSelect,
				fallbackId
			);
		}
	}

	/**
	 * Set post types
	 *
	 * @param {Array<string>} postTypes - Post type slugs
	 */
	async setPostTypes(postTypes) {
		// First uncheck all
		const checkboxes = this.page.locator(this.selectors.postTypesCheckbox);
		const count = await checkboxes.count();

		for (let i = 0; i < count; i++) {
			await checkboxes.nth(i).uncheck();
		}

		// Then check the specified ones
		for (const postType of postTypes) {
			await this.page.check(
				`input[name="cta_highlights_post_types[]"][value="${postType}"]`
			);
		}
	}

	/**
	 * Set insertion direction
	 *
	 * @param {string} direction - 'forward' or 'reverse'
	 */
	async setInsertionDirection(direction) {
		if (
			(await this.page
				.locator(this.selectors.insertionDirection)
				.count()) > 0
		) {
			await this.page.selectOption(
				this.selectors.insertionDirection,
				direction
			);
		}
	}

	/**
	 * Set insertion position
	 *
	 * @param {number} position - Position number
	 */
	async setInsertionPosition(position) {
		if (
			(await this.page
				.locator(this.selectors.insertionPosition)
				.count()) > 0
		) {
			await this.page.fill(
				this.selectors.insertionPosition,
				position.toString()
			);
		}
	}

	/**
	 * Set storage condition
	 *
	 * @param {string} condition - Storage condition
	 */
	async setStorageCondition(condition) {
		if (
			(await this.page.locator(this.selectors.storageCondition).count()) >
			0
		) {
			await this.page.selectOption(
				this.selectors.storageCondition,
				condition
			);
		}
	}

	/**
	 * Publish CTA
	 */
	async publish() {
		const isBlockEditor =
			(await this.page.locator(this.selectors.blockEditor).count()) > 0;

		if (isBlockEditor) {
			// Block Editor - use role-based selector
			const publishButton = this.page.getByRole('button', { name: /^Publish/i });
			await publishButton.click();

			// Check if pre-publish panel appears
			await this.page.waitForTimeout(500);
			const confirmPublish = this.page.getByRole('button', { name: /^Publish/i }).last();
			const isVisible = await confirmPublish.isVisible().catch(() => false);
			if (isVisible) {
				await confirmPublish.click();
			}

			// Wait for publish to complete
			await this.page.waitForTimeout(1000);
		} else {
			// Classic Editor
			await this.page.click(this.selectors.publishButton);
			await this.waitForNotice('success');
		}
	}

	/**
	 * Save draft
	 */
	async saveDraft() {
		await this.page.click(this.selectors.saveDraftButton);
		await this.waitForNotice('success');
	}

	/**
	 * Move to trash
	 */
	async moveToTrash() {
		await this.page.click(this.selectors.trashLink);
		await this.page.waitForURL(/edit\.php/, { timeout: 10000 });
	}

	/**
	 * Wait for notice
	 *
	 * @param {string} type - 'success', 'error', or 'warning'
	 */
	async waitForNotice(type = 'success') {
		const selector = this.selectors[`${type}Notice`];
		await this.page.waitForSelector(selector, { timeout: 10000 });
	}

	/**
	 * Get notice message
	 *
	 * @param {string} type - 'success', 'error', or 'warning'
	 * @return {Promise<string>} - Notice message text
	 */
	async getNoticeMessage(type = 'success') {
		const selector = this.selectors[`${type}Notice`];
		return await this.page.locator(selector).textContent();
	}

	/**
	 * Get current post ID from URL
	 *
	 * @return {Promise<string|null>} - Post ID
	 */
	async getCurrentPostId() {
		const url = this.page.url();
		const match = url.match(/post=(\d+)/);
		return match ? match[1] : null;
	}

	/**
	 * Get post permalink after publishing
	 *
	 * @return {Promise<string>} - Post permalink URL
	 */
	async getPermalink() {
		const isBlockEditor =
			(await this.page.locator(this.selectors.blockEditor).count()) > 0;

		if (isBlockEditor) {
			// Block Editor - get from sample permalink or editor header
			const samplePermalink = await this.page
				.locator('#sample-permalink a')
				.getAttribute('href')
				.catch(() => null);

			if (samplePermalink) {
				return samplePermalink;
			}

			// Fallback: construct from post ID
			const postId = await this.getCurrentPostId();
			return `${this.page.url().split('/wp-admin')[0]}/?p=${postId}`;
		} else {
			// Classic Editor - get from permalink field or construct from post ID
			const permalinkInput = await this.page
				.locator('#sample-permalink a')
				.getAttribute('href')
				.catch(() => null);

			if (permalinkInput) {
				return permalinkInput;
			}

			// After publishing, get from the Permalink field
			const permalinkField = await this.page
				.locator('.edit-slug-box a')
				.getAttribute('href')
				.catch(() => null);

			if (permalinkField) {
				return permalinkField;
			}

			// Fallback: construct from post ID
			const postId = await this.getCurrentPostId();
			const baseUrl = this.page.url().split('/wp-admin')[0];
			return `${baseUrl}/?p=${postId}`;
		}
	}

	/**
	 * Navigate to edit CTA page
	 *
	 * @param {string} ctaId - CTA ID
	 */
	async editCTA(ctaId) {
		await this.page.goto(`/wp-admin/post.php?post=${ctaId}&action=edit`);
		await this.waitForPageLoad();
	}

	/**
	 * Duplicate CTA (via row actions)
	 *
	 * @param {string} ctaId - CTA ID to duplicate
	 */
	async duplicateCTA(ctaId) {
		// This would hover over row and click duplicate link
		// Implementation depends on if duplicate functionality exists
		const row = this.page.locator(`#post-${ctaId}`);
		await row.hover();
		await row.locator('.duplicate-post').click();
		await this.waitForPageLoad();
	}

	/**
	 * Search for CTA by title
	 *
	 * @param {string} title - CTA title to search
	 */
	async searchCTA(title) {
		await this.page.fill('#post-search-input', title);
		await this.page.click('#search-submit');
		await this.waitForPageLoad();
	}

	/**
	 * Get CTA count from list page
	 *
	 * @return {Promise<number>} - Number of CTAs in table
	 */
	async getCTACount() {
		return await this.page.locator(this.selectors.ctaRow).count();
	}

	/**
	 * Check if CTA exists in list
	 *
	 * @param {string} ctaId - CTA ID
	 * @return {Promise<boolean>} - True if CTA exists
	 */
	async ctaExists(ctaId) {
		return (await this.page.locator(`#post-${ctaId}`).count()) > 0;
	}
}

module.exports = CTAAdminPage;
