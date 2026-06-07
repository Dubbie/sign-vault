import { describe, expect, it } from 'vitest'

import { parseLatestReleaseSection } from './release-metadata'

describe('parseLatestReleaseSection', () => {
  it('returns the newest release entry from a release-please changelog', () => {
    const changelog = `# Changelog

## [0.2.0] (2026-06-06)

### Features

* add version badge

## [0.1.0] (2026-06-01)

### Features

* initial release
`

    expect(parseLatestReleaseSection(changelog)).toEqual({
      releaseDate: '2026-06-06',
      releaseNotes: '### Features\n\n* add version badge',
    })
  })

  it('prefers a release-please linked heading over an older plain heading', () => {
    const changelog = `# Changelog

## [1.1.0](https://github.com/Dubbie/sign-vault/compare/v1.0.0...v1.1.0) (2026-06-07)

### Features

* add version badge

## [1.0.0] (2026-06-06)

### Features

* initial release
`

    expect(parseLatestReleaseSection(changelog)).toEqual({
      releaseDate: '2026-06-07',
      releaseNotes: '### Features\n\n* add version badge',
    })
  })

  it('supports simple hyphenated headings for manually seeded entries', () => {
    const changelog = `# Changelog

## v0.1.0 - 2026-06-06

- Seeded baseline release notes
`

    expect(parseLatestReleaseSection(changelog)).toEqual({
      releaseDate: '2026-06-06',
      releaseNotes: '- Seeded baseline release notes',
    })
  })
})
