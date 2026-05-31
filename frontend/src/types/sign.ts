export type Sign = {
  id: number
  folder_id: number
  name: string
  description: string | null
  public_url: string
  mime_type: string
  size_bytes: number
  width: number | null
  height: number | null
  created_at: string
  updated_at: string
}

export type CreateSignPayload = {
  file: File
  name?: string
  description?: string
}
