import api from '@/lib/api'

/** Fetch Sanctum CSRF cookie before any state-changing request. */
export async function ensureCsrfCookie(): Promise<void> {
  const base = api.defaults.baseURL?.replace(/\/api\/?$/, '') ?? ''
  await api.get('/sanctum/csrf-cookie', { baseURL: base })
}
