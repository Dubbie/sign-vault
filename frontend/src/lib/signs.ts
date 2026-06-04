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

  const { data } = await api.get<PaginatedSignResponse>(`/api/folders/${folderId}/signs`, { params })
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
