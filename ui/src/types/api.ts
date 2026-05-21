export interface Currency {
  id: number
  code: string
  name: string
  symbol: string | null
}

export interface BankSummary {
  id: number
  slug: string
  name: string
  logo_url: string | null
  rating: number | null
  phone: string | null
  email: string | null
}

export interface BankDetail extends BankSummary {
  legal_name: string | null
  description: string | null
  website: string | null
  legal_address: string | null
  license_number: string | null
  license_date: string | null
  rates?: ExchangeRate[]
  branches?: Branch[]
}

export interface ExchangeRate {
  id: number
  bank?: { slug: string; name: string } | null
  currency?: { code: string; name: string }
  market: string
  source: string
  buy: number | null
  sell: number | null
  rate_at: string
}

export interface Branch {
  id: number
  bank?: { slug: string; name: string }
  name: string
  city: string | null
  address: string
  phone: string | null
  lat: number
  lng: number
  is_primary: boolean
  distance_m?: number | null
}

export interface NbuResponse {
  nbu: ExchangeRate[]
  averages: Array<{
    currency: { code: string; name: string }
    avg_buy: number | null
    avg_sell: number | null
    banks: number
  }>
}

export interface StatisticsResponse {
  period: { from: string; to: string }
  summary: {
    samples: number
    buy: { min: number | null; max: number | null; avg: number | null }
    sell: { min: number | null; max: number | null; avg: number | null }
  }
  series: Array<{ day: string; avg_buy: number | null; avg_sell: number | null }>
}

export interface User {
  id: number
  name: string
  email: string
  notifications_enabled: boolean
}

export interface Subscription {
  id: number
  bank?: { slug: string; name: string } | null
  currency?: { code: string; name: string } | null
  threshold_pct: number
  created_at: string
}

export interface Paginated<T> {
  data: T[]
  meta?: { current_page: number; last_page: number; total: number }
}
