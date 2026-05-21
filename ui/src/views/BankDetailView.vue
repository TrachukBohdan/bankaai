<script setup lang="ts">
import { onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { storeToRefs } from 'pinia'
import RatesTable from '@/components/RatesTable.vue'
import { useBanksStore } from '@/stores/banks'

const route = useRoute()
const store = useBanksStore()
const { current, loading, error } = storeToRefs(store)

function load(): void {
  const slug = route.params.slug as string
  void store.fetchOne(slug)
}

onMounted(load)
watch(() => route.params.slug, load)
</script>

<template>
  <section class="page">
    <p v-if="loading" class="muted">Loading…</p>
    <p v-if="error" class="error">{{ error }}</p>
    <template v-if="current">
      <header class="header card">
        <img v-if="current.logo_url" :src="current.logo_url" :alt="current.name" class="logo" />
        <div>
          <h1>{{ current.name }}</h1>
          <p v-if="current.rating" class="rating">★ {{ current.rating }}</p>
          <p v-if="current.legal_name" class="muted">{{ current.legal_name }}</p>
          <p v-if="current.phone">📞 {{ current.phone }}</p>
          <p v-if="current.legal_address">📍 {{ current.legal_address }}</p>
          <a v-if="current.website" :href="current.website" target="_blank" rel="noopener">Website</a>
        </div>
      </header>

      <h2>Current rates (cash)</h2>
      <RatesTable :rates="current.rates ?? []" />

      <h2>Branches (sample)</h2>
      <ul v-if="current.branches?.length" class="branch-list card">
        <li v-for="b in current.branches" :key="b.id">
          <strong>{{ b.name }}</strong>
          <span class="muted">{{ b.address }}</span>
        </li>
      </ul>
      <p v-else class="muted">No branches loaded yet.</p>
    </template>
  </section>
</template>

<style scoped>
.header {
  display: flex;
  gap: 1.5rem;
  margin-bottom: 1.5rem;
  align-items: flex-start;
}
.logo {
  width: 96px;
  height: 96px;
  object-fit: contain;
  flex-shrink: 0;
}
.rating {
  color: #b45309;
  font-weight: 600;
}
.branch-list {
  list-style: none;
  padding: 0;
}
.branch-list li {
  padding: 0.65rem 0;
  border-bottom: 1px solid var(--c-border);
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}
.branch-list li:last-child {
  border-bottom: none;
}
</style>
