<script setup lang="ts">
import { onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import Card from 'primevue/card'
import Button from 'primevue/button'
import Message from 'primevue/message'
import Tag from 'primevue/tag'
import ProgressSpinner from 'primevue/progressspinner'
import { useStatusStore } from '@/stores/status'

const store = useStatusStore()
const { data, loading, error } = storeToRefs(store)

onMounted(() => {
  void store.fetchStatus()
})
</script>

<template>
  <Card>
    <template #title>
      <div class="title-row">
        <span>API status</span>
        <Button
          icon="pi pi-refresh"
          label="Refresh"
          severity="secondary"
          size="small"
          :loading="loading"
          @click="store.fetchStatus()"
        />
      </div>
    </template>
    <template #content>
      <div v-if="loading && !data" class="flex justify-center p-4">
        <ProgressSpinner style="width: 2rem; height: 2rem" />
      </div>
      <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>
      <dl v-if="data" class="status-grid">
        <dt>Service</dt>
        <dd>{{ data.service }}</dd>
        <dt>Status</dt>
        <dd>
          <Tag
            :value="data.status"
            :severity="data.status === 'ok' ? 'success' : 'warn'"
          />
        </dd>
        <dt>Laravel</dt>
        <dd>{{ data.versions.laravel }}</dd>
        <dt>PHP</dt>
        <dd>{{ data.versions.php }}</dd>
        <dt>Database</dt>
        <dd>
          <Tag
            :value="data.database.connected ? 'connected' : 'down'"
            :severity="data.database.connected ? 'success' : 'danger'"
          />
        </dd>
      </dl>
    </template>
  </Card>
</template>

<style scoped>
.title-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  width: 100%;
}
.status-grid {
  display: grid;
  grid-template-columns: auto 1fr;
  gap: 0.5rem 1.25rem;
  margin: 0;
}
.status-grid dt {
  font-weight: 600;
  color: var(--p-text-muted-color);
}
.status-grid dd {
  margin: 0;
}
.flex {
  display: flex;
}
.justify-center {
  justify-content: center;
}
.p-4 {
  padding: 1rem;
}
</style>
