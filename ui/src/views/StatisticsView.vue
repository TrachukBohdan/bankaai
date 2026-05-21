<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { storeToRefs } from 'pinia'
import RateFilters from '@/components/RateFilters.vue'
import RateHistoryChart from '@/components/RateHistoryChart.vue'
import { useRatesStore } from '@/stores/rates'
import type { StatsFilters } from '@/stores/rates'

const store = useRatesStore()
const { statistics, loading, error } = storeToRefs(store)
const filters = ref<StatsFilters>({})

function apply(f: StatsFilters): void {
  filters.value = { ...filters.value, ...f }
  void store.fetchStatistics(filters.value)
}

onMounted(() => {
  void store.fetchStatistics()
})
</script>

<template>
  <section class="page">
    <h1>Rate statistics</h1>
    <RateFilters @change="apply" />
    <div class="dates card">
      <label>
        From
        <input v-model="filters.from" type="date" @change="apply(filters)" />
      </label>
      <label>
        To
        <input v-model="filters.to" type="date" @change="apply(filters)" />
      </label>
    </div>
    <p v-if="loading" class="muted">Loading…</p>
    <p v-if="error" class="error">{{ error }}</p>

    <template v-if="statistics">
      <p class="muted">
        Period: {{ statistics.period.from }} → {{ statistics.period.to }}
        ({{ statistics.summary.samples }} samples)
      </p>
      <div class="summary">
        <section class="card">
          <h3>Buy</h3>
          <p>Min: <strong>{{ statistics.summary.buy.min ?? '—' }}</strong></p>
          <p>Max: <strong>{{ statistics.summary.buy.max ?? '—' }}</strong></p>
          <p>Avg: <strong>{{ statistics.summary.buy.avg ?? '—' }}</strong></p>
        </section>
        <section class="card">
          <h3>Sell</h3>
          <p>Min: <strong>{{ statistics.summary.sell.min ?? '—' }}</strong></p>
          <p>Max: <strong>{{ statistics.summary.sell.max ?? '—' }}</strong></p>
          <p>Avg: <strong>{{ statistics.summary.sell.avg ?? '—' }}</strong></p>
        </section>
      </div>
      <section class="card chart-section">
        <RateHistoryChart :statistics="statistics" />
      </section>
    </template>
  </section>
</template>

<style scoped>
.dates {
  display: flex;
  gap: 1.25rem;
  margin-bottom: 1.25rem;
  flex-wrap: wrap;
}
.dates label {
  flex: 1;
  min-width: 160px;
}
.summary {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
  margin: 1rem 0;
}
.chart-section {
  margin-top: 1rem;
}
@media (max-width: 640px) {
  .summary {
    grid-template-columns: 1fr;
  }
}
</style>
