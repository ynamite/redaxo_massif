export const $ = (sel, root = document) => root.querySelector(sel);
export const $$ = (sel, root = document) => [...root.querySelectorAll(sel)];

export const decryptEmailaddresses = () => {
  // Ersetze E-Mailadressen
  document.querySelectorAll('span.unicorn').forEach(function (element) {
    element.insertAdjacentText('afterend', '@')
    element.remove()
  })

  // Ersetze mailto-Links
  document
    .querySelectorAll('a[href^="javascript:decryptUnicorn"]')
    .forEach(function (element) {
      // Selektiere Einhorn-Werte
      var emails = element.getAttribute('href').match(/\((.*)\)/)[1]

      emails = emails
        // ROT13-Transformation
        .replace(/[a-z]/gi, function (s) {
          return String.fromCharCode(
            s.charCodeAt(0) + (s.toLowerCase() < 'n' ? 13 : -13)
          )
        })
        // Ersetze # durch @
        .replace(/\|/g, '@')

      // Ersetze Einhörner
      element.setAttribute('href', 'mailto:' + emails)
    })
}

export const elementIsVisibleInViewport = (el, partiallyVisible = true) => {
  const { top, left, bottom, right } = el.getBoundingClientRect()
  const { innerHeight, innerWidth } = window
  const viewPortTop = 0
  const viewPortBottom = innerHeight
  // console.log(
  //     el,
  //     'top: ' + top,
  //     'viewPortTop: ' + viewPortTop,
  //     'bottom: ' + bottom,
  //     'viewPortBottom ' + viewPortBottom
  // );
  //  top: 2470.10009765625 viewPortTop: 1952 bottom: 3213.6334228515625 viewPortBottom: 2946
  return partiallyVisible
    ? ((top <= viewPortTop &&
        bottom <= viewPortBottom &&
        bottom > viewPortTop) || // bottom is visible and in viewport
        (top >= viewPortTop && bottom <= viewPortBottom) || // is in viewport
        (top >= viewPortTop &&
          bottom >= viewPortBottom &&
          top < viewPortBottom) || // top is visible and in viewport
        (top <= viewPortBottom && bottom >= viewPortBottom)) && // is in viewport, but extends beyond top and bottom
        ((left > 0 && left < innerWidth) || (right > 0 && right < innerWidth))
    : top >= 0 && left >= 0 && bottom <= viewPortBottom && right <= innerWidth
}

export const getTemplate = (id) => {
  var template = document.getElementById('template-' + id)
  if (template) {
    return template.content.cloneNode(true)
  }
  return false
}

export const wrapInner = (parent, wrapper, attribute, attributevalue) => {
  if (typeof wrapper === 'string') {
    wrapper = document.createElement(wrapper)
  }
  parent.appendChild(wrapper).setAttribute(attribute, attributevalue)

  while (parent.firstChild !== wrapper) {
    wrapper.appendChild(parent.firstChild)
  }
}

export const getCustomPropertyValue = (target, propName) => {
  const probe = document.createElement('div')
  probe.style.cssText = `
    position:fixed;visibility:hidden;contain:strict;pointer-events:none;
    padding-left: var(${propName});
  `
  target.appendChild(probe)
  const px = parseFloat(getComputedStyle(probe).paddingLeft)
  target.removeChild(probe)
  return px
}