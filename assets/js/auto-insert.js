/**
 * CTA Highlights - Auto-Insertion
 * Version: 1.0.0
 *
 * Handles client-side evaluation of storage conditions for auto-inserted CTAs
 * Extends the base StorageManager to support getting values and evaluating conditions
 */

(function() {
	'use strict';

	/**
	 * Auto-Insert Manager
	 * Evaluates storage conditions and shows/hides auto-inserted CTAs
	 */
	class AutoInsertManager {
		constructor() {
			this.storageManager = this.createStorageManager();
		}

		/**
		 * Create StorageManager instance
		 * Extends the base StorageManager with additional methods
		 */
		createStorageManager() {
			const manager = {
				/**
				 * Get a value from localStorage or cookie
				 *
				 * @param {string} key Storage key
				 * @returns {*} Value from storage, or null if not found
				 */
				get: function(key) {
					let value = null;

					// Try localStorage first
					try {
						value = localStorage.getItem(key);
						if (value !== null) {
							// Try to parse as JSON, otherwise return as string
							try {
								return JSON.parse(value);
							} catch (e) {
								return value;
							}
						}
					} catch (e) {
						// localStorage not available
					}

					// Fallback to cookies
					try {
						value = this.getCookie(key);
						if (value !== null) {
							// Try to parse as JSON, otherwise return as string
							try {
								return JSON.parse(value);
							} catch (e) {
								return value;
							}
						}
					} catch (e) {
						// Cookie access failed
					}

					return null;
				},

				/**
				 * Get a cookie value
				 *
				 * @param {string} name Cookie name
				 * @returns {string|null} Cookie value or null if not found
				 */
				getCookie: function(name) {
					const nameEQ = `${name}=`;
					const cookies = document.cookie.split(';');

					for (let i = 0; i < cookies.length; i++) {
						let cookie = cookies[i];
						while (cookie.charAt(0) === ' ') {
							cookie = cookie.substring(1);
						}
						if (cookie.indexOf(nameEQ) === 0) {
							return cookie.substring(nameEQ.length);
						}
					}
					return null;
				},

				/**
				 * Log message if debug enabled
				 */
				log: function(message, error = null) {
					if (window.ctaHighlightsConfig && window.ctaHighlightsConfig.debug) {
						console.log(`[CTA Auto-Insert] ${message}`, error || '');
					}
				}
			};

			return manager;
		}

		/**
		 * Initialize auto-insertion evaluation
		 */
		init() {
			// Find all auto-inserted CTAs with storage conditions
			const autoInsertedCTAs = document.querySelectorAll('.cta-highlights-auto-inserted[data-has-storage-condition="true"]');

			if (autoInsertedCTAs.length === 0) {
				this.log('No auto-inserted CTAs with storage conditions found');
				return;
			}

			this.log(`Found ${autoInsertedCTAs.length} auto-inserted CTAs with storage conditions`);

			// Evaluate each CTA
			autoInsertedCTAs.forEach(cta => this.evaluateCTA(cta));
		}

		/**
		 * Evaluate storage conditions for a CTA
		 *
		 * @param {HTMLElement} cta CTA element
		 */
		evaluateCTA(cta) {
			const conditionJS = cta.dataset.storageCondition;

			if (!conditionJS) {
				this.log('No storage condition found for CTA', cta);
				this.showCTA(cta);
				return;
			}

			try {
				// Evaluate the condition JavaScript
				// The condition code has access to this.storageManager via closure
				const conditionPassed = eval(conditionJS);

				if (conditionPassed) {
					this.log(`Storage condition passed for CTA #${cta.dataset.ctaId}`);
					this.showCTA(cta);
					this.trackEvent('cta_auto_insert_shown', cta);
				} else {
					this.log(`Storage condition failed for CTA #${cta.dataset.ctaId}`);
					this.removeCTA(cta);
					this.trackEvent('cta_auto_insert_hidden', cta);
				}
			} catch (error) {
				this.log(`Error evaluating storage condition for CTA #${cta.dataset.ctaId}:`, error);
				// On error, default to showing the CTA (fail open)
				this.showCTA(cta);
			}
		}

		/**
		 * Show a CTA by removing display:none
		 *
		 * @param {HTMLElement} cta CTA element
		 */
		showCTA(cta) {
			cta.style.display = '';
			cta.setAttribute('aria-hidden', 'false');
		}

		/**
		 * Remove a CTA from the DOM
		 *
		 * @param {HTMLElement} cta CTA element
		 */
		removeCTA(cta) {
			cta.remove();
		}

		/**
		 * Track analytics event
		 * Supports multiple analytics providers via hooks
		 *
		 * @param {string} eventName Event name
		 * @param {HTMLElement} cta CTA element
		 */
		trackEvent(eventName, cta) {
			const eventData = {
				cta_id: cta.dataset.ctaId,
				event_category: 'CTA Auto-Insert',
				event_action: eventName
			};

			// Google Analytics 4 (gtag.js)
			if (typeof gtag === 'function') {
				gtag('event', eventName, {
					event_category: eventData.event_category,
					cta_id: eventData.cta_id
				});
				this.log(`GA4 event tracked: ${eventName}`);
			}

			// Google Analytics Universal (analytics.js)
			if (typeof ga === 'function') {
				ga('send', 'event', eventData.event_category, eventName, eventData.cta_id);
				this.log(`GA Universal event tracked: ${eventName}`);
			}

			// Custom event for theme/plugin developers
			const customEvent = new CustomEvent('ctaAutoInsertEvent', {
				detail: eventData
			});
			document.dispatchEvent(customEvent);

			this.log(`Custom event dispatched: ${eventName}`, eventData);
		}

		/**
		 * Log message if debug enabled
		 */
		log(message, data = null) {
			this.storageManager.log(message, data);
		}
	}

	/**
	 * Initialize on DOM ready
	 */
	function init() {
		// Create and initialize auto-insert manager
		const autoInsertManager = new AutoInsertManager();
		autoInsertManager.init();

		// Expose to window for potential external access
		window.ctaAutoInsertManager = autoInsertManager;
	}

	// Initialize when DOM is ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		// DOM already loaded
		init();
	}

})();
