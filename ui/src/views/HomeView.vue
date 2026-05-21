<script setup lang="ts">
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import Card from 'primevue/card'
import Button from 'primevue/button'
import StatusCard from '@/components/StatusCard.vue'
import BankCard from '@/components/BankCard.vue'
import { useBanksStore } from '@/stores/banks'

const router = useRouter()
const banksStore = useBanksStore()
const { list } = storeToRefs(banksStore)

onMounted(() => {
  void banksStore.fetchList()
})
</script>

<template>
  <section class="page home">
    <Card class="hero mb-4">
      <template #title>
        <span class="hero-title">BankaAi</span>
      </template>
      <template #subtitle>
        Live Ukrainian bank exchange rates, NBU official rates, branch finder, and alerts.
      </template>
      <template #footer>
        <div class="hero-actions">
          <Button label="View rates" icon="pi pi-chart-line" @click="router.push('/rates')" />
          <Button
            label="Find branches"
            icon="pi pi-map-marker"
            severity="secondary"
            outlined
            @click="router.push('/nearest')"
          />
        </div>
      </template>
    </Card>

    <StatusCard class="mb-4" />

    <section v-if="list.length">
      <h2 class="section-title">Featured banks</h2>
      <div class="grid cols-3">
        <BankCard v-for="bank in list.slice(0, 3)" :key="bank.slug" :bank="bank" />
      </div>
    </section>
  </section>
</template>

<style scoped>
.hero-title {
  font-size: 1.75rem;
}
.hero-actions {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
  justify-content: center;
}
.hero :deep(.p-card-body) {
  text-align: center;
}
.section-title {
  margin-bottom: 1rem;
  font-size: 1.25rem;
  font-weight: 600;
}
.mb-4 {
  margin-bottom: 1.5rem;
}
</style>
