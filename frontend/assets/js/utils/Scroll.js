import { swup } from '@/js/swup/index.js'

export const retainScrollPosition = () => {
  var localStorageSupport = supportsLocalStorage()

  if (!localStorageSupport || browserIsIE()) return

  var date = localStorage.getItem('scroll-pos-date')
  localStorage.setItem('scroll-pos-date', new Date())

  window.addEventListener('beforeunload', function () {
    localStorage.setItem('scroll-pos', window.pageYOffset)
  })

  if (!date) return

  var diff = Math.abs(new Date() - new Date(date))
  var timePassed = diff / 1000 / 60

  if (timePassed >= 5) return
  var top = localStorage.getItem('scroll-pos')
  if (top !== null) {
    window.scroll(0, parseInt(top, 10))
  }
}

export const scrollElementIntoView = ($el, offset = 0, animate = true) => {
  if (!$el) return
  const scrollTop =
    $el.getBoundingClientRect().top + window.pageYOffset - offset
  swup.scrollTo(scrollTop, animate)
}