import axios, { type AxiosError } from 'axios'

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

export async function createSigns(
  folderId: number,
  payload: CreateSignPayload,
  signal?: AbortSignal,
): Promise<Sign[]> {
  const formData = new FormData()
  payload.files.forEach((file) => {
    formData.append('files[]', file)
  })
  if (payload.variant_id !== undefined) {
    formData.append('variant_id', String(payload.variant_id))
  }

  const { data } = await api.post<{ signs: Sign[] }>(`/api/folders/${folderId}/signs`, formData, {
    signal,
  })
  return data.signs
}

export type BatchUploadProgress = {
  uploaded: number
  total: number
}

export type BatchUploadResult = {
  signs: Sign[]
  failedFiles: File[]
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
  signal?: AbortSignal,
): Promise<BatchUploadResult> {
  const batches = chunk(files, batchSize)
  const signs: Sign[] = []
  const failedFiles: File[] = []
  let uploaded = 0

  onProgress?.({ uploaded, total: files.length })

  for (const batch of batches) {
    if (signal?.aborted) break

    try {
      const createdSigns = await createSigns(
        folderId,
        { files: batch, variant_id: variantId },
        signal,
      )
      signs.push(...createdSigns)
    } catch (exception) {
      if (axios.isCancel(exception) || signal?.aborted) break

      // The whole batch is rejected if even one file in it is invalid (e.g.
      // fails server-side validation). Retry one file at a time so a single
      // bad file doesn't get the rest of its batch wrongly marked as failed.
      for (const file of batch) {
        if (signal?.aborted) break

        try {
          const createdSigns = await createSigns(
            folderId,
            { files: [file], variant_id: variantId },
            signal,
          )
          signs.push(...createdSigns)
        } catch (singleException) {
          if (axios.isCancel(singleException) || signal?.aborted) break
          failedFiles.push(file)
        }
      }
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
