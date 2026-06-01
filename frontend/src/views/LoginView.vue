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
    <div class="flex items-center gap-x-2 mb-6">
      <img src="../assets/logo.svg" alt="SignVault" class="size-8 mt-1" />
      <p class="text-2xl text-zinc-100 font-medium">
        Sign<span class="font-bold text-emerald-400">Vault</span>
      </p>
    </div>
    <h1 class="text-[clamp(1.5rem,5vw,2rem)] leading-tight text-zinc-100">Login with Discord</h1>
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
