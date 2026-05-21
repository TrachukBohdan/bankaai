<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { storeToRefs } from 'pinia'
import Card from 'primevue/card'
import Message from 'primevue/message'
import ProgressSpinner from 'primevue/progressspinner'
import DatePicker from 'primevue/datepicker'
import RateFilters from '@/components/RateFilters.vue'
import RateHistoryChart from '@/components/RateHistoryChart.vue'
import { formatLocalDate } from '@/lib/queryParams'
import { useRatesStore } from '@/stores/rates'
import type { StatsFilters } from '@/stores/rates'

const store = useRatesStore()
const { statistics, loading, error } = storeToRefs(store)
const filters = ref<StatsFilters>({})
const fromDate = ref<Date | null>(null)
const toDate = ref<Date | null>(null)

function buildParams(extra: StatsFilters = {}): StatsFilters {
  const merged: StatsFilters = {}

  const bank = 'bank' in extra ? extra.bank : filters.value.bank
  const currency = 'currency' in extra ? extra.currency : filters.value.currency
  if (bank) {
    merged.bank = bank
  }
  if (currency) {
    merged.currency = currency
  }
  if (fromDate.value) {
    merged.from = formatLocalDate(fromDate.value)
  }
  if (toDate.value) {
    merged.to = formatLocalDate(toDate.value)
  }
  return merged
}

function apply(f: StatsFilters = {}): void {
  const merged = buildParams(f)
  filters.value = merged
  void store.fetchStatistics(merged)
}

function onPeriodChange(): void {
  apply()
}

onMounted(() => {
  const today = new Date()
  toDate.value = today
  fromDate.value = new Date(today.getFullYear(), today.getMonth() - 3, today.getDate())
  apply()
})
</script>

<template>
  <section class="page">
    <h1 class="page-title">Rate statistics</h1>
    <RateFilters @change="apply" />

    <Card class="mb-3">
      <template #title>Period</template>
      <template #content>
        <div class="gap-form-row">
          <div class="field">
            <label for="stats-from">From</label>
            <DatePicker
              id="stats-from"
              v-model="fromDate"
              date-format="yy-mm-dd"
              show-icon
              class="w-full"
              @update:model-value="onPeriodChange"
            />
          </div>
          <div class="field">
            <label for="stats-to">To</label>
            <DatePicker
              id="stats-to"
              v-model="toDate"
              date-format="yy-mm-dd"
              show-icon
              class="w-full"
              @update:model-value="onPeriodChange"
            />
          </div>
        </div>
      </template>
    </Card>

    <div v-if="loading" class="flex justify-center p-4">
      <ProgressSpinner />
    </div>
    <Message v-if="error" severity="error" :closable="false" class="mb-3">{{ error }}</Message>

    <template v-if="statistics && !loading">
      <Message severity="info" :closable="false" class="mb-3">
        Period: {{ statistics.period.from }} → {{ statistics.period.to }}
        ({{ statistics.summary.samples }} samples)
      </Message>
      <div class="grid cols-2 mb-3">
        <Card>
          <template #title>Buy</template>
          <template #content>
            <p>Min: <strong>{{ statistics.summary.buy.min ?? '—' }}</strong></p>
            <p>Max: <strong>{{ statistics.summary.buy.max ?? '—' }}</strong></p>
            <p>Avg: <strong>{{ statistics.summary.buy.avg ?? '—' }}</strong></p>
          </template>
        </Card>
        <Card>
          <template #title>Sell</template>
          <template #content>
            <p>Min: <strong>{{ statistics.summary.sell.min ?? '—' }}</strong></p>
            <p>Max: <strong>{{ statistics.summary.sell.max ?? '—' }}</strong></p>
            <p>Avg: <strong>{{ statistics.summary.sell.avg ?? '—' }}</strong></p>
          </template>
        </Card>
      </div>
      <Card>
        <template #title>Chart</template>
        <template #content>
          <RateHistoryChart :key="`${statistics.period.from}-${statistics.period.to}-${statistics.summary.samples}`" :statistics="statistics" />
        </template>
      </Card>
    </template>
  </section>
</template>

<style scoped>
.page-title {
  font-size: 1.75rem;
  font-weight: 700;
  margin-bottom: 1rem;
}
.field label {
  display: block;
  margin-bottom: 0.35rem;
  font-weight: 500;
  font-size: 0.875rem;
}
.mb-3 {
  margin-bottom: 1rem;
}
</style>
