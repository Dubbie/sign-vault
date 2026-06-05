import { ref, onMounted, onUnmounted } from 'vue'

interface ToolbarPosition {
  x: number
  y: number
}

export type SelectedFormat =
  | { type: 'solid'; color: string }
  | { type: 'gradient'; fromColor: string; toColor: string }
  | { type: 'none' }

function normalizeColor(color: string): string {
  const trimmed = color.trim()
  if (trimmed.startsWith('#')) return trimmed.toLowerCase()
  const match = trimmed.match(/rgba?\((\d+),\s*(\d+),\s*(\d+)/)
  if (match) {
    const r = parseInt(match[1] ?? '0').toString(16).padStart(2, '0')
    const g = parseInt(match[2] ?? '0').toString(16).padStart(2, '0')
    const b = parseInt(match[3] ?? '0').toString(16).padStart(2, '0')
    return `#${r}${g}${b}`
  }
  return trimmed
}

function to3Digit(color: string): string {
  const hex = normalizeColor(color).replace('#', '')
  return `${hex[0]}${hex[2]}${hex[4]}`
}

function interpolateColor(from: string, to: string, t: number): string {
  const fr = parseInt(from.slice(1, 3), 16)
  const fg = parseInt(from.slice(3, 5), 16)
  const fb = parseInt(from.slice(5, 7), 16)
  const tr = parseInt(to.slice(1, 3), 16)
  const tg = parseInt(to.slice(3, 5), 16)
  const tb = parseInt(to.slice(5, 7), 16)
  const r = Math.round(fr + (tr - fr) * t)
  const g = Math.round(fg + (tg - fg) * t)
  const b = Math.round(fb + (tb - fb) * t)
  return `#${r.toString(16).padStart(2, '0')}${g.toString(16).padStart(2, '0')}${b.toString(16).padStart(2, '0')}`
}

export function useNameTagFormatter() {
  const editorRef = ref<HTMLElement | null>(null)
  const showToolbar = ref(false)
  const toolbarPosition = ref<ToolbarPosition>({ x: 0, y: 0 })
  const hasSelection = ref(false)
  const selectedFormat = ref<SelectedFormat>({ type: 'none' })

  let isMouseDown = false

  function isSelectionInsideEditor(): boolean {
    const sel = window.getSelection()
    if (!sel || sel.isCollapsed || !sel.rangeCount || !editorRef.value) {
      return false
    }

    const range = sel.getRangeAt(0)
    const editorRect = editorRef.value.getBoundingClientRect()
    const rect = range.getBoundingClientRect()

    return (
      rect.top >= editorRect.top - 50 &&
      rect.bottom <= editorRect.bottom + 50 &&
      rect.left >= editorRect.left - 50 &&
      rect.right <= editorRect.right + 50
    )
  }

  function detectSelectedFormat(): SelectedFormat {
    const sel = window.getSelection()
    if (!sel || sel.isCollapsed || !sel.rangeCount || !editorRef.value) {
      return { type: 'none' }
    }

    const range = sel.getRangeAt(0)
    const fragment = range.cloneContents()

    const gradientEls = fragment.querySelectorAll<HTMLElement>('[data-gradient]')
    if (gradientEls.length > 0) {
      const first = gradientEls[0]
      const last = gradientEls[gradientEls.length - 1]
      if (first && last) {
        const fromColor = normalizeColor(first.style.color || '')
        const toColor = normalizeColor(last.style.color || '')
        return { type: 'gradient', fromColor, toColor }
      }
    }

    let el: HTMLElement | null =
      range.startContainer instanceof HTMLElement
        ? range.startContainer
        : range.startContainer.parentElement

    while (el && el !== editorRef.value) {
      const color = el.style.color
      if (color && color !== '' && color !== 'transparent') {
        return { type: 'solid', color: normalizeColor(color) }
      }
      el = el.parentElement
    }

    return { type: 'none' }
  }

  function showToolbarAtSelection() {
    const sel = window.getSelection()
    if (!sel || sel.isCollapsed || !sel.rangeCount || !isSelectionInsideEditor()) {
      showToolbar.value = false
      selectedFormat.value = { type: 'none' }
      return
    }

    const range = sel.getRangeAt(0)
    const rect = range.getBoundingClientRect()
    const editorEl = editorRef.value
    if (!editorEl) return

    toolbarPosition.value = {
      x: rect.left + rect.width / 2,
      y: rect.top - 8,
    }
    selectedFormat.value = detectSelectedFormat()
    hasSelection.value = true
    showToolbar.value = true
  }

  function onSelectionChange() {
    if (isMouseDown) return

    const sel = window.getSelection()
    if (!sel || sel.isCollapsed || !sel.rangeCount || !editorRef.value) {
      showToolbar.value = false
      hasSelection.value = false
      selectedFormat.value = { type: 'none' }
    }
  }

  function onDocumentMouseDown() {
    isMouseDown = true
  }

  function onDocumentMouseUp(event: MouseEvent) {
    isMouseDown = false
    const target = event.target as HTMLElement | null
    if (target?.closest('[data-format-toolbar]')) return
    setTimeout(showToolbarAtSelection, 0)
  }

  function getTrackmaniaContent(): string {
    if (!editorRef.value) return ''

    const parts: string[] = []

    function walk(node: Node) {
      if (node.nodeType === Node.TEXT_NODE) {
        parts.push(node.textContent || '')
        return
      }

      if (!(node instanceof HTMLElement)) return

      const isGradientChar = node.dataset.gradient === 'true'
      if (isGradientChar) {
        parts.push(`$${to3Digit(node.style.color)}${node.textContent || ''}`)
        return
      }

      const color = node.style.color
      const isColored = color && color !== '' && color !== 'transparent'

      if (isColored) {
        parts.push(`$${to3Digit(color)}`)
      }

      for (const child of node.childNodes) {
        walk(child)
      }

      if (isColored) {
        parts.push('$z')
      }
    }

    const children: Node[] = Array.from(editorRef.value.childNodes)

    for (let i = 0; i < children.length; i++) {
      const child = children[i]!
      walk(child)

      const childEl =
        child instanceof HTMLElement ? (child as HTMLElement) : null
      if (childEl?.dataset.gradient === 'true') {
        const next = children[i + 1]
        const nextIsGradient =
          next instanceof HTMLElement && next.dataset.gradient === 'true'
        if (!nextIsGradient) {
          parts.push('$z')
        }
      }
    }

    return parts.join('')
  }

  function onKeydown(event: KeyboardEvent) {
    if (event.key === 'Escape') {
      showToolbar.value = false
    }
    if (event.key === 'Enter') {
      event.preventDefault()
    }
  }

  function onKeyup(event: KeyboardEvent) {
    if (event.key.startsWith('Arrow') || event.key === 'Shift') {
      showToolbarAtSelection()
    }
  }

  function applySolidColor(color: string) {
    applyFormatting((span) => {
      span.style.color = color
    })
  }

  function applyGradient(fromColor: string, toColor: string) {
    const sel = window.getSelection()
    if (!sel || sel.isCollapsed || !sel.rangeCount || !editorRef.value) return

    const range = sel.getRangeAt(0)
    const text = range.toString()
    if (!text) return

    const chars = Array.from(text)
    const fragment = document.createDocumentFragment()

    for (let i = 0; i < chars.length; i++) {
      const t = chars.length > 1 ? i / (chars.length - 1) : 0
      const color = interpolateColor(fromColor, toColor, t)
      const span = document.createElement('span')
      span.style.color = color
      span.dataset.gradient = 'true'
      span.textContent = chars[i] ?? ''
      fragment.appendChild(span)
    }

    range.deleteContents()
    range.insertNode(fragment)

    sel.removeAllRanges()
    showToolbar.value = false
    hasSelection.value = false
    selectedFormat.value = { type: 'none' }
    editorRef.value.focus()
  }

  function applyFormatting(styleFn: (span: HTMLSpanElement) => void) {
    const sel = window.getSelection()
    if (!sel || sel.isCollapsed || !sel.rangeCount || !editorRef.value) return

    const range = sel.getRangeAt(0)
    const span = document.createElement('span')
    styleFn(span)

    try {
      range.surroundContents(span)
    } catch {
      const fragment = range.extractContents()
      span.appendChild(fragment)
      range.insertNode(span)
    }

    sel.removeAllRanges()
    showToolbar.value = false
    hasSelection.value = false
    selectedFormat.value = { type: 'none' }
    editorRef.value.focus()
  }

  function resetFormatting() {
    const sel = window.getSelection()
    if (!sel || sel.isCollapsed || !sel.rangeCount || !editorRef.value) return

    const range = sel.getRangeAt(0)
    const text = range.toString()

    range.deleteContents()

    if (editorRef.value) {
      const empties = editorRef.value.querySelectorAll<HTMLElement>('[style]')
      for (const el of empties) {
        if (
          !el.textContent?.trim() &&
          (el.style.color || el.style.background || el.style.backgroundImage)
        ) {
          el.remove()
        }
      }
    }

    range.insertNode(document.createTextNode(text))

    sel.removeAllRanges()
    showToolbar.value = false
    hasSelection.value = false
    selectedFormat.value = { type: 'none' }
    editorRef.value.focus()
  }

  onMounted(() => {
    document.addEventListener('selectionchange', onSelectionChange)
    document.addEventListener('mousedown', onDocumentMouseDown)
    document.addEventListener('mouseup', onDocumentMouseUp)
  })

  onUnmounted(() => {
    document.removeEventListener('selectionchange', onSelectionChange)
    document.removeEventListener('mousedown', onDocumentMouseDown)
    document.removeEventListener('mouseup', onDocumentMouseUp)
  })

  return {
    editorRef,
    showToolbar,
    toolbarPosition,
    hasSelection,
    selectedFormat,
    applySolidColor,
    applyGradient,
    resetFormatting,
    onKeydown,
    onKeyup,
    getTrackmaniaContent,
  }
}
