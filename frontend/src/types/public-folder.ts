export type PublicFolderVisibility = 'public' | 'password'

export type PublicFolderVariant = {
  id: number
  name: string | null
  is_default: boolean
}

export type PublicFolder = {
  id: number
  name: string
  slug: string
  visibility: PublicFolderVisibility
  attribution_name: string | null
  attribution_source_url: string | null
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
  signs_count: number
  variants_count: number
  votes_count: number
  user_has_voted: boolean
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
