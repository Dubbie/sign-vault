export type FolderVisibility = 'private' | 'public' | 'password'

export type Folder = {
  id: number
  name: string
  slug: string
  visibility: FolderVisibility
  created_at: string
  updated_at: string
}

export type CreateFolderPayload = {
  name: string
  visibility: FolderVisibility
  password?: string
}

export type UpdateFolderPayload = {
  name: string
  visibility: FolderVisibility
  password?: string
}

export type FolderValidationErrors = Partial<Record<'name' | 'visibility' | 'password', string>>
