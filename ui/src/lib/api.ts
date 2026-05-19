import axios, { type AxiosInstance } from 'axios'

const baseURL = import.meta.env.VITE_API_URL ?? 'http://localhost:8000/api'

export const api: AxiosInstance = axios.create({
  baseURL,
  withCredentials: true,
  headers: {
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
})

export default api
