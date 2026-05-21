import { ref } from 'vue'
import { defineStore } from 'pinia'
import api from '@/lib/api'
import type { BankDetail, BankSummary } from '@/types/api'

export const useBanksStore = defineStore('banks', () => {
  const list = ref<BankSummary[]>([])
  const current = ref<BankDetail | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  async function fetchList(): Promise<void> {
    loading.value = true
    error.value = null
    try {
      const { data } = await api.get<{ data: BankSummary[] }>('/banks')
      list.value = data.data
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to load banks'
    } finally {
      loading.value = false
    }
  }

  async function fetchOne(slug: string): Promise<void> {
    loading.value = true
    error.value = null
    current.value = null
    try {
      const { data } = await api.get<{ data: BankDetail }>(`/banks/${slug}`)
      current.value = data.data
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to load bank'
    } finally {
      loading.value = false
    }
  }

  return { list, current, loading, error, fetchList, fetchOne }
})
