import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import { AxiosError } from 'axios'
import api from '@/lib/api'
import { ensureCsrfCookie } from '@/lib/auth'
import type { Subscription, User } from '@/types/api'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const subscriptions = ref<Subscription[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  const isAuthenticated = computed(() => user.value !== null)

  async function fetchMe(): Promise<void> {
    try {
      const { data } = await api.get<{ data: User }>('/me')
      user.value = data.data
    } catch {
      user.value = null
    }
  }

  async function register(payload: {
    name: string
    email: string
    password: string
    password_confirmation: string
  }): Promise<boolean> {
    loading.value = true
    error.value = null
    try {
      await ensureCsrfCookie()
      const { data } = await api.post<{ data: User }>('/auth/register', payload)
      user.value = data.data
      return true
    } catch (e) {
      error.value = extractError(e)
      return false
    } finally {
      loading.value = false
    }
  }

  async function login(email: string, password: string): Promise<boolean> {
    loading.value = true
    error.value = null
    try {
      await ensureCsrfCookie()
      const { data } = await api.post<{ data: User }>('/auth/login', { email, password })
      user.value = data.data
      return true
    } catch (e) {
      error.value = extractError(e)
      return false
    } finally {
      loading.value = false
    }
  }

  async function logout(): Promise<void> {
    await ensureCsrfCookie()
    await api.post('/auth/logout')
    user.value = null
    subscriptions.value = []
  }

  async function updateProfile(payload: Partial<User> & { password?: string; password_confirmation?: string }): Promise<boolean> {
    loading.value = true
    error.value = null
    try {
      await ensureCsrfCookie()
      const { data } = await api.put<{ data: User }>('/me', payload)
      user.value = data.data
      return true
    } catch (e) {
      error.value = extractError(e)
      return false
    } finally {
      loading.value = false
    }
  }

  async function fetchSubscriptions(): Promise<void> {
    const { data } = await api.get<{ data: Subscription[] }>('/me/subscriptions')
    subscriptions.value = data.data
  }

  async function addSubscription(payload: {
    bank_slug?: string
    currency_code?: string
    threshold_pct?: number
  }): Promise<boolean> {
    try {
      await ensureCsrfCookie()
      await api.post('/me/subscriptions', payload)
      await fetchSubscriptions()
      return true
    } catch (e) {
      error.value = extractError(e)
      return false
    }
  }

  async function removeSubscription(id: number): Promise<void> {
    await ensureCsrfCookie()
    await api.delete(`/me/subscriptions/${id}`)
    subscriptions.value = subscriptions.value.filter((s) => s.id !== id)
  }

  return {
    user,
    subscriptions,
    loading,
    error,
    isAuthenticated,
    fetchMe,
    register,
    login,
    logout,
    updateProfile,
    fetchSubscriptions,
    addSubscription,
    removeSubscription,
  }
})

function extractError(e: unknown): string {
  if (e instanceof AxiosError) {
    const msg = e.response?.data?.message
    if (typeof msg === 'string') return msg
    const errors = e.response?.data?.errors as Record<string, string[]> | undefined
    if (errors) return Object.values(errors).flat().join(' ')
  }
  return e instanceof Error ? e.message : 'Request failed'
}
