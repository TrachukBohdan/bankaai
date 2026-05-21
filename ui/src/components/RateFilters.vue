<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import Card from 'primevue/card'
import Select from 'primevue/select'
import { useCurrenciesStore } from '@/stores/currencies'
import { useBanksStore } from '@/stores/banks'
import { storeToRefs } from 'pinia'

const emit = defineEmits<{
  change: [filters: { bank?: string; currency?: string }]
}>()

const bank = ref<string | null>(null)
const currency = ref<string | null>(null)

const currenciesStore = useCurrenciesStore()
const banksStore = useBanksStore()
const { items: currencies } = storeToRefs(currenciesStore)
const { list: banks } = storeToRefs(banksStore)

const bankOptions = computed(() => [
  { label: 'All banks', value: null },
  ...banks.value.map((b) => ({ label: b.name, value: b.slug })),
])

const currencyOptions = computed(() => [
  { label: 'All currencies', value: null },
  ...currencies.value.map((c) => ({ label: `${c.code} — ${c.name}`, value: c.code })),
])

onMounted(() => {
  void currenciesStore.fetchAll()
  void banksStore.fetchList()
})

watch([bank, currency], () => {
  emit('change', {
    bank: bank.value ?? undefined,
    currency: currency.value ?? undefined,
  })
})
</script>

<template>
  <Card class="mb-3">
    <template #title>Filters</template>
    <template #content>
      <div class="gap-form-row">
        <div class="field">
          <label for="filter-bank">Bank</label>
          <Select
            id="filter-bank"
            v-model="bank"
            :options="bankOptions"
            option-label="label"
            option-value="value"
            placeholder="All banks"
            class="w-full"
          />
        </div>
        <div class="field">
          <label for="filter-currency">Currency</label>
          <Select
            id="filter-currency"
            v-model="currency"
            :options="currencyOptions"
            option-label="label"
            option-value="value"
            placeholder="All currencies"
            class="w-full"
          />
        </div>
      </div>
    </template>
  </Card>
</template>

<style scoped>
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
