export type FolderVisibility = 'private' | 'public' | 'password'

export type Variant = {
  id: number
  name: string | null
  is_default: boolean
  sort_order: number
  created_at: string
  updated_at: string
}

export type Folder = {
  id: number
  name: string
  slug: string
  public_slug: string
  visibility: FolderVisibility
  variants: Variant[]
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

export type CreateVariantPayload = {
  name: string
}

export type UpdateVariantPayload = {
  name?: string
  is_default?: boolean
}

export type ChangeSignVariantPayload = {
  ids: number[]
  variant_id: number
}
