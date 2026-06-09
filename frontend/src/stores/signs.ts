import { computed, ref } from 'vue'
import { defineStore } from 'pinia'

import {
  changeSignVariant as changeSignVariantRequest,
  createSignsInBatches,
  deleteSigns as deleteSignsRequest,
  generateUploadSessionId,
  getFolderSigns as getFolderSignsRequest,
  getSign as getSignRequest,
  getSignErrorMessage,
  getSignThumbnailStatuses,
  moveSigns as moveSignsRequest,
} from '@/lib/signs'
import type { BatchUploadProgress } from '@/lib/signs'
import type { CreateSignPayload, Sign } from '@/types/sign'

const COLUMN_RATIOS = [6, 4, 2, 1] as const
type ColumnRatio = (typeof COLUMN_RATIOS)[number]
const PER_COLUMN = 10
const UPLOAD_BATCH_SIZE = 20
const THUMBNAIL_POLL_INTERVAL_MS = 2000
const THUMBNAIL_POLL_TIMEOUT_MS = 30000

type ColumnState = { currentPage: number; hasMore: boolean }

function initialColumnState(): Record<ColumnRatio, ColumnState> {
  return {
    6: { currentPage: 0, hasMore: false },
    4: { currentPage: 0, hasMore: false },
    2: { currentPage: 0, hasMore: false },
    1: { currentPage: 0, hasMore: false },
  }
}

export const useSignsStore = defineStore('signs', () => {
  const signs = ref<Sign[]>([])
  const currentSign = ref<Sign | null>(null)
  const isLoading = ref(false)
  const isLoadingMore = ref(false)
  const isUploading = ref(false)
  const uploadProgress = ref<BatchUploadProgress | null>(null)
  const uploadFailedFiles = ref<File[]>([])
  const uploadCancelled = ref(false)
  let uploadController: AbortController | null = null
  const isMoving = ref(false)
  const error = ref<string | null>(null)
  const columnState = ref<Record<ColumnRatio, ColumnState>>(initialColumnState())

  const hasMore = computed(() => COLUMN_RATIOS.some((r) => columnState.value[r].hasMore))

  function clearCurrentSign() {
    currentSign.value = null
  }

  function clearError() {
    error.value = null
  }

  function setErrorFromUnknown(exception: unknown) {
    error.value = getSignErrorMessage(exception)
  }

  function upsertSign(sign: Sign) {
    const index = signs.value.findIndex((item) => item.id === sign.id)

    if (index === -1) {
      signs.value = [sign, ...signs.value]
      return
    }

    signs.value = signs.value.map((item) => (item.id === sign.id ? sign : item))
  }

  async function refreshPendingThumbnails(signIds: number[]) {
    const startedAt = Date.now()
    let pendingIds = [...new Set(signIds)]

    while (pendingIds.length > 0 && Date.now() - startedAt < THUMBNAIL_POLL_TIMEOUT_MS) {
      await new Promise((resolve) => {
        window.setTimeout(resolve, THUMBNAIL_POLL_INTERVAL_MS)
      })

      try {
        const statuses = await getSignThumbnailStatuses(pendingIds)
        pendingIds = []

        statuses.forEach((status) => {
          const existing = signs.value.find((s) => s.id === status.id)
          if (existing && status.thumbnail_status !== existing.thumbnail_status) {
            upsertSign({ ...existing, ...status })
          }
          if (status.thumbnail_status === 'pending') {
            pendingIds.push(status.id)
          }
        })
      } catch {
        break
      }
    }
  }

  function removeSign(id: number) {
    signs.value = signs.value.filter((sign) => sign.id !== id)

    if (currentSign.value?.id === id) {
      currentSign.value = null
    }
  }

  async function fetchFolderSigns(folderId: number, variantId?: number) {
    isLoading.value = true
    columnState.value = initialColumnState()
    clearError()

    try {
      const results = await Promise.all(
        COLUMN_RATIOS.map((ratio) =>
          getFolderSignsRequest(folderId, 1, PER_COLUMN, ratio, variantId),
        ),
      )

      signs.value = results.flatMap((r) => r.data)

      for (let i = 0; i < COLUMN_RATIOS.length; i++) {
        const ratio = COLUMN_RATIOS[i]!
        const meta = results[i]!.meta
        columnState.value[ratio] = {
          currentPage: meta.current_page,
          hasMore: meta.current_page < meta.last_page,
        }
      }

      return signs.value
    } catch (exception) {
      setErrorFromUnknown(exception)
      signs.value = []
      return []
    } finally {
      isLoading.value = false
    }
  }

  async function fetchMoreSigns(folderId: number, variantId?: number) {
    if (!hasMore.value || isLoadingMore.value) return

    isLoadingMore.value = true

    try {
      const ratiosWithMore = COLUMN_RATIOS.filter((r) => columnState.value[r].hasMore)

      const results = await Promise.all(
        ratiosWithMore.map((ratio) =>
          getFolderSignsRequest(
            folderId,
            columnState.value[ratio].currentPage + 1,
            PER_COLUMN,
            ratio,
            variantId,
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
      setErrorFromUnknown(exception)
    } finally {
      isLoadingMore.value = false
    }
  }

  async function fetchSign(id: number) {
    isLoading.value = true
    clearError()

    try {
      const sign = await getSignRequest(id)
      currentSign.value = sign
      upsertSign(sign)
      return sign
    } catch (exception) {
      setErrorFromUnknown(exception)
      currentSign.value = null
      return null
    } finally {
      isLoading.value = false
    }
  }

  async function uploadSign(folderId: number, payload: CreateSignPayload, batchSize?: number) {
    isUploading.value = true
    uploadProgress.value = {
      completedFiles: 0,
      totalFiles: payload.files.length,
      uploadedBytes: 0,
      totalBytes: payload.files.reduce((sum, file) => sum + file.size, 0),
    }
    uploadFailedFiles.value = []
    uploadCancelled.value = false
    clearError()

    uploadController = new AbortController()

    try {
      const uploadSessionId = generateUploadSessionId()

      const { signs: createdSigns, failedFiles } = await createSignsInBatches(
        folderId,
        payload.files,
        payload.variant_id,
        uploadSessionId,
        batchSize ?? UPLOAD_BATCH_SIZE,
        (progress) => {
          uploadProgress.value = progress
        },
        uploadController.signal,
      )

      createdSigns.forEach(upsertSign)
      uploadFailedFiles.value = failedFiles

      const pendingThumbnailSignIds = createdSigns
        .filter((sign) => sign.thumbnail_status === 'pending')
        .map((sign) => sign.id)

      if (pendingThumbnailSignIds.length > 0) {
        void refreshPendingThumbnails(pendingThumbnailSignIds)
      }

      if (failedFiles.length > 0 && createdSigns.length === 0) {
        error.value = 'Upload failed for all files. Please try again.'
        return null
      }

      return createdSigns
    } catch (exception) {
      setErrorFromUnknown(exception)
      return null
    } finally {
      isUploading.value = false
      uploadController = null
    }
  }

  function cancelUpload() {
    uploadCancelled.value = true
    uploadController?.abort()
  }

  async function deleteSigns(ids: number[]) {
    clearError()

    try {
      await deleteSignsRequest(ids)
      ids.forEach(removeSign)
    } catch (exception) {
      setErrorFromUnknown(exception)
    }
  }

  async function moveSigns(signIds: number[], targetFolderId: number) {
    isMoving.value = true
    clearError()

    try {
      await moveSignsRequest(signIds, targetFolderId)
      signIds.forEach(removeSign)
      return true
    } catch (exception) {
      setErrorFromUnknown(exception)
      return false
    } finally {
      isMoving.value = false
    }
  }

  async function changeSignVariant(signIds: number[], variantId: number) {
    clearError()

    try {
      await changeSignVariantRequest(signIds, variantId)
      return true
    } catch (exception) {
      setErrorFromUnknown(exception)
      return false
    }
  }

  async function copySignUrl(sign: Sign) {
    clearError()

    try {
      await navigator.clipboard.writeText(sign.public_url)
      return true
    } catch {
      error.value = 'Could not copy the sign URL. Please copy it manually.'
      return false
    }
  }

  return {
    signs,
    currentSign,
    isLoading,
    isLoadingMore,
    isUploading,
    uploadProgress,
    uploadFailedFiles,
    uploadCancelled,
    cancelUpload,
    error,
    hasMore,
    fetchFolderSigns,
    fetchMoreSigns,
    fetchSign,
    uploadSign,
    deleteSigns,
    moveSigns,
    isMoving,
    changeSignVariant,
    copySignUrl,
    clearCurrentSign,
    clearError,
  }
})
