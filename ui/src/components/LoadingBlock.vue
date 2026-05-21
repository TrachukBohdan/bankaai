<script setup lang="ts">
import Skeleton from 'primevue/skeleton'

defineProps<{ rows?: number }>()
</script>

<template>
  <div class="loading">
    <Skeleton v-for="i in rows ?? 4" :key="i" height="1rem" class="mb-2" />
  </div>
</template>

<style scoped>
.mb-2 {
  margin-bottom: 0.5rem;
}
</style>
