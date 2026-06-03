export type Sign = {
  id: number
  folder_id: number
  name: string
  public_url: string
  mime_type: string
  size_bytes: number
  width: number | null
  height: number | null
  column_ratio: number | null
  created_at: string
  updated_at: string
}

export type PaginatedSignResponse = {
  data: Sign[]
  meta: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}

export type CreateSignPayload = {
  files: File[]
}
