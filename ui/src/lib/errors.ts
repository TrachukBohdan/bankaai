import { AxiosError } from 'axios'

export function describeError(err: unknown): string {
  if (err instanceof AxiosError) {
    const status = err.response?.status
    const data = err.response?.data as { message?: string; errors?: Record<string, string[]> } | undefined
    const first = data?.errors ? Object.values(data.errors).flat()[0] : undefined
    return first ?? data?.message ?? (status ? `HTTP ${status}: ${err.message}` : `Network error: ${err.message}`)
  }
  if (err instanceof Error) return err.message
  return 'Unknown error'
}
