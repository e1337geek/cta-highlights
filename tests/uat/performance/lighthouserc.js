/**
 * Lighthouse CI Configuration
 *
 * Configuration for running Lighthouse audits in CI/CD pipeline.
 *
 * @see https://github.com/GoogleChrome/lighthouse-ci/blob/main/docs/configuration.md
 */

module.exports = {
	ci: {
		collect: {
			// URLs to audit
			url: [
				'http://localhost:8888', // Homepage
				'http://localhost:8888/sample-page/', // Page without CTA
				'http://localhost:8888/test-post/', // Post with CTA shortcode
				'http://localhost:8888/auto-insert-test/', // Post with auto-insert CTA
			],

			// Number of runs per URL (median of 3 recommended)
			numberOfRuns: 3,

			// Chrome flags
			settings: {
				// Preset: 'desktop' or 'mobile'
				preset: 'desktop',

				// Or custom settings
				// throttling: {
				// 	rttMs: 40,
				// 	throughputKbps: 10240,
				// 	cpuSlowdownMultiplier: 1,
				// },

				// Screen emulation
				// screenEmulation: {
				// 	mobile: false,
				// 	width: 1350,
				// 	height: 940,
				// 	deviceScaleFactor: 1,
				// 	disabled: false,
				// },

				// Form factor
				formFactor: 'desktop',

				// Only run specific audits
				// onlyAudits: [
				// 	'first-contentful-paint',
				// 	'largest-contentful-paint',
				// 	'total-blocking-time',
				// 	'cumulative-layout-shift',
				// ],

				// Skip audits
				skipAudits: [
					'uses-http2', // May not be available in local env
					'redirects-http', // Local env
				],
			},

			// Start local server (if needed)
			// startServerCommand: 'npm run env:start',
			// startServerReadyPattern: 'WordPress is ready',
			// startServerReadyTimeout: 120000,
		},

		assert: {
			// Performance budgets
			preset: 'lighthouse:recommended',

			// Or custom assertions
			assertions: {
				// Categories
				'categories:performance': ['error', { minScore: 0.9 }],
				'categories:accessibility': ['warn', { minScore: 0.95 }],
				'categories:best-practices': ['warn', { minScore: 0.9 }],
				'categories:seo': ['warn', { minScore: 0.9 }],

				// Core Web Vitals
				'first-contentful-paint': ['warn', { maxNumericValue: 1500 }],
				'largest-contentful-paint': ['error', { maxNumericValue: 2500 }],
				'cumulative-layout-shift': ['error', { maxNumericValue: 0.1 }],
				'total-blocking-time': ['warn', { maxNumericValue: 200 }],
				'speed-index': ['warn', { maxNumericValue: 3000 }],
				'interactive': ['warn', { maxNumericValue: 3500 }],

				// Resource sizes (in bytes)
				'resource-summary:script:size': ['warn', { maxNumericValue: 307200 }], // 300 KB
				'resource-summary:stylesheet:size': ['warn', { maxNumericValue: 102400 }], // 100 KB
				'resource-summary:document:size': ['warn', { maxNumericValue: 51200 }], // 50 KB
				'resource-summary:total:size': ['warn', { maxNumericValue: 1536000 }], // 1.5 MB

				// Best Practices
				'uses-optimized-images': 'off', // May vary by content
				'modern-image-formats': 'off', // May vary by content
				'offscreen-images': 'off', // May vary by content
			},
		},

		upload: {
			// Upload results to Lighthouse CI server (optional)
			// target: 'lhci',
			// serverBaseUrl: 'https://your-lhci-server.com',
			// token: process.env.LHCI_TOKEN,

			// Or upload to temporary public storage
			target: 'temporary-public-storage',

			// Report Generator
			// outputDir: './tests/uat/performance/lighthouse-reports',
			// reportFilenamePattern: '%%PATHNAME%%-%%DATETIME%%-report.%%EXTENSION%%',
		},

		// Server configuration (if running own LHCI server)
		// server: {
		// 	port: 9001,
		// 	storage: {
		// 		storageMethod: 'sql',
		// 		sqlDatabasePath: './lhci-database.sql',
		// 	},
		// },
	},
};
