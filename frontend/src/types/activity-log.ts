export interface ActivityLogEntry {
  id: number
  event: string
  actor: { id: number; display_name: string; avatar_url: string | null } | null
  subject_user: { id: number; display_name: string } | null
  subject_folder_id: number | null
  subject_sign_id: number | null
  metadata: Record<string, unknown> | null
  ip_address: string | null
  created_at: string
}

export interface PaginatedActivityLogs {
  data: ActivityLogEntry[]
  current_page: number
  last_page: number
  per_page: number
  total: number
}
