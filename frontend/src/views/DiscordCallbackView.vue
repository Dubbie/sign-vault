<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()
const errorMessage = ref('')

const statusMessage = computed(() => {
  if (errorMessage.value) return errorMessage.value
  return 'Completing Discord sign-in...'
})

onMounted(async () => {
  if (auth.isAuthenticated) {
    await router.replace({ name: 'dashboard' })
    return
  }

  const code = route.query.code
  const state = route.query.state

  if (typeof code !== 'string' || code.length === 0) {
    errorMessage.value = 'Missing Discord authorization code.'
    return
  }

  if (typeof state !== 'string' || state.length === 0) {
    errorMessage.value = 'Missing Discord authorization state.'
    return
  }

  try {
    await auth.handleDiscordCallback(code, state)
    await router.replace({ name: 'dashboard' })
  } catch {
    errorMessage.value = 'Discord sign-in failed. Please try logging in again.'
  }
})
</script>

<template>
  <div class="max-w-sm">
    <p class="mb-3 text-xs text-emerald-400 font-semibold">Discord callback</p>
    <h1 class="text-[clamp(1.5rem,5vw,2rem)] leading-tight text-zinc-100">Signing you in</h1>
    <p class="mt-4 text-zinc-400">
      {{ statusMessage }}
    </p>
  </div>
</template>
