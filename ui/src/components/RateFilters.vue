<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { useCurrenciesStore } from '@/stores/currencies'
import { useBanksStore } from '@/stores/banks'
import { storeToRefs } from 'pinia'

const emit = defineEmits<{
  change: [filters: { bank?: string; currency?: string }]
}>()

const bank = ref('')
const currency = ref('')

const currenciesStore = useCurrenciesStore()
const banksStore = useBanksStore()
const { items: currencies } = storeToRefs(currenciesStore)
const { list: banks } = storeToRefs(banksStore)

onMounted(() => {
  void currenciesStore.fetchAll()
  void banksStore.fetchList()
})

watch([bank, currency], () => {
  emit('change', {
    bank: bank.value || undefined,
    currency: currency.value || undefined,
  })
})
</script>

<template>
  <form class="filters card" @submit.prevent>
    <label>
      Bank
      <select v-model="bank">
        <option value="">All banks</option>
        <option v-for="b in banks" :key="b.slug" :value="b.slug">{{ b.name }}</option>
      </select>
    </label>
    <label>
      Currency
      <select v-model="currency">
        <option value="">All currencies</option>
        <option v-for="c in currencies" :key="c.code" :value="c.code">
          {{ c.code }} — {{ c.name }}
        </option>
      </select>
    </label>
  </form>
</template>

<style scoped>
.filters {
  display: flex;
  flex-wrap: wrap;
  gap: 1.25rem;
  margin-bottom: 1.25rem;
}
label {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  min-width: 200px;
  flex: 1;
}
select {
  min-width: 0;
}
</style>
