import type { AxiosError } from 'axios'

import api from '@/lib/api'
import type {
  CreateFolderPayload,
  CreateVariantPayload,
  Folder,
  UpdateFolderPayload,
  UpdateVariantPayload,
  Variant,
} from '@/types/folder'

type FolderApiError = {
  message?: string
  errors?: Record<string, string[]>
}

function getErrorMessage(error: unknown) {
  const axiosError = error as AxiosError<FolderApiError> | undefined
  const responseData = axiosError?.response?.data

  if (responseData?.message) {
    return responseData.message
  }

  if (responseData?.errors) {
    const firstError = Object.values(responseData.errors).flat()[0]

    if (firstError) {
      return firstError
    }
  }

  return 'Something went wrong.'
}

export function getFolderErrorMessage(error: unknown) {
  return getErrorMessage(error)
}

export async function getFolders(): Promise<Folder[]> {
  const { data } = await api.get<Folder[]>('/api/folders')
  return data
}

export async function getFolder(id: number): Promise<Folder> {
  const { data } = await api.get<Folder>(`/api/folders/${id}`)
  return data
}

export async function createFolder(payload: CreateFolderPayload): Promise<Folder> {
  const { data } = await api.post<Folder>('/api/folders', payload)
  return data
}

export async function updateFolder(id: number, payload: UpdateFolderPayload): Promise<Folder> {
  const { data } = await api.patch<Folder>(`/api/folders/${id}`, payload)
  return data
}

export async function deleteFolder(id: number): Promise<void> {
  await api.delete(`/api/folders/${id}`)
}

export async function getVariants(folderId: number): Promise<Variant[]> {
  const { data } = await api.get<Variant[]>(`/api/folders/${folderId}/variants`)
  return data
}

export async function createVariant(
  folderId: number,
  payload: CreateVariantPayload,
): Promise<Variant & { backfill_performed?: boolean }> {
  const { data } = await api.post<Variant & { backfill_performed?: boolean }>(
    `/api/folders/${folderId}/variants`,
    payload,
  )
  return data
}

export async function updateVariant(
  folderId: number,
  variantId: number,
  payload: UpdateVariantPayload,
): Promise<Variant> {
  const { data } = await api.patch<Variant>(
    `/api/folders/${folderId}/variants/${variantId}`,
    payload,
  )
  return data
}

export async function deleteVariant(folderId: number, variantId: number): Promise<void> {
  await api.delete(`/api/folders/${folderId}/variants/${variantId}`)
}
