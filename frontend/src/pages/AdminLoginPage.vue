<script setup lang="ts">
import { onMounted, ref } from 'vue'
import axios from 'axios'
import { useRoute, useRouter } from 'vue-router'
import { useAdminStore } from '../stores/admin'

const admin = useAdminStore()
const route = useRoute()
const router = useRouter()
const email = ref('')
const password = ref('')
const loading = ref(false)
const errorMessage = ref('')

onMounted(() => {
  if (route.query.error === 'unavailable') {
    errorMessage.value = 'Unable to verify your sign-in. Check that the API is running, then try again.'
  }
})

async function submitLogin() {
  loading.value = true
  errorMessage.value = ''

  try {
    await admin.login(email.value, password.value)
    const redirect = typeof route.query.redirect === 'string' ? route.query.redirect : '/admin'
    await router.replace(redirect)
  } catch (error) {
    errorMessage.value = axios.isAxiosError(error) && error.response?.status === 422
      ? 'The email or password is incorrect.'
      : 'Unable to sign in right now. Check that the API is running and try again.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <q-page class="row items-center justify-center q-pa-md">
    <q-card flat bordered class="bg-dark-2 q-pa-lg admin-login-card">
      <div class="text-h4 text-weight-bold q-mb-sm">Admin sign in</div>
      <div class="text-grey-5 q-mb-lg">Sign in to manage TelePinoy channels.</div>

      <q-banner v-if="errorMessage" rounded class="bg-negative text-white q-mb-md">
        {{ errorMessage }}
      </q-banner>

      <q-form class="q-gutter-md" @submit.prevent="submitLogin">
        <q-input v-model="email" type="email" autocomplete="username" outlined label="Email" required />
        <q-input v-model="password" type="password" autocomplete="current-password" outlined label="Password" required />
        <q-btn type="submit" color="primary" class="full-width" label="Sign in" :loading="loading" />
      </q-form>
    </q-card>
  </q-page>
</template>

<style scoped>
.admin-login-card {
  width: min(100%, 440px);
  border-radius: 20px;
}
</style>
