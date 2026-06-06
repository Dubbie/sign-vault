function escapeHtml(value: string) {
  return value
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#39;')
}

function renderInlineMarkdown(value: string) {
  let rendered = escapeHtml(value)

  rendered = rendered.replace(
    /\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/g,
    '<a href="$2" target="_blank" rel="noopener noreferrer">$1</a>',
  )
  rendered = rendered.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
  rendered = rendered.replace(/\*([^*]+)\*/g, '<em>$1</em>')
  rendered = rendered.replace(/`([^`]+)`/g, '<code>$1</code>')

  return rendered
}

export function renderReleaseNotesMarkdown(markdown: string) {
  const lines = markdown
    .replace(/\r\n/g, '\n')
    .split('\n')
    .map((line) => line.trimEnd())

  const html: string[] = []
  let paragraphLines: string[] = []
  let listItems: string[] = []

  function flushParagraph() {
    if (paragraphLines.length === 0) {
      return
    }

    html.push(`<p>${renderInlineMarkdown(paragraphLines.join(' '))}</p>`)
    paragraphLines = []
  }

  function flushList() {
    if (listItems.length === 0) {
      return
    }

    const items = listItems.map((item) => `<li>${renderInlineMarkdown(item)}</li>`).join('')
    html.push(`<ul>${items}</ul>`)
    listItems = []
  }

  for (const line of lines) {
    const trimmed = line.trim()

    if (trimmed.length === 0) {
      flushParagraph()
      flushList()
      continue
    }

    if (trimmed.startsWith('### ')) {
      flushParagraph()
      flushList()
      html.push(`<h3>${renderInlineMarkdown(trimmed.slice(4))}</h3>`)
      continue
    }

    if (trimmed.startsWith('## ')) {
      flushParagraph()
      flushList()
      html.push(`<h2>${renderInlineMarkdown(trimmed.slice(3))}</h2>`)
      continue
    }

    if (trimmed.startsWith('* ') || trimmed.startsWith('- ')) {
      flushParagraph()
      listItems.push(trimmed.slice(2))
      continue
    }

    paragraphLines.push(trimmed)
  }

  flushParagraph()
  flushList()

  if (html.length === 0) {
    return '<p>No published release notes yet.</p>'
  }

  return html.join('')
}
