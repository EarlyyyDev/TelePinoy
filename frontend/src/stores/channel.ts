import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import { getChannelBySlug, getChannels, type Channel } from '../services/channelService'

const FAVORITES_KEY = 'telepinoy-favorites'
const RECENT_KEY = 'telepinoy-recent'

function readStorage<T>(key: string, fallback: T): T {
  if (typeof window === 'undefined') {
    return fallback
  }

  const raw = window.localStorage.getItem(key)
  if (!raw) {
    return fallback
  }

  try {
    return JSON.parse(raw) as T
  } catch (error) {
    return fallback
  }
}

export const useChannelStore = defineStore('channel', () => {
  const channels = ref<Channel[]>([])
  const currentChannel = ref<Channel | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)
  const favorites = ref<number[]>(readStorage<number[]>(FAVORITES_KEY, []))
  const recentChannels = ref<number[]>(readStorage<number[]>(RECENT_KEY, []))

  const featuredChannels = computed(() => channels.value.filter((channel) => channel.is_featured).slice(0, 4))
  const categories = computed(() => Array.from(new Set(channels.value.map((channel) => channel.category))))
  const favoriteChannels = computed(() => channels.value.filter((channel) => favorites.value.includes(channel.id)))
  const recentWatched = computed(() => {
    const byId = new Map(channels.value.map((channel) => [channel.id, channel]))
    return recentChannels.value
      .map((id) => byId.get(id))
      .filter((channel): channel is Channel => Boolean(channel))
      .slice(0, 4)
  })

  function persistFavoriteState() {
    if (typeof window !== 'undefined') {
      window.localStorage.setItem(FAVORITES_KEY, JSON.stringify(favorites.value))
    }
  }

  function persistRecentState() {
    if (typeof window !== 'undefined') {
      window.localStorage.setItem(RECENT_KEY, JSON.stringify(recentChannels.value))
    }
  }

  async function loadChannels(params: Record<string, string | boolean | undefined> = {}) {
    loading.value = true
    error.value = null

    try {
      const response = await getChannels(params)
      channels.value = response.data
    } catch (loadError) {
      error.value = 'Unable to load TelePinoy channels right now.'
      console.error(loadError)
    } finally {
      loading.value = false
    }
  }

  async function loadChannelBySlug(slug: string) {
    loading.value = true
    error.value = null

    try {
      currentChannel.value = await getChannelBySlug(slug)
    } catch (loadError) {
      currentChannel.value = null
      error.value = 'Channel details could not be loaded.'
      console.error(loadError)
    } finally {
      loading.value = false
    }
  }

  function toggleFavorite(channelId: number) {
    if (favorites.value.includes(channelId)) {
      favorites.value = favorites.value.filter((id) => id !== channelId)
    } else {
      favorites.value = [channelId, ...favorites.value]
    }

    persistFavoriteState()
  }

  function markRecentlyWatched(channel: Channel) {
    recentChannels.value = [channel.id, ...recentChannels.value.filter((id) => id !== channel.id)].slice(0, 5)
    persistRecentState()
  }

  return {
    channels,
    currentChannel,
    featuredChannels,
    categories,
    favoriteChannels,
    recentWatched,
    favorites,
    loading,
    error,
    loadChannels,
    loadChannelBySlug,
    toggleFavorite,
    markRecentlyWatched,
  }
})
