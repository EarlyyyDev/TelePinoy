<script setup lang="ts">
import { computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useChannelStore } from '../stores/channel'

const route = useRoute()
const router = useRouter()
const store = useChannelStore()

const slug = computed(() => String(route.params.slug ?? ''))

async function loadChannel() {
  if (slug.value) {
    await store.loadChannelBySlug(slug.value)
  }
}

onMounted(() => {
  loadChannel()
})

watch(slug, () => {
  loadChannel()
})

function toggleFavorite() {
  if (store.currentChannel) {
    store.toggleFavorite(store.currentChannel.id)
  }
}
</script>

<template>
  <q-page class="q-pa-md">
    <div v-if="store.loading" class="text-center q-pa-xl">
      <q-spinner color="primary" size="3em" />
    </div>

    <div v-else-if="store.error || !store.currentChannel" class="text-center q-pa-xl text-negative">
      {{ store.error || 'Channel not found.' }}
    </div>

    <div v-else class="row q-col-gutter-xl q-mt-md">
      <div class="col-12 col-md-5">
        <q-img :src="store.currentChannel.logo_url || 'https://images.unsplash.com/photo-1516280440614-37939bbacd81?auto=format&fit=crop&w=900&q=80'" :ratio="4 / 5" class="channel-detail__logo" />
      </div>

      <div class="col-12 col-md-7">
        <q-badge color="primary" class="q-mb-md">{{ store.currentChannel.category }}</q-badge>
        <div class="text-h3 text-weight-bold q-mb-md">{{ store.currentChannel.name }}</div>
        <p class="text-body1 text-grey-5 q-mb-lg">{{ store.currentChannel.description }}</p>

        <div class="row q-col-gutter-sm q-mb-lg">
          <div class="col-auto">
            <q-btn color="primary" size="lg" icon="play_arrow" label="Watch Live" @click="router.push({ name: 'watch', params: { slug: store.currentChannel.slug } })" />
          </div>
          <div class="col-auto">
            <q-btn outline color="primary" size="lg" :icon="store.favorites.includes(store.currentChannel.id) ? 'favorite' : 'favorite_border'" :label="store.favorites.includes(store.currentChannel.id) ? 'Saved' : 'Add to Favorites'" @click="toggleFavorite" />
          </div>
        </div>

        <q-separator dark class="q-mb-lg" />

        <div class="text-h6 q-mb-sm">About this channel</div>
        <p class="text-grey-5 q-mb-none">
          This channel is part of the TelePinoy catalog and is ready to stream through the built-in video player.
        </p>
      </div>
    </div>
  </q-page>
</template>
