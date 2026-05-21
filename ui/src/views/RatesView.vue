<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { storeToRefs } from 'pinia'
import Message from 'primevue/message'
import Card from 'primevue/card'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import RateFilters from '@/components/RateFilters.vue'
import RatesTable from '@/components/RatesTable.vue'
import { useRatesStore } from '@/stores/rates'
import type { RateFilters as Filters } from '@/stores/rates'

const store = useRatesStore()
const { rates, nbu, loading, error } = storeToRefs(store)
const filters = ref<Filters>({})

const averages = computed(() => nbu.value?.averages ?? [])

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
    <h1 class="page-title">Exchange rates</h1>
    <RateFilters @change="apply" />
    <Message v-if="error" severity="error" :closable="false" class="mb-3">{{ error }}</Message>

    <h2 class="section-title">Bank rates (MinFin cash)</h2>
    <RatesTable :rates="rates" :loading="loading" class="mb-4" />

    <h2 class="section-title">NBU official &amp; bank average</h2>
    <div v-if="nbu" class="grid cols-2">
      <Card>
        <template #title>NBU</template>
        <template #content>
          <RatesTable :rates="nbu.nbu" />
        </template>
      </Card>
      <Card>
        <template #title>Average across tracked banks</template>
        <template #content>
          <DataTable
            v-if="averages.length"
            :value="averages"
            size="small"
            striped-rows
            data-key="currency.code"
          >
            <Column header="Currency">
              <template #body="{ data }">
                <strong>{{ data.currency.code }}</strong>
              </template>
            </Column>
            <Column header="Avg buy">
              <template #body="{ data }">
                {{ data.avg_buy?.toFixed(4) ?? '—' }}
              </template>
            </Column>
            <Column header="Avg sell">
              <template #body="{ data }">
                {{ data.avg_sell?.toFixed(4) ?? '—' }}
              </template>
            </Column>
            <Column field="banks" header="Banks" />
          </DataTable>
          <Message v-else severity="info" :closable="false">No average data available.</Message>
        </template>
      </Card>
    </div>
  </section>
</template>

<style scoped>
.page-title {
  font-size: 1.75rem;
  font-weight: 700;
  margin-bottom: 1rem;
}
.section-title {
  font-size: 1.15rem;
  font-weight: 600;
  margin: 0 0 0.75rem;
}
.mb-3 {
  margin-bottom: 1rem;
}
.mb-4 {
  margin-bottom: 1.5rem;
}
</style>
