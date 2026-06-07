import type { AxiosError } from 'axios'

import api from '@/lib/api'
import type { CreateSignPayload, PaginatedSignResponse, Sign } from '@/types/sign'

type SignApiError = {
  message?: string
  errors?: Record<string, string[]>
}

function getErrorMessage(error: unknown) {
  const axiosError = error as AxiosError<SignApiError> | undefined
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

export function getSignErrorMessage(error: unknown) {
  return getErrorMessage(error)
}

export async function getFolderSigns(
  folderId: number,
  page = 1,
  perPage = 10,
  columnRatio?: number,
  variantId?: number,
): Promise<PaginatedSignResponse> {
  const params: Record<string, number> = { page, per_page: perPage }
  if (columnRatio !== undefined) params.column_ratio = columnRatio
  if (variantId !== undefined) params.variant_id = variantId

  const { data } = await api.get<PaginatedSignResponse>(`/api/folders/${folderId}/signs`, {
    params,
  })
  return data
}

export async function getSign(id: number): Promise<Sign> {
  const { data } = await api.get<Sign>(`/api/signs/${id}`)
  return data
}

export async function createSigns(folderId: number, payload: CreateSignPayload): Promise<Sign[]> {
  const formData = new FormData()
  payload.files.forEach((file) => {
    formData.append('files[]', file)
  })
  if (payload.variant_id !== undefined) {
    formData.append('variant_id', String(payload.variant_id))
  }

  const { data } = await api.post<{ signs: Sign[] }>(`/api/folders/${folderId}/signs`, formData)
  return data.signs
}

export type BatchUploadProgress = {
  uploaded: number
  total: number
}

export type BatchUploadResult = {
  signs: Sign[]
  failedFiles: string[]
}

function chunk<T>(items: T[], size: number): T[][] {
  const chunks: T[][] = []
  for (let i = 0; i < items.length; i += size) {
    chunks.push(items.slice(i, i + size))
  }
  return chunks
}

export async function createSignsInBatches(
  folderId: number,
  files: File[],
  variantId: number | undefined,
  batchSize: number,
  onProgress?: (progress: BatchUploadProgress) => void,
): Promise<BatchUploadResult> {
  const batches = chunk(files, batchSize)
  const signs: Sign[] = []
  const failedFiles: string[] = []
  let uploaded = 0

  onProgress?.({ uploaded, total: files.length })

  for (const batch of batches) {
    try {
      const createdSigns = await createSigns(folderId, { files: batch, variant_id: variantId })
      signs.push(...createdSigns)
    } catch {
      failedFiles.push(...batch.map((file) => file.name))
    }

    uploaded += batch.length
    onProgress?.({ uploaded, total: files.length })
  }

  return { signs, failedFiles }
}

export async function deleteSigns(ids: number[]): Promise<void> {
  await api.delete('/api/signs', { data: { ids } })
}

export async function moveSigns(signIds: number[], targetFolderId: number): Promise<void> {
  await api.patch('/api/signs/move', {
    ids: signIds,
    folder_id: targetFolderId,
  })
}

export async function changeSignVariant(
  signIds: number[],
  variantId: number,
): Promise<{ message: string; changed_count: number }> {
  const { data } = await api.patch<{ message: string; changed_count: number }>(
    '/api/signs/variant',
    { ids: signIds, variant_id: variantId },
  )
  return data
}
