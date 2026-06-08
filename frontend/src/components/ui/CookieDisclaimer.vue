<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'

const DISMISS_KEY = 'signvault:cookie-disclaimer-dismissed'
const visible = ref(false)

onMounted(() => {
  visible.value = !localStorage.getItem(DISMISS_KEY)
})

function dismiss() {
  localStorage.setItem(DISMISS_KEY, '1')
  visible.value = false
}
</script>

<template>
  <div
    v-if="visible"
    class="fixed bottom-14 left-1/2 z-50 w-full max-w-4xl -translate-x-1/2 rounded-lg border border-border bg-surface px-6 py-4 shadow-lg"
  >
    <div class="flex items-center justify-between gap-4">
      <p class="text-xs text-zinc-400">
        This site stores a token in your browser's local storage to keep you signed in. We don't
        use advertising cookies — only cookieless Cloudflare Web Analytics and hashed,
        non-identifying usage stats.
        <RouterLink to="/privacy" class="text-emerald-400 underline-offset-2 hover:underline">
          Learn more
        </RouterLink>
      </p>
      <button
        type="button"
        class="cursor-pointer rounded bg-emerald-400 px-4 py-1.5 text-xs font-semibold text-background transition hover:bg-emerald-200"
        @click="dismiss"
      >
        Got it
      </button>
    </div>
  </div>
</template>
