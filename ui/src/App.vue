<script setup lang="ts">
import { RouterView } from 'vue-router'
import AppNav from '@/components/AppNav.vue'
</script>

<template>
  <div class="app-shell">
    <AppNav />
    <main class="main">
      <RouterView />
    </main>
  </div>
</template>

<style scoped>
.app-shell {
  min-height: 100vh;
  background: var(--c-bg);
  color: var(--c-text);
}
.main {
  min-height: calc(100vh - 56px);
}
</style>
