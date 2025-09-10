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
  // Add details and summary to block tags
  $R.opts.blockTags.push('details', 'summary')

  $R.add('plugin', 'details', {
    init: function (app) {
      this.app = app
      this.opts = app.opts
      this.lang = app.lang
      this.caret = app.caret
      this.utils = app.utils
      this.editor = app.editor
      this.source = app.source
      this.toolbar = app.toolbar
      this.insertion = app.insertion
      this.selection = app.selection
      this.cleaner = app.cleaner
    },

    start: function () {
      // Add toolbar button
      let obj = {
        title: redactorTranslations.details_title || 'Details',
        icon: false,
        tooltip:
          redactorTranslations.details_tooltip || 'Insert collapsible details',
        api: 'plugin.details.insert'
      }
      this.toolbar.addButton('details', obj)
    },

    // messages
    onbottomclick: function () {
      this.insertion.insertToEnd(this.editor.getLastNode(), 'details')
    },

    onstarted: function () {
      this._addProps()
      this._bindEventHandlers()
      this._addDragStyles()
    },

    oninsert: function () {
      this._bindEventHandlers()
    },

    onundo: function () {
      this._cleanupAllFeedback()
    },

    onredo: function () {
      this._cleanupAllFeedback()
    },

    onsynced: function (html) {
      const $html = $R.dom('<div>').html(html)
      $html.find('details').removeAttr('open')
      $html.find('details, details *').each(function (element) {
        element.removeAttribute('class')
        element.removeAttribute('style')
        element.removeAttribute('id')
        element.removeAttribute('draggable')
        element.removeAttribute('contenteditable')
        element.removeAttribute('data-details-component')
      })

      html = $html.html()
      const $source = this.source.getElement()
      $source.val(html)

      return html
    },

    // public
    insert: function () {
      this._insert()
    },

    // private
    _addProps: function () {
      const $editor = this.editor.getElement()
      // Add open, draggable and data attribute to existing details elements
      const $details = $editor.find('details')
      $details.attr({
        open: 'true',
        draggable: 'true',
        'data-details-component': 'true'
      })
      const $summary = $details.find('summary > div')
      $summary.attr({
        contenteditable: 'true'
      })
      const $content = $details.children('div')
      $content.attr({
        contenteditable: 'true'
      })
    },
    _insert: function () {
      // Check if we're inside a details element - prevent nesting
      var current = this.selection.getCurrent()
      var detailsParent = current ? $R.dom(current).closest('details') : null

      if (detailsParent.length) {
        // If inside details, insert after the details element instead
        var $detailsParent = $R.dom(detailsParent)
        var html =
          '<details open draggable="true" data-details-component="true">' +
          '<summary><div contenteditable="true">Summary</div></summary>' +
          '<div contenteditable="true">' +
          this.cleaner.paragraphize('Details content') +
          '</div>' +
          '</details>'

        $detailsParent.after(html)
        var insertedElement = $detailsParent.next().get()

        // Set focus to summary
        var summaryDiv = insertedElement.querySelector('summary div')
        if (summaryDiv) {
          this.caret.setStart(summaryDiv)
        }

        this._removeSpaceBefore(insertedElement)
        this._bindEventHandlers()
        return
      }

      // Create simple HTML structure with draggable attribute
      var html =
        '<details open draggable="true" data-details-component="true">' +
        '<summary><div contenteditable="true">Summary</div></summary>' +
        '<div contenteditable="true">' +
        this.cleaner.paragraphize('Details content') +
        '</div>' +
        '</details>'

      // Always insert at block level - force split if inside other elements
      var block = this.selection.getBlock()
      var insertion = null

      if (block && current !== block) {
        // We're inside a block element (like h2), force insert outside of it
        var $block = $R.dom(block)
        $block.after(html)
        var insertedElement = $block.next().get()

        // Set focus to summary
        var summaryDiv = insertedElement.querySelector('summary div')
        if (summaryDiv) {
          this.caret.setStart(summaryDiv)
        }

        insertion = insertedElement
      } else {
        // Normal insertion
        var inserted = this.insertion.insertHtml(html, false)
        if (inserted && inserted.length > 0) {
          var summaryDiv = inserted[0].querySelector('summary div')
          if (summaryDiv) {
            this.caret.setStart(summaryDiv)
          }
          insertion = inserted[0]
        }
      }
      if (insertion) {
        this._removeSpaceBefore(insertion)
      }

      // Bind events to the new element
      this._bindEventHandlers()
    },
    _removeSpaceBefore: function (element) {
      if (!element) return

      var prev = element.previousSibling
      var next = element.nextSibling
      var $prev = $R.dom(prev)
      var $next = $R.dom(next)

      if (this.opts.breakline) {
        if (next && $next.attr('data-redactor-tag') === 'br') {
          $next.find('br').first().remove()
        }
        if (prev && $prev.attr('data-redactor-tag') === 'br') {
          $prev.find('br').last().remove()
        }
      }

      if (prev) {
        this._removeInvisibleSpace(prev)
        this._removeInvisibleSpace(prev.previousSibling)
      }
    },
    _removeInvisibleSpace: function (el) {
      if (
        el &&
        el.nodeType === 3 &&
        this.utils.searchInvisibleChars(el.textContent) !== -1
      ) {
        el.parentNode.removeChild(el)
      }
    },
    _bindEventHandlers: function () {
      var self = this

      // Prevent details toggle with native DOM events to avoid Redactor conflicts
      var editorElement = this.editor.getElement().get()

      // Remove all existing event listeners first
      if (self._summaryClickHandler) {
        editorElement.removeEventListener(
          'click',
          self._summaryClickHandler,
          true
        )
        editorElement.removeEventListener(
          'mousedown',
          self._summaryClickHandler,
          true
        )
        editorElement.removeEventListener(
          'keydown',
          self._summaryKeyHandler,
          true
        )
      }

      // Create persistent handlers
      self._summaryClickHandler = function (e) {
        var summary = e.target.closest(
          'details[data-details-component] summary'
        )
        if (summary) {
          e.preventDefault()
          e.stopPropagation()
          e.stopImmediatePropagation()

          // Focus the editable div instead of toggling
          var summaryDiv = summary.querySelector('div[contenteditable]')
          if (summaryDiv) {
            summaryDiv.focus()

            // Set cursor position at click location or end
            if (e.type === 'click') {
              var range = document.createRange()
              var selection = window.getSelection()
              range.setStart(summaryDiv, 0)
              range.collapse(true)
              selection.addRange(range)
            }
          }
          return false
        }
      }

      self._summaryKeyHandler = function (e) {
        var summary = e.target.closest(
          'details[data-details-component] summary'
        )
        if (summary && (e.key === ' ' || e.key === 'Enter')) {
          e.preventDefault()
          e.stopPropagation()
          e.stopImmediatePropagation()
          return false
        }
      }

      // Add event listeners with capture to intercept before other handlers
      editorElement.addEventListener('click', self._summaryClickHandler, true)
      editorElement.addEventListener('keydown', self._summaryKeyHandler, true)

      // Handle drag and drop with native events
      if (self._dragStartHandler) {
        editorElement.removeEventListener('dragstart', self._dragStartHandler)
        editorElement.removeEventListener('dragover', self._dragOverHandler)
        editorElement.removeEventListener('drop', self._dropHandler)
        editorElement.removeEventListener('dragend', self._dragEndHandler)
      }

      // Create bound handlers
      self._dragStartHandler = function (e) {
        if (e.target.closest('details[data-details-component]')) {
          self._onDragStart(e)
        }
      }

      self._dragOverHandler = function (e) {
        if (self.draggedElement) {
          self._onDragOver(e)
        }
      }

      self._dropHandler = function (e) {
        if (self.draggedElement) {
          self._onDrop(e)
        }
      }

      self._dragEndHandler = function (e) {
        if (e.target.closest('details[data-details-component]')) {
          self._onDragEnd(e)
        }
      }

      // Add native event listeners
      editorElement.addEventListener('dragstart', self._dragStartHandler)
      editorElement.addEventListener('dragover', self._dragOverHandler)
      editorElement.addEventListener('drop', self._dropHandler)
      editorElement.addEventListener('dragend', self._dragEndHandler)
    },

    _onDragStart: function (e) {
      var detailsElement = e.target.closest('details[data-details-component]')
      if (!detailsElement) return

      this.draggedElement = detailsElement
      e.dataTransfer.effectAllowed = 'move'
      e.dataTransfer.setData('text/html', detailsElement.outerHTML)

      // Add visual feedback
      $R.dom(detailsElement).addClass('dragging')
    },

    _onDragOver: function (e) {
      e.preventDefault()
      e.dataTransfer.dropEffect = 'move'

      // Clear previous visual feedback
      this._clearDropFeedback()

      // Find the closest valid drop target
      var target = e.target
      var validTarget = null

      while (target && target !== this.editor.getElement().get()) {
        // Skip if target is the dragged element or inside it
        if (
          target === this.draggedElement ||
          this.draggedElement.contains(target)
        ) {
          target = target.parentNode
          continue
        }

        // Skip if target is inside another details element (prevent nesting)
        if (
          target.closest('details') &&
          target.closest('details') !== this.draggedElement
        ) {
          target = target.parentNode
          continue
        }

        // Allow block elements as drop targets
        if (this._isBlockElement(target)) {
          validTarget = target
          break
        }
        target = target.parentNode
      }

      if (validTarget) {
        this._showDropFeedback(validTarget, e.clientY)
      }
    },

    _onDrop: function (e) {
      e.preventDefault()

      if (!this.draggedElement) return

      // Clear visual feedback
      this._clearDropFeedback()

      // Find valid drop target (same logic as dragover)
      var target = e.target
      var validTarget = null

      while (target && target !== this.editor.getElement().get()) {
        // Skip if target is the dragged element or inside it
        if (
          target === this.draggedElement ||
          this.draggedElement.contains(target)
        ) {
          target = target.parentNode
          continue
        }

        // Skip if target is inside another details element (prevent nesting)
        if (
          target.closest('details') &&
          target.closest('details') !== this.draggedElement
        ) {
          target = target.parentNode
          continue
        }

        // Allow block elements as drop targets
        if (this._isBlockElement(target)) {
          validTarget = target
          break
        }
        target = target.parentNode
      }

      if (validTarget && validTarget !== this.draggedElement) {
        var rect = validTarget.getBoundingClientRect()
        // Use the same calculation as in the feedback function
        var topThreshold = rect.top + rect.height * 0.3
        var bottomThreshold = rect.top + rect.height * 0.7

        var insertAfter
        if (e.clientY < topThreshold) {
          insertAfter = false
        } else if (e.clientY > bottomThreshold) {
          insertAfter = true
        } else {
          insertAfter = false
        }

        // Store the dragged element reference before moving
        var draggedEl = this.draggedElement

        try {
          // Use Redactor's DOM methods for reliable insertion
          if (insertAfter) {
            $R.dom(validTarget).after(draggedEl)
          } else {
            $R.dom(validTarget).before(draggedEl)
          }

          // Clean up after successful move
          this._cleanupAfterMove(draggedEl)
        } catch (error) {
          console.warn('Details drag/drop failed:', error)
          // Fallback: don't move the element if there's an error
        }
      }

      this._cleanupDrag()
    },

    _onDragEnd: function (e) {
      this._cleanupDrag()
    },

    _cleanupDrag: function () {
      if (this.draggedElement) {
        $R.dom(this.draggedElement).removeClass('dragging')
        this.draggedElement = null
      }
      this._clearDropFeedback()
    },

    _isBlockElement: function (element) {
      var blockTags = [
        'p',
        'div',
        'h1',
        'h2',
        'h3',
        'h4',
        'h5',
        'h6',
        'blockquote',
        'pre',
        'ul',
        'ol',
        'li',
        'details'
      ]
      return blockTags.indexOf(element.tagName.toLowerCase()) !== -1
    },

    _cleanupAfterMove: function (element) {
      // Clean up attributes first
      this._cleanupElementAttributes(element)

      // Remove spacing issues
      this._removeSpaceBefore(element)

      // Clean up empty paragraphs that might have been created
      this._cleanupEmptyParagraphs(element)

      // Ensure proper paragraph after details if it's the last element
      this._ensureProperSpacing(element)

      // Clean up any orphaned empty elements around the moved element
      this._cleanupOrphanedElements()
    },

    _cleanupEmptyParagraphs: function (element) {
      // Remove empty paragraphs before and after the element
      var prev = element.previousSibling
      var next = element.nextSibling

      // Check previous sibling
      while (prev) {
        if (prev.nodeType === 1 && prev.tagName === 'P') {
          var isEmpty =
            !prev.textContent.trim() ||
            prev.innerHTML.trim() === '<br>' ||
            prev.innerHTML.trim() === '<br/>'
          if (isEmpty) {
            var toRemove = prev
            prev = prev.previousSibling
            toRemove.parentNode.removeChild(toRemove)
          } else {
            break
          }
        } else if (prev.nodeType === 3 && !prev.textContent.trim()) {
          // Remove empty text nodes
          var toRemove = prev
          prev = prev.previousSibling
          toRemove.parentNode.removeChild(toRemove)
        } else {
          break
        }
      }

      // Check next sibling
      while (next) {
        if (next.nodeType === 1 && next.tagName === 'P') {
          var isEmpty =
            !next.textContent.trim() ||
            next.innerHTML.trim() === '<br>' ||
            next.innerHTML.trim() === '<br/>'
          if (isEmpty) {
            var toRemove = next
            next = next.nextSibling
            toRemove.parentNode.removeChild(toRemove)
          } else {
            break
          }
        } else if (next.nodeType === 3 && !next.textContent.trim()) {
          // Remove empty text nodes
          var toRemove = next
          next = next.nextSibling
          toRemove.parentNode.removeChild(toRemove)
        } else {
          break
        }
      }
    },

    _cleanupOrphanedElements: function () {
      // Clean up any orphaned empty paragraphs in the entire editor
      var editor = this.editor.getElement().get()
      var emptyPs = editor.querySelectorAll('p')

      for (var i = 0; i < emptyPs.length; i++) {
        var p = emptyPs[i]
        var isEmpty =
          !p.textContent.trim() ||
          p.innerHTML.trim() === '<br>' ||
          p.innerHTML.trim() === '<br/>'
        var isOrphaned =
          isEmpty &&
          p.previousSibling &&
          p.previousSibling.tagName === 'DETAILS'

        if (isOrphaned) {
          // Check if this empty p is followed by another element (not just end of editor)
          if (p.nextSibling && p.nextSibling.nodeType === 1) {
            p.parentNode.removeChild(p)
          }
        }
      }
    },

    _ensureProperSpacing: function (element) {
      // Only add a paragraph after details if it's the last element in the editor
      var parent = element.parentNode
      var isLastChild = element === parent.lastElementChild

      if (isLastChild && parent === this.editor.getElement().get()) {
        // Add an empty paragraph for cursor placement
        var emptyP = document.createElement('p')
        emptyP.innerHTML = '<br>'
        parent.appendChild(emptyP)
      }
    },

    _addDragStyles: function () {
      if (document.getElementById('redactor-details-drag-styles')) return

      var style = document.createElement('style')
      style.id = 'redactor-details-drag-styles'
      style.textContent = `
        details[data-details-component].dragging {
          opacity: 0.5;
          cursor: grabbing !important;
        }
        details[data-details-component][draggable="true"] {
          cursor: grab;
        }
        details[data-details-component][draggable="true"]:hover {
          outline: 1px dashed #007cba;
        }
        .redactor-drop-active[data-drop-position="before"]::before {
          content: "";
          display: block;
          height: 3px;
          background: #007cba;
          margin: 2px 0;
          border-radius: 1px;
          box-shadow: 0 0 3px rgba(0,124,186,0.5);
        }
        .redactor-drop-active[data-drop-position="after"]::after {
          content: "";
          display: block;
          height: 3px;
          background: #007cba;
          margin: 2px 0;
          border-radius: 1px;
          box-shadow: 0 0 3px rgba(0,124,186,0.5);
        }
      `
      document.head.appendChild(style)
    },

    _showDropFeedback: function (element, clientY) {
      this._clearDropFeedback()

      var rect = element.getBoundingClientRect()
      // More precise calculation: use threshold zones
      var topThreshold = rect.top + rect.height * 0.3
      var bottomThreshold = rect.top + rect.height * 0.7

      var insertAfter
      if (clientY < topThreshold) {
        insertAfter = false
      } else if (clientY > bottomThreshold) {
        insertAfter = true
      } else {
        insertAfter = false
      }

      // Store position for drop logic
      this.dropTarget = {
        element: element,
        insertAfter: insertAfter
      }

      // Use visual feedback with CSS
      var position = insertAfter ? 'after' : 'before'
      element.setAttribute('data-drop-position', position)
      element.classList.add('redactor-drop-active')
    },

    _clearDropFeedback: function () {
      if (this.dropTarget) {
        this.dropTarget.element.removeAttribute('data-drop-position')
        this.dropTarget.element.classList.remove('redactor-drop-active')
        this.dropTarget = null
      }

      // Clear any lingering drop feedback
      var activeElements = this.editor
        .getElement()
        .get()
        .querySelectorAll('.redactor-drop-active')
      for (var i = 0; i < activeElements.length; i++) {
        activeElements[i].removeAttribute('data-drop-position')
        activeElements[i].classList.remove('redactor-drop-active')
      }
    },

    _cleanupAllFeedback: function () {
      // Clean up all visual feedback and attributes from the entire editor
      this._clearDropFeedback()

      var editorElement = this.editor.getElement().get()
      var allElements = editorElement.querySelectorAll('*')

      for (var i = 0; i < allElements.length; i++) {
        this._cleanupElementAttributes(allElements[i])
      }
    },

    _cleanupElementAttributes: function (element) {
      // Remove all our custom attributes and classes
      if (element && element.removeAttribute) {
        element.removeAttribute('data-drop-position')
        element.classList.remove('redactor-drop-active')
        element.classList.remove('dragging')

        // Clean up empty class attributes
        if (element.hasAttribute('class') && element.className.trim() === '') {
          element.removeAttribute('class')
        }
      }
    }
  })
})(Redactor)
