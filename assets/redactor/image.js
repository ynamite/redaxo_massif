/**
 * This file is part of the redactor package.
 *
 * @author (c) Friends Of REDAXO
 * @author <friendsof@redaxo.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
;(function ($R) {
  /**
   * Add webp and avif to allowed image types
   * Add sizes, loading and decoding to allowed image attributes
   */
  $R.opts.imageTypes.push('image/webp', 'image/avif')
  $R.opts.imageAttrs.push('sizes', 'loading', 'decoding')
  /**
   * Override Redactor Cleaner to allow loading and decoding attributes on images
   * Unfortunately we have to copy the whole function here, as imageattrs isn't originally retrieved from opts and Redactor does not provide hooks
   */
  $R.services.cleaner.prototype.input = function (html, paragraphize, started) {
    // fix &curren; entity in the links
    html = html.replace(/¤t/gi, '&current')

    // store
    var storedComments = []
    html = this.storeComments(html, storedComments)

    // pre/code
    html = this.encodeCode(html)

    // sanitize
    var $wrapper = this.utils.buildWrapper(html)
    $wrapper
      .find('a, b, i, strong, em, img, svg, details, audio')
      .removeAttr('onload onerror ontoggle onwheel onmouseover oncopy')
    $wrapper.find('a, iframe, embed').each(function (node) {
      var $node = $R.dom(node)
      var href = $node.attr('href')
      var src = $node.attr('src')
      if (href && href.trim().search(/^data|javascript:/i) !== -1)
        $node.attr('href', '')
      if (src && src.trim().search(/^data|javascript:/i) !== -1)
        $node.attr('src', '')
    })

    // this next line is the only changed part, the rest is copy of original Redactor code
    var imageattrs = this.opts.imageAttrs

    $wrapper.find('img').each(
      function (node) {
        if (node.attributes.length > 0) {
          var attrs = node.attributes
          for (var i = attrs.length - 1; i >= 0; i--) {
            var removeAttrs =
              attrs[i].name.search(/^data-/) === -1 &&
              imageattrs.indexOf(attrs[i].name) === -1
            var removeDataSrc =
              attrs[i].name === 'src' &&
              attrs[i].value.search(/^data|javascript:/i) !== -1
            if (this.opts.imageSrcData) removeDataSrc = false

            if (removeAttrs || removeDataSrc) {
              node.removeAttribute(attrs[i].name)
            }
          }
        }
      }.bind(this)
    )

    // get wrapper html
    html = this.utils.getWrapperHtml($wrapper)

    // converting entity
    html = html.replace(/\$/g, '&#36;')
    html = html.replace(/&amp;/g, '&')

    // convert to figure
    var converter = $R.create('cleaner.figure', this.app)
    html = converter.convert(html, this.convertRules)

    // store components
    html = this.storeComponents(html)

    // clean
    html = this.replaceTags(html, this.opts.replaceTags)
    html = this._setSpanAttr(html)
    html = this._setStyleCache(html)
    html = this.removeTags(html, this.deniedTags)
    html = this.opts.removeScript
      ? this._removeScriptTag(html)
      : this._replaceScriptTag(html)
    //html = (this.opts.removeScript) ? this._removeScriptTag(html) : html;
    html = this.opts.removeComments ? this.removeComments(html) : html
    html = this._isSpacedEmpty(html) ? this.opts.emptyHtml : html

    // restore components
    html = this.restoreComponents(html)

    // clear wrapped components
    html = this._cleanWrapped(html)

    // restore comments
    html = this.restoreComments(html, storedComments)

    // paragraphize
    html = paragraphize ? this.paragraphize(html) : html

    return html
  }

  /**
   * Override Redactor Image plugin to set handle figures and fetch processed image HTML from massif
   */

  const handleFigures = function ({ setCaption = true } = {}) {
    const self = this
    const $editor = this.app.editor.getElement()
    const $figures = $editor.find('figure')
    const maxWidth = rex.redactor_img_maxWidth || 1024

    $figures.each(function (figure) {
      const $figure = $(figure)
      let imageComponent = $R.create(
        'image.component',
        self.app,
        $figure.find('img')
      )
      let caption = setCaption
        ? imageComponent._get_title()
        : imageComponent._get_caption()
      if (!caption) $figure.find('figcaption').remove()
      imageComponent._set_caption(caption)
      const style = $figure.attr('style') || ''
      if (style.indexOf('max-width') === -1) {
        $figure.attr(
          'style',
          `margin-left: auto; margin-right: auto; max-width: ${maxWidth / 2}px;`
        )
      }
    })
  }

  const originalStart = $R.plugins.image.prototype.start
  $R.plugins.image.prototype = Object.assign($R.modules.image.prototype, {
    ...$R.plugins.image.prototype,
    start: function () {
      originalStart.call(this)
      const app = this.app
      this.opts = app.opts
      this.lang = app.lang
      this.caret = app.caret
      this.utils = app.utils
      this.editor = app.editor
      this.cleaner = app.cleaner
      this.source = app.source
      this.storage = app.storage
      this.component = app.component
      this.inspector = app.inspector
      this.insertion = app.insertion
      this.selection = app.selection
      this.justResized = false
      handleFigures.call(this, { setCaption: false })
    },
    _insert: async function (data) {
      const { label: filename } = data
      const maxWidth = rex.redactor_img_maxWidth || 1024
      if (!filename) return
      // fetch processed image HTML from massif
      const result = await fetch(
        `/redaxo/index.php?rex-api-call=massif_image_get&src=${filename}&maxWidth=${maxWidth}`,
        {
          method: 'GET',
          headers: {
            'Content-Type': 'text/html',
            'X-Requested-With': 'XMLHttpRequest'
          }
        }
      )
      if (!result.ok) throw new Error('Network response was not ok')
      let html = await result.text()
      let converter = $R.create('cleaner.figure', this.app)
      html = converter.convert(html, this.convertRules)
      this.insertion.insertRaw(html)
      handleFigures.call(this)
    },
    oncontextbar: function (e, contextbar) {
      if (this.justResized) {
        this.justResized = false
        return
      }

      const current = this.selection.getCurrent()
      const data = this.inspector.parse(current)
      const $img = $R.dom(current).closest('img')

      if (
        (!data.isFigcaption() && data.isComponentType('image')) ||
        $img.length !== 0
      ) {
        const node = $img.length !== 0 ? $img.get() : data.getComponent()
        const buttons = {
          edit: {
            title: this.lang.get('edit'),
            api: 'plugin.image.openModal'
          },
          change: {
            title: redactorTranslations.image_title,
            api: 'plugin.image.open'
          },
          remove: {
            title: this.lang.get('delete'),
            api: 'plugin.image.remove',
            args: node
          }
        }

        contextbar.set(e, node, buttons)
      }
    },

    onsynced: function (html) {
      this._clean(html)
    },

    // public
    openModal: function () {
      this.$image = this._getCurrent()
      this.app.api('module.modal.build', this._getModalData())
    },

    _buildPreview: function ($modal) {
      this.$preview = $modal.find('#redactor-modal-image-preview')

      this.$image = $R.modules.image.prototype._getCurrent.call(this)
      const element = $(this.$image.nodes[0]).find('img')
      const imageData = this.$image.getData()
      const $previewImg = $R.dom('<img>')
      $previewImg.attr('src', imageData.src)
      $previewImg.attr('srcset', element.attr('srcset'))
      $previewImg.attr('loading', 'lazy')

      this.$previewBox = $R.dom('<div>')
      this.$previewBox.append($previewImg)

      this.$preview.html('')
      this.$preview.append(this.$previewBox)
    },
    _clean: function (html) {
      // Remove style from figure tags
      const $html = $R.dom('<div>').html(html)
      $html.find('figure').removeAttr('style')
      html = $html.html()
      const $source = this.source.getElement()
      $source.val(html)
    }
  })
})(Redactor)
