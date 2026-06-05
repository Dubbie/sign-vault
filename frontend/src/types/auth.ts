export interface AuthUser {
  id: number
  discord_id: string
  discord_username: string
  discord_global_name: string | null
  discord_avatar: string | null
  email: string | null
  is_admin: boolean
  folders_count: number
  signs_count: number
}

export interface DiscordRedirectResponse {
  url: string
  state: string
}

export interface DiscordCallbackResponse {
  token: string
  user: AuthUser
}

export interface AppLimits {
  sign_upload_max_files: number
}

export type MeResponse = AuthUser | { user: AuthUser; limits?: AppLimits }

export interface AdminUser {
  id: number
  discord_id: string
  discord_username: string
  discord_global_name: string | null
  discord_avatar: string | null
  is_admin: boolean
  banned_at: string | null
  ban_reason: string | null
  folders_count: number
  signs_count: number
}

export interface PaginatedAdminUsers {
  data: AdminUser[]
  current_page: number
  last_page: number
  per_page: number
  total: number
  stats: {
    total: number
    admins: number
    banned: number
  }
}
