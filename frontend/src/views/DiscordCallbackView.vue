<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { useAuthStore } from '@/stores/auth'

import UiCard from '@/components/ui/UiCard.vue'
import UiEyebrow from '@/components/ui/UiEyebrow.vue'

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

  if (typeof code !== 'string' || code.length === 0) {
    errorMessage.value = 'Missing Discord authorization code.'
    return
  }

  try {
    await auth.handleDiscordCallback(code)
    await router.replace({ name: 'dashboard' })
  } catch {
    errorMessage.value = 'Discord sign-in failed. Please try logging in again.'
  }
})
</script>

<template>
  <UiCard max-width="30rem">
    <UiEyebrow>Discord callback</UiEyebrow>
    <h1 class="text-[clamp(2rem,5vw,2.75rem)] leading-tight text-heading">Signing you in</h1>
    <p class="mt-4 text-text-muted">
      {{ statusMessage }}
    </p>
  </UiCard>
</template>
