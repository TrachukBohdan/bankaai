<script setup lang="ts">
import { onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { useStatusStore } from '@/stores/status'

const store = useStatusStore()
const { data, loading, error } = storeToRefs(store)

onMounted(() => {
  void store.fetchStatus()
})
</script>

<template>
  <section class="status-card card">
    <div class="header">
      <h2>API status</h2>
      <button type="button" class="btn-ghost" @click="store.fetchStatus()">Refresh</button>
    </div>
    <p v-if="loading && !data" class="muted">Calling GET /api/status…</p>
    <p v-if="error" class="error">{{ error }}</p>
    <dl v-if="data">
      <div><dt>Service</dt><dd>{{ data.service }}</dd></div>
      <div><dt>Status</dt><dd>{{ data.status }}</dd></div>
      <div><dt>Laravel</dt><dd>{{ data.versions.laravel }}</dd></div>
      <div><dt>PHP</dt><dd>{{ data.versions.php }}</dd></div>
      <div>
        <dt>Database</dt>
        <dd :class="data.database.connected ? 'good' : 'error'">
          {{ data.database.connected ? 'connected' : 'down' }}
        </dd>
      </div>
    </dl>
  </section>
</template>

<style scoped>
.header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 0.75rem;
}
.header h2 {
  margin: 0;
}
dl {
  display: grid;
  grid-template-columns: auto 1fr;
  gap: 0.4rem 1.25rem;
  margin-top: 0.5rem;
}
dt {
  font-weight: 600;
  color: var(--c-text-muted);
}
dd {
  color: var(--c-text);
  font-weight: 500;
}
.good {
  color: var(--c-good);
}
</style>
