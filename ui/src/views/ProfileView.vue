<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import Card from 'primevue/card'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Checkbox from 'primevue/checkbox'
import Select from 'primevue/select'
import Button from 'primevue/button'
import Message from 'primevue/message'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Divider from 'primevue/divider'
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
const bankSlug = ref<string | null>(null)
const currencyCode = ref<string | null>(null)
const threshold = ref(5)

const bankOptions = computed(() => [
  { label: 'All banks', value: null },
  ...banks.list.map((b) => ({ label: b.name, value: b.slug })),
])

const currencyOptions = computed(() => [
  { label: 'All currencies', value: null },
  ...currencies.items.map((c) => ({ label: c.code, value: c.code })),
])

onMounted(async () => {
  if (!auth.isAuthenticated) await auth.fetchMe()
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
    bank_slug: bankSlug.value ?? undefined,
    currency_code: currencyCode.value ?? undefined,
    threshold_pct: threshold.value,
  })
}
</script>

<template>
  <section class="page page-narrow" style="max-width: 640px">
    <h1 class="page-title">Profile</h1>
    <Message v-if="!user" severity="info" :closable="false">Redirecting to login…</Message>

    <template v-else>
      <Card class="mb-3">
        <template #title>Account</template>
        <template #content>
          <form class="gap-form" @submit.prevent="saveProfile">
            <div class="field">
              <label for="profile-name">Name</label>
              <InputText id="profile-name" v-model="name" class="w-full" />
            </div>
            <div class="field-checkbox">
              <Checkbox v-model="notificationsEnabled" input-id="notif" binary />
              <label for="notif">Email notifications for significant rate changes</label>
            </div>
            <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>
            <Button type="submit" label="Save" icon="pi pi-save" :loading="loading" />
          </form>
        </template>
      </Card>

      <Card>
        <template #title>Subscriptions</template>
        <template #content>
          <DataTable
            v-if="subscriptions.length"
            :value="subscriptions"
            size="small"
            class="mb-3"
          >
            <Column header="Target">
              <template #body="{ data }">
                {{ data.bank?.name ?? 'All banks' }} /
                {{ data.currency?.code ?? 'All currencies' }}
              </template>
            </Column>
            <Column field="threshold_pct" header="Threshold %" />
            <Column header="">
              <template #body="{ data }">
                <Button
                  icon="pi pi-trash"
                  severity="danger"
                  text
                  rounded
                  aria-label="Remove"
                  @click="auth.removeSubscription(data.id)"
                />
              </template>
            </Column>
          </DataTable>
          <Message v-else severity="info" :closable="false" class="mb-3">
            No subscriptions yet.
          </Message>

          <Divider />

          <form class="gap-form" @submit.prevent="addSub">
            <h3 class="sub-title">Add subscription</h3>
            <div class="field">
              <label>Bank</label>
              <Select
                v-model="bankSlug"
                :options="bankOptions"
                option-label="label"
                option-value="value"
                class="w-full"
              />
            </div>
            <div class="field">
              <label>Currency</label>
              <Select
                v-model="currencyCode"
                :options="currencyOptions"
                option-label="label"
                option-value="value"
                class="w-full"
              />
            </div>
            <div class="field">
              <label for="threshold">Threshold %</label>
              <InputNumber
                id="threshold"
                v-model="threshold"
                :min="0.1"
                :max="100"
                :min-fraction-digits="1"
                class="w-full"
              />
            </div>
            <Button type="submit" label="Subscribe" icon="pi pi-bell" />
          </form>
        </template>
      </Card>
    </template>
  </section>
</template>

<style scoped>
.page-title {
  font-size: 1.75rem;
  font-weight: 700;
  margin-bottom: 1rem;
}
.field label {
  display: block;
  margin-bottom: 0.35rem;
  font-weight: 500;
  font-size: 0.875rem;
}
.field-checkbox {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.sub-title {
  font-size: 1rem;
  font-weight: 600;
  margin: 0;
}
.mb-3 {
  margin-bottom: 1rem;
}
</style>
