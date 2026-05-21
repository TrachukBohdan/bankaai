import { ref } from 'vue'
import { defineStore } from 'pinia'
import api from '@/lib/api'
import type { Currency } from '@/types/api'

export const useCurrenciesStore = defineStore('currencies', () => {
  const items = ref<Currency[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  async function fetchAll(): Promise<void> {
    if (items.value.length > 0) return
    loading.value = true
    error.value = null
    try {
      const { data } = await api.get<{ data: Currency[] }>('/currencies')
      items.value = data.data
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to load currencies'
    } finally {
      loading.value = false
    }
  }

  return { items, loading, error, fetchAll }
})
