<script setup lang="ts">
import { onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const { user, isAuthenticated } = storeToRefs(auth)

onMounted(() => {
  void auth.fetchMe()
})
</script>

<template>
  <nav class="app-nav">
    <RouterLink to="/" class="brand">BankaAi</RouterLink>
    <div class="links">
      <RouterLink to="/banks">Banks</RouterLink>
      <RouterLink to="/rates">Rates</RouterLink>
      <RouterLink to="/nearest">Nearest</RouterLink>
      <RouterLink to="/statistics">Statistics</RouterLink>
      <template v-if="isAuthenticated">
        <RouterLink to="/profile">Profile</RouterLink>
        <button type="button" class="link-btn" @click="auth.logout()">Logout</button>
      </template>
      <template v-else>
        <RouterLink to="/login">Login</RouterLink>
        <RouterLink to="/register" class="cta">Register</RouterLink>
      </template>
    </div>
    <span v-if="user" class="user-pill">{{ user.name }}</span>
  </nav>
</template>

<style scoped>
.app-nav {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 1rem;
  padding: 0.75rem 1.25rem;
  background: #0f172a;
  color: #e2e8f0;
}
.brand {
  font-weight: 700;
  font-size: 1.15rem;
  color: #38bdf8;
  text-decoration: none;
}
.links {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem 1rem;
  flex: 1;
}
.links a {
  color: #cbd5e1;
  text-decoration: none;
  font-size: 0.95rem;
}
.links a.router-link-active {
  color: #fff;
  font-weight: 600;
}
.cta {
  background: #2563eb;
  color: #fff !important;
  padding: 0.25rem 0.75rem;
  border-radius: 6px;
}
.link-btn {
  background: none;
  border: none;
  color: #cbd5e1;
  cursor: pointer;
  font-size: 0.95rem;
  padding: 0;
}
.user-pill {
  font-size: 0.8rem;
  background: #1e293b;
  padding: 0.2rem 0.6rem;
  border-radius: 999px;
}
@media (max-width: 640px) {
  .app-nav {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>
