import { computed, ref } from 'vue'
import { defineStore } from 'pinia'

import api from '@/lib/api'
import type {
  AuthUser,
  DiscordCallbackResponse,
  DiscordRedirectResponse,
  MeResponse,
} from '@/types/auth'

const TOKEN_KEY = 'signvault_token'

function readStoredToken() {
  if (typeof window === 'undefined') {
    return null
  }

  return localStorage.getItem(TOKEN_KEY)
}

function persistToken(token: string | null) {
  if (typeof window === 'undefined') {
    return
  }

  if (token) {
    localStorage.setItem(TOKEN_KEY, token)
    return
  }

  localStorage.removeItem(TOKEN_KEY)
}

export const useAuthStore = defineStore('auth', () => {
  const token = ref<string | null>(readStoredToken())
  const user = ref<AuthUser | null>(null)
  const signUploadMaxFiles = ref(20)
  const isLoading = ref(false)

  const isAuthenticated = computed(() => Boolean(token.value && user.value))
  const isAdmin = computed(() => user.value?.is_admin === true)

  async function loginWithDiscord() {
    isLoading.value = true

    try {
      const { data } = await api.get<DiscordRedirectResponse>('/api/auth/discord/redirect')
      window.location.assign(data.url)
    } finally {
      isLoading.value = false
    }
  }

  async function handleDiscordCallback(code: string, state: string) {
    isLoading.value = true

    try {
      const { data } = await api.post<DiscordCallbackResponse>('/api/auth/discord/callback', {
        code,
        state,
      })

      token.value = data.token
      user.value = data.user
      persistToken(data.token)
      return data
    } finally {
      isLoading.value = false
    }
  }

  async function fetchUser() {
    if (!token.value) {
      user.value = null
      return null
    }

    isLoading.value = true

    try {
      const { data } = await api.get<MeResponse>('/api/me')
      const nextUser = 'user' in data ? data.user : data
      const nextLimit = 'limits' in data ? data.limits?.sign_upload_max_files : undefined

      if (typeof nextLimit === 'number' && Number.isFinite(nextLimit)) {
        signUploadMaxFiles.value = nextLimit
      }

      user.value = nextUser
      return nextUser
    } catch {
      token.value = null
      user.value = null
      persistToken(null)
      return null
    } finally {
      isLoading.value = false
    }
  }

  async function logout() {
    if (token.value) {
      try {
        await api.post('/api/auth/logout')
      } catch {
        // Clear local state even if the server call fails.
      }
    }

    token.value = null
    user.value = null
    persistToken(null)
  }

  return {
    token,
    user,
    signUploadMaxFiles,
    isLoading,
    isAuthenticated,
    isAdmin,
    loginWithDiscord,
    handleDiscordCallback,
    fetchUser,
    logout,
  }
})
