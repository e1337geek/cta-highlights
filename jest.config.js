/**
 * Jest Configuration for CTA Highlights Plugin
 *
 * @see https://jestjs.io/docs/configuration
 */

module.exports = {
	// Test environment
	testEnvironment: 'jsdom',

	// Setup files
	setupFiles: [
		'<rootDir>/tests/_support/jest-setup.js',
	],

	// Setup after environment
	setupFilesAfterEnv: [
		'@wordpress/jest-console',
	],

	// Test match patterns
	testMatch: [
		'<rootDir>/tests/javascript/**/*.test.js',
		'<rootDir>/tests/javascript/**/*.spec.js',
	],

	// Module paths
	modulePaths: [
		'<rootDir>/assets/js',
		'<rootDir>/tests/javascript',
	],

	// Transform files
	transform: {
		'^.+\\.js$': 'babel-jest',
	},

	// Module name mapper (for imports)
	moduleNameMapper: {
		'^@/(.*)$': '<rootDir>/assets/js/$1',
		'^@tests/(.*)$': '<rootDir>/tests/javascript/$1',
	},

	// Coverage configuration
	collectCoverage: false, // Enable with --coverage flag
	collectCoverageFrom: [
		'assets/js/**/*.js',
		'!assets/js/**/*.min.js',
		'!**/node_modules/**',
		'!**/vendor/**',
		'!**/tests/**',
	],
	coverageDirectory: '<rootDir>/coverage/js',
	coverageReporters: [
		'html',
		'text',
		'text-summary',
		'lcov',
		'clover',
	],
	coverageThreshold: {
		global: {
			branches: 50,
			functions: 75,
			lines: 70,
			statements: 70,
		},
	},

	// Ignore patterns
	testPathIgnorePatterns: [
		'/node_modules/',
		'/vendor/',
		'/tests/e2e/',
	],

	// Watch mode ignore
	watchPathIgnorePatterns: [
		'/node_modules/',
		'/vendor/',
		'/coverage/',
	],

	// Verbose output
	verbose: true,

	// Clear mocks between tests
	clearMocks: true,

	// Restore mocks after each test
	restoreMocks: true,

	// Reset mocks before each test
	resetMocks: true,

	// Timeout for tests (5 seconds)
	testTimeout: 5000,

	// Error on deprecated APIs
	errorOnDeprecated: true,

	// Maximum worker pool size
	maxWorkers: '50%',

	// Notify on completion
	notify: false,

	// Bail on first failure (useful for CI)
	bail: false,
};
