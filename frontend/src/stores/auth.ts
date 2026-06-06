import { computed, ref } from 'vue'
import { defineStore } from 'pinia'

import api from '@/lib/api'
import type {
  AuthUser,
  LinkedProvider,
  MeResponse,
  OauthCallbackResponse,
  OauthLinkResponse,
  OauthRedirectResponse,
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

export type Provider = 'discord' | 'trackmania'

export const useAuthStore = defineStore('auth', () => {
  const token = ref<string | null>(readStoredToken())
  const user = ref<AuthUser | null>(null)
  const linkedProviders = ref<LinkedProvider[]>([])
  const signUploadMaxFiles = ref(20)
  const isLoading = ref(false)

  const isAuthenticated = computed(() => Boolean(token.value && user.value))
  const isAdmin = computed(() => user.value?.is_admin === true)

  async function loginWith(provider: Provider) {
    isLoading.value = true

    try {
      const { data } = await api.get<OauthRedirectResponse>(`/api/auth/${provider}/redirect`)
      window.location.assign(data.url)
    } finally {
      isLoading.value = false
    }
  }

  async function handleOauthCallback(
    provider: Provider,
    code: string,
    state: string,
  ): Promise<{ linked: boolean }> {
    isLoading.value = true

    try {
      const { data } = await api.post<OauthCallbackResponse | OauthLinkResponse>(
        `/api/auth/${provider}/callback`,
        { code, state },
      )

      if ('token' in data) {
        // Login flow — store new credentials.
        token.value = data.token
        user.value = data.user
        persistToken(data.token)
        return { linked: false }
      } else {
        // Link flow — keep existing token, just refresh user state.
        user.value = data.user
        return { linked: true }
      }
    } finally {
      isLoading.value = false
    }
  }

  async function linkProvider(provider: Provider) {
    isLoading.value = true

    try {
      const { data } = await api.post<OauthRedirectResponse>(`/api/auth/${provider}/link`)
      window.location.assign(data.url)
    } finally {
      isLoading.value = false
    }
  }

  async function unlinkProvider(provider: Provider) {
    await api.delete(`/api/auth/${provider}/unlink`)
    linkedProviders.value = linkedProviders.value.filter((p) => p.provider !== provider)
  }

  async function updateDisplayName(name: string) {
    const { data } = await api.patch<{ display_name: string }>('/api/me/profile', {
      display_name: name,
    })
    if (user.value) {
      user.value = { ...user.value, display_name: data.display_name }
    }
  }

  async function uploadAvatar(file: File) {
    const formData = new FormData()
    formData.append('avatar', file)
    const { data } = await api.post<{ avatar_url: string }>('/api/me/avatar', formData)
    if (user.value) {
      user.value = { ...user.value, avatar_url: data.avatar_url }
    }
  }

  async function fetchLinkedProviders() {
    if (!token.value) return

    try {
      const { data } = await api.get<{ providers: LinkedProvider[] }>('/api/me/providers')
      linkedProviders.value = data.providers
    } catch {
      // Non-critical — settings page will show empty state
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
    linkedProviders.value = []
    persistToken(null)
  }

  return {
    token,
    user,
    linkedProviders,
    signUploadMaxFiles,
    isLoading,
    isAuthenticated,
    isAdmin,
    loginWith,
    handleOauthCallback,
    linkProvider,
    unlinkProvider,
    fetchLinkedProviders,
    updateDisplayName,
    uploadAvatar,
    fetchUser,
    logout,
  }
})
