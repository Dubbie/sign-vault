export type PublicFolderVisibility = 'public' | 'password'

export type PublicFolder = {
  id: number
  name: string
  slug: string
  visibility: PublicFolderVisibility
}

export type OwnerInfo = {
  discord_username: string
  discord_global_name: string | null
  discord_avatar: string | null
}

export type PreviewSign = {
  id: number
  name: string
  public_url: string
  width: number | null
  height: number | null
}

export type PublicFolderListing = {
  id: number
  name: string
  slug: string
  visibility: 'public'
  signs_count: number
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
  public_url: string
  mime_type: string
  width: number | null
  height: number | null
}

export type PublicFolderContentsResponse = {
  folder: PublicFolder
  signs: PublicSign[]
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
