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
  <section class="status-card">
    <header class="status-card__header">
      <h2>API status</h2>
      <button
        type="button"
        class="status-card__refresh"
        :disabled="loading"
        @click="store.fetchStatus()"
      >
        <span v-if="loading">Refreshing…</span>
        <span v-else>Refresh</span>
      </button>
    </header>

    <p v-if="loading && !data" class="status-card__hint">Calling <code>GET /api/status</code>…</p>

    <p v-if="error" class="status-card__error" role="alert">
      {{ error }}
    </p>

    <dl v-if="data" class="status-card__grid">
      <dt>Service</dt>
      <dd>
        <span class="badge" :class="data.status === 'ok' ? 'badge--ok' : 'badge--warn'">
          {{ data.status }}
        </span>
        {{ data.service }}
      </dd>

      <dt>App</dt>
      <dd>{{ data.app.name }} <small>({{ data.app.env }})</small></dd>

      <dt>App URL</dt>
      <dd><code>{{ data.app.url }}</code></dd>

      <dt>Laravel</dt>
      <dd><code>{{ data.versions.laravel }}</code></dd>

      <dt>PHP</dt>
      <dd><code>{{ data.versions.php }}</code></dd>

      <dt>Container</dt>
      <dd><code>{{ data.host ?? '—' }}</code></dd>

      <dt>Server time</dt>
      <dd><code>{{ data.server_time }}</code></dd>

      <dt>Database</dt>
      <dd>
        <span
          class="badge"
          :class="data.database.connected ? 'badge--ok' : 'badge--warn'"
        >
          {{ data.database.connected ? 'connected' : 'unreachable' }}
        </span>
        <code>{{ data.database.connection }}</code>
        <span v-if="data.database.error" class="status-card__db-error">
          {{ data.database.error }}
        </span>
      </dd>
    </dl>
  </section>
</template>

<style scoped>
.status-card {
  border: 1px solid var(--color-border, #2c3e50);
  border-radius: 8px;
  padding: 1.25rem 1.5rem;
  margin: 1.5rem 0;
  background: var(--color-background-soft, transparent);
}

.status-card__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 0.75rem;
}

.status-card__header h2 {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 600;
}

.status-card__refresh {
  appearance: none;
  border: 1px solid currentColor;
  background: transparent;
  color: inherit;
  padding: 0.35rem 0.9rem;
  border-radius: 4px;
  cursor: pointer;
  font: inherit;
}

.status-card__refresh:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.status-card__hint {
  margin: 0.5rem 0;
  opacity: 0.7;
  font-style: italic;
}

.status-card__error {
  margin: 0.5rem 0;
  padding: 0.6rem 0.8rem;
  border-radius: 4px;
  background: rgba(255, 80, 80, 0.12);
  border: 1px solid rgba(255, 80, 80, 0.4);
  color: #d04040;
  font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
  font-size: 0.85rem;
}

.status-card__grid {
  display: grid;
  grid-template-columns: minmax(110px, max-content) 1fr;
  gap: 0.4rem 1rem;
  margin: 0;
}

.status-card__grid dt {
  font-weight: 600;
  opacity: 0.75;
}

.status-card__grid dd {
  margin: 0;
}

.status-card__db-error {
  display: block;
  margin-top: 0.25rem;
  font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
  font-size: 0.8rem;
  color: #d04040;
}

.badge {
  display: inline-block;
  padding: 0.1rem 0.5rem;
  border-radius: 999px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-right: 0.5rem;
}

.badge--ok {
  background: rgba(20, 160, 90, 0.15);
  color: #2c9c69;
  border: 1px solid rgba(20, 160, 90, 0.4);
}

.badge--warn {
  background: rgba(220, 130, 0, 0.15);
  color: #c87a00;
  border: 1px solid rgba(220, 130, 0, 0.4);
}

code {
  font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
  font-size: 0.85rem;
}
</style>
