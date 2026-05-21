<script setup lang="ts">
import { RouterLink } from 'vue-router'
import type { BankSummary } from '@/types/api'

defineProps<{ bank: BankSummary }>()
</script>

<template>
  <article class="bank-card card">
    <img v-if="bank.logo_url" :src="bank.logo_url" :alt="bank.name" class="logo" />
    <div v-else class="logo placeholder">{{ bank.name.charAt(0) }}</div>
    <h3>{{ bank.name }}</h3>
    <p v-if="bank.rating" class="rating">★ {{ bank.rating }}</p>
    <p v-if="bank.phone" class="muted">{{ bank.phone }}</p>
    <RouterLink :to="`/banks/${bank.slug}`" class="btn">View details</RouterLink>
  </article>
</template>

<style scoped>
.bank-card {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}
.logo {
  width: 64px;
  height: 64px;
  object-fit: contain;
  border-radius: 8px;
}
.logo.placeholder {
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--c-surface-muted);
  color: var(--c-text);
  font-weight: 700;
  font-size: 1.5rem;
  border: 1px solid var(--c-border);
}
h3 {
  margin: 0;
  color: var(--c-heading);
}
.rating {
  color: #b45309;
  font-weight: 600;
}
.btn {
  margin-top: auto;
  display: inline-block;
  text-align: center;
  text-decoration: none;
  width: 100%;
}
</style>
