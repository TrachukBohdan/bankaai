<script setup lang="ts">
import type { ExchangeRate } from '@/types/api'

defineProps<{ rates: ExchangeRate[]; loading?: boolean }>()
</script>

<template>
  <p v-if="loading" class="muted">Loading rates…</p>
  <p v-else-if="rates.length === 0" class="muted">No rates found for the selected filters.</p>
  <div v-else class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Bank</th>
          <th>Currency</th>
          <th>Buy</th>
          <th>Sell</th>
          <th>Updated</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="r in rates" :key="r.id">
          <td>{{ r.bank?.name ?? '—' }}</td>
          <td><strong>{{ r.currency?.code }}</strong></td>
          <td>{{ r.buy?.toFixed(4) ?? '—' }}</td>
          <td>{{ r.sell?.toFixed(4) ?? '—' }}</td>
          <td class="muted">{{ new Date(r.rate_at).toLocaleString() }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
