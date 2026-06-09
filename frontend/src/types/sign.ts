export type Sign = {
  id: number
  folder_id: number
  variant_id: number | null
  name: string
  public_url: string
  thumbnail_url: string | null
  thumbnail_status: 'pending' | 'ready' | 'failed'
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
  variant_id?: number
  upload_session_id?: string
}
