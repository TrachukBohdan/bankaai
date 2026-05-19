import { ref } from 'vue'
import { defineStore } from 'pinia'
import { AxiosError } from 'axios'
import api from '@/lib/api'

export interface ApiStatus {
  service: string
  status: 'ok' | 'degraded' | string
  app: {
    name: string
    env: string
    url: string
  }
  versions: {
    laravel: string
    php: string
  }
  host: string | null
  server_time: string
  database: {
    connection: string
    connected: boolean
    error: string | null
  }
}

export const useStatusStore = defineStore('status', () => {
  const data = ref<ApiStatus | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  async function fetchStatus(): Promise<void> {
    loading.value = true
    error.value = null

    try {
      const response = await api.get<ApiStatus>('/status')
      data.value = response.data
    } catch (err) {
      data.value = null
      if (err instanceof AxiosError) {
        error.value = err.response
          ? `HTTP ${err.response.status} from ${err.config?.url ?? 'API'}: ${err.message}`
          : `Network error: ${err.message}`
      } else if (err instanceof Error) {
        error.value = err.message
      } else {
        error.value = 'Unknown error while contacting the API.'
      }
    } finally {
      loading.value = false
    }
  }

  return { data, loading, error, fetchStatus }
})
