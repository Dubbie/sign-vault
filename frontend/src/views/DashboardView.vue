<script setup lang="ts">
import { onMounted } from 'vue'
import { RouterLink, useRouter } from 'vue-router'

import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()

onMounted(async () => {
  if (!auth.user && auth.token) {
    await auth.fetchUser()
  }

  if (!auth.isAuthenticated) {
    await router.replace({ name: 'login' })
  }
})

async function handleLogout() {
  await auth.logout()
  await router.replace({ name: 'login' })
}
</script>

<template>
  <section class="grid gap-6 text-center">
    <p class="text-primary">Dashboard</p>
    <h1 class="text-[clamp(2rem,4vw,2.5rem)] text-heading">Welcome back</h1>

    <RouterLink to="/folders" class="text-primary underline-offset-2 hover:underline">
      Manage folders
    </RouterLink>

    <div v-if="auth.user" class="grid gap-2">
      <p class="text-text-muted">Discord username</p>
      <p class="text-heading">{{ auth.user.discord_username }}</p>

      <p class="text-text-muted">Global name</p>
      <p class="text-heading">{{ auth.user.discord_global_name || 'Not set' }}</p>
    </div>

    <p v-if="auth.isLoading" class="text-text-muted">Loading session...</p>

    <button
      type="button"
      class="mx-auto cursor-pointer rounded-xl border border-border-danger bg-transparent px-4 py-2 text-danger-text transition duration-150 ease-in-out hover:bg-white/5"
      @click="handleLogout"
    >
      Logout
    </button>
  </section>
</template>
