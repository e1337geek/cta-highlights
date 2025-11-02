module.exports = {
	root: true,
	extends: [
		'plugin:@wordpress/eslint-plugin/recommended',
	],
	env: {
		browser: true,
		es6: true,
		node: true,
	},
	parserOptions: {
		ecmaVersion: 2020,
		sourceType: 'module',
	},
	globals: {
		// WordPress globals
		gtag: 'readonly',
		ga: 'readonly',
		tinyMCE: 'readonly',
		wp: 'readonly',
	},
	rules: {
		// Relax rules for this project
		'no-console': 'off', // Allow console for debugging
		'jsdoc/require-jsdoc': 'off',
		'jsdoc/require-param-type': 'off', // JSDoc types are optional in JS
		'jsdoc/check-tag-names': ['error', { definedTags: ['group'] }], // Allow @group tag
		'@wordpress/no-global-active-element': 'warn', // Warning instead of error
		'no-lonely-if': 'off',
	},
	overrides: [
		// Test files
		{
			files: ['tests/**/*.js', '**/*.test.js', '**/*.spec.js'],
			env: {
				jest: true,
			},
			globals: {
				page: 'readonly',
				browser: 'readonly',
				context: 'readonly',
				jestPuppeteer: 'readonly',
			},
			rules: {
				'no-eval': 'off', // Allow eval in tests for testing eval scenarios
				'no-unused-vars': 'off', // Test setup variables may appear unused
				'@wordpress/no-unused-vars-before-return': 'off',
				'@wordpress/no-global-active-element': 'off', // Allow in tests for focus testing
			},
		},
		// E2E test files (Playwright)
		{
			files: ['tests/e2e/**/*.js'],
			globals: {
				expect: 'readonly',
				test: 'readonly',
			},
		},
	],
};
