import { logger } from '@/js/lib/logger/index.js'

class DesktopMenu {
  constructor({ dropdowns = true } = {}) {
    logger.log('Initializing desktop menu...')
    this.menu = document.querySelector('#nav-desktop')
    if (!this.menu) return
    this.menuItems = this.menu.querySelectorAll('.rex-navi1 > li')
    this.dropdowns = dropdowns && this.menu.querySelectorAll('.rex-navi2')
    this.flipState = null

    this.init()
    if (dropdowns) this.setupDropdowns()
  }

  init() {}

  setupDropdowns() {
    if (!this.dropdowns || !this.dropdowns.length) return
  }

  async openDropdown(dropdown) {
    if (!dropdown) return
  }

  async closeDropdown(dropdown) {
    if (!dropdown) return
  }
  getActiveItem = () => {
    return [...this.menuItems].find(
      (item) =>
        item.classList.contains('rex-active') ||
        item.classList.contains('rex-current')
    )
  }

  setActiveItem = (item) => {
    const activeItem = this.getActiveItem()
    if (activeItem) {
      activeItem.classList.remove('rex-active', 'rex-current')
      activeItem
        .querySelector('a')
        .classList.remove('rex-active', 'rex-current')
    }
    item.classList.add('rex-active', 'rex-current')
    item.querySelector('a').classList.add('rex-active', 'rex-current')
  }
}

export default DesktopMenu
