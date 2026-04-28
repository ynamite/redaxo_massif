export function debounce(fn, delay = 300) {
  let timeout
  return (...args) => {
    clearTimeout(timeout)
    timeout = setTimeout(() => fn(...args), delay)
  }
}

export const wait = (ms) => new Promise((resolve) => setTimeout(resolve, ms))

export function groupBy(arr, key) {
  return arr.reduce((acc, item) => {
    const val = typeof key === 'function' ? key(item) : item[key]
    acc[val] = acc[val] || []
    acc[val].push(item)
    return acc
  }, {})
}

export function serializeParams(params) {
  return Object.entries(params)
    .map(
      ([key, val]) => `${encodeURIComponent(key)}=${encodeURIComponent(val)}`
    )
    .join('&')
}
