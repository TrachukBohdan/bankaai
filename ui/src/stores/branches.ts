import { ref } from 'vue'
import { defineStore } from 'pinia'
import api from '@/lib/api'
import type { Branch } from '@/types/api'

export const useBranchesStore = defineStore('branches', () => {
  const nearest = ref<Branch[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  async function fetchNearest(lat: number, lng: number, limit = 10, bank?: string): Promise<void> {
    loading.value = true
    error.value = null
    try {
      const { data } = await api.get<{ data: Branch[] }>('/branches/nearest', {
        params: { lat, lng, limit, bank },
      })
      nearest.value = data.data
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to load branches'
    } finally {
      loading.value = false
    }
  }

  return { nearest, loading, error, fetchNearest }
})
