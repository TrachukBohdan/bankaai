export interface Currency {
  id: number
  code: string
  name: string
  symbol: string | null
  iso_numeric: number | null
}

export interface BankSummary {
  id: number
  slug: string
  name: string
  logo_url: string | null
}

export interface Bank {
  id: number
  slug: string
  name: string
  legal_name: string | null
  description: string | null
  logo_url: string | null
  website: string | null
  phone: string | null
  email: string | null
  legal_address: string | null
  license_number: string | null
  license_date: string | null
  rating: number | null
  is_active: boolean
  rates?: Rate[]
  branches?: Branch[]
}

export interface Rate {
  id: number
  bank: BankSummary | null
  currency: { id: number; code: string; name: string } | null
  bank_id: number | null
  currency_id: number
  market: 'cash' | 'card' | 'official' | string
  source: 'minfin' | 'nbu' | string
  buy: number | null
  sell: number | null
  rate_at: string | null
  fetched_at: string | null
}

export interface Branch {
  id: number
  bank_id: number
  bank?: BankSummary
  name: string
  city: string | null
  address: string
  phone: string | null
  lat: number
  lng: number
  is_primary: boolean
  distance_m?: number
}

export interface RateAverage {
  currency: { id: number; code: string; name: string } | null
  avg_buy: number | null
  avg_sell: number | null
  samples: number
}

export interface RateChange {
  id: number
  bank: { id: number; slug: string; name: string } | null
  currency: { id: number; code: string } | null
  bank_id: number | null
  currency_id: number
  market: string
  source: string
  side: 'buy' | 'sell'
  previous_value: number
  new_value: number
  delta_pct: number
  threshold_pct: number
  observed_at: string
}

export interface StatisticsResponse {
  range: { from: string; to: string }
  summary: {
    samples: number
    buy: { min: number | null; max: number | null; avg: number | null }
    sell: { min: number | null; max: number | null; avg: number | null }
  }
  series: { day: string; avg_buy: number | null; avg_sell: number | null }[]
}

export interface User {
  id: number
  name: string
  email: string
  notifications_enabled: boolean
  created_at: string
}

export interface Subscription {
  id: number
  bank_id: number | null
  currency_id: number | null
  threshold_pct: number
  bank: { id: number; slug: string; name: string } | null
  currency: { id: number; code: string; name: string } | null
}
