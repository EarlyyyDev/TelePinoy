<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import ChannelCard from '../components/ChannelCard.vue'
import type { Channel } from '../services/channelService'
import { useChannelStore } from '../stores/channel'

const router = useRouter()
const store = useChannelStore()
const searchTerm = ref('')

onMounted(() => {
  store.loadChannels()
})

const filteredFeatured = computed(() => {
  const channels = store.channels.filter((channel) => channel.is_featured)
  const query = searchTerm.value.trim().toLowerCase()

  if (!query) {
    return channels.slice(0, 4)
  }

  return channels.filter((channel) => channel.name.toLowerCase().includes(query)).slice(0, 4)
})

const popularChannels = computed(() => {
  const query = searchTerm.value.trim().toLowerCase()
  const channels = [...store.channels].sort((a, b) => Number(b.is_featured) - Number(a.is_featured)).slice(0, 4)

  if (!query) {
    return channels
  }

  return channels.filter((channel) => channel.name.toLowerCase().includes(query))
})

function goToChannel(channel: Channel) {
  router.push({ name: 'channel-detail', params: { slug: channel.slug } })
}

function toggleFavorite(channel: { id: number }) {
  store.toggleFavorite(channel.id)
}
</script>

<template>
  <q-page class="q-pa-md q-pb-xl">
    <div class="hero-panel q-pa-lg q-mb-xl">
      <div class="row items-center justify-between q-col-gutter-lg">
        <div class="col-12 col-md-7">
          <div class="text-uppercase text-weight-bold text-primary q-mb-sm">Your Filipino Channels, Anytime, Anywhere.</div>
          <h1 class="text-h3 text-weight-bold q-mb-md">Discover Philippine television in one place.</h1>
          <p class="text-body1 text-grey-5 q-mb-lg">
            Browse live Filipino channels, catch trending shows, and keep your favorite programs close at hand.
          </p>

          <q-input
            v-model="searchTerm"
            outlined
            rounded
            dark
            color="primary"
            placeholder="Search channels"
            class="search-input"
            @keyup.enter="router.push({ name: 'channels', query: { search: searchTerm } })"
          >
            <template #append>
              <q-icon name="search" />
            </template>
          </q-input>
        </div>

        <div class="col-12 col-md-5">
          <q-card flat class="quick-access-panel">
            <div class="quick-access-heading">
              <div>
                <div class="eyebrow">Quick access</div>
                <div class="quick-access-title">Your next watch starts here.</div>
              </div>
              <q-chip dense class="quick-access-live">
                <span class="quick-access-live__dot" />
                LIVE
              </q-chip>
            </div>
            <p class="quick-access-description">Jump into the channel lineup and find something worth watching.</p>
            <div class="column q-gutter-sm">
              <q-btn unelevated no-caps class="quick-access-action quick-access-action--primary" @click="router.push({ name: 'channels' })">
                <q-icon name="grid_view" size="21px" />
                <span class="quick-access-action__copy">
                  <strong>Browse all channels</strong>
                  <small>Explore the full lineup</small>
                </span>
                <q-space />
                <q-icon name="arrow_forward" size="19px" />
              </q-btn>
              <q-btn flat no-caps class="quick-access-action quick-access-action--secondary" @click="router.push({ name: 'channels' })">
                <q-icon name="live_tv" size="21px" />
                <span class="quick-access-action__copy">
                  <strong>Watch live</strong>
                  <small>Find something on now</small>
                </span>
                <q-space />
                <q-icon name="arrow_forward" size="19px" />
              </q-btn>
            </div>
          </q-card>
        </div>
      </div>
    </div>

    <div v-if="store.error" class="text-negative q-mb-lg">{{ store.error }}</div>

    <section class="q-mb-xl">
      <div class="row items-center justify-between q-mb-md">
        <h2 class="text-h5 q-my-none">Featured channels</h2>
        <q-btn flat color="primary" label="View all channels" to="/channels" />
      </div>

      <div class="row q-col-gutter-md">
        <div v-for="channel in filteredFeatured" :key="channel.id" class="col-12 col-sm-6 col-md-3">
          <ChannelCard :channel="channel" :is-favorite="store.favorites.includes(channel.id)" @watch="goToChannel" @favorite="toggleFavorite" />
        </div>
      </div>
    </section>

    <section class="q-mb-xl">
      <div class="row items-center justify-between q-mb-md">
        <h2 class="text-h5 q-my-none">Popular channels</h2>
      </div>

      <div class="row q-col-gutter-md">
        <div v-for="channel in popularChannels" :key="channel.id" class="col-12 col-sm-6 col-md-3">
          <ChannelCard :channel="channel" :is-favorite="store.favorites.includes(channel.id)" @watch="goToChannel" @favorite="toggleFavorite" />
        </div>
      </div>
    </section>

    <section class="q-mb-xl">
      <div class="row items-center justify-between q-mb-md">
        <h2 class="text-h5 q-my-none">Categories</h2>
      </div>

      <div class="row q-col-gutter-sm">
        <div v-for="category in store.categories" :key="category" class="col-6 col-sm-4 col-md-2">
          <q-btn class="full-width" color="secondary" outline :label="category" @click="router.push({ name: 'channels', query: { category } })" />
        </div>
      </div>
    </section>

    <section class="q-mb-xl">
      <div class="row items-center justify-between q-mb-md">
        <h2 class="text-h5 q-my-none">Recently watched</h2>
      </div>

      <div v-if="store.recentWatched.length" class="row q-col-gutter-md">
        <div v-for="channel in store.recentWatched" :key="channel.id" class="col-12 col-sm-6 col-md-3">
          <ChannelCard :channel="channel" :is-favorite="store.favorites.includes(channel.id)" @watch="goToChannel" @favorite="toggleFavorite" />
        </div>
      </div>
      <q-banner v-else rounded class="bg-dark text-grey-5">Your recent watches will appear here.</q-banner>
    </section>

    <section>
      <div class="row items-center justify-between q-mb-md">
        <h2 class="text-h5 q-my-none">Favorites</h2>
      </div>

      <div v-if="store.favoriteChannels.length" class="row q-col-gutter-md">
        <div v-for="channel in store.favoriteChannels" :key="channel.id" class="col-12 col-sm-6 col-md-3">
          <ChannelCard :channel="channel" :is-favorite="true" @watch="goToChannel" @favorite="toggleFavorite" />
        </div>
      </div>
      <q-banner v-else rounded class="bg-dark text-grey-5">Save a few channels as favorites to build your watchlist.</q-banner>
    </section>
  </q-page>
</template>
