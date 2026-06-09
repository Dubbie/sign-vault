import axios, { type AxiosError } from 'axios'

import api from '@/lib/api'
import type { CreateSignPayload, PaginatedSignResponse, Sign } from '@/types/sign'

type SignApiError = {
  message?: string
  errors?: Record<string, string[]>
}

type PreparedUploadFile = {
  original_name: string
  mime_type: string
  size_bytes: number
  width: number | null
  height: number | null
}

type PreparedUpload = {
  id: string
  original_name: string
  storage_key: string
  public_url: string
  upload_url: string
  upload_headers: Record<string, string>
}

type FileDescriptor = {
  file: File
  metadata: PreparedUploadFile
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
  if (payload.upload_session_id !== undefined) {
    formData.append('upload_session_id', payload.upload_session_id)
  }

  const { data } = await api.post<{ signs: Sign[] }>(`/api/folders/${folderId}/signs`, formData, {
    signal,
  })
  return data.signs
}

export type BatchUploadProgress = {
  completedFiles: number
  totalFiles: number
  uploadedBytes: number
  totalBytes: number
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

function getTotalBytes(files: File[]) {
  return files.reduce((sum, file) => sum + file.size, 0)
}

function emitProgress(
  fileLoadedBytes: number[],
  files: File[],
  onProgress: ((progress: BatchUploadProgress) => void) | undefined,
) {
  if (!onProgress) {
    return
  }

  const uploadedBytes = fileLoadedBytes.reduce((sum, value) => sum + value, 0)
  const completedFiles = fileLoadedBytes.filter(
    (value, index) => value >= files[index]!.size,
  ).length

  onProgress({
    completedFiles,
    totalFiles: files.length,
    uploadedBytes,
    totalBytes: getTotalBytes(files),
  })
}

async function readImageDimensions(file: File): Promise<{ width: number; height: number } | null> {
  return new Promise((resolve) => {
    const image = new Image()
    const url = URL.createObjectURL(file)

    image.onload = () => {
      resolve({
        width: image.naturalWidth,
        height: image.naturalHeight,
      })
      URL.revokeObjectURL(url)
    }

    image.onerror = () => {
      resolve(null)
      URL.revokeObjectURL(url)
    }

    image.src = url
  })
}

async function readVideoDimensions(file: File): Promise<{ width: number; height: number } | null> {
  return new Promise((resolve) => {
    const video = document.createElement('video')
    const url = URL.createObjectURL(file)

    video.onloadedmetadata = () => {
      resolve({
        width: video.videoWidth,
        height: video.videoHeight,
      })
      URL.revokeObjectURL(url)
    }

    video.onerror = () => {
      resolve(null)
      URL.revokeObjectURL(url)
    }

    video.preload = 'metadata'
    video.src = url
  })
}

async function describeFile(file: File): Promise<FileDescriptor> {
  const dimensions =
    file.type === 'video/webm' ? await readVideoDimensions(file) : await readImageDimensions(file)

  return {
    file,
    metadata: {
      original_name: file.name,
      mime_type: file.type,
      size_bytes: file.size,
      width: dimensions?.width ?? null,
      height: dimensions?.height ?? null,
    },
  }
}

async function prepareSignUploads(
  folderId: number,
  files: PreparedUploadFile[],
  variantId: number | undefined,
  uploadSessionId: string,
): Promise<PreparedUpload[]> {
  const { data } = await api.post<{ uploads: PreparedUpload[] }>(
    `/api/folders/${folderId}/signs/uploads/prepare`,
    {
      files,
      variant_id: variantId,
      upload_session_id: uploadSessionId,
    },
  )

  return data.uploads
}

async function completeSignUploads(
  folderId: number,
  intentIds: string[],
  variantId: number | undefined,
  uploadSessionId: string,
): Promise<Sign[]> {
  const { data } = await api.post<{ signs: Sign[] }>(
    `/api/folders/${folderId}/signs/uploads/complete`,
    {
      intent_ids: intentIds,
      variant_id: variantId,
      upload_session_id: uploadSessionId,
    },
  )

  return data.signs
}

async function uploadPreparedFile(
  preparedUpload: PreparedUpload,
  file: File,
  fileIndex: number,
  fileLoadedBytes: number[],
  files: File[],
  onProgress: ((progress: BatchUploadProgress) => void) | undefined,
  signal?: AbortSignal,
): Promise<boolean> {
  for (let attempt = 0; attempt < 2; attempt++) {
    fileLoadedBytes[fileIndex] = 0
    emitProgress(fileLoadedBytes, files, onProgress)

    try {
      await axios.put(preparedUpload.upload_url, file, {
        headers: {
          'Content-Type': file.type,
          ...preparedUpload.upload_headers,
        },
        onUploadProgress: (progressEvent) => {
          fileLoadedBytes[fileIndex] = Math.min(progressEvent.loaded, file.size)
          emitProgress(fileLoadedBytes, files, onProgress)
        },
        signal,
      })

      fileLoadedBytes[fileIndex] = file.size
      emitProgress(fileLoadedBytes, files, onProgress)

      return true
    } catch (exception) {
      if (axios.isCancel(exception) || signal?.aborted) {
        throw exception
      }

      fileLoadedBytes[fileIndex] = 0
      emitProgress(fileLoadedBytes, files, onProgress)

      if (attempt === 1) {
        return false
      }
    }
  }

  return false
}

async function uploadPreparedBatch(
  folderId: number,
  files: File[],
  variantId: number | undefined,
  uploadSessionId: string,
  onProgress?: (progress: BatchUploadProgress) => void,
  signal?: AbortSignal,
): Promise<BatchUploadResult> {
  const descriptors = await Promise.all(files.map((file) => describeFile(file)))
  const preparedUploads = await prepareSignUploads(
    folderId,
    descriptors.map(({ metadata }) => metadata),
    variantId,
    uploadSessionId,
  )
  const fileLoadedBytes = Array.from({ length: files.length }, () => 0)
  const successfulIntentIds = new Set<string>()
  const failedFiles: File[] = []
  let nextIndex = 0

  emitProgress(fileLoadedBytes, files, onProgress)

  async function worker() {
    while (nextIndex < descriptors.length) {
      const fileIndex = nextIndex
      nextIndex += 1

      const descriptor = descriptors[fileIndex]!
      const preparedUpload = preparedUploads[fileIndex]!
      const didUpload = await uploadPreparedFile(
        preparedUpload,
        descriptor.file,
        fileIndex,
        fileLoadedBytes,
        files,
        onProgress,
        signal,
      )

      if (didUpload) {
        successfulIntentIds.add(preparedUpload.id)
        continue
      }

      failedFiles.push(descriptor.file)
    }
  }

  await Promise.all(Array.from({ length: Math.min(4, descriptors.length) }, () => worker()))

  const signs =
    successfulIntentIds.size > 0
      ? await completeSignUploads(
          folderId,
          Array.from(successfulIntentIds),
          variantId,
          uploadSessionId,
        )
      : []

  return { signs, failedFiles }
}

async function uploadSinglePreparedFile(
  folderId: number,
  file: File,
  variantId: number | undefined,
  uploadSessionId: string,
  signal?: AbortSignal,
): Promise<BatchUploadResult> {
  try {
    return await uploadPreparedBatch(
      folderId,
      [file],
      variantId,
      uploadSessionId,
      undefined,
      signal,
    )
  } catch {
    return { signs: [], failedFiles: [file] }
  }
}

export function generateUploadSessionId(): string {
  if (globalThis.crypto?.randomUUID) {
    return globalThis.crypto.randomUUID()
  }

  return `upload-${Date.now()}-${Math.random().toString(16).slice(2)}`
}

export async function createSignsInBatches(
  folderId: number,
  files: File[],
  variantId: number | undefined,
  uploadSessionId: string,
  batchSize: number,
  onProgress?: (progress: BatchUploadProgress) => void,
  signal?: AbortSignal,
): Promise<BatchUploadResult> {
  const batches = chunk(files, batchSize)
  const signs: Sign[] = []
  const failedFiles: File[] = []
  const fileLoadedBytes = Array.from({ length: files.length }, () => 0)

  emitProgress(fileLoadedBytes, files, onProgress)

  let batchOffset = 0

  for (const batch of batches) {
    if (signal?.aborted) {
      break
    }

    try {
      const { signs: createdSigns, failedFiles: batchFailedFiles } = await uploadPreparedBatch(
        folderId,
        batch,
        variantId,
        uploadSessionId,
        (progress) => {
          const totalBatchBytes = getTotalBytes(batch)
          let remainingBytes = progress.uploadedBytes

          for (let index = 0; index < batch.length; index++) {
            const file = batch[index]!
            const uploaded = totalBatchBytes === 0 ? 0 : Math.min(file.size, remainingBytes)

            fileLoadedBytes[batchOffset + index] = uploaded
            remainingBytes = Math.max(0, remainingBytes - file.size)
          }

          emitProgress(fileLoadedBytes, files, onProgress)
        },
        signal,
      )

      signs.push(...createdSigns)
      failedFiles.push(...batchFailedFiles)
    } catch (exception) {
      if (axios.isCancel(exception) || signal?.aborted) {
        break
      }

      for (let index = 0; index < batch.length; index++) {
        const file = batch[index]!

        if (signal?.aborted) {
          break
        }

        const result = await uploadSinglePreparedFile(
          folderId,
          file,
          variantId,
          uploadSessionId,
          signal,
        )

        if (result.signs.length > 0) {
          fileLoadedBytes[batchOffset + index] = file.size
          emitProgress(fileLoadedBytes, files, onProgress)
          signs.push(...result.signs)
          continue
        }

        failedFiles.push(file)
      }
    }

    batchOffset += batch.length
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
