<script setup lang="ts">
import { onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import BankCard from '@/components/BankCard.vue'
import { useBanksStore } from '@/stores/banks'

const store = useBanksStore()
const { list, loading, error } = storeToRefs(store)

onMounted(() => {
  void store.fetchList()
})
</script>

<template>
  <section class="page">
    <h1>Banks</h1>
    <p class="lead">Ukrainian banks tracked by BankaAi with live exchange rates and branch data.</p>
    <p v-if="loading" class="muted">Loading…</p>
    <p v-if="error" class="error">{{ error }}</p>
    <div class="grid cols-3">
      <BankCard v-for="bank in list" :key="bank.slug" :bank="bank" />
    </div>
  </section>
</template>
