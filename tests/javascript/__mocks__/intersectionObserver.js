/**
 * IntersectionObserver Mock
 *
 * Provides a mock implementation of IntersectionObserver API for testing
 * Allows manual triggering of intersection callbacks
 */

class IntersectionObserverMock {
	constructor(callback, options = {}) {
		this.callback = callback;
		this.options = options;
		this.observedElements = new Set();
		this.isIntersecting = false;

		// Store instance for test access
		if (!global.__intersectionObserverInstances) {
			global.__intersectionObserverInstances = [];
		}
		global.__intersectionObserverInstances.push(this);
	}

	observe(element) {
		this.observedElements.add(element);
	}

	unobserve(element) {
		this.observedElements.delete(element);
	}

	disconnect() {
		this.observedElements.clear();
	}

	// Test helper: manually trigger intersection
	__trigger(isIntersecting, element = null) {
		const elementsToTrigger = element
			? [element]
			: Array.from(this.observedElements);

		const entries = elementsToTrigger.map((el) => ({
			target: el,
			isIntersecting,
			intersectionRatio: isIntersecting ? 1.0 : 0,
			boundingClientRect: el.getBoundingClientRect(),
			intersectionRect: isIntersecting
				? el.getBoundingClientRect()
				: { top: 0, bottom: 0, left: 0, right: 0, width: 0, height: 0 },
			rootBounds: null,
			time: Date.now(),
		}));

		this.callback(entries, this);
	}

	// Test helper: reset instances
	static __reset() {
		global.__intersectionObserverInstances = [];
	}
}

module.exports = IntersectionObserverMock;
