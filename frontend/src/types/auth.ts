export interface AuthUser {
  id: number
  display_name: string
  avatar_url: string | null
  email: string | null
  is_admin: boolean
  folders_count: number
  signs_count: number
}

export interface LinkedProvider {
  provider: 'discord' | 'trackmania'
  username: string
  display_name: string | null
  avatar_url: string | null
}

export interface OauthRedirectResponse {
  url: string
  state: string
}

export interface OauthCallbackResponse {
  token: string
  user: AuthUser
}

export interface OauthLinkResponse {
  message: string
  user: AuthUser
}

export interface AppLimits {
  sign_upload_max_files: number
}

export type MeResponse = AuthUser | { user: AuthUser; limits?: AppLimits }

export interface AdminUser {
  id: number
  display_name: string
  avatar_url: string | null
  is_admin: boolean
  banned_at: string | null
  ban_reason: string | null
  folders_count: number
  signs_count: number
  providers: Array<{ provider: string; username: string }>
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
