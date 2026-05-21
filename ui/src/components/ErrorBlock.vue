<script setup lang="ts">
import Message from 'primevue/message'
import Button from 'primevue/button'

defineProps<{ message: string | null }>()
const emit = defineEmits<{ retry: [] }>()
</script>

<template>
  <Message v-if="message" severity="error" :closable="false" class="w-full">
    <div class="error-row">
      <span>{{ message }}</span>
      <Button label="Retry" icon="pi pi-refresh" size="small" severity="danger" outlined @click="emit('retry')" />
    </div>
  </Message>
</template>

<style scoped>
.error-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
  width: 100%;
}
</style>
