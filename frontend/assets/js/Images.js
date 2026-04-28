import lazysizes from 'lazysizes'
import parentFit from 'lazysizes/plugins/parent-fit/ls.parent-fit'
import unveilhooks from 'lazysizes/plugins/unveilhooks/ls.unveilhooks'
import respimg from 'lazysizes/plugins/respimg/ls.respimg'
import objectFit from 'lazysizes/plugins/object-fit/ls.object-fit'
import native from 'lazysizes/plugins/native-loading/ls.native-loading'
import { logger } from '@/js/lib/logger/index.js'
import { browserIsIE } from '@/js/utils/Browser.js'

const isIE = browserIsIE()

logger.log('images')

if (isIE) {
  document.querySelectorAll('.img-cell img').forEach(($img) => {
    const container = $img.parentElement
    bgPos =
      $img.dataset?.bgPost && $img.dataset?.bgPost != '%'
        ? $img.dataset.bgPost
        : ''
    let styleStr = container.getAttribute('style')
    container.setAttribute('style', `background-position: ${bgPos};${styleStr}`)
  })
}

export { lazysizes, parentFit, unveilhooks, respimg, objectFit, native }
