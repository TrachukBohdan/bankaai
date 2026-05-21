<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()
const { loading, error } = storeToRefs(auth)

const email = ref('demo@bankaai.test')
const password = ref('password')

async function submit(): Promise<void> {
  const ok = await auth.login(email.value, password.value)
  if (ok) await router.push('/profile')
}
</script>

<template>
  <section class="page auth-page">
    <form class="card" @submit.prevent="submit">
      <h1>Login</h1>
      <label>
        Email
        <input v-model="email" type="email" required autocomplete="email" />
      </label>
      <label>
        Password
        <input v-model="password" type="password" required autocomplete="current-password" />
      </label>
      <p v-if="error" class="error">{{ error }}</p>
      <button type="submit" :disabled="loading">{{ loading ? '…' : 'Sign in' }}</button>
      <p class="muted hint">Demo: demo@bankaai.test / password</p>
    </form>
  </section>
</template>

<style scoped>
.auth-page {
  max-width: 420px;
}
form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}
form h1 {
  margin-bottom: 0.25rem;
}
label {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}
.hint {
  text-align: center;
  margin-top: 0.25rem;
}
</style>
