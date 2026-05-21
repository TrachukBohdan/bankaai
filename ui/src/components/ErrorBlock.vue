<script setup lang="ts">
defineProps<{ message: string | null }>()
const emit = defineEmits<{ retry: [] }>()
</script>

<template>
  <div v-if="message" class="error-block">
    <span class="error">{{ message }}</span>
    <button class="btn btn-ghost" @click="emit('retry')">Retry</button>
  </div>
</template>

<style scoped>
.error-block {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  border: 1px solid var(--c-bad);
  border-radius: 8px;
  background: rgba(248, 81, 73, 0.08);
}
</style>
