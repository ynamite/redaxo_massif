import { swup } from '@/js/swup/index.js'

const setup = async () => {
  const has = document.querySelector('details')
  if (has) {
    const Accordions = (await import('@/js/lib/accordion/details.js')).default
    const elements = document.querySelectorAll('details')
    new Accordions(elements)
  }
}

setup()
swup.hooks.on('page:view', () => {
  setup()
})
