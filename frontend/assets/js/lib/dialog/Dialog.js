/*!
 * massif menu
 * @author: Yves Torres, studio@massif.ch
 */
import { gsap } from 'gsap'
import { logger } from '@/js/lib/logger/index.js'
import { addEvent } from '@/js/lib/events/index.js'
import { ScrollTrigger } from 'gsap/ScrollTrigger'

gsap.registerPlugin(ScrollTrigger)

class Dialog {
  hooks = {}
  constructor(dialog, { focusFirstInteractiveElement = false } = {}) {
    logger.log('Initializing dialog...')
    this.dialog = dialog
    if (!this.dialog) return
    this.backdrop = this.dialog.querySelector('::backdrop')
    this.focusFirstInteractiveElement = focusFirstInteractiveElement
    this.isOpen = false
    this.lockY = 0
    this.clickedTrigger = null
    this.closeButtons = this.dialog.querySelectorAll('[data-dialog-close]')
    this.shouldShowSpinner = false
    this.spinner = this.dialog.querySelector('[data-dialog-spinner]')
    this.innerElement = this.dialog.querySelector('[data-dialog-inner]')
    this.init()
  }

  init() {
    // Menu toggle button
    addEvent(document, 'click.dialogHandler', (event) => {
      let target = event.target.closest('[data-dialog-target]')
      if (target) {
        const dialogId = target.getAttribute('data-dialog-target')
        if (dialogId === this.dialog.id) {
          event.preventDefault()
          this.clickedTrigger = target
          this.toggle()
        }
      } else {
        target = event.target.closest('[data-dialog-content]')
        if (target) return
        target = event.target.closest('dialog')
        if (this.isOpen && target === this.dialog) {
          event.preventDefault()
          this.close()
        }
      }
    })
    // Close buttons inside dialog
    this.closeButtons.forEach((btn) => {
      addEvent(btn, 'click.dialogClose', (e) => {
        e.preventDefault()
        this.close()
        btn.blur()
      })
    })
    // Escape key to close
    addEvent(document, 'keydown.dialogClose', (e) => {
      if (e.key === 'Escape' && this.isOpen) {
        e.preventDefault()
        this.close()
        this.dialog.blur()
      }
    })
  }

  toggle() {
    this.isOpen ? this.close() : this.open()
  }

  async open() {
    if (this.isOpen) return

    console.log('Opening dialog...')
    this.isOpen = true

    // add class to html element
    document.documentElement.classList.add('dialog-open')

    this.lockScroll()

    if (this.hooks?.onDialogOpening) {
      this.callback(this.hooks.onDialogOpening)
    }
    if (this.shouldShowSpinner) {
      this.showSpinner()
      gsap.set(this.innerElement, { autoAlpha: 0 })
    }

    // Slide in menu with spring animation
    this.dialog.showModal()
    await gsap.from(this.dialog, {
      y: 100,
      autoAlpha: 0,
      duration: 0.5,
      ease: 'back.out(1.2)',
      clearProps: true
    })

    if (this.shouldShowSpinner) {
      gsap.to(this.innerElement, { autoAlpha: 1, duration: 0.3 })
      this.hideSpinner()
    }

    if (this.hooks?.onDialogOpened) {
      await this.callback(this.hooks.onDialogOpened)
    }

    // Focus management
    if (this.focusFirstInteractiveElement) {
      setTimeout(() => {
        // Focus the first menu link instead of a close button
        const firstLink = this.dialog.querySelector(
          'a, button, input, select, textarea, [tabindex]:not([tabindex="-1"])'
        )
        if (firstLink) firstLink.focus()
      }, 100)
    }
  }

  async close() {
    if (!this.isOpen) return

    this.isOpen = false

    // Remove focusable elements from tab order

    await gsap.to(this.dialog, {
      y: 100,
      autoAlpha: 0,
      duration: 0.4,
      ease: 'back.in(1.2)',
      clearProps: true
    })
    this.unlockScroll()
    this.dialog.close()

    // remove class from html element
    document.documentElement.classList.remove('dialog-open')

    this.clickedTrigger?.blur() // remove focus from trigger
    this.clickedTrigger = null

    if (this.hooks?.onDialogClosed) {
      await this.callback(this.hooks.onDialogClosed)
    }
  }

  showSpinner() {
    this.spinner.classList.remove('hidden')
    gsap.set(this.spinner, { autoAlpha: 1 })
  }
  hideSpinner() {
    gsap.set(this.spinner, { autoAlpha: 0 })
  }

  lockScroll() {
    this.lockY = window.scrollY || document.documentElement.scrollTop
    // Freeze the page at its current position (iOS-safe)
    document.body.style.position = 'fixed'
    document.body.style.top = `-${this.lockY}px`
    document.body.style.left = '0'
    document.body.style.right = '0'
    document.body.style.width = '100%'
    document.body.style.overflowY = 'scroll'
    ScrollTrigger.refresh()
  }

  unlockScroll() {
    document.documentElement.classList.add('disable-smooth-scroll')
    document.body.style.position = ''
    document.body.style.top = ''
    document.body.style.left = ''
    document.body.style.right = ''
    document.body.style.width = ''
    document.body.style.overflowY = ''
    window.scrollTo(0, this.lockY) // restore scroll position precisely
    ScrollTrigger.refresh()
    setTimeout(() => {
      document.documentElement.classList.remove('disable-smooth-scroll')
    })
  }

  async callback(fn) {
    if (typeof fn === 'function') {
      return await fn(this)
    }
    return null
  }
}

export default Dialog
