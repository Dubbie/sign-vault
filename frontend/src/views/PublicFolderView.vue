<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'

import {
  getPublicFolder,
  getPublicFolderErrorMessage,
  getPublicFolderSigns,
  unlockPublicFolder,
} from '@/lib/public-folders'
import type { PublicFolder, PublicSign } from '@/types/public-folder'
import { useAuthStore } from '@/stores/auth'
import { useFoldersStore } from '@/stores/folders'
import { banUser } from '@/lib/admin'

import UiErrorBanner from '@/components/ui/UiErrorBanner.vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiFormField from '@/components/ui/UiFormField.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiModal from '@/components/ui/UiModal.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import SignGrid from '@/components/signs/SignGrid.vue'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const foldersStore = useFoldersStore()

const folderSlug = computed(() => String(route.params.slug))

async function checkIsAuthor() {
  if (!authStore.isAuthenticated || !folder.value) return false

  if (foldersStore.folders.length === 0) {
    await foldersStore.fetchFolders()
  }

  return foldersStore.folders.some((f) => f.public_slug === folder.value!.slug)
}

const COLUMN_RATIOS = [6, 4, 2, 1] as const
type ColumnRatio = (typeof COLUMN_RATIOS)[number]

type ColumnState = { currentPage: number; hasMore: boolean }

function initialColumnState(): Record<ColumnRatio, ColumnState> {
  return {
    6: { currentPage: 0, hasMore: false },
    4: { currentPage: 0, hasMore: false },
    2: { currentPage: 0, hasMore: false },
    1: { currentPage: 0, hasMore: false },
  }
}

const folder = ref<PublicFolder | null>(null)
const signs = ref<PublicSign[]>([])
const signsTotal = ref(0)
const columnState = ref<Record<ColumnRatio, ColumnState>>(initialColumnState())
const signsHasMore = computed(() => COLUMN_RATIOS.some((r) => columnState.value[r].hasMore))
const unlockedPassword = ref<string | null>(null)
const requiresPassword = ref(false)
const isLoading = ref(false)
const isLoadingSigns = ref(false)
const isLoadingMore = ref(false)
const isUnlocking = ref(false)
const isAuthor = ref(false)
const error = ref<string | null>(null)
const copiedSignId = ref<number | null>(null)
const selectedVariantId = ref<number | null>(null)

const variants = computed(() => folder.value?.variants ?? [])
const showVariantSwitcher = computed(() => variants.value.length > 1)
const variantOptions = computed(() =>
  variants.value.map((variant) => ({
    value: String(variant.id),
    label: variantDisplayLabel(variant),
  })),
)
const selectedVariantOption = computed({
  get() {
    return String(activeVariantId() ?? '')
  },
  set(value: string) {
    if (!value) return
    void handleVariantSwitch(Number(value))
  },
})

function activeVariantId(): number | null {
  if (selectedVariantId.value) return selectedVariantId.value
  const defaultV = variants.value.find((v) => v.is_default)
  return defaultV?.id ?? null
}

function variantDisplayLabel(v: { name: string | null; is_default: boolean }) {
  if (v.is_default) return v.name ?? folder.value?.name ?? 'Default'
  return v.name ?? 'Unnamed'
}

const showBanModal = ref(false)
const banReason = ref('')
const isBanning = ref(false)
const bannedUserName = ref('')

function canBan() {
  return authStore.isAdmin && folder.value && folder.value.user_id !== authStore.user?.id
}

async function handleBan() {
  if (!folder.value || !banReason.value.trim()) return

  isBanning.value = true
  error.value = null

  try {
    await banUser(folder.value.user_id, banReason.value.trim())
    bannedUserName.value =
      folder.value.owner?.discord_global_name || folder.value.owner?.discord_username || 'User'
    showBanModal.value = false
    banReason.value = ''
    folder.value = null
    signs.value = []
  } catch {
    error.value = 'Failed to ban user.'
  } finally {
    isBanning.value = false
  }
}

const unlockForm = reactive({ password: '' })

function visibilityLabel(visibility: string) {
  return visibility.charAt(0).toUpperCase() + visibility.slice(1)
}

function clearUnlockForm() {
  unlockForm.password = ''
}

async function loadSignsPerColumn(password: string | null, variantId?: number) {
  isLoadingSigns.value = true
  columnState.value = initialColumnState()
  signs.value = []
  signsTotal.value = 0

  try {
    const results = await Promise.all(
      COLUMN_RATIOS.map((ratio) =>
        getPublicFolderSigns(folderSlug.value, 1, password ?? undefined, ratio, variantId),
      ),
    )

    signs.value = results.flatMap((r) => r.data)
    signsTotal.value = results.reduce((sum, r) => sum + r.meta.total, 0)

    for (let i = 0; i < COLUMN_RATIOS.length; i++) {
      const ratio = COLUMN_RATIOS[i]!
      const meta = results[i]!.meta
      columnState.value[ratio] = {
        currentPage: meta.current_page,
        hasMore: meta.current_page < meta.last_page,
      }
    }
  } catch (exception) {
    error.value = getPublicFolderErrorMessage(exception)
  } finally {
    isLoadingSigns.value = false
  }
}

async function loadMoreSigns() {
  if (!signsHasMore.value || isLoadingMore.value) return

  isLoadingMore.value = true

  try {
    const ratiosWithMore = COLUMN_RATIOS.filter((r) => columnState.value[r].hasMore)

    const results = await Promise.all(
      ratiosWithMore.map((ratio) =>
        getPublicFolderSigns(
          folderSlug.value,
          columnState.value[ratio].currentPage + 1,
          unlockedPassword.value ?? undefined,
          ratio,
          activeVariantId() ?? undefined,
        ),
      ),
    )

    signs.value = [...signs.value, ...results.flatMap((r) => r.data)]

    for (let i = 0; i < ratiosWithMore.length; i++) {
      const ratio = ratiosWithMore[i]!
      const meta = results[i]!.meta
      columnState.value[ratio] = {
        currentPage: meta.current_page,
        hasMore: meta.current_page < meta.last_page,
      }
    }
  } catch (exception) {
    error.value = getPublicFolderErrorMessage(exception)
  } finally {
    isLoadingMore.value = false
  }
}

async function handleVariantSwitch(variantId: number) {
  if (selectedVariantId.value === variantId) return

  selectedVariantId.value = variantId
  await router.replace({
    query: { ...route.query, variant: variantId },
  })
  await loadSignsPerColumn(unlockedPassword.value, variantId)
}

async function loadPublicFolder() {
  isLoading.value = true
  error.value = null
  selectedVariantId.value = null

  try {
    const response = await getPublicFolder(folderSlug.value)

    if ('requires_password' in response) {
      requiresPassword.value = true
      return
    }

    folder.value = response.folder
    requiresPassword.value = false
    document.title = `${response.folder.name} — SignVault`
    checkIsAuthor().then((result) => {
      isAuthor.value = result
    })

    const variantParam = route.query.variant
    const initialVariantId = variantParam ? Number(variantParam) : null
    const validVariant =
      initialVariantId && response.folder.variants.some((v) => v.id === initialVariantId)
        ? initialVariantId
        : null

    selectedVariantId.value = validVariant

    void loadSignsPerColumn(null, validVariant ?? undefined)
  } catch (exception) {
    const axiosError = exception as { response?: { status?: number } }

    if (axiosError.response?.status === 404) {
      error.value = 'Folder not found.'
      folder.value = null
      requiresPassword.value = false
      isAuthor.value = false
      return
    }

    error.value = getPublicFolderErrorMessage(exception)
  } finally {
    isLoading.value = false
  }
}

async function handleUnlock() {
  error.value = null
  isUnlocking.value = true

  try {
    const response = await unlockPublicFolder(folderSlug.value, {
      password: unlockForm.password,
    })

    unlockedPassword.value = unlockForm.password
    folder.value = response.folder
    requiresPassword.value = false
    document.title = `${response.folder.name} — SignVault`
    checkIsAuthor().then((result) => {
      isAuthor.value = result
    })

    clearUnlockForm()
    void loadSignsPerColumn(unlockedPassword.value, selectedVariantId.value ?? undefined)
  } catch (exception) {
    const axiosError = exception as { response?: { status?: number } }

    if (axiosError.response?.status === 404) {
      error.value = 'Folder not found.'
      folder.value = null
      requiresPassword.value = false
      isAuthor.value = false
      return
    }

    error.value = getPublicFolderErrorMessage(exception)
  } finally {
    isUnlocking.value = false
  }
}

async function handleCopy(signId: number) {
  const sign = signs.value.find((s) => s.id === signId)
  if (!sign) return

  error.value = null

  try {
    await navigator.clipboard.writeText(sign.public_url)
    copiedSignId.value = signId
    window.setTimeout(() => {
      if (copiedSignId.value === signId) copiedSignId.value = null
    }, 1000)
  } catch {
    error.value = 'Could not copy the sign URL. Please copy it manually.'
  }
}

onMounted(loadPublicFolder)

watch(folderSlug, () => {
  clearUnlockForm()
  folder.value = null
  signs.value = []
  signsTotal.value = 0
  columnState.value = initialColumnState()
  unlockedPassword.value = null
  requiresPassword.value = false
  isAuthor.value = false
  copiedSignId.value = null
  void loadPublicFolder()
})
</script>

<template>
  <div>
    <p v-if="isLoading && !folder && !requiresPassword" class="mt-4 text-zinc-400">
      Loading folder...
    </p>

    <UiErrorBanner v-if="error">
      {{ error }}
    </UiErrorBanner>

    <div v-if="requiresPassword" class="mx-auto max-w-md mt-12 text-center">
      <svg
        class="mx-auto size-10 text-muted mb-4"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="1.5"
          d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"
        />
      </svg>

      <p
        class="mb-1 inline-block rounded-full border border-border px-3 py-0.5 text-xs font-semibold text-muted"
      >
        Password required
      </p>

      <h2 class="mt-3 text-xl text-heading">This folder is protected</h2>
      <p class="mt-1 text-sm text-muted">Enter the password to view the contents.</p>

      <form class="mt-6 text-left" @submit.prevent="handleUnlock">
        <UiFormField label="Password" name="password">
          <UiInput
            v-model="unlockForm.password"
            type="password"
            name="password"
            autocomplete="current-password"
            required
          />
        </UiFormField>

        <div class="mt-4">
          <UiButton variant="primary" type="submit" full-width :disabled="isUnlocking">
            {{ isUnlocking ? 'Unlocking...' : 'Unlock folder' }}
          </UiButton>
        </div>
      </form>
    </div>

    <div v-else-if="bannedUserName" class="mx-auto mt-12 max-w-md text-center">
      <p
        class="inline-block rounded-full border border-red-900/50 bg-red-950/20 px-3 py-0.5 text-xs font-semibold text-red-400"
      >
        User Banned
      </p>
      <h2 class="mt-3 text-xl text-heading">{{ bannedUserName }} has been banned</h2>
      <p class="mt-1 text-sm text-muted">All their folders and signs have been removed.</p>
    </div>

    <div v-else-if="folder">
      <!-- Breadcrumb component here -->
      <div class="mb-12 flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
          <nav class="flex items-center gap-2 text-on-surface-variant mb-4">
            <span>Explore</span>
            <span
              ><svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="3"
                stroke="currentColor"
                class="size-3"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="m8.25 4.5 7.5 7.5-7.5 7.5"
                />
              </svg>
            </span>
            <span class="text-primary">{{ folder.name }}</span>
          </nav>

          <div class="flex items-center gap-4">
            <h1 class="text-headline-xl text-on-surface">{{ folder.name }}</h1>
            <div>
              <span
                class="mr-2 px-2 py-0.5 rounded bg-primary/10 border border-primary/20 text-primary"
              >
                {{ visibilityLabel(folder.visibility) }}
              </span>
              <span>{{ signsTotal }} signs</span>
            </div>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <button
            class="glass-card font-semibold px-component-padding-x py-component-padding-y rounded-lg flex items-center gap-2 text-on-surface hover:bg-surface-variant/50 transition-all"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
              stroke-width="1.5"
              stroke="currentColor"
              class="size-5"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244"
              />
            </svg>

            Copy URL
          </button>

          <RouterLink v-if="isAuthor" :to="`/folders/${folder.id}`">
            <button
              class="bg-primary font-semibold text-on-primary px-component-padding-x py-component-padding-y rounded-lg flex items-center gap-2 font-label-md emerald-glow transition-all"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
                class="size-5"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008Z"
                />
              </svg>

              Manage folder
            </button>
          </RouterLink>

          <UiButton v-if="canBan()" variant="danger" type="button" @click="showBanModal = true">
            Ban User
          </UiButton>
        </div>
      </div>

      <div v-if="showVariantSwitcher" class="max-w-sm">
        <UiFormField label="Variant" name="variant">
          <UiSelect v-model="selectedVariantOption" name="variant" :options="variantOptions" />
        </UiFormField>
      </div>

      <div>
        <p v-if="isLoadingSigns" class="text-zinc-400">Loading signs...</p>
        <p v-else-if="signs.length === 0" class="mt-4 text-zinc-400">No signs available.</p>

        <SignGrid
          v-else
          :signs="signs"
          :copied-sign-id="copiedSignId"
          :selectable="false"
          :has-more="signsHasMore"
          :is-loading-more="isLoadingMore"
          @copy="handleCopy"
          @load-more="loadMoreSigns"
        />
      </div>
    </div>

    <UiModal
      :model-value="showBanModal"
      title="Ban User"
      @update:model-value="showBanModal = false"
    >
      <div v-if="folder">
        <p class="text-sm text-zinc-300">
          Ban
          <strong>{{ folder.owner?.discord_global_name || folder.owner?.discord_username }}</strong
          >? This will:
        </p>
        <ul class="mt-2 list-inside list-disc text-sm text-zinc-400">
          <li>Delete all their folders and signs</li>
          <li>Revoke all active sessions</li>
          <li>Prevent them from logging in</li>
        </ul>

        <UiFormField label="Ban reason" name="reason" class="mt-4">
          <UiInput
            v-model="banReason"
            placeholder="Why is this user being banned?"
            maxlength="500"
            required
          />
        </UiFormField>

        <div class="mt-4 flex justify-end gap-3">
          <UiButton variant="secondary" @click="showBanModal = false"> Cancel </UiButton>
          <UiButton variant="danger" :disabled="!banReason.trim() || isBanning" @click="handleBan">
            {{ isBanning ? 'Banning...' : 'Ban & Nuke Content' }}
          </UiButton>
        </div>
      </div>
    </UiModal>
  </div>
</template>
