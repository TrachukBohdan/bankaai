<script setup lang="ts">
import { ref } from 'vue'
import { storeToRefs } from 'pinia'
import Button from 'primevue/button'
import Message from 'primevue/message'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
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
    <h1 class="page-title">Nearest branches</h1>
    <p class="page-subtitle">Uses your browser location to find the closest bank branches.</p>

    <Button
      label="Use my location"
      icon="pi pi-map-marker"
      :loading="loading"
      class="mb-3"
      @click="locate"
    />
    <Message v-if="geoError" severity="error" :closable="false" class="mb-3">{{ geoError }}</Message>
    <Message v-if="error" severity="error" :closable="false" class="mb-3">{{ error }}</Message>

    <NearestBranchesMap v-if="nearest.length" :branches="nearest" :center="position ?? undefined" />

    <DataTable
      v-if="nearest.length"
      :value="nearest"
      striped-rows
      paginator
      :rows="10"
      class="mt-3"
    >
      <Column header="Bank">
        <template #body="{ data }">{{ data.bank?.name }}</template>
      </Column>
      <Column field="name" header="Branch" />
      <Column field="address" header="Address" />
      <Column header="Distance">
        <template #body="{ data }">
          <Tag v-if="data.distance_m" :value="`${Math.round(data.distance_m)} m`" severity="info" />
        </template>
      </Column>
    </DataTable>
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
  margin-bottom: 1rem;
}
.mb-3 {
  margin-bottom: 1rem;
}
.mt-3 {
  margin-top: 1rem;
}
</style>
