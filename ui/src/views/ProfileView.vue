<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useAuthStore } from '@/stores/auth'
import { useBanksStore } from '@/stores/banks'
import { useCurrenciesStore } from '@/stores/currencies'

const router = useRouter()
const auth = useAuthStore()
const banks = useBanksStore()
const currencies = useCurrenciesStore()
const { user, subscriptions, loading, error } = storeToRefs(auth)

const name = ref('')
const notificationsEnabled = ref(true)
const bankSlug = ref('')
const currencyCode = ref('')
const threshold = ref(5)

onMounted(async () => {
  if (!auth.isAuthenticated) {
    await auth.fetchMe()
  }
  if (!auth.isAuthenticated) {
    await router.push('/login')
    return
  }
  name.value = user.value?.name ?? ''
  notificationsEnabled.value = user.value?.notifications_enabled ?? true
  await auth.fetchSubscriptions()
  await banks.fetchList()
  await currencies.fetchAll()
})

async function saveProfile(): Promise<void> {
  await auth.updateProfile({
    name: name.value,
    notifications_enabled: notificationsEnabled.value,
  })
}

async function addSub(): Promise<void> {
  await auth.addSubscription({
    bank_slug: bankSlug.value || undefined,
    currency_code: currencyCode.value || undefined,
    threshold_pct: threshold.value,
  })
}
</script>

<template>
  <section class="page">
    <h1>Profile</h1>
    <p v-if="!user" class="muted">Redirecting to login…</p>
    <template v-else>
      <form class="card" @submit.prevent="saveProfile">
        <h2>Account</h2>
        <label>
          Name
          <input v-model="name" />
        </label>
        <label class="checkbox">
          <input v-model="notificationsEnabled" type="checkbox" />
          Email notifications for significant rate changes
        </label>
        <p v-if="error" class="error">{{ error }}</p>
        <button type="submit" :disabled="loading">Save</button>
      </form>

      <section class="card">
        <h2>Subscriptions</h2>
        <ul v-if="subscriptions.length" class="subs">
          <li v-for="s in subscriptions" :key="s.id">
            <span>
              {{ s.bank?.name ?? 'All banks' }} /
              {{ s.currency?.code ?? 'All currencies' }}
              ({{ s.threshold_pct }}%)
            </span>
            <button type="button" class="btn-remove" @click="auth.removeSubscription(s.id)">
              Remove
            </button>
          </li>
        </ul>
        <p v-else class="muted">No subscriptions yet.</p>

        <form class="sub-form" @submit.prevent="addSub">
          <h3>Add subscription</h3>
          <label>
            Bank
            <select v-model="bankSlug">
              <option value="">All banks</option>
              <option v-for="b in banks.list" :key="b.slug" :value="b.slug">{{ b.name }}</option>
            </select>
          </label>
          <label>
            Currency
            <select v-model="currencyCode">
              <option value="">All currencies</option>
              <option v-for="c in currencies.items" :key="c.code" :value="c.code">{{ c.code }}</option>
            </select>
          </label>
          <label>
            Threshold %
            <input v-model.number="threshold" type="number" min="0.1" max="100" step="0.1" />
          </label>
          <button type="submit">Subscribe</button>
        </form>
      </section>
    </template>
  </section>
</template>

<style scoped>
form,
section.card {
  margin-bottom: 1.25rem;
}
form {
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
}
label {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}
.checkbox {
  flex-direction: row;
  align-items: center;
  gap: 0.5rem;
}
.subs {
  list-style: none;
  padding: 0;
  margin-bottom: 1rem;
}
.subs li {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  padding: 0.65rem 0;
  border-bottom: 1px solid var(--c-border);
  color: var(--c-text);
}
.subs li:last-child {
  border-bottom: none;
}
.btn-remove {
  background: #fee2e2;
  color: #991b1b;
  font-size: 0.85rem;
  padding: 0.35rem 0.75rem;
}
.btn-remove:hover {
  background: #fecaca;
}
.sub-form {
  padding-top: 1rem;
  border-top: 1px solid var(--c-border);
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
}
</style>
