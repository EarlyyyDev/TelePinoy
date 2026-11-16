<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import mainLogo from '../assets/mainlogo.jpg'
import { useAdminStore } from '../stores/admin'

const drawer = ref(false)
const router = useRouter()
const admin = useAdminStore()

async function signOut() {
  try {
    await admin.logout()
  } catch (error) {
    console.error(error)
  } finally {
    await router.push('/')
  }
}
</script>

<template>
  <q-layout view="lHh Lpr lFf" class="telepinoy-layout">
    <q-header class="app-header text-white">
      <q-toolbar class="app-toolbar">
        <q-btn flat round dense icon="menu" aria-label="Open navigation menu" class="lt-md q-mr-sm" @click="drawer = !drawer" />

        <div class="row items-center no-wrap brand-lockup">
          <q-avatar class="brand-mark">
            <img :src="mainLogo" alt="TelePinoy logo" />
          </q-avatar>
          <div>
            <div class="brand-title text-weight-bold">TelePinoy</div>
            <div class="brand-caption">{{ admin.isAuthenticated ? 'Admin workspace' : 'Filipino television' }}</div>
          </div>
        </div>

        <q-space />

        <div class="gt-sm row items-center q-gutter-sm">
          <q-btn flat to="/" label="Home" class="app-nav-button" />
          <q-btn v-if="admin.isAuthenticated" flat :to="{ name: 'admin' }" label="Dashboard" class="app-nav-button" />
          <q-btn
            flat
            :to="admin.isAuthenticated ? { name: 'admin-channels' } : { name: 'channels' }"
            :label="admin.isAuthenticated ? 'Manage channels' : 'Channels'"
            class="app-nav-button"
          />
          <q-btn v-if="admin.isAuthenticated" outline color="grey-4" label="Sign out" @click="signOut" />
        </div>
      </q-toolbar>
    </q-header>

    <q-drawer v-model="drawer" side="left" bordered class="bg-dark text-white">
      <q-list padding>
        <q-item clickable to="/" exact @click="drawer = false">
          <q-item-section avatar>
            <q-icon name="home" />
          </q-item-section>
          <q-item-section>Home</q-item-section>
        </q-item>
        <q-item v-if="admin.isAuthenticated" clickable :to="{ name: 'admin' }" exact @click="drawer = false">
          <q-item-section avatar><q-icon name="dashboard" /></q-item-section>
          <q-item-section>Dashboard</q-item-section>
        </q-item>
        <q-item clickable :to="admin.isAuthenticated ? { name: 'admin-channels' } : { name: 'channels' }" @click="drawer = false">
          <q-item-section avatar><q-icon name="live_tv" /></q-item-section>
          <q-item-section>{{ admin.isAuthenticated ? 'Manage channels' : 'Channels' }}</q-item-section>
        </q-item>
        <q-item v-if="admin.isAuthenticated" clickable @click="signOut">
          <q-item-section avatar>
            <q-icon name="logout" />
          </q-item-section>
          <q-item-section>Sign out</q-item-section>
        </q-item>
      </q-list>
    </q-drawer>

    <q-page-container>
      <router-view />
    </q-page-container>
  </q-layout>
</template>
