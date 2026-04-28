/*!
 * Store – reactive state management for vanilla JS
 * @author Massif Store Module
 */

/**
 * Deep clone a value (plain objects, arrays, Dates).
 * @param {any} obj
 * @returns {any}
 */
function deepClone(obj) {
	if (obj === null || typeof obj !== "object") return obj;
	if (obj instanceof Date) return new Date(obj.getTime());
	if (Array.isArray(obj)) return obj.map(deepClone);

	const out = {};
	for (const k in obj) {
		if (Object.hasOwn(obj, k)) {
			out[k] = deepClone(obj[k]);
		}
	}
	return out;
}

/**
 * Check whether a value is a plain object or array (proxy-worthy).
 * @param {any} v
 * @returns {boolean}
 */
function isPlainObjectOrArray(v) {
	return (
		v !== null &&
		typeof v === "object" &&
		(Array.isArray(v) || v.constructor === Object)
	);
}

// ── Reactive Proxy Factory ──────────────────────────────────────────

const PROXY_TARGET = Symbol("proxyTarget");

/**
 * Unwrap a proxy (or return the value as-is).
 * @param {any} v
 * @returns {any}
 */
function unwrap(v) {
	return v?.[PROXY_TARGET] ?? v;
}

/**
 * Create a deeply-reactive proxy that notifies `onChange` on mutation.
 *
 * Proxies are cached per-object via a WeakMap so repeated property access
 * always returns the *same* proxy instance (stable identity).
 *
 * @param {Object|Array} target
 * @param {(info: {path: string, prop: string, oldValue: any, newValue: any, op: string}) => void} onChange
 * @param {string} [path='']
 * @param {WeakMap} [cache]  – shared across one reactive tree
 * @returns {Proxy}
 */
function createReactiveProxy(
	target,
	onChange,
	path = "",
	cache = new WeakMap(),
) {
	if (!isPlainObjectOrArray(target)) return target;
	if (cache.has(target)) return cache.get(target);

	const proxy = new Proxy(target, {
		get(obj, prop) {
			if (prop === PROXY_TARGET) return obj;

			const value = obj[prop];
			if (isPlainObjectOrArray(value)) {
				return createReactiveProxy(
					value,
					onChange,
					path ? `${path}.${String(prop)}` : String(prop),
					cache,
				);
			}
			return value;
		},

		set(obj, prop, value) {
			const oldValue = obj[prop];
			const raw = unwrap(value);
			obj[prop] = raw;

			onChange({
				path: path ? `${path}.${String(prop)}` : String(prop),
				prop: String(prop),
				oldValue,
				newValue: raw,
				op: "set",
			});
			return true;
		},

		deleteProperty(obj, prop) {
			const oldValue = obj[prop];
			delete obj[prop];

			onChange({
				path: path ? `${path}.${String(prop)}` : String(prop),
				prop: String(prop),
				oldValue,
				newValue: undefined,
				op: "delete",
			});
			return true;
		},
	});

	cache.set(target, proxy);
	return proxy;
}

// ── Store ───────────────────────────────────────────────────────────

class Store {
	/** @param {{ maxHistory?: number }} opts */
	constructor(opts = {}) {
		/** @private */ this._state = new Map();
		/** @private */ this._subs = new Map();
		/** @private */ this._history = new Map();
		/** @private */ this._maxHistory = opts.maxHistory ?? 10;
	}

	// ── Core CRUD ───────────────────────────────────────────────────

	/**
	 * Store a value (static – no proxy wrapping).
	 * @param {string} key
	 * @param {any} value
	 * @param {{ history?: boolean }} opts
	 */
	set(key, value, { history = false } = {}) {
		const old = this._state.get(key);
		if (history) this._pushHistory(key, old);

		const raw = unwrap(value);
		this._state.set(key, raw);
		this._notify(key, raw, old);
		return raw;
	}

	/**
	 * Store a reactive value. Mutations on the returned proxy auto-notify subscribers.
	 * @param {string} key
	 * @param {any} value
	 * @param {{ history?: boolean }} opts
	 */
	setReactive(key, value, { history = false } = {}) {
		const old = this._state.get(key);
		if (history) this._pushHistory(key, old);

		let stored = value;
		if (isPlainObjectOrArray(value)) {
			const cloned = deepClone(value);
			stored = createReactiveProxy(cloned, (info) => {
				// On any nested mutation, notify with the top-level proxy value
				this._notify(key, this._state.get(key), this._state.get(key), info);
			});
		}

		this._state.set(key, stored);
		this._notify(key, stored, old);
		return stored;
	}

	/**
	 * @param {string} key
	 * @param {any} [defaultValue]
	 * @returns {any}
	 */
	get(key, defaultValue) {
		return this._state.has(key) ? this._state.get(key) : defaultValue;
	}

	/** @param {string} key */
	has(key) {
		return this._state.has(key);
	}

	/** @param {string} key */
	delete(key) {
		if (!this._state.has(key)) return false;
		const old = this._state.get(key);
		this._state.delete(key);
		this._history.delete(key);
		this._notify(key, undefined, old);
		return true;
	}

	clear() {
		const snapshot = [...this._state.entries()];
		this._state.clear();
		this._history.clear();
		for (const [key, old] of snapshot) {
			this._notify(key, undefined, old);
		}
	}

	// ── Iteration ───────────────────────────────────────────────────

	keys() {
		return [...this._state.keys()];
	}
	values() {
		return [...this._state.values()];
	}
	entries() {
		return [...this._state.entries()];
	}

	// ── Convenience mutators ────────────────────────────────────────

	/**
	 * Update via callback: `store.update('count', n => n + 1)`
	 * @param {string} key
	 * @param {(current: any) => any} fn
	 * @param {any} [defaultValue]
	 */
	update(key, fn, defaultValue) {
		return this.set(key, fn(this.get(key, defaultValue)));
	}

	increment(key, amount = 1, defaultValue = 0) {
		return this.set(key, this.get(key, defaultValue) + amount);
	}

	decrement(key, amount = 1, defaultValue = 0) {
		return this.increment(key, -amount, defaultValue);
	}

	toggle(key, defaultValue = false) {
		return this.set(key, !this.get(key, defaultValue));
	}

	// ── Subscriptions ───────────────────────────────────────────────

	/**
	 * Subscribe to changes on `key`.
	 * @param {string} key
	 * @param {(newVal: any, oldVal: any, key: string, changeInfo?: object) => void} cb
	 * @returns {() => void} unsubscribe
	 */
	subscribe(key, cb) {
		if (!this._subs.has(key)) this._subs.set(key, new Set());
		this._subs.get(key).add(cb);

		return () => {
			const set = this._subs.get(key);
			if (!set) return;
			set.delete(cb);
			if (set.size === 0) this._subs.delete(key);
		};
	}

	// ── History ─────────────────────────────────────────────────────

	/** @param {string} key */
	getHistory(key) {
		return this._history.get(key) ?? [];
	}

	// ── Private ─────────────────────────────────────────────────────

	/** @private */
	_pushHistory(key, value) {
		if (!this._history.has(key)) this._history.set(key, []);
		const h = this._history.get(key);
		h.push({ value: deepClone(value), timestamp: Date.now() });
		if (h.length > this._maxHistory) h.shift();
	}

	/** @private */
	_notify(key, newVal, oldVal, changeInfo = null) {
		const subs = this._subs.get(key);
		if (!subs) return;
		for (const cb of subs) {
			try {
				cb(newVal, oldVal, key, changeInfo);
			} catch (err) {
				console.error(`[store] subscriber error for "${key}":`, err);
			}
		}
	}
}

// ── Singleton ─────────────────────────────────────────────────────

const store = new Store();

// ── Hook-style helpers (bound to singleton) ───────────────────────

/**
 * Create a reactive state binding.
 *
 * ```js
 * const [getTodos, setTodos] = createState('todos', [])
 * setTodos([{ text: 'Ship it', done: false }])
 * getTodos()[0].done = true  // auto-notifies subscribers
 * ```
 *
 * @param {string} key
 * @param {any} [defaultValue]
 * @returns {[() => any, (value: any) => any]}
 */
export function createState(key, defaultValue) {
	if (!store.has(key) && defaultValue !== undefined) {
		store.setReactive(key, defaultValue);
	}

	const get = () => store.get(key, defaultValue);

	const set = (value) => {
		const resolved =
			typeof value === "function" ? value(store.get(key, defaultValue)) : value;
		return store.setReactive(key, resolved);
	};

	return [get, set];
}

/**
 * Create a static (non-reactive) state binding.
 *
 * Use when you don't need proxy-based mutation tracking
 * and always replace values via the setter.
 *
 * @param {string} key
 * @param {any} [defaultValue]
 * @returns {[() => any, (value: any) => any]}
 */
export function createStaticState(key, defaultValue) {
	if (!store.has(key) && defaultValue !== undefined) {
		store.set(key, defaultValue);
	}

	const get = () => store.get(key, defaultValue);

	const set = (value) => {
		const resolved =
			typeof value === "function" ? value(store.get(key, defaultValue)) : value;
		return store.set(key, resolved);
	};

	return [get, set];
}

/**
 * Subscribe to changes on a key. Optionally fire immediately with current value.
 *
 * ```js
 * const unsub = onChange('todos', (newVal, oldVal, key, info) => {
 *   console.log('changed:', info?.path)
 * })
 * ```
 *
 * @param {string} key
 * @param {Function} cb
 * @param {{ immediate?: boolean }} opts
 * @returns {() => void} unsubscribe
 */
export function onChange(key, cb, { immediate = false } = {}) {
	if (immediate) cb(store.get(key), undefined, key, null);
	return store.subscribe(key, cb);
}

/**
 * Derive a value from multiple keys. Re-computes whenever any dependency changes.
 *
 * ```js
 * const [getTotal, unsub] = computed(['price', 'tax'], (price, tax) => price + tax)
 * ```
 *
 * @param {string[]} keys
 * @param {(...values: any[]) => any} fn
 * @returns {[() => any, () => void]} [getter, unsubscribe]
 */
export function computed(keys, fn) {
	let cached = fn(...keys.map((k) => store.get(k)));

	const recompute = () => {
		cached = fn(...keys.map((k) => store.get(k)));
	};

	const unsubs = keys.map((k) => store.subscribe(k, recompute));
	const unsub = () =>
		unsubs.forEach((u) => {
			u();
		});

	return [() => cached, unsub];
}

// ── Exports ───────────────────────────────────────────────────────

export { store, Store, deepClone, unwrap };

export default {
	createState,
	createStaticState,
	onChange,
	computed,
	store,
	Store,
	deepClone,
	unwrap,
};
