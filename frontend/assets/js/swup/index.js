/*!
 * massif swup
 * @author: Yves Torres, studio@massif.ch
 */
import './style.css'
import Swup from 'swup'
import SwupPreloadPlugin from '@swup/preload-plugin'
import SwupBodyClassPlugin from '@swup/body-class-plugin'
import SwupHeadPlugin from '@swup/head-plugin'
import SwupJsPlugin from '@swup/js-plugin'
import SwupA11yPlugin from '@swup/a11y-plugin'
import SwupMorphPlugin from 'swup-morph-plugin'
import SwupRouteNamePlugin from '@swup/route-name-plugin'
import SwupScrollPlugin from '@swup/scroll-plugin'
// import SwupFragmentPlugin from '@swup/fragment-plugin'
import { animations } from '@/js/swup/animations'
import { init as initApp } from '@/js/Main.js'

const computedStyle = getComputedStyle(document.documentElement, null)

export const swup = new Swup({
  ignoreVisit: (url, { el } = {}) =>
    el?.closest('[href^="tel:"]') ||
    el?.closest('[href^="mailto:"]') ||
    el?.closest('[target="_blank"]') ||
    el?.closest('[data-no-swup]') ||
    el?.closest('[data-modal]') ||
    el?.closest('.glightbox'),
  containers: ['#content'],
  native: false,
  plugins: [
    new SwupPreloadPlugin({
      preloadInitialPage: false,
      preloadHoveredLinks: process.env.NODE_ENV !== 'development'
    }),
    new SwupBodyClassPlugin(),
    new SwupHeadPlugin({
      persistAssets: true
    }),
    new SwupJsPlugin({ animations }),
    new SwupA11yPlugin({
      respectReducedMotion: false
    }),
    new SwupMorphPlugin({
      containers: ['#nav-desktop', '#nav-mobile', '#footer']
    }),
    new SwupRouteNamePlugin(),
    new SwupScrollPlugin({
      animateScroll: {
        betweenPages: true,
        samePageWithHash: true,
        samePage: true
      },      
      shouldResetScrollPosition: (link) => !link.matches('.back-btn'),
      /**
       * Overwrite swup's scrollTo function
       */
      scrollFunction: function (el, top, left, animate, start, end) {
        if (!animate) {
          document.documentElement.classList.add('disable-smooth-scroll')
        }
        const currentTarget = document.querySelector(
          '[data-swup-scroll-target]'
        )
        const { hash } = swup?.visit?.to
        if (hash) {
          const scrollTarget = swup.getAnchorElement(hash)
          const target = scrollTarget?.querySelector('h1') || scrollTarget
          if (currentTarget !== target) {
            currentTarget?.removeAttribute('data-swup-scroll-target')
            target?.setAttribute('data-swup-scroll-target', '')
          }
        }

        const scrollPadding = parseFloat(
          computedStyle.getPropertyValue('scroll-padding-top'),
          10
        )
        start()
        window.scrollTo(0, top - scrollPadding)
        end()
        if (!animate) {
          document.documentElement.classList.remove('disable-smooth-scroll')
        }
      }
    })
  ]
})

swup.hooks.on('content:replace', () => {
  const $content = document.querySelector('#content')
})
swup.hooks.on('page:view', () => {
  initApp()
})
