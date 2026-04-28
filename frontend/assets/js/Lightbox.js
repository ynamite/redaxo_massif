import { swup } from '@/js/swup/index.js'

const has = document.querySelector('[data-glightbox]')
if (has) {
  const glightbox = (await import('glightbox/src/js/glightbox.js')).default
  await import('glightbox/dist/css/glightbox.css')
  const bookingAnchors = document.querySelectorAll('[data-glightbox]')
  const setup = () => {
    const lightbox = glightbox({ selector: null })
    lightbox.setElements([
      {
        href: bookingAnchors[0].href,
        width: '1040',
        height: '100dvh'
      }
    ])
    bookingAnchors.forEach((anchor) => {
      anchor.addEventListener('click', (e) => {
        e.preventDefault()
        lightbox.open()
      })
    })
  }
  setup()
  swup.hooks.on('page:view', () => {
    setup()
  })
}
