<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import {
  getPublicFolder,
  getPublicFolderErrorMessage,
  getPublicFolderSigns,
  unlockPublicFolder,
  voteFolder,
} from '@/lib/public-folders'
import type { PublicFolder, PublicSign } from '@/types/public-folder'
import { useAuthStore } from '@/stores/auth'
import { useFoldersStore } from '@/stores/folders'
import { banUser } from '@/lib/admin'

import UiErrorBanner from '@/components/ui/UiErrorBanner.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiBreadcrumbs from '@/components/ui/UiBreadcrumbs.vue'
import UiFormField from '@/components/ui/UiFormField.vue'
import UiInput from '@/components/ui/UiInput.vue'
import UiModal from '@/components/ui/UiModal.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import SignGrid from '@/components/signs/SignGrid.vue'
import { Ban, Link, ThumbsUp, Wrench } from '@lucide/vue'

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
const copiedPublicUrl = ref(false)
const selectedVariantId = ref<number | null>(null)
const votesCount = ref(0)
const userHasVoted = ref(false)
const isVoting = ref(false)

const variants = computed(() => folder.value?.variants ?? [])
const showVariantSwitcher = computed(() => variants.value.length > 1)
const breadcrumbs = computed(() =>
  folder.value
    ? [
        { label: 'Explore', to: { name: 'explore' } },
        {
          label: folder.value.name,
          to: { name: 'public-folder', params: { slug: folder.value.slug } },
        },
      ]
    : [{ label: 'Explore', to: { name: 'explore' } }],
)
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
const ownerDisplayName = computed(() => folder.value?.owner.display_name || 'Unknown')

function activeVariantId(): number | null {
  if (selectedVariantId.value) return selectedVariantId.value
  const defaultV = variants.value.find((v) => v.is_default)
  return defaultV?.id ?? null
}

function variantDisplayLabel(v: { name: string | null; is_default: boolean }) {
  if (v.is_default) return v.name ?? folder.value?.name ?? 'Default'
  return v.name ?? 'Unnamed'
}

function attributionDisplayName() {
  return folder.value?.attribution_name?.trim() ?? ''
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
    bannedUserName.value = folder.value.owner?.display_name || 'User'
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

async function handleCopyPublicUrl() {
  error.value = null

  try {
    await navigator.clipboard.writeText(window.location.href)
    copiedPublicUrl.value = true
    window.setTimeout(() => {
      copiedPublicUrl.value = false
    }, 1500)
  } catch {
    error.value = 'Could not copy the public URL. Please copy it manually.'
  }
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
    votesCount.value = response.folder.votes_count
    userHasVoted.value = response.folder.user_has_voted
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
    votesCount.value = response.folder.votes_count
    userHasVoted.value = response.folder.user_has_voted
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

async function handleVote() {
  if (!authStore.user || isVoting.value) return

  isVoting.value = true
  try {
    const result = await voteFolder(folderSlug.value)
    votesCount.value = result.votes_count
    userHasVoted.value = result.user_has_voted
  } catch {
    error.value = 'Failed to submit vote.'
  } finally {
    isVoting.value = false
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
  copiedPublicUrl.value = false
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
      <div class="mb-4 flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
          <UiBreadcrumbs :items="breadcrumbs" class="mb-4" />

          <div class="flex items-center gap-4">
            <h1 class="text-headline-xl text-on-surface">{{ folder.name }}</h1>
            <div>
              <span
                class="block mr-2 px-2 py-0.5 rounded bg-primary/10 border border-primary/20 text-primary"
              >
                {{ visibilityLabel(folder.visibility) }}
              </span>
            </div>
          </div>

          <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
            <p v-if="folder.attribution_name" class="text-body-lg text-on-surface-variant">
              Original author:
              <a
                v-if="folder.attribution_source_url"
                :href="folder.attribution_source_url"
                target="_blank"
                rel="noopener noreferrer"
                class="text-primary hover:text-primary/80"
              >
                {{ attributionDisplayName() }}
              </a>
              <span v-else class="text-on-surface">{{ attributionDisplayName() }}</span>
            </p>
            <span v-if="folder.attribution_name" class="text-outline/50">•</span>
            <p class="text-on-surface-variant text-body-lg">
              Curated by <span class="text-on-surface">{{ ownerDisplayName }}</span>
            </p>
            <span class="text-outline/50">•</span>
            <p class="text-on-surface-variant text-body-lg">
              {{ signsTotal }} signs in this folder
            </p>

            <button
              type="button"
              class="ml-auto flex cursor-pointer items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-mono font-semibold transition-colors"
              :class="
                userHasVoted
                  ? 'bg-emerald-500 text-background'
                  : 'bg-zinc-700 text-zinc-300 hover:bg-emerald-500 hover:text-white'
              "
              :disabled="!authStore.user || isVoting"
              :title="authStore.user ? (userHasVoted ? 'Remove vote' : 'Vote ++') : 'Login to vote'"
              @click="handleVote"
            >
              <ThumbsUp class="size-4" />
              <span v-if="votesCount > 0" class="font-sans text-xs font-normal">{{
                votesCount
              }}</span>
            </button>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <UiButton variant="secondary" type="button" @click="handleCopyPublicUrl">
            <Link class="size-5" />

            {{ copiedPublicUrl ? 'Copied!' : 'Copy URL' }}
          </UiButton>

          <UiButton v-if="isAuthor" :to="`/folders/${folder.id}`">
            <Wrench class="size-5" />

            Manage folder
          </UiButton>

          <UiButton v-if="canBan()" variant="danger" type="button" @click="showBanModal = true">
            <Ban class="size-5" />
            Ban User
          </UiButton>
        </div>
      </div>

      <div v-if="showVariantSwitcher" class="max-w-sm mb-4">
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
          <strong>{{ folder.owner?.display_name }}</strong
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
          <UiButton variant="secondary" size="md" @click="showBanModal = false"> Cancel </UiButton>
          <UiButton
            variant="danger"
            :disabled="!banReason.trim() || isBanning"
            size="md"
            @click="handleBan"
          >
            {{ isBanning ? 'Banning...' : 'Ban & Nuke Content' }}
          </UiButton>
        </div>
      </div>
    </UiModal>
  </div>
</template>
