<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { storeToRefs } from 'pinia'
import RateFilters from '@/components/RateFilters.vue'
import RatesTable from '@/components/RatesTable.vue'
import { useRatesStore } from '@/stores/rates'
import type { RateFilters as Filters } from '@/stores/rates'

const store = useRatesStore()
const { rates, nbu, loading, error } = storeToRefs(store)
const filters = ref<Filters>({})

function apply(f: Filters): void {
  filters.value = f
  void store.fetchRates(f)
  void store.fetchNbu(f)
}

onMounted(() => {
  void store.fetchRates()
  void store.fetchNbu()
})
</script>

<template>
  <section class="page">
    <h1>Exchange rates</h1>
    <RateFilters @change="apply" />
    <p v-if="error" class="error">{{ error }}</p>

    <h2>Bank rates (MinFin cash)</h2>
    <RatesTable :rates="rates" :loading="loading" />

    <h2>NBU official &amp; bank average</h2>
    <div v-if="nbu" class="nbu-grid">
      <section class="card">
        <h3>NBU</h3>
        <RatesTable :rates="nbu.nbu" />
      </section>
      <section class="card">
        <h3>Average across tracked banks</h3>
        <div v-if="nbu.averages.length" class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Currency</th>
                <th>Avg buy</th>
                <th>Avg sell</th>
                <th>Banks</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="a in nbu.averages" :key="a.currency.code">
                <td><strong>{{ a.currency.code }}</strong></td>
                <td>{{ a.avg_buy?.toFixed(4) ?? '—' }}</td>
                <td>{{ a.avg_sell?.toFixed(4) ?? '—' }}</td>
                <td>{{ a.banks }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <p v-else class="muted">No average data available.</p>
      </section>
    </div>
  </section>
</template>

<style scoped>
.nbu-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.25rem;
}
@media (max-width: 768px) {
  .nbu-grid {
    grid-template-columns: 1fr;
  }
}
</style>
