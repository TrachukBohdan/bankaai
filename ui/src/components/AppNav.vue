<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import Menubar from 'primevue/menubar'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()
const { user, isAuthenticated } = storeToRefs(auth)

onMounted(() => {
  void auth.fetchMe()
})

const items = computed(() => {
  const nav = [
    { label: 'Banks', icon: 'pi pi-building', command: () => router.push('/banks') },
    { label: 'Rates', icon: 'pi pi-chart-line', command: () => router.push('/rates') },
    { label: 'Nearest', icon: 'pi pi-map-marker', command: () => router.push('/nearest') },
    { label: 'Statistics', icon: 'pi pi-chart-bar', command: () => router.push('/statistics') },
  ]
  if (isAuthenticated.value) {
    nav.push({ label: 'Profile', icon: 'pi pi-user', command: () => router.push('/profile') })
  }
  return nav
})
</script>

<template>
  <Menubar :model="items" class="app-menubar">
    <template #start>
      <Button
        label="BankaAi"
        icon="pi pi-wallet"
        text
        class="brand-btn"
        @click="router.push('/')"
      />
    </template>
    <template #end>
      <Tag v-if="user" :value="user.name" severity="info" class="mr-2" />
      <template v-if="isAuthenticated">
        <Button label="Logout" icon="pi pi-sign-out" severity="secondary" text @click="auth.logout()" />
      </template>
      <template v-else>
        <Button label="Login" icon="pi pi-sign-in" severity="secondary" text @click="router.push('/login')" />
        <Button label="Register" icon="pi pi-user-plus" @click="router.push('/register')" />
      </template>
    </template>
  </Menubar>
</template>

<style scoped>
.app-menubar {
  border-radius: 0;
  border-left: none;
  border-right: none;
  border-top: none;
}
.brand-btn :deep(.p-button-label) {
  font-weight: 700;
  font-size: 1.1rem;
}
.mr-2 {
  margin-right: 0.5rem;
}
</style>
