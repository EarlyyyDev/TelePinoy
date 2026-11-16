<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import ChannelCard from '../components/ChannelCard.vue'
import type { Channel } from '../services/channelService'
import { useChannelStore } from '../stores/channel'

const store = useChannelStore()
const route = useRoute()
const router = useRouter()
const search = ref('')
const selectedCategory = ref<string>('All')

onMounted(() => {
  store.loadChannels()
  if (route.query.category) {
    selectedCategory.value = String(route.query.category)
  }
  if (route.query.search) {
    search.value = String(route.query.search)
  }
})

const categories = computed(() => ['All', ...store.categories])

const filteredChannels = computed(() => {
  const query = search.value.trim().toLowerCase()

  return store.channels.filter((channel) => {
    const matchesCategory = selectedCategory.value === 'All' || channel.category === selectedCategory.value
    const matchesSearch = !query || channel.name.toLowerCase().includes(query) || channel.description.toLowerCase().includes(query)
    return matchesCategory && matchesSearch
  })
})

function goToChannel(channel: Channel) {
  router.push({ name: 'channel-detail', params: { slug: channel.slug } })
}

function toggleFavorite(channel: { id: number }) {
  store.toggleFavorite(channel.id)
}
</script>

<template>
  <q-page class="q-pa-md">
    <div class="text-h4 text-weight-bold q-mb-lg">Browse channels</div>

    <div class="row q-col-gutter-md q-mb-lg items-center">
      <div class="col-12 col-md-8">
        <q-input v-model="search" outlined rounded dark dense clearable placeholder="Search by channel or description">
          <template #prepend>
            <q-icon name="search" />
          </template>
        </q-input>
      </div>

      <div class="col-12 col-md-4">
        <q-select
          v-model="selectedCategory"
          :options="categories"
          label="Filter by category"
          outlined
          dense
          emit-value
          map-options
          dark
        />
      </div>
    </div>

    <div class="row q-col-gutter-md q-mb-lg">
      <div v-for="category in categories" :key="category" class="col-auto">
        <q-chip
          :color="selectedCategory === category ? 'primary' : 'secondary'"
          :text-color="selectedCategory === category ? 'white' : 'grey-3'"
          clickable
          @click="selectedCategory = category"
        >
          {{ category }}
        </q-chip>
      </div>
    </div>

    <div v-if="store.loading" class="text-center q-pa-xl">
      <q-spinner color="primary" size="3em" />
    </div>

    <div v-else-if="!filteredChannels.length" class="text-center q-pa-xl">
      <div class="text-h6 text-grey-5">No channels match your search.</div>
    </div>

    <div v-else class="row q-col-gutter-md">
      <div v-for="channel in filteredChannels" :key="channel.id" class="col-12 col-sm-6 col-md-4 col-xl-3">
        <ChannelCard :channel="channel" :is-favorite="store.favorites.includes(channel.id)" @watch="goToChannel" @favorite="toggleFavorite" />
      </div>
    </div>
  </q-page>
</template>
