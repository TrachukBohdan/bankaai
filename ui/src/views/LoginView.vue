<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import Card from 'primevue/card'
import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import Button from 'primevue/button'
import Message from 'primevue/message'
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
  <section class="page-narrow">
    <Card>
      <template #title>Login</template>
      <template #content>
        <form class="gap-form" @submit.prevent="submit">
          <div class="field">
            <label for="email">Email</label>
            <InputText id="email" v-model="email" type="email" class="w-full" autocomplete="email" />
          </div>
          <div class="field">
            <label for="password">Password</label>
            <Password
              id="password"
              v-model="password"
              class="w-full"
              :feedback="false"
              toggle-mask
              input-class="w-full"
              autocomplete="current-password"
            />
          </div>
          <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>
          <Button type="submit" label="Sign in" icon="pi pi-sign-in" class="w-full" :loading="loading" />
          <Message severity="secondary" :closable="false">
            Demo: demo@bankaai.test / password
          </Message>
        </form>
      </template>
    </Card>
  </section>
</template>

<style scoped>
.field label {
  display: block;
  margin-bottom: 0.35rem;
  font-weight: 500;
  font-size: 0.875rem;
}
</style>
