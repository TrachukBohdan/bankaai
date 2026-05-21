<script setup lang="ts">
import { onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { storeToRefs } from 'pinia'
import StatusCard from '@/components/StatusCard.vue'
import BankCard from '@/components/BankCard.vue'
import { useBanksStore } from '@/stores/banks'

const banksStore = useBanksStore()
const { list } = storeToRefs(banksStore)

onMounted(() => {
  void banksStore.fetchList()
})
</script>

<template>
  <section class="page home">
    <header class="hero card">
      <h1>BankaAi</h1>
      <p class="lead">
        Live Ukrainian bank exchange rates, NBU official rates, branch finder, and alerts.
      </p>
      <div class="actions">
        <RouterLink to="/rates" class="btn">View rates</RouterLink>
        <RouterLink to="/nearest" class="btn btn-ghost">Find branches</RouterLink>
      </div>
    </header>

    <StatusCard />

    <section v-if="list.length" class="featured">
      <h2>Featured banks</h2>
      <div class="grid cols-3">
        <BankCard v-for="bank in list.slice(0, 3)" :key="bank.slug" :bank="bank" />
      </div>
    </section>
  </section>
</template>

<style scoped>
.hero {
  text-align: center;
  margin-bottom: 1.5rem;
}
.hero h1 {
  font-size: 2.25rem;
  margin-bottom: 0.5rem;
}
.lead {
  max-width: 520px;
  margin: 0 auto 1.25rem;
}
.actions {
  display: flex;
  gap: 0.75rem;
  justify-content: center;
  flex-wrap: wrap;
}
.actions .btn {
  text-decoration: none;
}
.featured h2 {
  margin-bottom: 1rem;
}
</style>
