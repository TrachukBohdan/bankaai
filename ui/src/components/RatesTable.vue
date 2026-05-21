<script setup lang="ts">
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import type { ExchangeRate } from '@/types/api'

defineProps<{ rates: ExchangeRate[]; loading?: boolean }>()
</script>

<template>
  <DataTable
    :value="rates"
    :loading="loading"
    striped-rows
    paginator
    :rows="10"
    :rows-per-page-options="[10, 25, 50]"
    data-key="id"
    size="small"
    class="rates-table"
  >
    <template #empty>
      <span>No rates found for the selected filters.</span>
    </template>
    <Column header="Bank">
      <template #body="{ data }">
        {{ data.bank?.name ?? '—' }}
      </template>
    </Column>
    <Column field="currency.code" header="Currency">
      <template #body="{ data }">
        <strong>{{ data.currency?.code }}</strong>
      </template>
    </Column>
    <Column header="Buy">
      <template #body="{ data }">
        {{ data.buy?.toFixed(4) ?? '—' }}
      </template>
    </Column>
    <Column header="Sell">
      <template #body="{ data }">
        {{ data.sell?.toFixed(4) ?? '—' }}
      </template>
    </Column>
    <Column header="Updated">
      <template #body="{ data }">
        {{ new Date(data.rate_at).toLocaleString() }}
      </template>
    </Column>
  </DataTable>
</template>
