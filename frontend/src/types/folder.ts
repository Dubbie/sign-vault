import type { GridBackgroundPreset } from '@/types/grid-background'

export type FolderVisibility = 'private' | 'public' | 'password'

export type FolderAuthor = {
  id?: number
  name: string
  source_url: string | null
  sort_order?: number
}

export type Variant = {
  id: number
  name: string | null
  is_default: boolean
  sort_order: number
  grid_background_preset: GridBackgroundPreset | null
  created_at: string
  updated_at: string
}

export type Folder = {
  id: number
  name: string
  slug: string
  public_slug: string
  visibility: FolderVisibility
  authors: FolderAuthor[]
  variants: Variant[]
  created_at: string
  updated_at: string
}

export type CreateFolderPayload = {
  name: string
  visibility: FolderVisibility
  password?: string
  authors?: FolderAuthor[]
}

export type UpdateFolderPayload = {
  name: string
  visibility: FolderVisibility
  password?: string
  authors?: FolderAuthor[]
}

export type FolderValidationErrors = Partial<
  Record<'name' | 'visibility' | 'password' | 'authors', string>
>

export type CreateVariantPayload = {
  name: string
  grid_background_preset?: GridBackgroundPreset | null
}

export type UpdateVariantPayload = {
  name?: string
  is_default?: boolean
  grid_background_preset?: GridBackgroundPreset | null
}

export type ChangeSignVariantPayload = {
  ids: number[]
  variant_id: number
}
