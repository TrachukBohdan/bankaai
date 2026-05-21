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
  <section class="page-narrow">
    <Card>
      <template #title>Register</template>
      <template #content>
        <form class="gap-form" @submit.prevent="submit">
          <div class="field">
            <label for="name">Name</label>
            <InputText id="name" v-model="name" class="w-full" />
          </div>
          <div class="field">
            <label for="email">Email</label>
            <InputText id="email" v-model="email" type="email" class="w-full" />
          </div>
          <div class="field">
            <label for="password">Password</label>
            <Password id="password" v-model="password" class="w-full" toggle-mask input-class="w-full" />
          </div>
          <div class="field">
            <label for="password2">Confirm password</label>
            <Password
              id="password2"
              v-model="passwordConfirmation"
              class="w-full"
              :feedback="false"
              toggle-mask
              input-class="w-full"
            />
          </div>
          <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>
          <Button type="submit" label="Create account" icon="pi pi-user-plus" class="w-full" :loading="loading" />
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
