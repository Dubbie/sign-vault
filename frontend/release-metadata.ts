import { readFileSync } from 'node:fs'
import { join } from 'node:path'

type PackageJson = {
  version?: string
}

export type ReleaseMetadata = {
  version: string
  releaseDate: string
  releaseNotes: string
}

const releaseHeadingPattern =
  /^##\s+\[?v?(\d+\.\d+\.\d+)\]?\s*(?:[-–]\s*|\()\s*(\d{4}-\d{2}-\d{2})\)?\s*$/m

export function parseLatestReleaseSection(changelog: string): Omit<ReleaseMetadata, 'version'> | null {
  const normalized = changelog.replace(/\r\n/g, '\n')
  const match = releaseHeadingPattern.exec(normalized)

  if (!match) {
    return null
  }

  const heading = match[0]
  const releaseDate = match[2]
  const bodyStart = match.index + heading.length
  const nextHeadingIndex = normalized.slice(bodyStart).search(/\n##\s+/)
  const bodyEnd = nextHeadingIndex === -1 ? normalized.length : bodyStart + nextHeadingIndex + 1
  const releaseNotes = normalized
    .slice(bodyStart, bodyEnd)
    .trim()

  return {
    releaseDate,
    releaseNotes,
  }
}

export function readReleaseMetadata(repoRoot: string): ReleaseMetadata {
  const packageJson = JSON.parse(
    readFileSync(join(repoRoot, 'package.json'), 'utf8'),
  ) as PackageJson
  const version = packageJson.version ?? '0.0.0'

  let changelog = ''

  try {
    changelog = readFileSync(join(repoRoot, 'CHANGELOG.md'), 'utf8')
  } catch {
    return {
      version,
      releaseDate: 'Unreleased',
      releaseNotes: 'Release notes will appear here after the first published release.',
    }
  }

  const latestRelease = parseLatestReleaseSection(changelog)

  if (!latestRelease) {
    return {
      version,
      releaseDate: 'Unreleased',
      releaseNotes: 'Release notes will appear here after the first published release.',
    }
  }

  return {
    version,
    ...latestRelease,
  }
}
