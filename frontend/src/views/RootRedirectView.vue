<script setup lang="ts">
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'

import { useAuthStore } from '@/stores/auth'

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
  <section class="redirect-card">
    <p class="eyebrow">SignVault</p>
    <h1>Redirecting...</h1>
    <p class="description">Preparing your session.</p>
  </section>
</template>

<style scoped>
.redirect-card {
  width: min(100%, 30rem);
  padding: 2rem;
  border: 1px solid var(--color-border);
  border-radius: 1.5rem;
  background: var(--color-surface);
  box-shadow: var(--shadow-elevated);
  backdrop-filter: blur(18px);
}

.eyebrow {
  margin-bottom: 0.75rem;
  color: var(--color-primary);
  font-size: 0.85rem;
  font-weight: 600;
  letter-spacing: 0.14em;
  text-transform: uppercase;
}

h1 {
  color: var(--color-heading);
  font-size: clamp(2rem, 5vw, 2.75rem);
  line-height: 1.05;
}

.description {
  margin-top: 1rem;
  color: var(--color-text-muted);
}
</style>
