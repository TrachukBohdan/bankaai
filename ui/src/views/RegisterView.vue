<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()
const { loading, error } = storeToRefs(auth)

const name = ref('')
const email = ref('')
const password = ref('')
const passwordConfirmation = ref('')

async function submit(): Promise<void> {
  const ok = await auth.register({
    name: name.value,
    email: email.value,
    password: password.value,
    password_confirmation: passwordConfirmation.value,
  })
  if (ok) await router.push('/profile')
}
</script>

<template>
  <section class="page auth-page">
    <form class="card" @submit.prevent="submit">
      <h1>Register</h1>
      <label>
        Name
        <input v-model="name" required />
      </label>
      <label>
        Email
        <input v-model="email" type="email" required />
      </label>
      <label>
        Password
        <input v-model="password" type="password" required />
      </label>
      <label>
        Confirm password
        <input v-model="passwordConfirmation" type="password" required />
      </label>
      <p v-if="error" class="error">{{ error }}</p>
      <button type="submit" :disabled="loading">Create account</button>
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
label {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}
</style>
