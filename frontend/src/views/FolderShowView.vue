<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'

import {
  createVariant as createVariantRequest,
  deleteVariant as deleteVariantRequest,
  updateVariant as updateVariantRequest,
} from '@/lib/folders'
import { useFoldersStore } from '@/stores/folders'
import { useSignsStore } from '@/stores/signs'
import type { Variant } from '@/types/folder'

import UiErrorBanner from '@/components/ui/UiErrorBanner.vue'
import UiAlert from '@/components/ui/UiAlert.vue'
import UiBadge from '@/components/ui/UiBadge.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiSelect from '@/components/ui/UiSelect.vue'
import UiDropdown from '@/components/ui/UiDropdown.vue'
import UiModal from '@/components/ui/UiModal.vue'
import UiFormField from '@/components/ui/UiFormField.vue'
import UiInput from '@/components/ui/UiInput.vue'
import SignGrid from '@/components/signs/SignGrid.vue'
import UploadSignsModal from '@/components/signs/UploadSignsModal.vue'
import MoveSignsModal from '@/components/signs/MoveSignsModal.vue'
import EditFolderModal from '@/components/folders/EditFolderModal.vue'

const foldersStore = useFoldersStore()
const signsStore = useSignsStore()
const route = useRoute()

const folderId = computed(() => Number(route.params.id))
const folder = computed(() => foldersStore.currentFolder)

const variants = computed<Variant[]>(() => folder.value?.variants ?? [])
const showVariantTabs = computed(() => variants.value.length > 1)
const activeVariantContentKey = computed(() => activeVariant()?.id ?? 0)
const variantCreateActionLabel = computed(() =>
  showVariantTabs.value ? 'Add variant' : 'Make variant',
)
const variantOptions = computed(() =>
  variants.value.map((variant) => ({
    value: String(variant.id),
    label: variantSelectLabel(variant),
  })),
)
const changeVariantOptions = computed(() =>
  variants.value
    .filter((variant) => activeVariant()?.id !== variant.id)
    .map((variant) => ({
      value: String(variant.id),
      label: variantDisplayLabel(variant),
    })),
)
const selectedVariantSelectValue = computed({
  get() {
    return String(activeVariant()?.id ?? '')
  },
  set(value: string) {
    if (!value) return
    const variant = variants.value.find((candidate) => String(candidate.id) === value)
    if (!variant) return
    void handleVariantSelect(variant)
  },
})
const activeVariantRecord = computed(() => activeVariant())

function isDefaultVariant(variant: Variant) {
  return variant.is_default
}

function variantDisplayLabel(variant: Variant | null) {
  if (!variant) return 'Default'
  if (isDefaultVariant(variant)) return variant.name ?? folder.value?.name ?? 'Default'
  return variant.name ?? 'Unnamed'
}

function variantSelectLabel(variant: Variant) {
  const label = variantDisplayLabel(variant)
  return variant.is_default ? `${label} (default)` : label
}

function activeVariant(): Variant | null {
  if (selectedVariantId.value) {
    return variants.value.find((v) => v.id === selectedVariantId.value) ?? null
  }
  return variants.value.find((v) => v.is_default) ?? null
}

async function handleVariantSelect(variant: Variant, close?: () => void) {
  if (activeVariant()?.id === variant.id) {
    close?.()
    return
  }
  close?.()
  selectedVariantId.value = variant.id
  await signsStore.fetchFolderSigns(folderId.value, variant.id)
}

async function handleVariantToggle() {
  if (!showVariantTabs.value) {
    selectedVariantId.value = null
    await signsStore.fetchFolderSigns(folderId.value)
  } else if (!selectedVariantId.value) {
    const defaultV = variants.value.find((v) => v.is_default)
    if (defaultV) {
      selectedVariantId.value = defaultV.id
      await signsStore.fetchFolderSigns(folderId.value, defaultV.id)
    }
  }
}

const showUploadModal = ref(false)
const showMoveModal = ref(false)
const showEditModal = ref(false)
const showDeleteConfirm = ref(false)
const showChangeVariantModal = ref(false)
const changeVariantSelectValue = ref('')
const showVariantActions = ref(false)
const copiedSignId = ref<number | null>(null)
const copiedPublicUrl = ref(false)
const selectedSignIds = ref<number[]>([])
const isDeleting = ref(false)
const selectedVariantId = ref<number | null>(null)
const variantError = ref<string | null>(null)
const newVariantName = ref('')
const isCreatingVariant = ref(false)
const renamingVariant = ref<{ id: number; name: string } | null>(null)
const showDeleteVariantConfirm = ref<number | null>(null)
const showCreateVariantInput = ref(false)

function visibilityLabel(visibility: string) {
  return visibility.charAt(0).toUpperCase() + visibility.slice(1)
}

const publicFolderPath = computed(() => {
  const origin = window.location.origin
  const path = `/public/folders/${folder.value?.public_slug ?? ''}`
  return `${origin}${path}`
})

function canShareFolder() {
  return folder.value?.visibility !== 'private'
}

async function loadFolder() {
  const id = folderId.value
  if (!Number.isFinite(id)) {
    foldersStore.error = 'Invalid folder id.'
    return
  }
  const loadedFolder = await foldersStore.fetchFolder(id)
  if (loadedFolder) {
    document.title = `${loadedFolder.name} — SignVault`

    const variantParam = route.query.variant
    if (variantParam) {
      const parsed = Number(variantParam)
      const found = loadedFolder.variants.find((v) => v.id === parsed)
      if (found) {
        selectedVariantId.value = found.id
        await signsStore.fetchFolderSigns(id, found.id)
        return
      }
    }

    await signsStore.fetchFolderSigns(id)
  }
}

onMounted(loadFolder)

onUnmounted(() => {
  foldersStore.clearCurrentFolder()
  signsStore.clearCurrentSign()
  signsStore.signs = []
})

watch(folderId, () => {
  foldersStore.clearCurrentFolder()
  signsStore.clearCurrentSign()
  signsStore.signs = []
  copiedSignId.value = null
  copiedPublicUrl.value = false
  selectedSignIds.value = []
  selectedVariantId.value = null
  void loadFolder()
})

async function handleCopy(signId: number) {
  const sign = signsStore.signs.find((s) => s.id === signId)
  if (!sign) return

  const copied = await signsStore.copySignUrl(sign)
  if (!copied) return

  copiedSignId.value = signId
  window.setTimeout(() => {
    if (copiedSignId.value === signId) copiedSignId.value = null
  }, 1500)
}

async function handleCopyPublicUrl() {
  signsStore.clearError()
  try {
    await navigator.clipboard.writeText(publicFolderPath.value)
    copiedPublicUrl.value = true
    window.setTimeout(() => {
      copiedPublicUrl.value = false
    }, 1500)
  } catch {
    signsStore.error = 'Could not copy the public URL. Please copy it manually.'
  }
}

async function handleDeleteSelected() {
  isDeleting.value = true
  try {
    await signsStore.deleteSigns(selectedSignIds.value)
    selectedSignIds.value = []
    showDeleteConfirm.value = false
  } catch {
    // error is set in the store
  } finally {
    isDeleting.value = false
  }
}

async function handleCreateVariant() {
  if (!folder.value || !newVariantName.value.trim()) return
  isCreatingVariant.value = true
  variantError.value = null

  try {
    const created = await createVariantRequest(folder.value.id, {
      name: newVariantName.value.trim(),
    })
    newVariantName.value = ''
    showCreateVariantInput.value = false
    await foldersStore.fetchFolder(folder.value.id)
    selectedVariantId.value = created.id
    await signsStore.fetchFolderSigns(folder.value.id, created.id)
  } catch {
    variantError.value = 'Failed to create variant.'
  } finally {
    isCreatingVariant.value = false
  }
}

function openRenameVariant(variant: Variant, close?: () => void) {
  close?.()
  renamingVariant.value = {
    id: variant.id,
    name: variant.name ?? folder.value?.name ?? '',
  }
}

function closeRenameVariant() {
  renamingVariant.value = null
}

async function handleConfirmRenameVariant() {
  if (!renamingVariant.value) return

  await handleRenameVariant(renamingVariant.value.id, renamingVariant.value.name)
}

function openDeleteVariant(variantId: number, close?: () => void) {
  close?.()
  showDeleteVariantConfirm.value = variantId
}

function closeDeleteVariant() {
  showDeleteVariantConfirm.value = null
}

async function handleConfirmDeleteVariant() {
  if (showDeleteVariantConfirm.value === null) return

  await handleDeleteVariant(showDeleteVariantConfirm.value)
}

async function handleRenameVariant(variantId: number, newName: string) {
  if (!folder.value || !newName.trim()) return
  variantError.value = null

  try {
    await updateVariantRequest(folder.value.id, variantId, {
      name: newName.trim(),
    })
    renamingVariant.value = null
    await foldersStore.fetchFolder(folder.value.id)
  } catch {
    variantError.value = 'Failed to rename variant.'
  }
}

async function handleSetDefault(variantId: number, close?: () => void) {
  close?.()
  if (!folder.value) return
  variantError.value = null

  try {
    await updateVariantRequest(folder.value.id, variantId, {
      is_default: true,
    })
    await foldersStore.fetchFolder(folder.value.id)
    selectedVariantId.value = variantId
    await signsStore.fetchFolderSigns(folderId.value, variantId)
  } catch {
    variantError.value = 'Failed to set default variant.'
  }
}

async function handleDeleteVariant(variantId: number) {
  if (!folder.value) return
  variantError.value = null

  try {
    await deleteVariantRequest(folder.value.id, variantId)
    showDeleteVariantConfirm.value = null
    if (selectedVariantId.value === variantId) {
      selectedVariantId.value = null
    }
    await foldersStore.fetchFolder(folder.value.id)
    await signsStore.fetchFolderSigns(folderId.value)
  } catch {
    variantError.value = 'Failed to delete variant.'
  }
}

function clearSelection() {
  selectedSignIds.value = []
}

async function handleChangeVariantSubmit() {
  if (!folder.value || !changeVariantSelectValue.value) return

  const targetVariantId = Number(changeVariantSelectValue.value)
  await signsStore.changeSignVariant(selectedSignIds.value, targetVariantId)
  selectedSignIds.value = []
  showChangeVariantModal.value = false
  await signsStore.fetchFolderSigns(folderId.value, selectedVariantId.value ?? undefined)
}

watch(
  () => showChangeVariantModal.value,
  (open) => {
    if (open) {
      changeVariantSelectValue.value = changeVariantOptions.value[0]?.value ?? ''
    } else {
      changeVariantSelectValue.value = ''
    }
  },
)
</script>

<template>
  <div>
    <RouterLink
      to="/folders"
      class="text-sm flex items-center gap-x-2 text-emerald-400 underline-offset-2 hover:text-emerald-200"
    >
      <svg
        xmlns="http://www.w3.org/2000/svg"
        fill="none"
        viewBox="0 0 24 24"
        stroke-width="3"
        stroke="currentColor"
        class="size-4"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"
        />
      </svg>

      <span>Back to folders</span>
    </RouterLink>

    <div v-if="foldersStore.error && !folder" class="mt-3">
      <UiErrorBanner>{{ foldersStore.error }}</UiErrorBanner>
    </div>

    <p v-if="foldersStore.isLoading && !folder" class="mt-3 text-zinc-400">Loading folder...</p>

    <div v-else-if="folder" class="mt-3">
      <header class="flex flex-wrap items-start justify-between gap-4">
        <div>
          <div class="flex items-center gap-4">
            <h1 class="text-[clamp(2rem,4vw,2.5rem)] leading-none text-zinc-100">
              {{ folder.name }}
            </h1>
            <UiBadge class="mt-1.5" :label="visibilityLabel(folder.visibility)" />
          </div>
          <p class="font-mono text-xs text-zinc-400 mt-2">{{ folder.slug }}</p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
          <UiButton variant="secondary" type="button" @click="showEditModal = true">
            Edit
          </UiButton>

          <UiButton
            v-if="canShareFolder()"
            variant="secondary"
            type="button"
            @click="handleCopyPublicUrl"
          >
            {{ copiedPublicUrl ? 'Copied!' : 'Copy public URL' }}
          </UiButton>

          <UiButton variant="primary" type="button" @click="showUploadModal = true">
            Upload signs
          </UiButton>
        </div>
      </header>

      <section class="mt-4 border-t border-white/10 pt-4">
        <template v-if="showVariantTabs">
          <div class="flex flex-wrap items-end justify-between gap-3">
            <div class="flex items-baseline gap-x-2">
              <h2 class="text-sm font-semibold text-zinc-400">Variants</h2>
              <p class="text-xs font-mono text-zinc-500">{{ variants.length }} total</p>
            </div>

            <UiButton variant="secondary" type="button" @click="showCreateVariantInput = true">
              {{ variantCreateActionLabel }}
            </UiButton>
          </div>

          <div class="mt-3 grid gap-3 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-start">
            <div class="grid gap-[0.4rem]">
              <UiSelect
                v-model="selectedVariantSelectValue"
                name="variant"
                :options="variantOptions"
              />
            </div>

            <div class="flex items-start justify-end gap-2">
              <UiDropdown
                v-if="activeVariantRecord"
                v-model="showVariantActions"
                placement="bottom-end"
                trigger-class="inline-flex"
              >
                <template #trigger="{ toggle }">
                  <button
                    type="button"
                    class="inline-flex size-9 items-center justify-center rounded border border-white/10 bg-background/60 text-zinc-300 transition hover:border-white/20 hover:bg-surface hover:text-zinc-100 focus:outline-none focus:bg-surface focus:border-emerald-400"
                    :aria-label="`Variant actions for ${variantDisplayLabel(activeVariantRecord)}`"
                    @click="toggle"
                  >
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M5 12h.01M12 12h.01M19 12h.01"
                      />
                    </svg>
                  </button>
                </template>

                <template #default="{ close }">
                  <div>
                    <button
                      type="button"
                      class="flex w-full items-center rounded px-3 py-2 text-left text-sm text-zinc-300 transition hover:bg-white/5 hover:text-zinc-100"
                      @click="openRenameVariant(activeVariantRecord, close)"
                    >
                      Rename
                    </button>
                    <button
                      v-if="activeVariantRecord && !activeVariantRecord.is_default"
                      type="button"
                      class="flex w-full items-center rounded px-3 py-2 text-left text-sm text-zinc-300 transition hover:bg-white/5 hover:text-zinc-100"
                      @click="handleSetDefault(activeVariantRecord.id, close)"
                    >
                      Make default
                    </button>
                    <button
                      v-if="activeVariantRecord && !activeVariantRecord.is_default"
                      type="button"
                      class="flex w-full items-center rounded px-3 py-2 text-left text-sm text-red-400 transition hover:bg-red-500/10"
                      @click="openDeleteVariant(activeVariantRecord.id, close)"
                    >
                      Delete
                    </button>
                  </div>
                </template>
              </UiDropdown>
            </div>
          </div>
        </template>

        <template v-else>
          <UiAlert tone="info">
            <p class="font-medium text-zinc-100">Need some variety?</p>
            <p class="mt-1 text-zinc-400">
              Variants let you keep separate sign sets inside the same folder. Create one when you
              want a different version without duplicating the folder.
            </p>
            <template #actions>
              <UiButton variant="secondary" type="button" @click="showCreateVariantInput = true">
                Make variant
              </UiButton>
            </template>
          </UiAlert>
        </template>

        <div v-if="showCreateVariantInput" class="mt-3 flex flex-wrap items-center gap-2">
          <UiInput
            v-model="newVariantName"
            placeholder="Variant name..."
            class="min-w-[12rem] flex-1"
            @keyup.enter="handleCreateVariant"
            @keyup.escape="((showCreateVariantInput = false), (newVariantName = ''))"
          />
          <UiButton
            variant="primary"
            type="button"
            :disabled="!newVariantName.trim() || isCreatingVariant"
            @click="handleCreateVariant"
          >
            Create
          </UiButton>
          <UiButton
            variant="secondary"
            type="button"
            @click="((showCreateVariantInput = false), (newVariantName = ''))"
          >
            Cancel
          </UiButton>
        </div>
      </section>

      <UiErrorBanner v-if="variantError">
        {{ variantError }}
      </UiErrorBanner>

      <UiErrorBanner v-if="signsStore.error && folder" class="mt-4">
        {{ signsStore.error }}
      </UiErrorBanner>

      <div class="mt-6">
        <Transition name="variant-content" mode="out-in">
          <div :key="activeVariantContentKey" class="grid gap-2">
            <p v-if="signsStore.isLoading" class="text-zinc-400">Loading signs...</p>
            <p v-else-if="signsStore.signs.length === 0" class="text-zinc-400">
              No signs uploaded yet.
            </p>
            <SignGrid
              v-else
              class="mb-13"
              :signs="signsStore.signs"
              :copied-sign-id="copiedSignId"
              :has-more="signsStore.hasMore"
              :is-loading-more="signsStore.isLoadingMore"
              v-model="selectedSignIds"
              @copy="handleCopy"
              @load-more="signsStore.fetchMoreSigns(folderId, selectedVariantId ?? undefined)"
            />
          </div>
        </Transition>
      </div>
    </div>

    <UploadSignsModal
      v-if="folder"
      v-model="showUploadModal"
      :folder-id="folder.id"
      :variants="variants"
      :selected-variant-id="selectedVariantId"
      @saved="signsStore.fetchFolderSigns(folder.id)"
    />

    <MoveSignsModal
      v-if="folder"
      v-model="showMoveModal"
      :folder-id="folder.id"
      :sign-ids="selectedSignIds"
      @saved="(signsStore.fetchFolderSigns(folder.id), (selectedSignIds = []))"
    />

    <EditFolderModal v-if="folder" v-model="showEditModal" :folder-id="folder.id" />

    <Transition name="toolbar">
      <div v-if="selectedSignIds.length > 0" class="fixed flex flex-col bottom-0 top-0 left-2 z-40">
        <div
          class="bg-background/60 backdrop-blur border border-white/20 shadow-2xl p-3 rounded-md my-auto flex flex-col max-w-3xl items-center justify-between"
        >
          <p class="text-sm text-zinc-300 mb-6">
            <span class="font-semibold text-zinc-100">{{ selectedSignIds.length }}</span>
            selected
          </p>

          <div class="flex flex-col items-center gap-3">
            <UiButton class="w-full" variant="secondary" type="button" @click="clearSelection">
              Clear
            </UiButton>

            <UiButton class="w-full" variant="primary" type="button" @click="showMoveModal = true">
              Move
            </UiButton>

            <UiButton
              v-if="showVariantTabs"
              class="w-full"
              variant="secondary"
              type="button"
              @click="showChangeVariantModal = true"
            >
              Change Variant
            </UiButton>

            <UiButton
              class="w-full"
              variant="danger"
              type="button"
              @click="showDeleteConfirm = true"
            >
              Delete
            </UiButton>
          </div>
        </div>
      </div>
    </Transition>

    <UiModal v-model="showDeleteConfirm" title="Delete signs">
      <p class="text-zinc-300 text-sm">
        Are you sure you want to delete
        <span class="font-semibold text-zinc-100">{{ selectedSignIds.length }}</span>
        sign{{ selectedSignIds.length === 1 ? '' : 's' }}? This action cannot be undone.
      </p>

      <div class="mt-6 flex justify-end gap-3">
        <UiButton variant="secondary" type="button" @click="showDeleteConfirm = false">
          Cancel
        </UiButton>

        <UiButton
          variant="danger"
          type="button"
          :disabled="isDeleting"
          @click="handleDeleteSelected"
        >
          {{ isDeleting ? 'Deleting...' : 'Delete' }}
        </UiButton>
      </div>
    </UiModal>

    <UiModal v-model="showChangeVariantModal" title="Change variant">
      <p class="text-sm text-zinc-300 mb-4">
        Move
        <span class="font-semibold text-zinc-100">{{ selectedSignIds.length }}</span>
        sign{{ selectedSignIds.length === 1 ? '' : 's' }} to a different variant.
      </p>

      <div class="grid gap-2">
        <UiSelect
          v-model="changeVariantSelectValue"
          name="change-variant"
          :options="changeVariantOptions"
        />

        <p v-if="changeVariantOptions.length === 0" class="text-sm text-zinc-400">
          No other variants available.
        </p>
      </div>

      <div class="mt-6 flex justify-end gap-3">
        <UiButton variant="secondary" type="button" @click="showChangeVariantModal = false">
          Cancel
        </UiButton>
        <UiButton
          variant="primary"
          type="button"
          :disabled="!changeVariantSelectValue"
          @click="handleChangeVariantSubmit"
        >
          Change
        </UiButton>
      </div>
    </UiModal>

    <UiModal
      :model-value="renamingVariant !== null"
      title="Rename variant"
      @update:model-value="closeRenameVariant"
    >
      <div v-if="renamingVariant">
        <UiFormField label="Variant name" name="variant-name">
          <UiInput
            v-model="renamingVariant.name"
            placeholder="Variant name..."
            @keyup.enter="handleConfirmRenameVariant"
            @keyup.escape="closeRenameVariant"
          />
        </UiFormField>

        <div class="mt-6 flex justify-end gap-3">
          <UiButton variant="secondary" type="button" @click="closeRenameVariant">
            Cancel
          </UiButton>
          <UiButton
            variant="primary"
            type="button"
            :disabled="!renamingVariant.name.trim()"
            @click="handleConfirmRenameVariant"
          >
            Save
          </UiButton>
        </div>
      </div>
    </UiModal>

    <UiModal
      :model-value="showDeleteVariantConfirm !== null"
      title="Delete variant"
      @update:model-value="closeDeleteVariant"
    >
      <div v-if="showDeleteVariantConfirm !== null">
        <p class="text-sm text-zinc-300">
          Delete this variant? Signs in it will remain in the folder, but the variant itself will be
          removed.
        </p>

        <div class="mt-6 flex justify-end gap-3">
          <UiButton variant="secondary" type="button" @click="closeDeleteVariant">
            Cancel
          </UiButton>
          <UiButton variant="danger" type="button" @click="handleConfirmDeleteVariant">
            Delete
          </UiButton>
        </div>
      </div>
    </UiModal>
  </div>
</template>

<style scoped>
.variant-content-enter-active {
  transition:
    opacity 0.18s ease-out,
    transform 0.18s ease-out;
}

.variant-content-leave-active {
  transition:
    opacity 0.14s ease-in,
    transform 0.14s ease-in;
}

.variant-content-enter-from,
.variant-content-leave-to {
  opacity: 0;
  transform: translateY(8px);
}

.toolbar-enter-active {
  transition: transform 0.25s ease-out;
}

.toolbar-leave-active {
  transition: transform 0.2s ease-in;
}

.toolbar-enter-from,
.toolbar-leave-to {
  transform: translateX(-100%);
}
</style>
