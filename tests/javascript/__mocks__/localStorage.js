/**
 * localStorage Mock
 *
 * Provides a complete implementation of localStorage API for testing
 * Includes quota limits and error simulation
 */

class LocalStorageMock {
	constructor() {
		this.store = {};
		this.quotaExceeded = false;
		this.disabled = false;
	}

	getItem(key) {
		if (this.disabled) {
			throw new Error('localStorage is disabled');
		}
		return this.store[key] || null;
	}

	setItem(key, value) {
		if (this.disabled) {
			throw new Error('localStorage is disabled');
		}
		if (this.quotaExceeded) {
			throw new DOMException('QuotaExceededError');
		}
		this.store[key] = String(value);
	}

	removeItem(key) {
		if (this.disabled) {
			throw new Error('localStorage is disabled');
		}
		delete this.store[key];
	}

	clear() {
		if (this.disabled) {
			throw new Error('localStorage is disabled');
		}
		this.store = {};
	}

	get length() {
		return Object.keys(this.store).length;
	}

	key(index) {
		const keys = Object.keys(this.store);
		return keys[index] || null;
	}

	// Test helpers
	__setQuotaExceeded(value) {
		this.quotaExceeded = value;
	}

	__setDisabled(value) {
		this.disabled = value;
	}

	__reset() {
		this.store = {};
		this.quotaExceeded = false;
		this.disabled = false;
	}
}

module.exports = LocalStorageMock;
