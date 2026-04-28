/*!
 * massif event bus
 * @author: Yves Torres, studio@massif.ch
 */

import { swup } from '@/js/swup/index.js'

const controllers = new Map()
const observers = {}

/*
 * Add event listener with AbortController
 * element: DOM element
 * type: event type (e.g. 'click')
 * label: label for the event (e.g. 'eventName.namespace')
 * fn: event handler function
 */
const addEvent = (element, _type, fn) => {
  if (!element) return
  let [type, namespace] = _type.split('.')
  if (!namespace) namespace = 'default'
  const controller = new AbortController()
  const signal = controller.signal
  if (!controllers.has(element)) {
    controllers.set(element, new Map())
  }
  if (!controllers.get(element).has(namespace)) {
    controllers.get(element).set(namespace, new Map())
  }
  if (!controllers.get(element).get(namespace).has(type)) {
    controllers.get(element).get(namespace).set(type, [])
  }
  controllers.get(element).get(namespace).get(type).push(controller)
  element.addEventListener(type, fn, { signal })
}

/*
 * Remove event listener with AbortController
 * label: label for the event (e.g. 'eventName.namespace', 'name' or '.namespace' to remove all in namespace)
 * element: DOM element (optional, if not provided, removes from all elements)
 */
const removeEvent = (_type, element = null) => {
  let [type, namespace] = _type.split('.')
  if (!namespace) namespace = 'default'

  // If no element specified, remove from all elements
  if (namespace && (element === null || element === undefined || type === '')) {
    return removeAllEvents(namespace)
  }

  if (!controllers.has(element)) return
  if (!controllers.get(element).has(namespace)) return
  if (type && !controllers.get(element).get(namespace).has(type)) return

  // Abort all controllers for this type
  const controllerArray = controllers.get(element).get(namespace).get(type)
  controllerArray.forEach((controller) => controller.abort('removed'))

  // Clean up: remove the type entry
  controllers.get(element).get(namespace).delete(type)

  // Clean up: remove namespace if empty
  if (controllers.get(element).get(namespace).size === 0) {
    controllers.get(element).delete(namespace)
  }

  // Clean up: remove element if empty
  if (controllers.get(element).size === 0) {
    controllers.delete(element)
  }
}

const removeAllEvents = (namespace = null) => {
  // Create a copy of keys to avoid mutation during iteration
  const elements = [...controllers.keys()]
  for (const element of elements) {
    if (namespace) {
      // Remove specific namespace from this element
      if (!controllers.get(element).has(namespace)) continue
      const types = [...controllers.get(element).get(namespace).keys()]
      for (const type of types) {
        removeEvent(`${type}.${namespace}`, element)
      }
    } else {
      // Remove all namespaces from this element
      const namespaces = [...controllers.get(element).keys()]
      for (const ns of namespaces) {
        const types = [...controllers.get(element).get(ns).keys()]
        for (const type of types) {
          removeEvent(`${type}.${ns}`, element)
        }
      }
    }
  }
}

const connectObserver = (element, type, label, fn) => {
  if (!element) return
  const [name, namespace] = label.split('.')
  let observer
  switch (type) {
    case 'resize':
      observer = new ResizeObserver(() => {
        fn()
      })
      break
    case 'scroll':
      observer = new IntersectionObserver(() => {
        fn()
      })
      break
  }
  observer.observe(element)
  if (namespace) {
    if (!observers[namespace]) observers[namespace] = {}
    observers[namespace][name] = observer
  } else observers[name] = observer
}

const disconnectObserver = (label) => {
  const [name, namespace] = label.split('.')
  if (namespace) {
    if (!observers[namespace] || !observers[namespace][name]) return
    observers[namespace][name].disconnect()
    delete observers[namespace][name]
    if (Object.keys(observers[namespace]).length === 0)
      delete observers[namespace]
  } else {
    if (!observers[name]) return
    observers[name].disconnect()
    delete observers[name]
  }
}

const disconnectAllObservers = (namespace = null) => {
  if (namespace) {
    if (!observers[namespace]) return
    for (const name in observers[namespace]) {
      disconnectObserver(`${name}.${namespace}`)
    }
  } else {
    for (const label in observers) {
      if (
        typeof observers[label] === 'object' &&
        typeof observers[label]?.disconnect !== 'function'
      ) {
        const namespace = label
        for (const name in observers[namespace]) {
          disconnectObserver(`${name}.${namespace}`)
        }
      } else disconnectObserver(label)
    }
  }
}

swup.hooks.on(
  'page:view',
  () => {
    removeAllEvents()
    disconnectAllObservers()
  },
  { before: true }
)

export {
  addEvent,
  removeEvent,
  removeAllEvents,
  connectObserver,
  disconnectObserver,
  disconnectAllObservers
}
