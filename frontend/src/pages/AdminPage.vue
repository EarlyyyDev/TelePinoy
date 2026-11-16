<script setup lang="ts">
import { onMounted, ref } from 'vue'
import axios from 'axios'
import { useRouter } from 'vue-router'
import { getAdminDashboard, type AdminDashboardStats } from '../services/adminService'

const router = useRouter()
const stats = ref<AdminDashboardStats | null>(null)
const loading = ref(false)
const error = ref('')

onMounted(loadDashboard)

async function loadDashboard() {
  loading.value = true
  error.value = ''

  try {
    stats.value = await getAdminDashboard()
  } catch (loadError) {
    error.value = axios.isAxiosError(loadError) && loadError.response?.status === 401
      ? 'Your admin session expired. Sign in again to continue.'
      : 'Unable to load dashboard statistics. Please retry.'
    console.error(loadError)
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <q-page class="q-pa-md q-pb-xl">
    <div class="row items-center justify-between q-mb-lg">
      <div>
        <div class="text-h4 text-weight-bold">Admin dashboard</div>
        <div class="text-grey-5">TelePinoy channel and audience overview</div>
      </div>
      <q-btn color="primary" icon="live_tv" label="Manage channels" @click="router.push({ name: 'admin-channels' })" />
    </div>

    <q-banner v-if="error" rounded class="bg-negative text-white q-mb-md">
      {{ error }}
      <template #action>
        <q-btn flat color="white" label="Retry" @click="loadDashboard" />
      </template>
    </q-banner>

    <div v-if="loading && !stats" class="text-center q-pa-xl">
      <q-spinner color="primary" size="3em" />
    </div>

    <div v-else-if="stats" class="row q-col-gutter-md">
      <div class="col-12 col-sm-6 col-xl-3">
        <q-card flat class="admin-stat-card bg-dark-2 q-pa-lg">
          <q-icon name="groups" color="primary" size="32px" />
          <div class="text-h3 text-weight-bold q-mt-md">{{ stats.viewer_starts_this_month.toLocaleString() }}</div>
          <div class="text-subtitle1">Total viewers this month</div>
          <div class="text-caption text-grey-5 q-mt-sm">Playback starts are counted; this is not a unique-person count.</div>
        </q-card>
      </div>

      <div class="col-12 col-sm-6 col-xl-3">
        <q-card flat class="admin-stat-card bg-dark-2 q-pa-lg" clickable @click="router.push({ name: 'admin-channels', query: { tab: 'live' } })">
          <q-icon name="live_tv" color="positive" size="32px" />
          <div class="text-h3 text-weight-bold q-mt-md">{{ stats.live_channels.toLocaleString() }}</div>
          <div class="text-subtitle1">Total channels available</div>
          <div class="text-caption text-grey-5 q-mt-sm">Published and visible to viewers.</div>
        </q-card>
      </div>

      <div class="col-12 col-sm-6 col-xl-3">
        <q-card flat class="admin-stat-card bg-dark-2 q-pa-lg" clickable @click="router.push({ name: 'admin-channels', query: { tab: 'imported' } })">
          <q-icon name="playlist_add" color="warning" size="32px" />
          <div class="text-h3 text-weight-bold q-mt-md">{{ stats.imported_channels.toLocaleString() }}</div>
          <div class="text-subtitle1">Imported drafts</div>
          <div class="text-caption text-grey-5 q-mt-sm">Imported channels waiting to be tested and published.</div>
        </q-card>
      </div>

      <div class="col-12 col-sm-6 col-xl-3">
        <q-card flat class="admin-stat-card bg-dark-2 q-pa-lg" clickable @click="router.push({ name: 'admin-channels', query: { tab: 'custom' } })">
          <q-icon name="library_add" color="info" size="32px" />
          <div class="text-h3 text-weight-bold q-mt-md">{{ stats.total_channels.toLocaleString() }}</div>
          <div class="text-subtitle1">Total channels in catalog</div>
          <div class="text-caption text-grey-5 q-mt-sm">{{ stats.custom_drafts }} unpublished custom channels.</div>
        </q-card>
      </div>
    </div>

    <div v-if="stats" class="row q-col-gutter-md q-mt-md">
      <div class="col-12 col-lg-7">
        <q-card flat class="bg-dark-2 q-pa-lg full-height">
          <div class="text-h6 text-weight-bold q-mb-sm">Channel publishing workflow</div>
          <div class="text-grey-5">Import a playlist or create a channel, preview the stream, then publish it to the Live section.</div>
          <q-btn class="q-mt-md" outline color="primary" label="Open channel manager" @click="router.push({ name: 'admin-channels' })" />
        </q-card>
      </div>
      <div class="col-12 col-lg-5">
        <q-card flat class="bg-dark-2 q-pa-lg full-height">
          <div class="text-h6 text-weight-bold q-mb-sm">About viewer statistics</div>
          <div class="text-grey-5">The monthly figure counts playback-start events. It does not identify or estimate unique viewers.</div>
          <q-btn flat color="primary" icon="refresh" label="Refresh statistics" class="q-mt-sm" :loading="loading" @click="loadDashboard" />
        </q-card>
      </div>
    </div>
  </q-page>
</template>

<style scoped>
.admin-stat-card {
  height: 100%;
  min-height: 190px;
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 18px;
}
</style>
