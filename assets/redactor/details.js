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
  $R.add('plugin', 'details', {
    init: function (app) {
      this.app = app
      this.toolbar = app.toolbar
      this.insertion = app.insertion
      this.selection = app.selection
      this.editor = app.editor
      this.component = app.component
      this.caret = app.caret
      this.editor.opts.blockTags.push('details', 'summary')
    },

    // public
    start: function () {
      let obj = {
        title: redactorTranslations.details_title || 'Akkordeon',
        icon: false,
        tooltip:
          redactorTranslations.details_tooltip || 'Insert collapsible details',
        api: 'plugin.details.insert'
      }

      this.toolbar.addButton('details', obj)
      this._bindEvents()
    },

    insert: function () {
      let selected = this.selection.getText()
      let summaryText = redactorTranslations.details_summary || 'Summary'
      let detailsText = redactorTranslations.details_text || 'Details'

      // If text is selected, use it as the details content
      if (selected && selected.trim() !== '') {
        detailsText = selected
      }

      let html =
        '<details open><summary><span>' +
        summaryText +
        '</span></summary>' +
        detailsText +
        '</details>'

      this.insertion.insertHtml(html)
      this._processDetailsElements()
    },

    _bindEvents: function () {
      const self = this
      // Use jQuery since Redactor uses jQuery
      this.app.editor.getElement().on('click', this._handleClick.bind(this))
      this.app.editor.getElement().on('keydown', this._handleKeydown.bind(this))

      // Hook into the clean methods to remove editor-specific attributes
      let originalClean = this.app.cleaner.clean
      this.app.cleaner.clean = function (html) {
        html = html.replace(/\s*open(?=[\s>])/g, '')
        return originalClean.call(this, html)
      }.bind(this)
    },

    _handleClick: function (e) {
      // If clicked on span inside summary, prevent default behavior
      if (e.target.tagName === 'SPAN' && e.target.closest('summary')) {
        e.preventDefault()
        e.stopPropagation()
        return false
      }

      // Only toggle if clicked directly on summary, not on children
      if (e.target.tagName === 'SUMMARY') {
        e.preventDefault()
        e.stopPropagation()
        this._handleToggle(e.target)
        return false
      }
    },

    _handleToggle: function (summaryElement) {
      let details = summaryElement.closest('details')
      if (details) {
        if (details.hasAttribute('open')) {
          details.removeAttribute('open')
        } else {
          details.setAttribute('open', '')
        }
      }
    },

    _handleKeydown: function (e) {
      let selection = this.app.selection.get()
      let range = selection.rangeCount > 0 ? selection.getRangeAt(0) : null

      if (!range) return

      // Find the actual element we're in by traversing up from the range
      let currentElement = range.commonAncestorContainer

      // If we're in a text node, get the parent element
      if (currentElement.nodeType === Node.TEXT_NODE) {
        currentElement = currentElement.parentElement
      }

      // Find if we're inside a details element
      let summaryElement = currentElement.closest('summary')
      let detailsElement = currentElement.closest('details')

      if (!summaryElement && !detailsElement) return

      // Handle Enter key
      if (e.keyCode === 13) {
        if (summaryElement) {
          // In summary: always escape on Enter
          e.preventDefault()
          this._escapeDetails(detailsElement)
        } else if (detailsElement) {
          // In details content: escape if at end or on empty line
          if (
            this._isAtEndOfDetailsContent(range, detailsElement) ||
            this._isOnEmptyLine(range)
          ) {
            e.preventDefault()
            this._escapeDetails(detailsElement)
          }
        }
      }
      // Handle Arrow Down key
      else if (e.keyCode === 40) {
        if (summaryElement) {
          // In summary: check if details is open
          if (detailsElement.hasAttribute('open')) {
            // Details is open, move to content
            e.preventDefault()
            this._focusInsideDetails(detailsElement)
          } else {
            // Details is closed, escape
            e.preventDefault()
            this._escapeDetails(detailsElement)
          }
        } else if (detailsElement) {
          // In details content: escape if at the bottom
          if (this._isAtEndOfDetailsContent(range, detailsElement)) {
            e.preventDefault()
            this._escapeDetails(detailsElement)
          }
        }
      }
    },

    _escapeDetails: function (detailsElement) {
      let nextSibling = detailsElement.nextSibling

      // Skip over whitespace-only text nodes
      while (
        nextSibling &&
        nextSibling.nodeType === Node.TEXT_NODE &&
        !nextSibling.textContent.trim()
      ) {
        nextSibling = nextSibling.nextSibling
      }

      if (nextSibling) {
        // There's content after the details element, just move cursor there
        if (nextSibling.nodeType === Node.TEXT_NODE) {
          this.caret.setStart(nextSibling)
        } else {
          this.caret.setStart(nextSibling)
        }
      } else {
        // No content after details, create a new paragraph
        let newP = document.createElement('p')
        newP.innerHTML = '<br>'
        detailsElement.parentNode.insertBefore(newP, null) // Insert at end
        this.caret.setStart(newP)
      }

      // Trigger change event to ensure Redactor knows content has changed
      this.app.broadcast('editor.change')
    },

    _focusInsideDetails: function (detailsElement) {
      // Find the first content node after summary
      let summary = detailsElement.querySelector('summary')
      let nextNode = summary ? summary.nextSibling : detailsElement.firstChild

      // Skip text nodes that are just whitespace
      while (
        nextNode &&
        nextNode.nodeType === Node.TEXT_NODE &&
        !nextNode.textContent.trim()
      ) {
        nextNode = nextNode.nextSibling
      }

      if (!nextNode) {
        // No content after summary, create a paragraph
        let p = document.createElement('p')
        p.innerHTML = '<br>'
        detailsElement.appendChild(p)
        nextNode = p
      }

      // Set cursor at the start of the content
      if (nextNode.nodeType === Node.TEXT_NODE) {
        this.caret.setStart(nextNode)
      } else {
        this.caret.setStart(nextNode)
      }
    },

    _isAtEndOfDetailsContent: function (range, detailsElement) {
      // Get all text content after the summary
      let summary = detailsElement.querySelector('summary')
      let walker = document.createTreeWalker(
        detailsElement,
        NodeFilter.SHOW_TEXT,
        {
          acceptNode: function (node) {
            // Only accept text nodes that come after the summary
            if (summary && summary.contains(node)) {
              return NodeFilter.FILTER_REJECT
            }
            return NodeFilter.FILTER_ACCEPT
          }
        },
        false
      )

      let lastTextNode = null
      let currentNode
      while ((currentNode = walker.nextNode())) {
        if (currentNode.textContent.trim() !== '') {
          lastTextNode = currentNode
        }
      }

      if (!lastTextNode) {
        return true // No text content in details, consider at end
      }

      // Check if cursor is at the end of the last text node
      return (
        range.endContainer === lastTextNode &&
        range.endOffset === lastTextNode.textContent.length
      )
    },

    _isOnEmptyLine: function (range) {
      let container = range.startContainer

      if (container.nodeType === Node.TEXT_NODE) {
        let text = container.textContent
        let cursorPos = range.startOffset

        // Check if we're at the start of an empty line
        if (cursorPos === 0 && text.trim() === '') {
          return true
        }

        // Check if the current line is empty
        let beforeCursor = text.substring(0, cursorPos)
        let afterCursor = text.substring(cursorPos)

        let lastNewlineIndex = beforeCursor.lastIndexOf('\n')
        let nextNewlineIndex = afterCursor.indexOf('\n')

        let currentLine = ''
        if (lastNewlineIndex === -1 && nextNewlineIndex === -1) {
          currentLine = text
        } else if (lastNewlineIndex === -1) {
          currentLine = text.substring(0, cursorPos + nextNewlineIndex)
        } else if (nextNewlineIndex === -1) {
          currentLine = text.substring(lastNewlineIndex + 1)
        } else {
          currentLine = text.substring(
            lastNewlineIndex + 1,
            cursorPos + nextNewlineIndex
          )
        }

        return currentLine.trim() === ''
      }

      // Check if we're in an empty element
      let element =
        container.nodeType === Node.ELEMENT_NODE
          ? container
          : container.parentElement
      return (
        element &&
        (element.textContent.trim() === '' || element.innerHTML === '<br>')
      )
    },

    _processDetailsElements: function () {
      // Use jQuery since Redactor uses jQuery
      let $details = this.app.editor.getElement().find('details')
      $($details.get()).each(function (index) {
        const detail = this
        if (detail) {
          detail.removeAttribute('open')
        }
      })
    }
  })
})(Redactor)
