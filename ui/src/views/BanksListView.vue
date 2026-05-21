<script setup lang="ts">
import { onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import Message from 'primevue/message'
import ProgressSpinner from 'primevue/progressspinner'
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
    <h1 class="page-title">Banks</h1>
    <p class="page-subtitle">Ukrainian banks tracked by BankaAi with live exchange rates and branch data.</p>

    <div v-if="loading && !list.length" class="flex justify-center p-6">
      <ProgressSpinner />
    </div>
    <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>

    <div v-if="list.length" class="grid cols-3">
      <BankCard v-for="bank in list" :key="bank.slug" :bank="bank" />
    </div>
  </section>
</template>

<style scoped>
.page-title {
  font-size: 1.75rem;
  font-weight: 700;
  margin-bottom: 0.5rem;
}
.page-subtitle {
  color: var(--p-text-muted-color);
  margin-bottom: 1.5rem;
}
.flex {
  display: flex;
}
.justify-center {
  justify-content: center;
}
.p-6 {
  padding: 2rem;
}
</style>
