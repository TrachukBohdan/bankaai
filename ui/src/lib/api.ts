import axios, { type AxiosInstance } from 'axios'

const baseURL = import.meta.env.VITE_API_URL ?? 'http://localhost:8080/api'

// Sanctum SPA mode requires a separate /sanctum/csrf-cookie request that lives
// at the app root, not under /api. We derive that root from VITE_API_URL.
const sanctumURL = baseURL.replace(/\/api\/?$/, '') || ''

/**
 * Axios instance shared by every Pinia store. We rely on:
 *   - withCredentials: true so the laravel_session + XSRF-TOKEN cookies are sent
 *   - Accept: application/json so Laravel returns JSON (and 422 on validation)
 *   - axios's xsrfCookieName/xsrfHeaderName defaults match Sanctum's cookie name
 */
export const api: AxiosInstance = axios.create({
  baseURL,
  withCredentials: true,
  xsrfCookieName: 'XSRF-TOKEN',
  xsrfHeaderName: 'X-XSRF-TOKEN',
  headers: {
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
})

/**
 * Call before any state-mutating request when there is no XSRF cookie yet.
 * Idempotent and safe to call on app boot.
 */
export async function ensureCsrfCookie(): Promise<void> {
  await axios.get(`${sanctumURL}/sanctum/csrf-cookie`, { withCredentials: true })
}

export default api
