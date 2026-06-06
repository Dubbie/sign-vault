import { describe, expect, it } from 'vitest'

import { renderReleaseNotesMarkdown } from './release-notes'

describe('renderReleaseNotesMarkdown', () => {
  it('renders release-please headings, lists, links, and inline formatting', () => {
    const markdown = `### Features

* add version badge ([abc123](https://example.com/commit/abc123))
* show **formatted** notes with \`code\`
`

    expect(renderReleaseNotesMarkdown(markdown)).toBe(
      '<h3>Features</h3><ul><li>add version badge (<a href="https://example.com/commit/abc123" target="_blank" rel="noopener noreferrer">abc123</a>)</li><li>show <strong>formatted</strong> notes with <code>code</code></li></ul>',
    )
  })

  it('escapes html while preserving markdown structure', () => {
    const markdown = '* ship <script>alert(1)</script>'

    expect(renderReleaseNotesMarkdown(markdown)).toBe(
      '<ul><li>ship &lt;script&gt;alert(1)&lt;/script&gt;</li></ul>',
    )
  })
})
