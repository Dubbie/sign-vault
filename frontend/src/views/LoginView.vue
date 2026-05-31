<script setup lang="ts">
import { ref } from 'vue'

import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const errorMessage = ref('')

async function handleLogin() {
  errorMessage.value = ''

  try {
    await auth.loginWithDiscord()
  } catch {
    errorMessage.value = 'Unable to start Discord login. Please try again.'
  }
}
</script>

<template>
  <section class="auth-card">
    <p class="eyebrow">SignVault</p>
    <h1>Login with Discord</h1>
    <p class="description">
      Continue with Discord to access your saved Trackmania sign library.
    </p>

    <button class="primary-button" type="button" :disabled="auth.isLoading" @click="handleLogin">
      {{ auth.isLoading ? 'Redirecting...' : 'Login with Discord' }}
    </button>

    <p v-if="errorMessage" class="error-message">
      {{ errorMessage }}
    </p>
  </section>
</template>

<style scoped>
.auth-card {
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

.primary-button {
  width: 100%;
  margin-top: 1.75rem;
  padding: 0.95rem 1.25rem;
  border: 1px solid transparent;
  border-radius: 0.9rem;
  background: linear-gradient(135deg, var(--color-primary), var(--color-primary-strong));
  color: #04111e;
  font-weight: 700;
  cursor: pointer;
  transition:
    transform 0.15s ease,
    opacity 0.15s ease;
}

.primary-button:hover:not(:disabled) {
  transform: translateY(-1px);
}

.primary-button:disabled {
  opacity: 0.75;
  cursor: wait;
}

.error-message {
  margin-top: 1rem;
  color: var(--color-danger);
}
</style>
