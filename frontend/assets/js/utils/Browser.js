export const browserIsIE = () => {
  return /*@cc_on!@*/ false || !!document.documentMode
}

export const supportsLocalStorage = () => {
  try {
    localStorage.setItem('_', '_')
    localStorage.removeItem('_')
    return true
  } catch (e) {
    return false
  }
}
export async function copyToClipboard(text) {
  try {
    await navigator.clipboard.writeText(text)
    return true
  } catch (err) {
    console.error('Copy failed', err)
    return false
  }
}

export const localStore = {
  get: (k) => JSON.parse(localStorage.getItem(k)),
  set: (k, v) => localStorage.setItem(k, JSON.stringify(v)),
  remove: (k) => localStorage.removeItem(k)
};