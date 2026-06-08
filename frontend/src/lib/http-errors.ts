import type { AxiosError } from 'axios'

export function getErrorStatus(error: unknown): number | undefined {
  const axiosError = error as AxiosError | undefined

  return axiosError?.response?.status
}

export function isNotFoundError(error: unknown): boolean {
  return getErrorStatus(error) === 404
}
