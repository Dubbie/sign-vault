<script setup lang="ts">
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'

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
  <section class="dashboard-card">
    <p class="eyebrow">Dashboard</p>
    <h1>Welcome back</h1>

    <div v-if="auth.user" class="profile-card">
      <p class="label">Discord username</p>
      <p class="value">{{ auth.user.discord_username }}</p>

      <p class="label">Global name</p>
      <p class="value">
        {{ auth.user.discord_global_name || 'Not set' }}
      </p>
    </div>

    <p v-if="auth.isLoading" class="muted">Loading session...</p>

    <button class="secondary-button" type="button" @click="handleLogout">
      Logout
    </button>
  </section>
</template>

<style scoped>
.dashboard-card {
  width: min(100%, 36rem);
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

.profile-card {
  margin-top: 1.5rem;
  padding: 1rem;
  border: 1px solid var(--color-border);
  border-radius: 1rem;
  background: var(--color-surface-strong);
}

.label {
  margin-top: 0.75rem;
  color: var(--color-text-muted);
  font-size: 0.85rem;
  text-transform: uppercase;
  letter-spacing: 0.08em;
}

.label:first-child {
  margin-top: 0;
}

.value {
  margin-top: 0.25rem;
  color: var(--color-heading);
  font-weight: 600;
}

.muted {
  margin-top: 1rem;
  color: var(--color-text-muted);
}

.secondary-button {
  margin-top: 1.5rem;
  padding: 0.9rem 1.2rem;
  border: 1px solid var(--color-border-strong);
  border-radius: 0.9rem;
  background: transparent;
  color: var(--color-heading);
  font-weight: 600;
  cursor: pointer;
  transition:
    border-color 0.15s ease,
    transform 0.15s ease;
}

.secondary-button:hover {
  border-color: var(--color-primary);
  transform: translateY(-1px);
}
</style>
