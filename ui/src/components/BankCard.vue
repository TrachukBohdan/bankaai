<script setup lang="ts">
import { RouterLink } from 'vue-router'
import Card from 'primevue/card'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import Avatar from 'primevue/avatar'
import type { BankSummary } from '@/types/api'

defineProps<{ bank: BankSummary }>()
</script>

<template>
  <Card class="bank-card h-full">
    <template #header>
      <div class="card-header">
        <img v-if="bank.logo_url" :src="bank.logo_url" :alt="bank.name" class="logo" />
        <Avatar v-else :label="bank.name.charAt(0)" size="xlarge" shape="circle" />
      </div>
    </template>
    <template #title>{{ bank.name }}</template>
    <template #subtitle>
      <Tag v-if="bank.rating" :value="`★ ${bank.rating}`" severity="warn" />
      <span v-if="bank.phone" class="phone">{{ bank.phone }}</span>
    </template>
    <template #footer>
      <RouterLink :to="`/banks/${bank.slug}`" class="w-full">
        <Button label="View details" icon="pi pi-arrow-right" class="w-full" />
      </RouterLink>
    </template>
  </Card>
</template>

<style scoped>
.bank-card :deep(.p-card-body) {
  display: flex;
  flex-direction: column;
  height: 100%;
}
.card-header {
  display: flex;
  justify-content: center;
  padding: 1rem 1rem 0;
}
.logo {
  width: 72px;
  height: 72px;
  object-fit: contain;
}
.phone {
  display: block;
  margin-top: 0.25rem;
  font-size: 0.875rem;
}
.h-full {
  height: 100%;
}
</style>
