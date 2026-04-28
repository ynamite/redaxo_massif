import { swup } from '@/js/swup/index.js'
const init = async () => {
  const has = document.querySelector('.rex-yform')
  if (has) {
    const Form = (await import('@/js/lib/form/index.js')).default
    const form = new Form('.rex-yform', {
      //animateLabels: true,
      // callbacks: { initForm: [formToggleFields] }
    })

    form.init()
  }
}
init()
swup.hooks.on('page:view', () => {
  init()
})
