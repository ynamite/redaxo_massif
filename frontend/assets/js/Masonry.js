import { gsap } from 'gsap'
import { getCustomPropertyValue } from '@/js/utils/Dom.js'

class MasonryList extends HTMLElement {
  #patched = false
  #observer = null

  get patched() {
    return this.#patched
  }

  #layout = () => {
    const rowGap = getCustomPropertyValue(this, '--row-gap')
    this.style.setProperty('--masonry-list-row-gap', `${Math.round(rowGap)}`)
  }

  connectedCallback() {
    const style = getComputedStyle(this)
    if (style.gridTemplateRows === 'masonry') return
    this.#patched = true

    this.style.gridAutoRows = '0px'
    this.style.setProperty('row-gap', '1px', 'important')

    this.#observer = new ResizeObserver(this.#layout)
    this.#observer.observe(this)
    this.#layout()

    gsap.to(this, {
      opacity: 1,
      duration: 1,
      delay: 0.1,
      ease: 'power2.out'
    })
  }

  disconnectedCallback() {
    this.#observer?.disconnect()
  }
}

class MasonryItem extends HTMLElement {
  #observer = null

  #layout = () => {
    const { height } = this.getBoundingClientRect()
    this.style.gridRowEnd = `span calc(${Math.round(
      height
    )} + var(--masonry-list-row-gap))`
  }

  connectedCallback() {
    const masonry = this.closest('masonry-list')
    if (!masonry?.patched) return

    this.#observer = new ResizeObserver(this.#layout)
    this.#observer.observe(this)
    this.#layout()
  }

  disconnectedCallback() {
    this.#observer?.disconnect()
  }
}

if (typeof window !== 'undefined') {
  customElements.define('masonry-list', MasonryList)
  customElements.define('masonry-item', MasonryItem)
}
