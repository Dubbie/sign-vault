import { ref } from 'vue'
import { defineStore } from 'pinia'

import {
  createSigns as createSignsRequest,
  deleteSigns as deleteSignsRequest,
  getFolderSigns as getFolderSignsRequest,
  getSign as getSignRequest,
  getSignErrorMessage,
} from '@/lib/signs'
import type { CreateSignPayload, Sign } from '@/types/sign'

export const useSignsStore = defineStore('signs', () => {
  const signs = ref<Sign[]>([])
  const currentSign = ref<Sign | null>(null)
  const isLoading = ref(false)
  const isUploading = ref(false)
  const error = ref<string | null>(null)

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

  function removeSign(id: number) {
    signs.value = signs.value.filter((sign) => sign.id !== id)

    if (currentSign.value?.id === id) {
      currentSign.value = null
    }
  }

  async function fetchFolderSigns(folderId: number) {
    isLoading.value = true
    clearError()

    try {
      signs.value = await getFolderSignsRequest(folderId)
      return signs.value
    } catch (exception) {
      setErrorFromUnknown(exception)
      signs.value = []
      return []
    } finally {
      isLoading.value = false
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

  async function uploadSign(folderId: number, payload: CreateSignPayload) {
    isUploading.value = true
    clearError()

    try {
      const createdSigns = await createSignsRequest(folderId, payload)
      createdSigns.forEach(upsertSign)
      return createdSigns
    } catch (exception) {
      setErrorFromUnknown(exception)
      return null
    } finally {
      isUploading.value = false
    }
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
    isUploading,
    error,
    fetchFolderSigns,
    fetchSign,
    uploadSign,
    deleteSigns,
    copySignUrl,
    clearCurrentSign,
    clearError,
  }
})
