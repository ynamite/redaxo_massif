import { gsap } from 'gsap'

let accordions = new Map()

export class Accordion {
  constructor(
    el,
    options = {
      toggleSelector: '[data-accordion-toggle]',
      contentSelector: '[data-accordion-content]',
      hooks: {}
    }
  ) {
    this.el = el
    this.toggle = el.querySelector(options.toggleSelector)
    this.content = el.querySelector(options.contentSelector)
    this.hooks = options.hooks
    // Store the animation object (so we can cancel it if needed)
    this.animation = null
    // Store if the element is closing
    this.isClosing = false
    // Store if the element is expanding
    this.isExpanding = false
    // Detect user clicks on the toggle element
    this.toggle.addEventListener('click', (e) => this.onClick(e))
    return this
  }

  async onClick(e) {
    // Stop default behaviour from the browser
    e.preventDefault()
    // Add an overflow on the <details> to avoid content overflowing
    this.content.style.overflow = 'hidden'
    // Check if the element is being closed or is already closed
    if (this.isClosing || this.content.hidden) {
      // close other accordions
      if (accordions) {
        for (const accordion of accordions.values()) {
          if (accordion !== this && !accordion.content.hidden) {
            await accordion.shrink()
          }
        }
      }
      this.open()
      // Check if the element is being opened or is already open
    } else if (this.isExpanding || !this.content.hidden) {
      this.shrink()
    }
  }

  async shrink() {
    // Set the element as "being closed"
    this.toggle.setAttribute('aria-expanded', 'false')
    this.isClosing = true
    const endHeight = `0px`
    const duration = this.#getDuration()

    // Set the element as "being expanding"
    await gsap.to(this.content, {
      height: endHeight,
      duration: duration,
      ease: 'back.in(0.6)',
      clearProps: true
    })
    this.content.hidden = true
    if (this.hooks?.closed) {
      await this.#callback(this.hooks.closed)
    }
    this.finish()
  }

  open() {
    // Apply a fixed height on the element
    this.toggle.setAttribute('aria-expanded', 'true')
    this.content.style.height = `${this.content.offsetHeight}px`
    // Force the [open] attribute on the details element
    this.content.hidden = false
    // Wait for the next frame to call the expand function
    // window.requestAnimationFrame(() => this.expand())
    gsap.delayedCall(0, () => this.expand())
  }

  async expand() {
    // Set the element as "being expanding"
    this.isExpanding = true
    // duration based on height (shorter for small content, longer for large content)
    const duration = this.#getDuration()
    await gsap.to(this.content, {
      height: 'auto',
      duration: duration,
      ease: 'back.out(0.6)',
      clearProps: true
    })
    if (this.hooks?.opened) {
      await this.#callback(this.hooks.opened)
    }
    this.finish()
  }

  finish() {
    this.isClosing = false
    this.isExpanding = false
    // Remove the overflow hidden and the fixed height
    this.content.style.height = this.content.style.overflow = ''
  }

  #getDuration() {
    return Math.min(Math.max(this.content.scrollHeight / 1000, 0.5), 1.2)
  }

  async #callback(fn) {
    if (typeof fn === 'function') {
      return await fn(this)
    }
    return null
  }
}
class Accordions {
  constructor(elements, options = {}) {
    if (!elements.length) return
    accordions = new Map()
    Array.from(elements).map((el) =>
      accordions.set(el, new Accordion(el, options))
    )
  }
  getAccordions() {
    return accordions
  }
}

export default Accordions
