<script setup lang="ts">
import { onMounted, computed, ref, watch } from 'vue'
import { useAuthStore, type Provider } from '@/stores/auth'
import UiAvatar from '@/components/ui/UiAvatar.vue'
import { Camera } from '@lucide/vue'

const auth = useAuthStore()

// ── Profile ──────────────────────────────────────────────────────────────────

const displayName = ref('')
const nameLoading = ref(false)
const nameSaved = ref(false)
const nameError = ref('')

const avatarInput = ref<HTMLInputElement | null>(null)
const avatarPreview = ref<string | null>(null)
const avatarLoading = ref(false)
const avatarError = ref('')

// Sync from store once user data is available (handles hard reload before fetchUser resolves).
watch(
  () => auth.user,
  (u) => {
    if (u) {
      if (!displayName.value) displayName.value = u.display_name
      if (!avatarPreview.value) avatarPreview.value = u.avatar_url
    }
  },
  { immediate: true },
)

async function saveDisplayName() {
  const trimmed = displayName.value.trim()
  if (!trimmed || trimmed === auth.user?.display_name) return
  nameError.value = ''
  nameLoading.value = true
  nameSaved.value = false
  try {
    await auth.updateDisplayName(trimmed)
    nameSaved.value = true
    setTimeout(() => (nameSaved.value = false), 2500)
  } catch {
    nameError.value = 'Failed to save display name.'
  } finally {
    nameLoading.value = false
  }
}

function triggerAvatarPicker() {
  avatarInput.value?.click()
}

async function handleAvatarChange(event: Event) {
  const file = (event.target as HTMLInputElement).files?.[0]
  if (!file) return

  const allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif']
  if (!allowed.includes(file.type)) {
    avatarError.value = 'Only JPEG, PNG, WebP, and GIF images are supported.'
    return
  }
  if (file.size > 5 * 1024 * 1024) {
    avatarError.value = 'Image must be under 5 MB.'
    return
  }

  avatarError.value = ''
  const blobUrl = URL.createObjectURL(file)
  avatarPreview.value = blobUrl
  avatarLoading.value = true

  try {
    await auth.uploadAvatar(file)
    // Replace the temporary blob URL with the persisted S3 URL.
    URL.revokeObjectURL(blobUrl)
    avatarPreview.value = auth.user?.avatar_url ?? null
  } catch {
    avatarError.value = 'Failed to upload avatar.'
    URL.revokeObjectURL(blobUrl)
    avatarPreview.value = auth.user?.avatar_url ?? null
  } finally {
    avatarLoading.value = false
    if (avatarInput.value) avatarInput.value.value = ''
  }
}

// ── Providers ────────────────────────────────────────────────────────────────

const ALL_PROVIDERS: Array<{ id: Provider; label: string }> = [
  { id: 'discord', label: 'Discord' },
  { id: 'trackmania', label: 'Trackmania' },
]

const linkedProviderIds = computed(() => new Set(auth.linkedProviders.map((p) => p.provider)))
const canUnlink = computed(() => auth.linkedProviders.length > 1)

const providerError = ref('')

function providerInfo(provider: Provider) {
  return auth.linkedProviders.find((p) => p.provider === provider) ?? null
}

async function link(provider: Provider) {
  providerError.value = ''
  try {
    await auth.linkProvider(provider)
  } catch {
    providerError.value = `Failed to connect ${provider}. Please try again.`
  }
}

async function unlink(provider: Provider) {
  providerError.value = ''
  try {
    await auth.unlinkProvider(provider)
  } catch {
    providerError.value = `Failed to disconnect ${provider}. Please try again.`
  }
}

onMounted(() => {
  auth.fetchLinkedProviders()
})
</script>

<template>
  <div class="mx-auto max-w-2xl space-y-10">
    <!-- Profile section -->
    <section>
      <h2 class="text-headline-xl text-on-surface mb-1">Profile</h2>
      <p class="text-body-sm text-on-surface-variant mb-6">Your public display name and avatar.</p>

      <div class="flex items-end gap-6">
        <!-- Avatar -->
        <div class="shrink-0 size-20">
          <button
            type="button"
            class="group cursor-pointer relative size-20 overflow-hidden rounded-xl bg-zinc-700 ring-offset-2 ring-offset-background transition hover:ring-2 hover:ring-primary focus-visible:ring-2 focus-visible:ring-primary outline-none"
            :disabled="avatarLoading"
            aria-label="Upload avatar"
            @click="triggerAvatarPicker"
          >
            <UiAvatar
              :name="displayName || auth.user?.display_name"
              :src="avatarPreview"
              alt="Your avatar"
              text-class="text-xl"
              class="size-20 rounded-xl"
            />
            <div
              class="absolute inset-0 flex items-center justify-center bg-black/50 opacity-0 transition group-hover:opacity-100"
              :class="{ 'opacity-100': avatarLoading }"
            >
              <Camera v-if="!avatarLoading" class="size-6 text-white" />
              <svg
                v-else
                class="size-5 animate-spin text-white"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
              >
                <circle
                  class="opacity-25"
                  cx="12"
                  cy="12"
                  r="10"
                  stroke="currentColor"
                  stroke-width="4"
                />
                <path
                  class="opacity-75"
                  fill="currentColor"
                  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                />
              </svg>
            </div>
          </button>
          <input
            ref="avatarInput"
            type="file"
            accept="image/jpeg,image/png,image/webp,image/gif"
            class="sr-only"
            @change="handleAvatarChange"
          />
          <p v-if="avatarError" class="mt-2 text-xs text-red-400 max-w-20 text-center">
            {{ avatarError }}
          </p>
        </div>

        <!-- Display name -->
        <div class="flex-1">
          <label
            for="display-name"
            class="block text-sm font-medium text-on-surface-variant mb-1.5"
          >
            Display name
          </label>
          <div class="flex gap-2">
            <input
              id="display-name"
              v-model="displayName"
              type="text"
              maxlength="50"
              class="flex-1 rounded-lg border border-outline-variant/30 bg-surface-container-low px-3 py-2 text-sm text-on-surface placeholder-zinc-500 focus:outline-none focus:border-primary transition"
              placeholder="Your name"
              @keydown.enter="saveDisplayName"
            />
            <button
              type="button"
              :disabled="
                nameLoading || !displayName.trim() || displayName.trim() === auth.user?.display_name
              "
              class="shrink-0 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-on-primary transition hover:bg-primary/90 disabled:opacity-40 disabled:cursor-not-allowed"
              @click="saveDisplayName"
            >
              {{ nameLoading ? 'Saving…' : nameSaved ? 'Saved ✓' : 'Save' }}
            </button>
          </div>
          <p v-if="nameError" class="mt-1.5 text-xs text-red-400">{{ nameError }}</p>
        </div>
      </div>
    </section>

    <!-- Providers section -->
    <section>
      <h2 class="text-headline-xl text-on-surface mb-1">Linked accounts</h2>
      <p class="text-body-sm text-on-surface-variant mb-6">
        Manage your connected sign-in providers.
      </p>

      <div class="space-y-3">
        <div
          v-for="{ id, label } in ALL_PROVIDERS"
          :key="id"
          class="flex items-center justify-between gap-4 rounded-xl border border-outline-variant/30 bg-surface-container-low px-5 py-4"
        >
          <div>
            <p class="font-medium text-on-surface text-sm">{{ label }}</p>
            <p v-if="linkedProviderIds.has(id)" class="text-xs text-on-surface-variant/50 mt-0.5">
              Connected as
              <span class="text-on-surface-variant font-medium">{{
                providerInfo(id)?.username
              }}</span>
            </p>
            <p v-else class="text-xs text-on-surface-variant/50 mt-0.5">Not connected</p>
          </div>

          <button
            v-if="!linkedProviderIds.has(id)"
            type="button"
            class="cursor-pointer shrink-0 rounded-lg border border-emerald-500/40 bg-emerald-500/10 px-3 py-1.5 text-xs font-medium text-emerald-400 transition hover:bg-emerald-500/20"
            @click="link(id)"
          >
            Connect
          </button>

          <button
            v-else
            type="button"
            :disabled="!canUnlink"
            class="cursor-pointer shrink-0 rounded-lg border border-red-500/30 bg-transparent px-3 py-1.5 text-xs font-medium text-red-400/80 transition hover:bg-red-500/10 disabled:opacity-40 disabled:cursor-not-allowed"
            :title="!canUnlink ? 'You must keep at least one login method' : undefined"
            @click="unlink(id)"
          >
            Disconnect
          </button>
        </div>
      </div>

      <p v-if="!canUnlink" class="mt-4 text-xs text-on-surface-variant/50">
        You must have at least one connected provider to log in.
      </p>
      <p v-if="providerError" class="mt-3 text-xs text-red-400">{{ providerError }}</p>
    </section>
  </div>
</template>
