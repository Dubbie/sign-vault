import type { GridBackgroundPreset } from '@/types/grid-background'
import type { FolderAuthor } from '@/types/folder'

export type PublicFolderVisibility = 'public' | 'password'

export type PublicFolderVariant = {
  id: number
  name: string | null
  is_default: boolean
  grid_background_preset: GridBackgroundPreset | null
}

export type PublicFolder = {
  id: number
  name: string
  slug: string
  visibility: PublicFolderVisibility
  authors: FolderAuthor[]
  user_id: number
  votes_count: number
  user_has_voted: boolean
  owner: {
    display_name: string
    avatar_url: string | null
  }
  variants: PublicFolderVariant[]
}

export type OwnerInfo = {
  display_name: string
  avatar_url: string | null
}

export type PreviewSign = {
  id: number
  name: string
  variant_id: number | null
  public_url: string
  thumbnail_url: string | null
  mime_type: string
  width: number | null
  height: number | null
  column_ratio: number | null
}

export type PublicFolderListing = {
  id: number
  name: string
  slug: string
  visibility: 'public'
  authors: FolderAuthor[]
  signs_count: number
  variants_count: number
  votes_count: number
  user_has_voted: boolean
  preview_grid_background_preset: GridBackgroundPreset | null
  owner: OwnerInfo
  preview_signs: PreviewSign[]
}

export type PaginationMeta = {
  current_page: number
  last_page: number
  per_page: number
  total: number
}

export type PaginatedPublicFolderResponse = {
  data: PublicFolderListing[]
  meta: PaginationMeta
}

export type PublicSign = {
  id: number
  name: string
  variant_id: number | null
  public_url: string
  thumbnail_url: string | null
  mime_type: string
  width: number | null
  height: number | null
  column_ratio: number | null
}

export type PublicFolderContentsResponse = {
  folder: PublicFolder
}

export type PublicFolderRequiresPasswordResponse = {
  requires_password: true
}

export type PublicFolderResponse =
  | PublicFolderContentsResponse
  | PublicFolderRequiresPasswordResponse

export type UnlockPublicFolderPayload = {
  password: string
}
