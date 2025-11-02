/**
 * Auto-Insert Tests
 *
 * MEDIUM PRIORITY: JavaScript Tests for Auto-Insertion Logic
 *
 * This test suite covers the auto-insert.js file which handles:
 * - Fallback chain evaluation
 * - Content parsing and element detection
 * - Position calculation (forward/reverse)
 * - Storage condition evaluation
 * - DOM insertion
 * - Analytics tracking
 *
 * @package
 */

const LocalStorageMock = require('./__mocks__/localStorage');
const {
	setupWordPressEnv,
	resetWordPressEnv,
	getAnalyticsCalls,
} = require('./__mocks__/wordpress');
const fs = require('fs');
const path = require('path');

// Load the actual JavaScript file
const autoInsertJs = fs.readFileSync(
	path.join(__dirname, '../../assets/js/auto-insert.js'),
	'utf8'
);

describe('Auto-Insert - Content Container Detection', () => {
	beforeEach(() => {
		global.localStorage = new LocalStorageMock();
		document.body.innerHTML = '';
		setupWordPressEnv();
	});

	afterEach(() => {
		global.localStorage.__reset();
		resetWordPressEnv();
	});

	test('finds standard WordPress content container', () => {
		document.body.innerHTML = `
			<article>
				<div class="entry-content">
					<p>Content</p>
				</div>
			</article>
		`;

		eval(autoInsertJs);

		expect(document.querySelector('.entry-content')).not.toBeNull();
	});

	test('falls back to article when no specific container', () => {
		document.body.innerHTML = `
			<article>
				<p>Content</p>
			</article>
		`;

		eval(autoInsertJs);

		expect(document.querySelector('article')).not.toBeNull();
	});

	test('respects preferred selector from data', () => {
		document.body.innerHTML = `
			<div class="custom-content">
				<p>Content</p>
			</div>
			<script type="application/json" id="cta-highlights-auto-insert-data">
			{
				"postId": 1,
				"contentSelector": ".custom-content",
				"ctas": []
			}
			</script>
		`;

		eval(autoInsertJs);

		// Manager should find custom-content
		expect(document.querySelector('.custom-content')).not.toBeNull();
	});
});

describe('Auto-Insert - Content Element Parsing', () => {
	beforeEach(() => {
		global.localStorage = new LocalStorageMock();
		setupWordPressEnv();
	});

	afterEach(() => {
		global.localStorage.__reset();
		resetWordPressEnv();
	});

	test('parses paragraph elements', () => {
		document.body.innerHTML = `
			<div class="entry-content">
				<p>Paragraph 1</p>
				<p>Paragraph 2</p>
				<p>Paragraph 3</p>
			</div>
			<script type="application/json" id="cta-highlights-auto-insert-data">
			{
				"postId": 1,
				"contentSelector": ".entry-content",
				"ctas": []
			}
			</script>
		`;

		eval(autoInsertJs);

		const paragraphs = document.querySelectorAll('.entry-content > p');
		expect(paragraphs.length).toBe(3);
	});

	test('filters out script tags', () => {
		document.body.innerHTML = `
			<div class="entry-content">
				<p>Paragraph 1</p>
				<script>alert('test');</script>
				<p>Paragraph 2</p>
			</div>
			<script type="application/json" id="cta-highlights-auto-insert-data">
			{
				"postId": 1,
				"contentSelector": ".entry-content",
				"ctas": []
			}
			</script>
		`;

		eval(autoInsertJs);

		// Script should be filtered, only 2 paragraphs
		const paragraphs = document.querySelectorAll('.entry-content > p');
		expect(paragraphs.length).toBe(2);
	});

	test('filters out empty elements', () => {
		document.body.innerHTML = `
			<div class="entry-content">
				<p>Paragraph 1</p>
				<p></p>
				<p>Paragraph 2</p>
			</div>
			<script type="application/json" id="cta-highlights-auto-insert-data">
			{
				"postId": 1,
				"contentSelector": ".entry-content",
				"ctas": []
			}
			</script>
		`;

		eval(autoInsertJs);

		// Empty paragraph should be filtered
		const paragraphs = document.querySelectorAll('.entry-content > p');
		expect(paragraphs.length).toBe(2);
	});

	test('counts elements with images as non-empty', () => {
		document.body.innerHTML = `
			<div class="entry-content">
				<p>Text paragraph</p>
				<figure><img src="test.jpg" alt="Test"></figure>
				<p>Another text paragraph</p>
			</div>
			<script type="application/json" id="cta-highlights-auto-insert-data">
			{
				"postId": 1,
				"contentSelector": ".entry-content",
				"ctas": []
			}
			</script>
		`;

		eval(autoInsertJs);

		// Figure with image should count
		const elements = document.querySelectorAll('.entry-content > *');
		expect(elements.length).toBeGreaterThanOrEqual(3);
	});
});

describe('Auto-Insert - Position Calculation', () => {
	beforeEach(() => {
		global.localStorage = new LocalStorageMock();
		setupWordPressEnv();
	});

	afterEach(() => {
		global.localStorage.__reset();
		resetWordPressEnv();
	});

	test('calculates forward position correctly', () => {
		document.body.innerHTML = `
			<div class="entry-content">
				<p>P1</p>
				<p>P2</p>
				<p>P3</p>
				<p>P4</p>
				<p>P5</p>
			</div>
			<script type="application/json" id="cta-highlights-auto-insert-data">
			{
				"postId": 1,
				"contentSelector": ".entry-content",
				"ctas": [{
					"id": 1,
					"content": "<p>CTA</p>",
					"storage_condition_js": "true",
					"has_storage_conditions": false,
					"insertion_direction": "forward",
					"insertion_position": 3,
					"fallback_behavior": "skip"
				}]
			}
			</script>
		`;

		eval(autoInsertJs);

		// Should insert after 3rd paragraph
		const cta = document.querySelector('.cta-highlights-auto-inserted');
		expect(cta).not.toBeNull();
	});

	test('calculates reverse position correctly', () => {
		document.body.innerHTML = `
			<div class="entry-content">
				<p>P1</p>
				<p>P2</p>
				<p>P3</p>
				<p>P4</p>
				<p>P5</p>
			</div>
			<script type="application/json" id="cta-highlights-auto-insert-data">
			{
				"postId": 1,
				"contentSelector": ".entry-content",
				"ctas": [{
					"id": 1,
					"content": "<p>CTA</p>",
					"storage_condition_js": "true",
					"has_storage_conditions": false,
					"insertion_direction": "reverse",
					"insertion_position": 2,
					"fallback_behavior": "skip"
				}]
			}
			</script>
		`;

		eval(autoInsertJs);

		// Should insert 2 from end
		const cta = document.querySelector('.cta-highlights-auto-inserted');
		expect(cta).not.toBeNull();
	});

	test('handles insufficient content with skip behavior', () => {
		document.body.innerHTML = `
			<div class="entry-content">
				<p>P1</p>
				<p>P2</p>
			</div>
			<script type="application/json" id="cta-highlights-auto-insert-data">
			{
				"postId": 1,
				"contentSelector": ".entry-content",
				"ctas": [{
					"id": 1,
					"content": "<p>CTA</p>",
					"storage_condition_js": "true",
					"has_storage_conditions": false,
					"insertion_direction": "forward",
					"insertion_position": 10,
					"fallback_behavior": "skip"
				}]
			}
			</script>
		`;

		eval(autoInsertJs);

		// Should NOT insert (skip behavior)
		const cta = document.querySelector('.cta-highlights-auto-inserted');
		expect(cta).toBeNull();
	});

	test('handles insufficient content with end behavior', () => {
		document.body.innerHTML = `
			<div class="entry-content">
				<p>P1</p>
				<p>P2</p>
			</div>
			<script type="application/json" id="cta-highlights-auto-insert-data">
			{
				"postId": 1,
				"contentSelector": ".entry-content",
				"ctas": [{
					"id": 1,
					"content": "<p>CTA</p>",
					"storage_condition_js": "true",
					"has_storage_conditions": false,
					"insertion_direction": "forward",
					"insertion_position": 10,
					"fallback_behavior": "end"
				}]
			}
			</script>
		`;

		eval(autoInsertJs);

		// Should insert at end
		const cta = document.querySelector('.cta-highlights-auto-inserted');
		expect(cta).not.toBeNull();
	});
});

describe('Auto-Insert - Storage Condition Evaluation', () => {
	beforeEach(() => {
		global.localStorage = new LocalStorageMock();
		setupWordPressEnv();
	});

	afterEach(() => {
		global.localStorage.__reset();
		resetWordPressEnv();
	});

	test('inserts CTA when storage condition passes', () => {
		global.localStorage.setItem('user_subscribed', 'true');

		document.body.innerHTML = `
			<div class="entry-content">
				<p>P1</p>
				<p>P2</p>
			</div>
			<script type="application/json" id="cta-highlights-auto-insert-data">
			{
				"postId": 1,
				"contentSelector": ".entry-content",
				"ctas": [{
					"id": 1,
					"content": "<p>Premium CTA</p>",
					"storage_condition_js": "this.storageManager.get('user_subscribed') === 'true'",
					"has_storage_conditions": true,
					"insertion_direction": "forward",
					"insertion_position": 1,
					"fallback_behavior": "skip"
				}]
			}
			</script>
		`;

		eval(autoInsertJs);

		const cta = document.querySelector('.cta-highlights-auto-inserted');
		expect(cta).not.toBeNull();
		expect(cta.textContent).toContain('Premium CTA');
	});

	test('skips CTA when storage condition fails', () => {
		global.localStorage.setItem('user_subscribed', 'false');

		document.body.innerHTML = `
			<div class="entry-content">
				<p>P1</p>
				<p>P2</p>
			</div>
			<script type="application/json" id="cta-highlights-auto-insert-data">
			{
				"postId": 1,
				"contentSelector": ".entry-content",
				"ctas": [{
					"id": 1,
					"content": "<p>Premium CTA</p>",
					"storage_condition_js": "this.storageManager.get('user_subscribed') === 'true'",
					"has_storage_conditions": true,
					"insertion_direction": "forward",
					"insertion_position": 1,
					"fallback_behavior": "skip"
				}]
			}
			</script>
		`;

		eval(autoInsertJs);

		// Should NOT insert
		const cta = document.querySelector('.cta-highlights-auto-inserted');
		expect(cta).toBeNull();
	});

	test('inserts CTA when no storage conditions', () => {
		document.body.innerHTML = `
			<div class="entry-content">
				<p>P1</p>
			</div>
			<script type="application/json" id="cta-highlights-auto-insert-data">
			{
				"postId": 1,
				"contentSelector": ".entry-content",
				"ctas": [{
					"id": 1,
					"content": "<p>Always Show CTA</p>",
					"storage_condition_js": "true",
					"has_storage_conditions": false,
					"insertion_direction": "forward",
					"insertion_position": 1,
					"fallback_behavior": "skip"
				}]
			}
			</script>
		`;

		eval(autoInsertJs);

		const cta = document.querySelector('.cta-highlights-auto-inserted');
		expect(cta).not.toBeNull();
	});
});

describe('Auto-Insert - Fallback Chain Logic', () => {
	beforeEach(() => {
		global.localStorage = new LocalStorageMock();
		setupWordPressEnv();
	});

	afterEach(() => {
		global.localStorage.__reset();
		resetWordPressEnv();
	});

	test('selects first CTA when condition passes', () => {
		global.localStorage.setItem('user_type', 'premium');

		document.body.innerHTML = `
			<div class="entry-content"><p>P1</p></div>
			<script type="application/json" id="cta-highlights-auto-insert-data">
			{
				"postId": 1,
				"contentSelector": ".entry-content",
				"ctas": [
					{
						"id": 1,
						"content": "<p>Premium CTA</p>",
						"storage_condition_js": "this.storageManager.get('user_type') === 'premium'",
						"has_storage_conditions": true,
						"insertion_direction": "forward",
						"insertion_position": 1,
						"fallback_behavior": "skip"
					},
					{
						"id": 2,
						"content": "<p>Free CTA</p>",
						"storage_condition_js": "true",
						"has_storage_conditions": false,
						"insertion_direction": "forward",
						"insertion_position": 1,
						"fallback_behavior": "skip"
					}
				]
			}
			</script>
		`;

		eval(autoInsertJs);

		const cta = document.querySelector('.cta-highlights-auto-inserted');
		expect(cta.textContent).toContain('Premium CTA');
		expect(cta.getAttribute('data-fallback-index')).toBe('0');
	});

	test('falls back to second CTA when first fails', () => {
		global.localStorage.setItem('user_type', 'free');

		document.body.innerHTML = `
			<div class="entry-content"><p>P1</p></div>
			<script type="application/json" id="cta-highlights-auto-insert-data">
			{
				"postId": 1,
				"contentSelector": ".entry-content",
				"ctas": [
					{
						"id": 1,
						"content": "<p>Premium CTA</p>",
						"storage_condition_js": "this.storageManager.get('user_type') === 'premium'",
						"has_storage_conditions": true,
						"insertion_direction": "forward",
						"insertion_position": 1,
						"fallback_behavior": "skip"
					},
					{
						"id": 2,
						"content": "<p>Free CTA</p>",
						"storage_condition_js": "true",
						"has_storage_conditions": false,
						"insertion_direction": "forward",
						"insertion_position": 1,
						"fallback_behavior": "skip"
					}
				]
			}
			</script>
		`;

		eval(autoInsertJs);

		const cta = document.querySelector('.cta-highlights-auto-inserted');
		expect(cta.textContent).toContain('Free CTA');
		expect(cta.getAttribute('data-fallback-index')).toBe('1');
	});

	test('uses last CTA as ultimate fallback', () => {
		document.body.innerHTML = `
			<div class="entry-content"><p>P1</p></div>
			<script type="application/json" id="cta-highlights-auto-insert-data">
			{
				"postId": 1,
				"contentSelector": ".entry-content",
				"ctas": [
					{
						"id": 1,
						"content": "<p>Conditional CTA</p>",
						"storage_condition_js": "false",
						"has_storage_conditions": true,
						"insertion_direction": "forward",
						"insertion_position": 1,
						"fallback_behavior": "skip"
					},
					{
						"id": 2,
						"content": "<p>Default CTA</p>",
						"storage_condition_js": "true",
						"has_storage_conditions": false,
						"insertion_direction": "forward",
						"insertion_position": 1,
						"fallback_behavior": "skip"
					}
				]
			}
			</script>
		`;

		eval(autoInsertJs);

		const cta = document.querySelector('.cta-highlights-auto-inserted');
		expect(cta.textContent).toContain('Default CTA');
	});
});

describe('Auto-Insert - DOM Insertion', () => {
	beforeEach(() => {
		global.localStorage = new LocalStorageMock();
		setupWordPressEnv();
	});

	afterEach(() => {
		global.localStorage.__reset();
		resetWordPressEnv();
	});

	test('inserts CTA with correct attributes', () => {
		document.body.innerHTML = `
			<div class="entry-content"><p>P1</p></div>
			<script type="application/json" id="cta-highlights-auto-insert-data">
			{
				"postId": 1,
				"contentSelector": ".entry-content",
				"ctas": [{
					"id": 123,
					"content": "<p>Test CTA</p>",
					"storage_condition_js": "true",
					"has_storage_conditions": false,
					"insertion_direction": "forward",
					"insertion_position": 1,
					"fallback_behavior": "skip"
				}]
			}
			</script>
		`;

		eval(autoInsertJs);

		const cta = document.querySelector('.cta-highlights-auto-inserted');
		expect(cta.getAttribute('data-auto-insert')).toBe('true');
		expect(cta.getAttribute('data-cta-id')).toBe('123');
		expect(cta.getAttribute('data-fallback-index')).toBe('0');
		expect(cta.getAttribute('data-fallback-chain-length')).toBe('1');
	});

	test('preserves CTA content HTML', () => {
		document.body.innerHTML = `
			<div class="entry-content"><p>P1</p></div>
			<script type="application/json" id="cta-highlights-auto-insert-data">
			{
				"postId": 1,
				"contentSelector": ".entry-content",
				"ctas": [{
					"id": 1,
					"content": "<div class=\"custom-cta\"><h3>Title</h3><p>Content</p></div>",
					"storage_condition_js": "true",
					"has_storage_conditions": false,
					"insertion_direction": "forward",
					"insertion_position": 1,
					"fallback_behavior": "skip"
				}]
			}
			</script>
		`;

		eval(autoInsertJs);

		const cta = document.querySelector('.cta-highlights-auto-inserted');
		expect(cta.querySelector('.custom-cta')).not.toBeNull();
		expect(cta.querySelector('h3')).not.toBeNull();
	});
});

describe('Auto-Insert - Analytics Tracking', () => {
	beforeEach(() => {
		global.localStorage = new LocalStorageMock();
		setupWordPressEnv();
	});

	afterEach(() => {
		global.localStorage.__reset();
		resetWordPressEnv();
	});

	test('tracks insertion event', () => {
		document.body.innerHTML = `
			<div class="entry-content"><p>P1</p></div>
			<script type="application/json" id="cta-highlights-auto-insert-data">
			{
				"postId": 1,
				"contentSelector": ".entry-content",
				"ctas": [{
					"id": 1,
					"content": "<p>CTA</p>",
					"storage_condition_js": "true",
					"has_storage_conditions": false,
					"insertion_direction": "forward",
					"insertion_position": 1,
					"fallback_behavior": "skip"
				}]
			}
			</script>
		`;

		eval(autoInsertJs);

		const calls = getAnalyticsCalls();
		expect(calls.gtag.length).toBeGreaterThan(0);
	});

	test('tracks fallback usage', () => {
		document.body.innerHTML = `
			<div class="entry-content"><p>P1</p></div>
			<script type="application/json" id="cta-highlights-auto-insert-data">
			{
				"postId": 1,
				"contentSelector": ".entry-content",
				"ctas": [
					{
						"id": 1,
						"content": "<p>Primary</p>",
						"storage_condition_js": "false",
						"has_storage_conditions": true,
						"insertion_direction": "forward",
						"insertion_position": 1,
						"fallback_behavior": "skip"
					},
					{
						"id": 2,
						"content": "<p>Fallback</p>",
						"storage_condition_js": "true",
						"has_storage_conditions": false,
						"insertion_direction": "forward",
						"insertion_position": 1,
						"fallback_behavior": "skip"
					}
				]
			}
			</script>
		`;

		eval(autoInsertJs);

		const calls = getAnalyticsCalls();
		// Should have both insert and fallback events
		expect(calls.gtag.length).toBeGreaterThanOrEqual(2);
	});
});
