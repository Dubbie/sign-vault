<script setup lang="ts">
import { ref } from 'vue'

import { useAuthStore } from '@/stores/auth'

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
  <div class="max-w-sm">
    <p class="mb-3 text-2xl text-white tracking-tight font-black">
      Sign<span class="text-orange-400">Vault</span>
    </p>
    <h1 class="text-[clamp(1.5rem,5vw,2rem)] leading-tight text-white">Login with Discord</h1>
    <p class="mt-3 mb-6 text-zinc-400">
      Continue with Discord to access your saved Trackmania sign library.
    </p>

    <UiButton
      variant="primary"
      full-width
      type="button"
      :disabled="auth.isLoading"
      @click="handleLogin"
    >
      {{ auth.isLoading ? 'Redirecting...' : 'Login with Discord' }}
    </UiButton>

    <p v-if="errorMessage" class="mt-4 text-danger">
      {{ errorMessage }}
    </p>
  </div>
</template>
