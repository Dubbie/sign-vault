<script setup lang="ts">
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'

import { useAuthStore } from '@/stores/auth'

import UiCard from '@/components/ui/UiCard.vue'
import UiEyebrow from '@/components/ui/UiEyebrow.vue'

const auth = useAuthStore()
const router = useRouter()

onMounted(async () => {
  if (auth.token && !auth.user && !auth.isLoading) {
    await auth.fetchUser()
  }

  await router.replace(auth.isAuthenticated ? { name: 'dashboard' } : { name: 'login' })
})
</script>

<template>
  <UiCard max-width="30rem">
    <UiEyebrow>SignVault</UiEyebrow>
    <h1 class="text-[clamp(2rem,5vw,2.75rem)] leading-tight text-zinc-100">Redirecting...</h1>
    <p class="mt-4 text-zinc-400">Preparing your session.</p>
  </UiCard>
</template>
