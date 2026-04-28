const get = (name) => {
  const value = `; ${document.cookie}`
  const parts = value.split(`; ${name}=`)
  if (parts.length === 2) {
    return decodeURIComponent(parts.pop().split(';').shift())
  }
  return null
}

const remove = (name, options = {}) => {
  set(name, '', { ...options, expires: -1 })
}

const set = (name, value, options = {}) => {
  let cookieString = `${name}=${encodeURIComponent(value)}`

  if (options.expires) {
    if (options.expires instanceof Date) {
      cookieString += `; expires=${options.expires.toUTCString()}`
    } else {
      // Assume it's days
      const date = new Date()
      date.setDate(date.getDate() + options.expires)
      cookieString += `; expires=${date.toUTCString()}`
    }
  }
  if (!options?.sameSite) {
    options.sameSite = 'strict' // Default to Strict if not specified
  }
  if (!options?.path) {
    options.path = '/'
  }
  if (!options.secure) {
    options.secure = true // Default to true if not specified
  }

  if (options.path) cookieString += `; path=${options.path}`
  if (options.domain) cookieString += `; domain=${options.domain}`
  if (options.secure) cookieString += `; secure`
  if (options.sameSite) cookieString += `; samesite=${options.sameSite}`

  document.cookie = cookieString
}

export { get as getCookie, remove as removeCookie, set as setCookie }
