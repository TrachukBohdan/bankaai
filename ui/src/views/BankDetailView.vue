<script setup lang="ts">
import { onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { storeToRefs } from 'pinia'
import Card from 'primevue/card'
import Tag from 'primevue/tag'
import Button from 'primevue/button'
import Message from 'primevue/message'
import ProgressSpinner from 'primevue/progressspinner'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import RatesTable from '@/components/RatesTable.vue'
import { useBanksStore } from '@/stores/banks'

const route = useRoute()
const store = useBanksStore()
const { current, loading, error } = storeToRefs(store)

function load(): void {
  void store.fetchOne(route.params.slug as string)
}

onMounted(load)
watch(() => route.params.slug, load)
</script>

<template>
  <section class="page">
    <div v-if="loading && !current" class="flex justify-center p-6">
      <ProgressSpinner />
    </div>
    <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>

    <template v-if="current">
      <Card class="mb-4">
        <template #content>
          <div class="header">
            <img v-if="current.logo_url" :src="current.logo_url" :alt="current.name" class="logo" />
            <div>
              <h1 class="page-title">{{ current.name }}</h1>
              <Tag v-if="current.rating" :value="`★ ${current.rating}`" severity="warn" class="mb-2" />
              <p v-if="current.legal_name" class="detail-line">{{ current.legal_name }}</p>
              <p v-if="current.phone" class="detail-line">
                <i class="pi pi-phone mr-1" />{{ current.phone }}
              </p>
              <p v-if="current.legal_address" class="detail-line">
                <i class="pi pi-map-marker mr-1" />{{ current.legal_address }}
              </p>
              <a
                v-if="current.website"
                :href="current.website"
                target="_blank"
                rel="noopener noreferrer"
                class="mt-2 inline-link"
              >
                <Button label="Website" icon="pi pi-external-link" link />
              </a>
            </div>
          </div>
        </template>
      </Card>

      <h2 class="section-title">Current rates (cash)</h2>
      <RatesTable :rates="current.rates ?? []" class="mb-4" />

      <h2 class="section-title">Branches (sample)</h2>
      <DataTable
        v-if="current.branches?.length"
        :value="current.branches"
        size="small"
        striped-rows
        paginator
        :rows="10"
      >
        <Column field="name" header="Branch" />
        <Column field="address" header="Address" />
        <Column field="city" header="City" />
      </DataTable>
      <Message v-else severity="info" :closable="false">No branches loaded yet.</Message>
    </template>
  </section>
</template>

<style scoped>
.header {
  display: flex;
  gap: 1.5rem;
  align-items: flex-start;
}
.logo {
  width: 96px;
  height: 96px;
  object-fit: contain;
  flex-shrink: 0;
}
.page-title {
  font-size: 1.5rem;
  font-weight: 700;
  margin: 0 0 0.5rem;
}
.section-title {
  font-size: 1.15rem;
  font-weight: 600;
  margin: 0 0 0.75rem;
}
.detail-line {
  margin: 0.25rem 0;
  color: var(--p-text-color);
}
.mr-1 {
  margin-right: 0.25rem;
}
.mb-2 {
  margin-bottom: 0.5rem;
}
.mb-4 {
  margin-bottom: 1.5rem;
}
.mt-2 {
  margin-top: 0.5rem;
}
.inline-link {
  text-decoration: none;
}
</style>
