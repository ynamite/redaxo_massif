# Store

Reactive state management for vanilla JavaScript. Provides a simple key-value store with optional deep reactivity via `Proxy`, subscriptions, computed values, and history tracking.

## Quick Start

```js
import { createState, onChange } from './store.js'

const [getTodos, setTodos] = createState('todos', [])

onChange('todos', (newVal, oldVal, key, info) => {
  console.log(`Changed at ${info?.path}`)
  renderTodos()
})

// Replace the whole value
setTodos([{ text: 'Ship it', done: false }])

// Or mutate directly — subscribers fire automatically
getTodos()[0].done = true
```

## API

### `createState(key, defaultValue?)`

Creates a reactive state binding. Objects and arrays are wrapped in a deep `Proxy`, so direct mutations trigger subscriber notifications.

Returns `[getter, setter]`.

```js
const [getUser, setUser] = createState('user', { name: 'Yves', role: 'dev' })

// Read
getUser().name // 'Yves'

// Replace entirely
setUser({ name: 'Yves', role: 'admin' })

// Mutate in place (reactive — subscribers fire)
getUser().role = 'admin'

// Updater function (receives current value)
setUser((prev) => ({ ...prev, role: 'lead' }))
```

**When to use:** You want to mutate nested properties directly and have the UI react. Good for complex objects, arrays of items, form state.

### `createStaticState(key, defaultValue?)`

Same interface as `createState`, but values are stored without a reactive proxy. Subscribers only fire when you call the setter — direct mutation is silent.

Returns `[getter, setter]`.

```js
const [getCount, setCount] = createStaticState('count', 0)

setCount(5) // subscribers fire
setCount((n) => n + 1) // updater pattern works too
```

**When to use:** Primitives, values you always replace wholesale, or cases where you want explicit control over when notifications happen.

### `onChange(key, callback, options?)`

Subscribe to changes on a key.

```js
const unsub = onChange('user', (newVal, oldVal, key, changeInfo) => {
  // changeInfo is present for reactive proxy mutations:
  // { path: 'address.city', prop: 'city', oldValue: 'Bern', newValue: 'Zurich', op: 'set' }
  console.log('user changed')
})

// Stop listening
unsub()
```

**Options:**

| Option      | Type      | Default | Description                                  |
| ----------- | --------- | ------- | -------------------------------------------- |
| `immediate` | `boolean` | `false` | Fire callback immediately with current value |

```js
// Fire right away, then on every change
onChange('theme', applyTheme, { immediate: true })
```

### `computed(keys, fn)`

Derive a value from one or more store keys. Re-computes automatically when any dependency changes.

Returns `[getter, unsubscribe]`.

```js
const [getPrice, setPrice] = createStaticState('price', 100)
const [getTax, setTax] = createStaticState('tax', 0.08)

const [getTotal, unsub] = computed(['price', 'tax'], (price, tax) => {
  return price * (1 + tax)
})

getTotal() // 108

setTax(0.1)
getTotal() // 110

// Clean up when no longer needed
unsub()
```

### Store Instance

The module exports a singleton `store` for direct access. All the helpers above are sugar over these methods.

```js
import { store } from './store.js'
```

#### Core Methods

```js
store.set(key, value, { history: false })       // store a static value
store.setReactive(key, value, { history: false }) // store a reactive value
store.get(key, defaultValue?)                    // retrieve
store.has(key)                                   // check existence
store.delete(key)                                // remove key + history
store.clear()                                    // wipe everything
```

#### Iteration

```js
store.keys() // ['user', 'todos', ...]
store.values() // [{ name: 'Yves' }, [...], ...]
store.entries() // [['user', {...}], ['todos', [...]]]
```

#### Convenience Mutators

```js
store.update('count', (n) => n + 1) // functional update
store.update('count', (n) => n + 1, 0) // with default if key missing
store.increment('count') // +1
store.increment('count', 5) // +5
store.decrement('count') // -1
store.toggle('darkMode') // flip boolean
```

#### Subscriptions

```js
const unsub = store.subscribe('count', (newVal, oldVal, key, changeInfo) => {
  console.log(`${key}: ${oldVal} → ${newVal}`)
})

unsub()
```

#### History

Pass `{ history: true }` to `set` or `setReactive` to track previous values (deep-cloned snapshots, not references).

```js
store.set('score', 10, { history: true })
store.set('score', 20, { history: true })
store.set('score', 30, { history: true })

store.getHistory('score')
// [
//   { value: undefined, timestamp: 1710000000000 },
//   { value: 10, timestamp: 1710000000100 },
//   { value: 20, timestamp: 1710000000200 },
// ]
```

History max size defaults to 10. Configure via the `Store` constructor:

```js
import { Store } from './store.js'
const myStore = new Store({ maxHistory: 50 })
```

### Utilities

```js
import { deepClone, unwrap } from './store.js'

// Deep clone plain objects, arrays, dates
const copy = deepClone(someObject)

// Get the raw object behind a reactive proxy
const raw = unwrap(reactiveProxy)
```

## Reactive vs Static — When to Use What

| Scenario                              | Use                 | Why                                                  |
| ------------------------------------- | ------------------- | ---------------------------------------------------- |
| Todo list, form fields, nested config | `createState`       | Mutate items in place, UI updates automatically      |
| Counters, flags, simple strings       | `createStaticState` | No nesting to track, setter is clearer               |
| Large objects you always replace      | `createStaticState` | Avoids proxy overhead when you never mutate in place |
| Shared state across many components   | Either              | Depends on mutation pattern                          |

## Gotchas

**Reactive old-vs-new on nested mutations.** When you mutate a nested property on a reactive proxy (e.g. `getUser().name = 'foo'`), the subscriber receives the same object reference for both `newVal` and `oldVal` — because the mutation happened in place before notification. If you need to diff old vs new, snapshot manually or use the `changeInfo` parameter which includes `oldValue` and `newValue` for the specific property that changed.

**Proxy identity.** Proxies are cached per reactive tree, so `getUser() === getUser()` is `true`. But `getUser() === unwrap(getUser())` is `false` — one is a proxy, the other is the raw object. Use `unwrap()` if you need the underlying object (e.g. for `JSON.stringify` or passing to libraries that don't play nice with proxies).

**History captures snapshots.** History entries are deep clones, not references. This means history is reliable but has a cost for large objects. Don't enable it on high-frequency updates with large payloads.

**Cleanup.** `onChange` and `computed` return unsubscribe functions. Call them when you're done to avoid memory leaks — there's no automatic cleanup like React's `useEffect`.
