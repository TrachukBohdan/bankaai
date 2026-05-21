<script setup lang="ts">
import { useRouter } from 'vue-router'
import Card from 'primevue/card'
import Button from 'primevue/button'

const router = useRouter()
</script>

<template>
  <section class="page flex justify-center">
    <Card class="text-center" style="max-width: 24rem">
      <template #title>404</template>
      <template #content>
        <p class="mb-4">This page does not exist.</p>
        <Button label="Go home" icon="pi pi-home" @click="router.push('/')" />
      </template>
    </Card>
  </section>
</template>

<style scoped>
.flex {
  display: flex;
}
.justify-center {
  justify-content: center;
  align-items: flex-start;
  padding-top: 3rem;
}
.text-center :deep(.p-card-body) {
  text-align: center;
}
.mb-4 {
  margin-bottom: 1rem;
}
</style>
