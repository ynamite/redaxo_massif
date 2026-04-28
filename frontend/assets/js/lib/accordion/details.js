import { gsap } from 'gsap'

export class Accordion {
  constructor(el) {
    // Store the <details> element
    this.el = el
    // Store the <summary> element
    this.summary = el.querySelector('summary')
    // Store the <div class="content"> element
    this.content = el.querySelector('div')

    // Store the animation object (so we can cancel it if needed)
    this.animation = null
    // Store if the element is closing
    this.isClosing = false
    // Store if the element is expanding
    this.isExpanding = false
    // Detect user clicks on the summary element
    this.summary.addEventListener('click', (e) => this.onClick(e))
  }

  onClick(e) {
    // Stop default behaviour from the browser
    e.preventDefault()
    // Add an overflow on the <details> to avoid content overflowing
    this.el.style.overflow = 'hidden'
    // Check if the element is being closed or is already closed
    if (this.isClosing || !this.el.open) {
      this.open()
      // Check if the element is being opened or is already open
    } else if (this.isExpanding || this.el.open) {
      this.shrink()
    }
  }

  async shrink() {
    // Set the element as "being closed"
    this.isClosing = true
    const endHeight = `${this.summary.offsetHeight}px`
    const duration = this.#getDuration()

    // Set the element as "being expanding"
    await gsap.to(this.el, {
      height: endHeight,
      duration: duration,
      ease: 'back.in(1.2)',
      clearProps: true
    })
    this.el.open = false
    this.finish()
  }

  open() {
    // Apply a fixed height on the element
    this.el.style.height = `${this.el.offsetHeight}px`
    // Force the [open] attribute on the details element
    this.el.open = true
    // Wait for the next frame to call the expand function
    // window.requestAnimationFrame(() => this.expand())
    gsap.delayedCall(0, () => this.expand())
  }

  async expand() {
    // Set the element as "being expanding"
    this.isExpanding = true
    // duration based on height (shorter for small content, longer for large content)
    const duration = this.#getDuration()
    await gsap.to(this.el, {
      height: 'auto',
      duration: duration,
      ease: 'back.out(1.7)',
      clearProps: true
    })
    this.el.scrollIntoView()
    this.finish()
  }

  finish() {
    this.isClosing = false
    this.isExpanding = false
    // Remove the overflow hidden and the fixed height
    this.el.style.height = this.el.style.overflow = ''
  }

  #getDuration() {
    return Math.min(Math.max(this.content.scrollHeight / 1000, 0.5), 1.2)
  }
}
class Accordions {
  constructor(elements) {
    if (!elements.length) return
    elements.forEach((el) => new Accordion(el))
  }
}

export default Accordions
