/**
 * CTA Highlights - Vanilla JavaScript (No jQuery)
 * Version: 1.0.0
 *
 * Handles the optional highlight effect for inline CTA elements.
 * CTAs remain in their inline position - highlight effect adds overlay and elevation.
 */

(function () {
	'use strict';

	/**
	 * Storage Manager
	 * Handles cooldown tracking using localStorage with cookie fallback
	 */
	class StorageManager {
		/**
		 * Set a cookie
		 *
		 * @param {string} name          Cookie name
		 * @param {string} value         Cookie value
		 * @param {number} expirySeconds Expiry time in seconds
		 */
		setCookie(name, value, expirySeconds) {
			const expiryDate = new Date();
			expiryDate.setTime(expiryDate.getTime() + expirySeconds * 1000);
			const expires = `expires=${expiryDate.toUTCString()}`;

			// Use path=/ to make cookie available across the entire site
			document.cookie = `${name}=${value};${expires};path=/;SameSite=Lax`;
		}

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
		}

		/**
		 * Remove a cookie
		 *
		 * @param {string} name Cookie name
		 */
		removeCookie(name) {
			document.cookie = `${name}=;expires=Thu, 01 Jan 1970 00:00:00 UTC;path=/;`;
		}

		/**
		 * Set a value with expiry time
		 * Uses localStorage first, falls back to cookies if unavailable
		 *
		 * @param {string} key           Storage key
		 * @param {number} expirySeconds Expiry time in seconds
		 */
		set(key, expirySeconds) {
			const expiryTime = Date.now() + expirySeconds * 1000;
			const data = JSON.stringify({
				timestamp: Date.now(),
				expiryTime,
			});

			// Try localStorage first
			try {
				localStorage.setItem(key, data);
				this.log(`Set cooldown in localStorage: ${key}`);
				return;
			} catch (e) {
				this.log(
					'localStorage not available or quota exceeded, falling back to cookies',
					e
				);
			}

			// Fallback to cookies
			try {
				this.setCookie(key, data, expirySeconds);
				this.log(`Set cooldown in cookie: ${key}`);
			} catch (e) {
				this.log('Cookie storage also failed', e);
			}
		}

		/**
		 * Check if a cooldown is active
		 * Checks localStorage first, then cookies
		 *
		 * @param {string} key Storage key
		 * @return {boolean} True if cooldown is active
		 */
		isCooldownActive(key) {
			let item = null;
			let source = null;

			// Try localStorage first
			try {
				item = localStorage.getItem(key);
				if (item) {
					source = 'localStorage';
				}
			} catch (e) {
				this.log('Error accessing localStorage', e);
			}

			// Fallback to cookies if not found in localStorage
			if (!item) {
				try {
					item = this.getCookie(key);
					if (item) {
						source = 'cookie';
					}
				} catch (e) {
					this.log('Error accessing cookies', e);
				}
			}

			// Not found in either storage
			if (!item) {
				return false;
			}

			// Parse and check expiry
			try {
				const data = JSON.parse(item);
				const now = Date.now();

				if (now < data.expiryTime) {
					this.log(`Cooldown active (${source}): ${key}`);
					return true;
				}

				// Expired, clean up from both storages
				this.removeFromBothStorages(key);
				return false;
			} catch (e) {
				this.log('Error parsing cooldown data', e);
				// Clean up corrupted data
				this.removeFromBothStorages(key);
				return false;
			}
		}

		/**
		 * Remove a key from both localStorage and cookies
		 *
		 * @param {string} key Storage key
		 */
		removeFromBothStorages(key) {
			try {
				localStorage.removeItem(key);
			} catch (e) {
				// Silent fail - localStorage might not be available
			}

			try {
				this.removeCookie(key);
			} catch (e) {
				// Silent fail - cookies might not be available
			}
		}

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
				console.log(`[CTA Highlights] ${message}`, error || '');
			}
		}
	}

	/**
	 * CTA Highlight Manager
	 * Manages the highlight effect for CTA elements
	 */
	class CTAHighlight {
		constructor(config) {
			this.config = config;
			this.storage = new StorageManager();
			this.overlay = null;
			this.closeButton = null;
			this.activeCTA = null;
			this.hideTimeout = null;
			this.isActive = false;
			this.previousFocus = null;
			this.focusTrapHandler = null;
			this.resizeObserver = null;
		}

		/**
		 * Initialize the highlight system
		 */
		init() {
			// Check if any CTAs have highlight enabled
			const highlightCTAs = document.querySelectorAll(
				'.cta-highlights-wrapper[data-highlight="true"]'
			);

			if (highlightCTAs.length === 0) {
				this.log('No highlight-enabled CTAs found');
				return;
			}

			// Check global cooldown
			if (this.storage.isCooldownActive('cta_highlights_global')) {
				this.log('Global cooldown active');
				return;
			}

			// Detect and set page background
			this.setPageBackground();

			// Create overlay and close button
			this.createOverlay();
			this.createCloseButton();

			// Setup event listeners
			this.setupEventListeners();

			// Setup intersection observers for each CTA
			highlightCTAs.forEach((cta) => this.setupObserver(cta));
		}

		/**
		 * Initialize a specific CTA element
		 * Used for dynamically inserted CTAs (e.g., auto-insertion)
		 *
		 * @param {HTMLElement} ctaElement CTA element to initialize
		 */
		initializeCTA(ctaElement) {
			// Verify element has highlight enabled
			if (
				!ctaElement ||
				ctaElement.getAttribute('data-highlight') !== 'true'
			) {
				this.log('Element does not have highlight enabled');
				return;
			}

			// Check global cooldown
			if (this.storage.isCooldownActive('cta_highlights_global')) {
				this.log('Global cooldown active, skipping CTA initialization');
				return;
			}

			// Ensure overlay and close button exist (lazy initialization)
			if (!this.overlay) {
				this.setPageBackground();
				this.createOverlay();
				this.createCloseButton();
				this.setupEventListeners();
			}

			// Setup observer for this specific CTA
			this.setupObserver(ctaElement);
			this.log('Initialized CTA:', ctaElement);
		}

		/**
		 * Detect page background color and set CSS custom property
		 */
		setPageBackground() {
			let bgColor = '#ffffff';

			// Try to detect actual page background
			const bodyBg = window.getComputedStyle(
				document.body
			).backgroundColor;
			if (
				bodyBg &&
				bodyBg !== 'rgba(0, 0, 0, 0)' &&
				bodyBg !== 'transparent'
			) {
				bgColor = bodyBg;
			} else {
				const htmlBg = window.getComputedStyle(
					document.documentElement
				).backgroundColor;
				if (
					htmlBg &&
					htmlBg !== 'rgba(0, 0, 0, 0)' &&
					htmlBg !== 'transparent'
				) {
					bgColor = htmlBg;
				}
			}

			// Set CSS custom property for CTA background
			document.documentElement.style.setProperty(
				'--cta-highlights-cta-background',
				bgColor
			);
		}

		/**
		 * Create the overlay element
		 */
		createOverlay() {
			this.overlay = document.createElement('div');
			this.overlay.className = 'cta-highlights-overlay';
			this.overlay.setAttribute('aria-hidden', 'true');
			this.overlay.setAttribute('role', 'presentation');

			// Apply custom color from config
			if (this.config.overlayColor) {
				this.overlay.style.backgroundColor = this.config.overlayColor;
			}

			document.body.appendChild(this.overlay);
		}

		/**
		 * Create the close button
		 */
		createCloseButton() {
			this.closeButton = document.createElement('button');
			this.closeButton.className = 'cta-highlights-close';
			this.closeButton.setAttribute('aria-label', 'Close highlight');
			this.closeButton.setAttribute('type', 'button');
			this.closeButton.innerHTML = '&times;';

			document.body.appendChild(this.closeButton);
		}

		/**
		 * Setup global event listeners
		 */
		setupEventListeners() {
			// Overlay click dismissal - only on direct overlay clicks
			this.overlay.addEventListener('click', (e) => {
				if (e.target === this.overlay) {
					this.dismiss('overlay-click');
				}
			});

			// Close button click
			this.closeButton.addEventListener('click', () => {
				this.dismiss('close-button');
			});

			// ESC key dismissal
			document.addEventListener('keydown', (e) => {
				if (e.key === 'Escape' && this.isActive) {
					this.dismiss('escape-key');
				}
			});

			// Scroll dismissal (debounced)
			let scrollTimeout;
			window.addEventListener(
				'scroll',
				() => {
					if (!this.isActive) return;

					clearTimeout(scrollTimeout);
					scrollTimeout = setTimeout(() => {
						this.checkScrollDismissal();
					}, 100);
				},
				{ passive: true }
			);

			// Anchor click dismissal within active CTA
			document.addEventListener('click', (e) => {
				if (!this.isActive) return;

				const anchor = e.target.closest('a');
				if (
					anchor &&
					this.activeCTA &&
					this.activeCTA.contains(anchor)
				) {
					this.dismiss('anchor-click');
				}
			});

			// Handle window resize
			window.addEventListener(
				'resize',
				() => {
					if (this.isActive && this.activeCTA) {
						this.updateCTAPosition();
					}
				},
				{ passive: true }
			);
		}

		/**
		 * Setup intersection observer for a CTA
		 *
		 * @param {HTMLElement} cta CTA element to observe
		 */
		setupObserver(cta) {
			const templateName = cta.dataset.template;

			// Check template-specific cooldown
			const cooldownKey = `cta_highlights_template_${templateName}`;
			if (this.storage.isCooldownActive(cooldownKey)) {
				this.log(`Template "${templateName}" cooldown active`);
				return;
			}

			// Calculate threshold based on CTA height vs viewport height
			const threshold = this.calculateThreshold(cta);

			const observerOptions = {
				root: null,
				rootMargin: '0px',
				threshold,
			};

			const observer = new IntersectionObserver((entries) => {
				entries.forEach((entry) => {
					if (entry.isIntersecting && !this.isActive) {
						// Additional check: is CTA fully visible or at top of viewport?
						if (this.isFullyVisibleOrAtTop(entry.target)) {
							this.activate(cta);
							observer.disconnect(); // Only trigger once
						}
					}
				});
			}, observerOptions);

			observer.observe(cta);
		}

		/**
		 * Calculate appropriate threshold for IntersectionObserver
		 *
		 * @param {HTMLElement} element Element to calculate threshold for
		 * @return {number} Threshold value (0-1)
		 */
		calculateThreshold(element) {
			const ctaHeight = element.offsetHeight;
			const viewportHeight = window.innerHeight;

			// If CTA is taller than viewport, use minimal threshold
			if (ctaHeight > viewportHeight) {
				return 0.01;
			}

			// Otherwise, require full visibility
			return 1.0;
		}

		/**
		 * Check if CTA is fully visible or positioned at top of viewport
		 *
		 * @param {HTMLElement} element Element to check
		 * @return {boolean} True if element is fully visible or at top
		 */
		isFullyVisibleOrAtTop(element) {
			const rect = element.getBoundingClientRect();
			const viewportHeight = window.innerHeight;

			// Check if CTA is taller than viewport
			if (rect.height > viewportHeight) {
				// Trigger when top is at or near top of viewport
				return rect.top <= 50 && rect.top >= -50;
			}

			// Check if fully visible in viewport
			return rect.top >= 0 && rect.bottom <= viewportHeight;
		}

		/**
		 * Activate the highlight effect
		 * CTA stays inline - we just add overlay and elevate z-index
		 *
		 * @param {HTMLElement} cta CTA element to highlight
		 */
		activate(cta) {
			if (this.isActive) return;

			this.isActive = true;
			this.activeCTA = cta;

			const duration = parseInt(cta.dataset.duration, 10) || 5;
			const templateName = cta.dataset.template;

			// Store current focus for restoration
			this.previousFocus = document.activeElement;

			// Show overlay FIRST (behind everything)
			this.overlay.classList.add('active');

			// Then elevate CTA (ensures it's on top of overlay)
			// CTA remains in its inline position, just elevated
			cta.classList.add('cta-highlights-active');
			cta.setAttribute('aria-modal', 'true');

			// Force reflow to ensure stacking context is established
			void cta.offsetHeight;

			// Show close button LAST (highest z-index)
			this.closeButton.classList.add('active');

			// Setup focus trap to keep keyboard navigation within CTA
			this.setupFocusTrap(cta);

			// Auto-dismiss after duration
			this.hideTimeout = setTimeout(() => {
				this.dismiss('auto-timeout');
			}, duration * 1000);

			// Set cooldowns
			this.storage.set(
				'cta_highlights_global',
				this.config.globalCooldown
			);
			this.storage.set(
				`cta_highlights_template_${templateName}`,
				this.config.templateCooldown
			);

			// Announce to screen readers
			this.announceToScreenReader(
				'Call to action highlighted. Press Escape to dismiss.',
				'polite'
			);

			this.log(`Activated highlight for template: ${templateName}`);
		}

		/**
		 * Dismiss the highlight effect
		 *
		 * @param {string} reason Reason for dismissal (for logging)
		 */
		dismiss(reason) {
			if (!this.isActive) return;

			this.log(`Dismissed (${reason})`);

			// Clear auto-dismiss timeout
			if (this.hideTimeout) {
				clearTimeout(this.hideTimeout);
				this.hideTimeout = null;
			}

			// Hide overlay and close button
			this.overlay.classList.remove('active');
			this.closeButton.classList.remove('active');

			// Remove CTA elevation - returns to inline position
			if (this.activeCTA) {
				this.activeCTA.classList.remove('cta-highlights-active');
				this.activeCTA.setAttribute('aria-modal', 'false');
			}

			// Remove focus trap
			this.removeFocusTrap();

			// Restore focus
			if (
				this.previousFocus &&
				typeof this.previousFocus.focus === 'function'
			) {
				try {
					this.previousFocus.focus();
				} catch (e) {
					// Focus restoration failed, not critical
				}
			}

			// Reset state
			this.isActive = false;
			this.activeCTA = null;
			this.previousFocus = null;
		}

		/**
		 * Check if scroll should dismiss highlight
		 */
		checkScrollDismissal() {
			if (!this.activeCTA) return;

			const ctaRect = this.activeCTA.getBoundingClientRect();
			const viewportHeight = window.innerHeight;

			// Dismiss if CTA is scrolled out of viewport
			if (ctaRect.bottom < 0 || ctaRect.top > viewportHeight) {
				this.dismiss('scroll');
			}
		}

		/**
		 * Update CTA position (called on resize)
		 */
		updateCTAPosition() {
			// CTA is inline, so position updates automatically
			// Just need to check if it's still in viewport
			this.checkScrollDismissal();
		}

		/**
		 * Setup focus trap within CTA
		 * Keeps keyboard navigation within the highlighted CTA
		 *
		 * @param {HTMLElement} cta CTA element
		 */
		setupFocusTrap(cta) {
			// Find all focusable elements within CTA
			const focusableElements = cta.querySelectorAll(
				'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
			);

			if (focusableElements.length === 0) return;

			const firstFocusable = focusableElements[0];
			const lastFocusable =
				focusableElements[focusableElements.length - 1];

			// Focus first element
			firstFocusable.focus();

			// Create focus trap handler
			this.focusTrapHandler = (e) => {
				if (e.key !== 'Tab') return;

				if (e.shiftKey) {
					// Shift + Tab: moving backwards
					if (document.activeElement === firstFocusable) {
						e.preventDefault();
						lastFocusable.focus();
					}
				} else {
					// Tab: moving forwards
					if (document.activeElement === lastFocusable) {
						e.preventDefault();
						firstFocusable.focus();
					}
				}
			};

			document.addEventListener('keydown', this.focusTrapHandler);
		}

		/**
		 * Remove focus trap
		 */
		removeFocusTrap() {
			if (this.focusTrapHandler) {
				document.removeEventListener('keydown', this.focusTrapHandler);
				this.focusTrapHandler = null;
			}
		}

		/**
		 * Announce message to screen readers
		 *
		 * @param {string} message  Message to announce
		 * @param {string} priority Priority level ('polite' or 'assertive')
		 */
		announceToScreenReader(message, priority = 'polite') {
			const announcement = document.createElement('div');
			announcement.className = 'cta-highlights-sr-only';
			announcement.setAttribute('aria-live', priority);
			announcement.setAttribute('aria-atomic', 'true');
			announcement.setAttribute(
				'role',
				priority === 'assertive' ? 'alert' : 'status'
			);
			announcement.textContent = message;

			document.body.appendChild(announcement);

			// Remove after announcement is made
			setTimeout(() => {
				announcement.remove();
			}, 1000);
		}

		/**
		 * Log message if debug enabled
		 * @param message
		 */
		log(message) {
			if (this.config.debug) {
				console.log(`[CTA Highlights] ${message}`);
			}
		}
	}

	/**
	 * Initialize on DOM ready
	 */
	function init() {
		// Check if config exists
		if (!window.ctaHighlightsConfig) {
			console.warn('[CTA Highlights] Configuration not found');
			return;
		}

		// Create and initialize highlight manager
		const highlightManager = new CTAHighlight(window.ctaHighlightsConfig);
		highlightManager.init();

		// Expose to window for potential external access
		window.ctaHighlightsManager = highlightManager;
	}

	// Initialize when DOM is ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		// DOM already loaded
		init();
	}

	// Export for testing (CommonJS and global)
	if (typeof module !== 'undefined' && module.exports) {
		module.exports = { StorageManager, CTAHighlight };
	} else if (typeof window !== 'undefined' && window.__TEST__) {
		window.StorageManager = StorageManager;
		window.CTAHighlight = CTAHighlight;
	}
})();
