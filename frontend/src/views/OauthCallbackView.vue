<script setup lang="ts">
import type { AxiosError } from 'axios'
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { useAuthStore } from '@/stores/auth'
import type { Provider } from '@/stores/auth'

const props = defineProps<{
  provider: Provider
}>()

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()
const errorMessage = ref('')

const providerLabel = computed(() =>
  props.provider === 'discord' ? 'Discord' : 'Trackmania',
)

const statusMessage = computed(() => {
  if (errorMessage.value) return errorMessage.value
  return `Completing ${providerLabel.value} sign-in...`
})

onMounted(async () => {
  const code = route.query.code
  const state = route.query.state

  if (typeof code !== 'string' || code.length === 0) {
    errorMessage.value = `Missing ${providerLabel.value} authorization code.`
    return
  }

  if (typeof state !== 'string' || state.length === 0) {
    errorMessage.value = `Missing ${providerLabel.value} authorization state.`
    return
  }

  try {
    const { linked } = await auth.handleOauthCallback(props.provider, code, state)
    await router.replace({ name: linked ? 'settings' : 'dashboard' })
  } catch (error) {
    const axiosError = error as AxiosError<{ message?: string }> | undefined
    const serverMessage = axiosError?.response?.data?.message
    errorMessage.value =
      serverMessage || `${providerLabel.value} sign-in failed. Please try logging in again.`
  }
})
</script>

<template>
  <div class="max-w-sm">
    <p class="mb-3 text-xs text-emerald-400 font-semibold">{{ providerLabel }} callback</p>
    <h1 class="text-[clamp(1.5rem,5vw,2rem)] leading-tight text-zinc-100">Signing you in</h1>
    <p class="mt-4 text-zinc-400">
      {{ statusMessage }}
    </p>
  </div>
</template>
