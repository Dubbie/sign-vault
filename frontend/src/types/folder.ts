import type { GridBackgroundPreset } from '@/types/grid-background'

export type FolderVisibility = 'private' | 'public' | 'password'

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
  attribution_name: string | null
  attribution_source_url: string | null
  variants: Variant[]
  created_at: string
  updated_at: string
}

export type CreateFolderPayload = {
  name: string
  visibility: FolderVisibility
  password?: string
  attribution_name?: string
  attribution_source_url?: string
}

export type UpdateFolderPayload = {
  name: string
  visibility: FolderVisibility
  password?: string
  attribution_name?: string
  attribution_source_url?: string
}

export type FolderValidationErrors = Partial<
  Record<'name' | 'visibility' | 'password' | 'attribution_name' | 'attribution_source_url', string>
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
