<script setup lang="ts">
import { ref } from 'vue'
import { storeToRefs } from 'pinia'
import NearestBranchesMap from '@/components/NearestBranchesMap.vue'
import { useBranchesStore } from '@/stores/branches'
import { getCurrentPosition } from '@/lib/geolocation'

const store = useBranchesStore()
const { nearest, loading, error } = storeToRefs(store)
const position = ref<{ lat: number; lng: number } | null>(null)
const geoError = ref<string | null>(null)

async function locate(): Promise<void> {
  geoError.value = null
  try {
    position.value = await getCurrentPosition()
    await store.fetchNearest(position.value.lat, position.value.lng, 10)
  } catch (e) {
    geoError.value = e instanceof Error ? e.message : 'Geolocation failed'
  }
}
</script>

<template>
  <section class="page">
    <h1>Nearest branches</h1>
    <p class="lead">Uses your browser location to find the closest bank branches.</p>
    <button type="button" :disabled="loading" @click="locate">
      {{ loading ? 'Searching…' : 'Use my location' }}
    </button>
    <p v-if="geoError" class="error">{{ geoError }}</p>
    <p v-if="error" class="error">{{ error }}</p>

    <NearestBranchesMap v-if="nearest.length" :branches="nearest" :center="position ?? undefined" />

    <ul v-if="nearest.length" class="list card">
      <li v-for="b in nearest" :key="b.id">
        <strong>{{ b.bank?.name }}</strong> — {{ b.name }}
        <span class="muted">{{ b.address }}</span>
        <span v-if="b.distance_m" class="dist">{{ Math.round(b.distance_m) }} m</span>
      </li>
    </ul>
  </section>
</template>

<style scoped>
button {
  margin: 1rem 0;
}
.list {
  list-style: none;
  padding: 1rem;
  margin-top: 1.25rem;
}
.list li {
  padding: 0.75rem 0;
  border-bottom: 1px solid var(--c-border);
  display: flex;
  flex-wrap: wrap;
  gap: 0.35rem 0.75rem;
  color: var(--c-text);
}
.list li:last-child {
  border-bottom: none;
}
.dist {
  font-weight: 600;
  color: var(--c-accent);
}
</style>
