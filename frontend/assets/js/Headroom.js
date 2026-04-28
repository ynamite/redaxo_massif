import { increment } from '@/js/lib/store/index.js'
import { swup } from '@/js/swup/index.js'
import { gsap } from 'gsap'
import { ScrollTrigger } from 'gsap/ScrollTrigger'

gsap.registerPlugin(ScrollTrigger)

let scrollTrigger = null

const Headroom = () => {
  const $html = document.documentElement
  const $body = $html.querySelector('body')
  $html.classList.remove('hr-not-top')
  $html.classList.remove('hr-bottom')
  $html.classList.remove('hr-pinned')
  requestAnimationFrame(() => {
    increment('scrollTriggerOrder')
    scrollTrigger = ScrollTrigger.create({
      trigger: $body,
      start: '100 top',
      end: 'bottom bottom',
      onUpdate: async (self) => {
        self.progress === 0
          ? $html.classList.remove('hr-not-top')
          : $html.classList.add('hr-not-top')

        self.progress === 1
          ? $html.classList.add('hr-bottom')
          : $html.classList.remove('hr-bottom')

        self.direction === -1
          ? $html.classList.remove('hr-pinned')
          : $html.classList.add('hr-pinned')
      }
    })
  })
}

Headroom()

swup.hooks.on(
  'page:view',
  () => {
    if (scrollTrigger) {
      scrollTrigger.refresh()
    }
  }
  // { before: true }
)
// swup.hooks.on('page:view', () => {
//   Headroom()
// })
