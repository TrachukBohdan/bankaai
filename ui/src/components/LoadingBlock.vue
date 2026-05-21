<script setup lang="ts">
defineProps<{ rows?: number }>()
</script>

<template>
  <div class="loading">
    <div v-for="i in (rows ?? 4)" :key="i" class="skeleton" :style="{ height: '14px', marginBottom: '10px', width: (60 + Math.random() * 40) + '%' }" />
  </div>
</template>

<style scoped>
.loading { padding: 0.5rem 0; }
</style>
