<script setup lang="ts">
import { ref } from 'vue'

import { useAuthStore } from '@/stores/auth'

import UiCard from '@/components/ui/UiCard.vue'
import UiEyebrow from '@/components/ui/UiEyebrow.vue'
import UiButton from '@/components/ui/UiButton.vue'

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
  <UiCard max-width="30rem">
    <UiEyebrow>SignVault</UiEyebrow>
    <h1 class="text-[clamp(2rem,5vw,2.75rem)] leading-tight text-heading">Login with Discord</h1>
    <p class="mt-4 text-text-muted">
      Continue with Discord to access your saved Trackmania sign library.
    </p>

    <UiButton variant="primary" full-width type="button" :disabled="auth.isLoading" @click="handleLogin">
      {{ auth.isLoading ? 'Redirecting...' : 'Login with Discord' }}
    </UiButton>

    <p v-if="errorMessage" class="mt-4 text-danger">
      {{ errorMessage }}
    </p>
  </UiCard>
</template>
