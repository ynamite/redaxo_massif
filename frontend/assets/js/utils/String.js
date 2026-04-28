export const trimText = (str, maxlength = 20) => {
  if (!str) return ''
  var regex = /[!-\/:-@\[-`{-~]$/
  if (str.length > maxlength) {
    str = $.trim(str).substring(0, maxlength).split(' ').slice(0, -1).join(' ')
    // remove special chars from text end
    str = str.replace(regex, '')
    return str + '&hellip;'
  }
  return ''
}

export function slugify(str) {
  return str
    .toLowerCase()
    .trim()
    .replace(/[^a-z0-9 -]/g, '')
    .replace(/\s+/g, '-')
    .replace(/-+/g, '-')
}
