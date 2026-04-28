import { gsap } from 'gsap'

const getContent = () => document.querySelector('#content')

const animations = [
  {
    from: '(.*)',
    to: '(.*)',
    out: async () => {
      await gsap.to(getContent(), {
        opacity: 0,
        duration: 0.2
      })
    },
    in: async () => {
      await gsap.from(getContent(), {
        opacity: 0,
        duration: 0.2
      })
    }
  }
]

export { animations }
