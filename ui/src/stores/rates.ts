import { ref } from 'vue'
import { defineStore } from 'pinia'
import api from '@/lib/api'
import { cleanQueryParams } from '@/lib/queryParams'
import type { ExchangeRate, NbuResponse, StatisticsResponse } from '@/types/api'

export interface RateFilters {
  bank?: string
  currency?: string
}

export interface StatsFilters extends RateFilters {
  from?: string
  to?: string
}

export const useRatesStore = defineStore('rates', () => {
  const rates = ref<ExchangeRate[]>([])
  const nbu = ref<NbuResponse | null>(null)
  const statistics = ref<StatisticsResponse | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  async function fetchRates(filters: RateFilters = {}): Promise<void> {
    loading.value = true
    error.value = null
    try {
      const { data } = await api.get<{ data: ExchangeRate[] }>('/rates', {
        params: cleanQueryParams(filters),
      })
      rates.value = data.data
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to load rates'
    } finally {
      loading.value = false
    }
  }

  async function fetchNbu(filters: RateFilters = {}): Promise<void> {
    loading.value = true
    error.value = null
    try {
      const { data } = await api.get<NbuResponse>('/rates/nbu', {
        params: cleanQueryParams(filters),
      })
      nbu.value = data
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to load NBU rates'
    } finally {
      loading.value = false
    }
  }

  async function fetchStatistics(filters: StatsFilters = {}): Promise<void> {
    loading.value = true
    error.value = null
    try {
      const { data } = await api.get<StatisticsResponse>('/rates/statistics', {
        params: cleanQueryParams(filters),
      })
      statistics.value = data
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to load statistics'
    } finally {
      loading.value = false
    }
  }

  return { rates, nbu, statistics, loading, error, fetchRates, fetchNbu, fetchStatistics }
})
