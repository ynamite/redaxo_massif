import './style.css'
import { addEvent, removeEvent } from '@/js/lib/events/index.js'
import swup from '@/js/swup/index.js'
import { lock, unlock } from 'tua-body-scroll-lock'
import { gsap } from 'gsap'

const htmlElement = document.documentElement

let zIndex = 50

const closeOpenDialogs = () => {
  const dialogs = document.querySelectorAll('dialog[open]')
  if (dialogs.length === 0) return
  dialogs.forEach((dialog) => {
    const content = dialog.querySelector('.dialog-content')
    closeDialog(dialog, content)
  })
}

const closeDialog = (dialog, content) => {
  if (!dialog || !content) {
    closeOpenDialogs()
    return
  }
  dialog.close('dismiss')
  setTimeout(() => {
    htmlElement.classList.remove('dialog-open')
  }, 375)
  setTimeout(() => {
    zIndex--
    unlock(content)
  }, 500)
}

const Dialogs = () => {
  const dialogTriggerButtons = document.querySelectorAll('[data-dialog-open]')
  const dialogs = document.querySelectorAll('dialog')
  dialogTriggerButtons.forEach((button) => {
    addEvent(
      button,
      'click.dialogOpen',
      (event) => {
        event.preventDefault()
        const dialogId = button.getAttribute('data-dialog-open')
        const dialog = document.getElementById(dialogId)
        if (dialog) {
          zIndex++
          dialog.style.zIndex = zIndex
          htmlElement.classList.add('dialog-open')
          const content = dialog.querySelector('.dialog-content')
          dialog.showModal()
          lock(content)
          content.scrollTop = 0
          // add event listener for esc key
          removeEvent('dialog.esc')
          addEvent(document, 'keydown.dialogEsc', (event) => {
            if (event.key === 'Escape') {
              event.preventDefault()
              closeDialog(dialog, content)
              removeEvent('dialog.esc')
            }
          })
        }
      },
      { passive: true }
    )
  })
  dialogs.forEach((dialog) => {
    const closeButtons = dialog.querySelectorAll('[data-dialog-close]')
    const toTopButton = dialog.querySelector('[data-dialog-scroll-to-top]')
    const content = dialog.querySelector('.dialog-content')
    closeButtons.forEach((button) => {
      addEvent(button, 'click.dialogClose', (event) => {
        event.preventDefault()
        closeDialog(dialog, content)
      })
    })

    if (toTopButton) {
      addEvent(
        toTopButton,
        'click.dialogScrollToTop',
        (event) => {
          event.preventDefault()
          gsap.to(content, {
            scrollTo: {
              y: 0,
              autoKill: false
            },
            duration: 0.5,
            ease: 'power2.inOut'
          })
        },
        { passive: true }
      )
    }
    dialog.addEventListener('click', (event) => {
      if (event.target === dialog) {
        closeDialog(dialog, content)
      }
    })
  })
}

swup.hooks.on('page:view', () => Dialogs())

export default Dialogs
