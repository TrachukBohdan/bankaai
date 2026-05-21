/** Drop empty values so Axios does not send stale filter keys to the API. */
export function cleanQueryParams<T extends object>(params: T): Partial<T> {
  const out: Partial<T> = {}
  for (const [key, value] of Object.entries(params as Record<string, unknown>)) {
    if (value !== undefined && value !== '') {
      ;(out as Record<string, unknown>)[key] = value
    }
  }
  return out
}

/** Format a local calendar date as YYYY-MM-DD (avoids UTC shift from toISOString). */
export function formatLocalDate(d: Date): string {
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}
