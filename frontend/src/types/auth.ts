export interface AuthUser {
  id: number
  discord_id: string
  discord_username: string
  discord_global_name: string | null
  discord_avatar: string | null
  email: string | null
}

export interface DiscordRedirectResponse {
  url: string
  state: string
}

export interface DiscordCallbackResponse {
  token: string
  user: AuthUser
}

export type MeResponse = AuthUser | { user: AuthUser }
