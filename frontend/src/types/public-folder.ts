export type PublicFolderVisibility = 'public' | 'password'

export type PublicFolder = {
  id: number
  name: string
  slug: string
  visibility: PublicFolderVisibility
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
