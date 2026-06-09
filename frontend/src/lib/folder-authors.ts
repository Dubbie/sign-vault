import type { FolderAuthor } from '@/types/folder'

export type EditableFolderAuthor = {
  name: string
  source_url: string
}

export function emptyEditableFolderAuthor(): EditableFolderAuthor {
  return {
    name: '',
    source_url: '',
  }
}

export function toEditableFolderAuthors(
  authors: FolderAuthor[] | null | undefined,
): EditableFolderAuthor[] {
  if (!authors?.length) {
    return [emptyEditableFolderAuthor()]
  }

  return authors.map((author) => ({
    name: author.name,
    source_url: author.source_url ?? '',
  }))
}

export function toFolderAuthorPayload(authors: EditableFolderAuthor[]): FolderAuthor[] | undefined {
  const normalized = authors
    .map((author) => ({
      name: author.name.trim(),
      source_url: author.source_url.trim() || null,
    }))
    .filter((author) => author.name !== '' || author.source_url !== null)

  if (normalized.length === 0) {
    return undefined
  }

  return normalized
}
