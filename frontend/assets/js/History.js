import { swup } from '@/js/swup/index.js'

swup.hooks.on('page:view', () => {
  const backBtns = document.querySelectorAll('.back-btn')
  if (backBtns) {
    backBtns.forEach((btn) => {
      btn.href = 'javascript:void(0);'
      btn.addEventListener('click', (event) => {
        event.preventDefault()
        window.history.back()
      })
    })
  }
})
