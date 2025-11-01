/**
 * CTA Highlights - Auto-Insertion
 * Version: 2.0.0
 *
 * Fully client-side auto-insertion with fallback chain support
 * Handles content parsing, position calculation, storage evaluation, and DOM insertion
 */

(function () {
	'use strict';

	// Content container selectors (try in order)
	const CONTENT_SELECTORS = [
		'.entry-content', // Standard WordPress
		'.post-content', // Common theme pattern
		'.wp-block-post-content', // Gutenberg FSE
		'article .content', // Semantic HTML
		'.elementor-widget-theme-post-content .elementor-widget-container', // Elementor
		'.et_pb_post_content', // Divi
		'.fl-post-content', // Beaver Builder
		'.brxe-post-content', // Bricks Builder
		'.oxygen-builder-body .ct-text-block', // Oxygen
		'article', // Generic article
		'main', // Last resort
	];

	/**
	 * Auto-Insert Manager
	 * Handles client-side evaluation and insertion of CTAs
	 */
	class AutoInsertManager {
		constructor() {
			this.storageManager = this.createStorageManager();
		}

		/**
		 * Create StorageManager instance
		 * Provides localStorage and cookie access
		 */
		createStorageManager() {
			const manager = {
				/**
				 * Get a value from localStorage or cookie
				 *
				 * @param {string} key Storage key
				 * @return {*} Value from storage, or null if not found
				 */
				get(key) {
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
				 * @return {string|null} Cookie value or null if not found
				 */
				getCookie(name) {
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
				 * @param message
				 * @param error
				 */
				log(message, error = null) {
					if (
						window.ctaHighlightsConfig &&
						window.ctaHighlightsConfig.debug
					) {
						console.log(
							`[CTA Auto-Insert] ${message}`,
							error || ''
						);
					}
				},
			};

			return manager;
		}

		/**
		 * Initialize auto-insertion
		 * Reads JSON data and processes fallback chain
		 */
		init() {
			// Read data from inline JSON
			const dataElement = document.getElementById(
				'cta-highlights-auto-insert-data'
			);

			if (!dataElement) {
				this.log('No auto-insert data found');
				return;
			}

			try {
				const data = JSON.parse(dataElement.textContent);

				if (!data.ctas || data.ctas.length === 0) {
					this.log('No CTAs in fallback chain');
					return;
				}

				this.log(
					`Found fallback chain with ${data.ctas.length} CTA(s)`
				);
				this.processFallbackChain(data);
			} catch (error) {
				this.log('Error parsing auto-insert data:', error);
			}
		}

		/**
		 * Process fallback chain
		 * Evaluates conditions and inserts the first matching CTA
		 *
		 * @param {Object} chainData Fallback chain data
		 */
		processFallbackChain(chainData) {
			// Find content container
			const container = this.findContentContainer(
				chainData.contentSelector
			);
			if (!container) {
				this.log('Content container not found');
				return;
			}

			// Parse content elements
			const elements = this.parseContentElements(container);
			if (elements.length === 0) {
				this.log('No content elements found');
				return;
			}

			this.log(`Found ${elements.length} content elements`);

			// Evaluate storage conditions and select CTA
			let selectedCTA = null;
			let selectedIndex = -1;

			for (let i = 0; i < chainData.ctas.length; i++) {
				const cta = chainData.ctas[i];

				// No storage conditions = always matches
				if (!cta.has_storage_conditions) {
					this.log(
						`CTA #${cta.id} has no storage conditions - selected`
					);
					selectedCTA = cta;
					selectedIndex = i;
					break;
				}

				// Evaluate storage conditions
				try {
					// eslint-disable-next-line no-eval -- Evaluating user-configured storage conditions from admin
				const conditionPassed = eval(cta.storage_condition_js);

					if (conditionPassed) {
						this.log(
							`CTA #${cta.id} storage conditions passed - selected`
						);
						selectedCTA = cta;
						selectedIndex = i;
						break;
					} else {
						this.log(
							`CTA #${cta.id} storage conditions failed - trying next`
						);
					}
				} catch (error) {
					this.log(`Error evaluating CTA #${cta.id}:`, error);
					// On error, try next in chain
				}
			}

			// If no CTA matched, use last one as ultimate fallback
			if (!selectedCTA && chainData.ctas.length > 0) {
				selectedCTA = chainData.ctas[chainData.ctas.length - 1];
				selectedIndex = chainData.ctas.length - 1;
				this.log(
					`No CTAs matched - using last CTA #${selectedCTA.id} as fallback`
				);
			}

			if (!selectedCTA) {
				this.log('No CTA selected');
				return;
			}

			// Calculate position using SELECTED CTA's own settings
			const positionInfo = this.calculateInsertPosition(
				elements,
				selectedCTA
			);

			if (!positionInfo) {
				this.log(
					`Position calculation failed or skipped for CTA #${selectedCTA.id}`
				);
				return;
			}

			// Insert CTA into DOM
			this.insertCTAIntoContent(
				container,
				selectedCTA,
				positionInfo,
				selectedIndex,
				chainData.ctas.length
			);
		}

		/**
		 * Find content container using selector list
		 *
		 * @param {string} preferredSelector Preferred selector from settings
		 * @return {HTMLElement|null} Content container or null
		 */
		findContentContainer(preferredSelector) {
			// Try preferred selector first
			let container = document.querySelector(preferredSelector);
			if (container) {
				this.log(`Found content container: ${preferredSelector}`);
				return container;
			}

			// Try fallback selectors
			for (const selector of CONTENT_SELECTORS) {
				container = document.querySelector(selector);
				if (container) {
					this.log(`Found content container: ${selector}`);
					return container;
				}
			}

			this.log('No content container found');
			return null;
		}

		/**
		 * Parse content elements (direct children only)
		 * Filters out script/style tags and empty elements
		 *
		 * @param {HTMLElement} container Content container
		 * @return {Array} Array of content elements
		 */
		parseContentElements(container) {
			// Get direct children only
			const children = Array.from(container.children);

			// Filter out script, style, noscript tags and empty elements
			const elements = children.filter((el) => {
				const tagName = el.tagName.toLowerCase();

				// Filter out script, style, noscript
				if (['script', 'style', 'noscript'].includes(tagName)) {
					return false;
				}

				// Filter out empty elements
				if (this.isEmptyElement(el)) {
					return false;
				}

				return true;
			});

			return elements;
		}

		/**
		 * Check if an element is empty (no meaningful content)
		 *
		 * @param {HTMLElement} el Element to check
		 * @return {boolean} True if element is empty
		 */
		isEmptyElement(el) {
			// Check if element has text content
			const textContent = el.textContent.trim();
			if (textContent.length > 0) {
				return false;
			}

			// Check if element has meaningful child elements (img, iframe, video, etc.)
			// These count as content even without text
			const meaningfulTags = [
				'img',
				'iframe',
				'video',
				'audio',
				'embed',
				'object',
				'svg',
				'canvas',
				'picture',
			];
			const hasMeaningfulChildren = Array.from(
				el.querySelectorAll('*')
			).some((child) => {
				return meaningfulTags.includes(child.tagName.toLowerCase());
			});

			if (hasMeaningfulChildren) {
				return false;
			}

			// Element is empty
			return true;
		}

		/**
		 * Calculate insertion position
		 * Replicates PHP position calculation logic
		 *
		 * @param {Array}  elements Content elements
		 * @param {Object} cta      CTA configuration
		 * @return {Object|null} Position info or null to skip
		 */
		calculateInsertPosition(elements, cta) {
			const totalElements = elements.length;
			// eslint-disable-next-line @wordpress/no-unused-vars-before-return -- Variable used after early return check
		const position = parseInt(cta.insertion_position, 10);
			const direction = cta.insertion_direction;
			const fallbackBehavior = cta.fallback_behavior;

			if (totalElements === 0) return null;

			let targetIndex;

			if (direction === 'forward') {
				// Forward: count from beginning
				targetIndex = position;
			} else {
				// Reverse: count from end
				targetIndex = totalElements - position;
			}

			// Handle out of bounds
			if (targetIndex > totalElements) {
				if (fallbackBehavior === 'end') {
					targetIndex = totalElements;
				} else {
					// Skip insertion
					this.log(
						`Position ${position} (${direction}) exceeds content length, skipping`
					);
					return null;
				}
			}

			if (targetIndex < 0) {
				targetIndex = 0;
			}

			return {
				element: elements[targetIndex] || null,
				insertBefore: targetIndex < totalElements,
				index: targetIndex,
			};
		}

		/**
		 * Insert CTA into content
		 *
		 * @param {HTMLElement} container     Content container
		 * @param {Object}      cta           CTA configuration
		 * @param {Object}      positionInfo  Position information
		 * @param {number}      fallbackIndex Index in fallback chain
		 * @param {number}      chainLength   Total chain length
		 */
		insertCTAIntoContent(
			container,
			cta,
			positionInfo,
			fallbackIndex,
			chainLength
		) {
			// Create wrapper element
			const wrapper = document.createElement('div');
			wrapper.className =
				'cta-highlights-wrapper cta-highlights-auto-inserted';
			wrapper.setAttribute('data-auto-insert', 'true');
			wrapper.setAttribute('data-cta-id', cta.id);
			wrapper.setAttribute('data-fallback-index', fallbackIndex);
			wrapper.setAttribute('data-fallback-chain-length', chainLength);
			wrapper.innerHTML = cta.content;

			// Insert into DOM
			if (positionInfo.element && positionInfo.insertBefore) {
				// Insert before the target element
				positionInfo.element.parentNode.insertBefore(
					wrapper,
					positionInfo.element
				);
			} else if (positionInfo.element) {
				// Insert after the target element
				positionInfo.element.parentNode.insertBefore(
					wrapper,
					positionInfo.element.nextSibling
				);
			} else {
				// Fallback: append to container
				container.appendChild(wrapper);
			}

			// Check if the inserted content has highlight enabled
			// The CTA content might contain a <section class="cta-highlights-wrapper" data-highlight="true">
			const highlightElement = wrapper.querySelector(
				'.cta-highlights-wrapper[data-highlight="true"]'
			);
			if (highlightElement && window.ctaHighlightsManager) {
				// Initialize the highlight feature for this dynamically inserted CTA
				this.log(`Initializing highlight feature for CTA #${cta.id}`);
				window.ctaHighlightsManager.initializeCTA(highlightElement);
			}

			// Track analytics
			this.trackEvent('cta_auto_insert_shown', wrapper);

			if (fallbackIndex > 0) {
				this.trackEvent('cta_fallback_used', wrapper);
				this.log(
					`CTA #${cta.id} inserted using fallback (position ${fallbackIndex} of ${chainLength})`
				);
			} else {
				this.log(`CTA #${cta.id} inserted (primary CTA)`);
			}
		}

		/**
		 * Track analytics event
		 * Supports multiple analytics providers
		 *
		 * @param {string}      eventName Event name
		 * @param {HTMLElement} cta       CTA element
		 */
		trackEvent(eventName, cta) {
			const eventData = {
				cta_id: cta.dataset.ctaId,
				event_category: 'CTA Auto-Insert',
				event_action: eventName,
			};

			// Google Analytics 4 (gtag.js)
			if (typeof gtag === 'function') {
				gtag('event', eventName, {
					event_category: eventData.event_category,
					cta_id: eventData.cta_id,
				});
				this.log(`GA4 event tracked: ${eventName}`);
			}

			// Google Analytics Universal (analytics.js)
			if (typeof ga === 'function') {
				ga(
					'send',
					'event',
					eventData.event_category,
					eventName,
					eventData.cta_id
				);
				this.log(`GA Universal event tracked: ${eventName}`);
			}

			// Custom event for theme/plugin developers
			const customEvent = new CustomEvent('ctaAutoInsertEvent', {
				detail: eventData,
			});
			document.dispatchEvent(customEvent);

			this.log(`Custom event dispatched: ${eventName}`, eventData);
		}

		/**
		 * Log message if debug enabled
		 * @param message
		 * @param data
		 */
		log(message, data = null) {
			this.storageManager.log(message, data);
		}
	}

	/**
	 * Initialize on DOM ready
	 */
	function init() {
		const autoInsertManager = new AutoInsertManager();

		// Wait 50ms after DOMContentLoaded for page builders to render
		setTimeout(() => {
			autoInsertManager.init();
		}, 50);

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
