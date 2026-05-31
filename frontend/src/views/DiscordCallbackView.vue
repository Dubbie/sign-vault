<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()
const errorMessage = ref('')

const statusMessage = computed(() => {
  if (errorMessage.value) {
    return errorMessage.value
  }

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
  <section class="callback-card">
    <p class="eyebrow">Discord callback</p>
    <h1>Signing you in</h1>
    <p class="description">
      {{ statusMessage }}
    </p>
  </section>
</template>

<style scoped>
.callback-card {
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
