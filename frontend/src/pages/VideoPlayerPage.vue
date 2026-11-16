<script setup lang="ts">
import Hls from 'hls.js'
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useChannelStore } from '../stores/channel'
import { recordChannelView } from '../services/channelService'

const route = useRoute()
const store = useChannelStore()
const videoRef = ref<HTMLVideoElement | null>(null)
const playerError = ref('')
let activeHls: Hls | null = null
let recordedViewSlug = ''

async function loadChannelAndPlay() {
  activeHls?.destroy()
  activeHls = null
  const slug = String(route.params.slug ?? '')
  if (!slug) {
    playerError.value = 'A channel slug is required.'
    return
  }

  await store.loadChannelBySlug(slug)
  recordedViewSlug = ''
  startStream()
}

function recordPlaybackStart() {
  const channel = store.currentChannel
  if (!channel || recordedViewSlug === channel.slug) return

  recordedViewSlug = channel.slug
  store.markRecentlyWatched(channel)
  recordChannelView(channel.slug).catch((error: unknown) => {
    console.error('Unable to record playback start.', error)
  })
}

function startStream() {
  const channel = store.currentChannel
  const videoElement = videoRef.value

  if (!channel || !videoElement) {
    return
  }

  playerError.value = ''

  if (!channel.stream_url) {
    playerError.value = 'This channel does not have a stream URL configured.'
    return
  }

  videoElement.addEventListener('playing', recordPlaybackStart)

  if (Hls.isSupported()) {
    activeHls = new Hls()
    activeHls.loadSource(channel.stream_url)
    activeHls.attachMedia(videoElement)
    activeHls.on(Hls.Events.ERROR, (_, data) => {
      if (data.fatal) {
        playerError.value = 'The live stream could not be loaded.'
      }
    })
  } else if (videoElement.canPlayType('application/vnd.apple.mpegurl')) {
    videoElement.src = channel.stream_url
  } else {
    playerError.value = 'Your browser does not support HLS streaming.'
  }
}

onMounted(() => {
  loadChannelAndPlay()
})

onBeforeUnmount(() => {
  activeHls?.destroy()
  activeHls = null
  videoRef.value?.removeEventListener('playing', recordPlaybackStart)
})

watch(
  () => route.params.slug,
  () => {
    loadChannelAndPlay()
  },
)
</script>

<template>
  <q-page class="q-pa-md">
    <div v-if="store.loading" class="text-center q-pa-xl">
      <q-spinner color="primary" size="3em" />
    </div>

    <div v-else-if="store.error || !store.currentChannel" class="text-center q-pa-xl text-negative">
      {{ store.error || 'Channel not found.' }}
    </div>

    <div v-else class="row q-col-gutter-xl">
      <div class="col-12 col-lg-8">
        <q-card flat class="bg-dark-2 q-pa-sm stream-card">
          <video ref="videoRef" controls playsinline class="stream-player" />
          <div v-if="playerError" class="text-negative q-pa-md">{{ playerError }}</div>
        </q-card>
      </div>

      <div class="col-12 col-lg-4">
        <q-card flat bordered class="bg-dark-2 q-pa-md">
          <div class="row items-center q-col-gutter-sm q-mb-md">
            <div class="col-auto">
              <q-img :src="store.currentChannel.logo_url || 'https://images.unsplash.com/photo-1516280440614-37939bbacd81?auto=format&fit=crop&w=900&q=80'" spinner-color="white" style="width: 72px; height: 72px; border-radius: 12px;" />
            </div>
            <div class="col">
              <div class="text-h6 text-weight-bold">{{ store.currentChannel.name }}</div>
              <div class="text-grey-5">{{ store.currentChannel.category }}</div>
            </div>
          </div>

          <p class="text-grey-5 q-mb-none">{{ store.currentChannel.description }}</p>
        </q-card>
      </div>
    </div>
  </q-page>
</template>
