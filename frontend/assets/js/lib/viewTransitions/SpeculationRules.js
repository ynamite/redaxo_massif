if (
  HTMLScriptElement.supports &&
  HTMLScriptElement.supports('speculationrules')
) {
  const specScript = document.createElement('script')
  specScript.type = 'speculationrules'
  const specRules = {
    prefetch: [
      {
        where: {
          and: [
            { href_matches: '/*' },
            { not: { selector_matches: '[data-no-prefetch]' } },
            { not: { selector_matches: '[data-no-preload]' } }
          ]
        },
        eagerness: 'eager'
      }
    ],
    prerender: [
      {
        where: {
          and: [
            { href_matches: '/*' },
            { not: { selector_matches: '[data-no-prefetch]' } },
            { not: { selector_matches: '[data-no-preload]' } }
          ]
        },
        eagerness: 'moderate'
      }
    ]
  }
  specScript.textContent = JSON.stringify(specRules)
  document.body.append(specScript)
} else {
  // get all anchors pointing to the same origin
  const linkElems = document.querySelectorAll('a[href^="/"]')
  linkElems.forEach((linkElem) => {
    const prefetchLink = document.createElement('link')
    prefetchLink.rel = 'prefetch'
    prefetchLink.href = linkElem.href
    document.head.append(prefetchLink)
  })
}
