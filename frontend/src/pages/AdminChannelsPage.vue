<script setup lang="ts">
import { computed, nextTick, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import Hls from 'hls.js'
import {
  createChannel,
  deleteChannel,
  getAdminChannels,
  importIptvOrgChannels,
  markChannelTested,
  setChannelPlayable,
  updateChannel,
} from '../services/adminService'
import type { Channel, ChannelPayload } from '../services/channelService'

type ChannelTab = 'live' | 'imported' | 'custom'

const route = useRoute()
const router = useRouter()
const channels = ref<Channel[]>([])
const currentTab = ref<ChannelTab>('live')
const editingChannel = ref<Channel | null>(null)
const form = ref<ChannelPayload>(emptyForm())
const loading = ref(false)
const saving = ref(false)
const importing = ref(false)
const importDialog = ref(false)
const playlistUrl = ref('')
const previewDialog = ref(false)
const previewChannel = ref<Channel | null>(null)
const videoRef = ref<HTMLVideoElement | null>(null)
const previewStatus = ref('')
const previewError = ref('')
const publishingSlug = ref<string | null>(null)
const formErrors = ref<Record<string, string>>({})
const requestError = ref('')
const notice = ref('')
const search = ref('')
let previewHls: Hls | null = null
let testReported = false

function emptyForm(): ChannelPayload {
  return {
    name: '',
    category: 'General',
    description: '',
    logo_url: '',
    stream_url: '',
    is_featured: false,
  }
}

const liveChannels = computed(() => channels.value.filter((channel) => channel.is_active))
const importedChannels = computed(() => channels.value.filter((channel) => channel.source === 'imported' && !channel.is_active))
const customChannels = computed(() => channels.value.filter((channel) => channel.source !== 'imported' && !channel.is_active))
const visibleChannels = computed(() => {
  const source = currentTab.value === 'live'
    ? liveChannels.value
    : currentTab.value === 'imported'
      ? importedChannels.value
      : customChannels.value
  const query = search.value.trim().toLowerCase()

  if (!query) return source
  return source.filter((channel) => channel.name.toLowerCase().includes(query)
    || channel.category.toLowerCase().includes(query)
    || channel.description.toLowerCase().includes(query))
})

onMounted(async () => {
  await loadChannels()
  await syncTabFromRoute()
})

watch(() => route.query.tab, syncTabFromRoute)

async function syncTabFromRoute() {
  const requestedTab = route.query.tab
  if (requestedTab === 'live' || requestedTab === 'imported' || requestedTab === 'custom') {
    currentTab.value = requestedTab
  }
}

async function changeTab(tab: ChannelTab) {
  currentTab.value = tab
  await router.replace({ name: 'admin-channels', query: { tab } })
}

async function loadChannels() {
  loading.value = true
  requestError.value = ''
  try {
    channels.value = await getAdminChannels()
  } catch (error) {
    requestError.value = 'Unable to load channels. Please sign in again and retry.'
    console.error(error)
  } finally {
    loading.value = false
  }
}

function resetForm() {
  formErrors.value = {}
  requestError.value = ''
  form.value = emptyForm()
  editingChannel.value = null
}

function populateForm(channel: Channel) {
  formErrors.value = {}
  requestError.value = ''
  notice.value = ''
  currentTab.value = channel.source === 'imported' ? 'imported' : 'custom'
  form.value = {
    name: channel.name,
    slug: channel.slug,
    category: channel.category,
    description: channel.description,
    logo_url: channel.logo_url ?? '',
    stream_url: channel.stream_url ?? '',
    is_featured: channel.is_featured,
  }
  editingChannel.value = channel
}

async function submitForm() {
  saving.value = true
  formErrors.value = {}
  requestError.value = ''
  notice.value = ''

  const payload: ChannelPayload = {
    ...form.value,
    slug: form.value.slug?.trim() || undefined,
    logo_url: form.value.logo_url?.trim() || null,
    stream_url: form.value.stream_url?.trim() || null,
  }

  try {
    if (editingChannel.value) {
      await updateChannel(editingChannel.value.slug, payload)
      notice.value = 'Channel saved. Test its stream again if the URL changed, then publish it.'
    } else {
      await createChannel(payload)
      notice.value = 'Custom channel created as an unpublished draft. Preview and publish it when ready.'
    }

    resetForm()
    currentTab.value = 'custom'
    await loadChannels()
  } catch (error) {
    if (axios.isAxiosError<{ errors?: Record<string, string[]>; message?: string }>(error)) {
      const errors = error.response?.data?.errors
      if (errors) {
        formErrors.value = Object.fromEntries(
          Object.entries(errors).map(([field, messages]) => [field, messages[0] ?? 'Invalid value.']),
        )
        requestError.value = 'Please correct the highlighted fields.'
      } else {
        requestError.value = error.response?.data?.message ?? 'The channel could not be saved. Please try again.'
      }
    } else {
      requestError.value = 'The channel could not be saved. Please try again.'
      console.error(error)
    }
  } finally {
    saving.value = false
  }
}

function openImportDialog() {
  playlistUrl.value = ''
  importDialog.value = true
}

async function importPlaylist() {
  importing.value = true
  requestError.value = ''
  notice.value = ''

  try {
    const result = await importIptvOrgChannels(playlistUrl.value.trim())
    notice.value = `${result.message} Added ${result.created}, updated ${result.updated}, skipped ${result.skipped}.`
    importDialog.value = false
    await loadChannels()
    await changeTab('imported')
  } catch (error) {
    requestError.value = axios.isAxiosError<{ errors?: Record<string, string[]>; message?: string }>(error)
      ? Object.values(error.response?.data?.errors ?? {}).flat()[0] ?? error.response?.data?.message ?? 'Playlist import failed.'
      : 'Playlist import failed. Please try again.'
    console.error(error)
  } finally {
    importing.value = false
  }
}

function isTested(channel: Channel) {
  return Boolean(channel.stream_tested_at && channel.tested_stream_url === channel.stream_url)
}

async function openPreview(channel: Channel) {
  previewChannel.value = channel
  previewStatus.value = ''
  previewError.value = ''
  testReported = false
  previewDialog.value = true
}

async function startPreview() {
  await nextTick()
  previewHls?.destroy()
  previewHls = null
  const channel = previewChannel.value
  const video = videoRef.value
  if (!channel?.stream_url || !video) return

  previewStatus.value = 'Connecting to the stream…'
  previewError.value = ''

  const onLoaded = () => {
    void reportTestSuccess(channel)
  }
  video.addEventListener('loadeddata', onLoaded, { once: true })
  video.addEventListener('error', () => {
    previewStatus.value = ''
    previewError.value = 'Playback failed. Check the URL, stream availability, and provider playback/CORS restrictions.'
  }, { once: true })

  if (Hls.isSupported()) {
    previewHls = new Hls()
    previewHls.on(Hls.Events.FRAG_BUFFERED, () => {
      void reportTestSuccess(channel)
    })
    previewHls.on(Hls.Events.ERROR, (_event, data) => {
      if (data.fatal) {
        previewStatus.value = ''
        previewError.value = `Playback test failed: ${data.details}`
      }
    })
    previewHls.loadSource(channel.stream_url)
    previewHls.attachMedia(video)
  } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
    video.src = channel.stream_url
    try {
      await video.play()
    } catch {
      previewStatus.value = 'Press Play to start the preview and test this stream.'
    }
  } else {
    previewStatus.value = ''
    previewError.value = 'This browser does not support HLS playback.'
  }
}

async function reportTestSuccess(channel: Channel) {
  if (testReported) return
  testReported = true
  try {
    const tested = await markChannelTested(channel.slug)
    channels.value = channels.value.map((item) => item.id === tested.id ? tested : item)
    if (previewChannel.value?.id === tested.id) {
      previewChannel.value = tested
    }
    previewStatus.value = 'Stream playback verified. You can publish this channel now.'
  } catch (error) {
    previewError.value = axios.isAxiosError<{ message?: string }>(error)
      ? error.response?.data?.message ?? 'The playback check succeeded, but the result could not be saved.'
      : 'The playback check succeeded, but the result could not be saved.'
    console.error(error)
  }
}

function closePreview() {
  previewHls?.destroy()
  previewHls = null
  if (videoRef.value) {
    videoRef.value.pause()
    videoRef.value.removeAttribute('src')
    videoRef.value.load()
  }
  previewDialog.value = false
}

async function publishChannel(channel: Channel, isActive: boolean) {
  publishingSlug.value = channel.slug
  requestError.value = ''
  notice.value = ''
  try {
    const saved = await setChannelPlayable(channel.slug, isActive)
    channels.value = channels.value.map((item) => item.id === saved.id ? saved : item)
    notice.value = isActive ? `${channel.name} is now published in Live.` : `${channel.name} has been unpublished.`
  } catch (error) {
    requestError.value = axios.isAxiosError<{ message?: string }>(error)
      ? error.response?.data?.message ?? 'The channel could not be published.'
      : 'The channel could not be published.'
  } finally {
    publishingSlug.value = null
  }
}

async function removeChannel(channel: Channel) {
  requestError.value = ''
  notice.value = ''
  try {
    await deleteChannel(channel.slug)
    await loadChannels()
    if (editingChannel.value?.id === channel.id) resetForm()
  } catch (error) {
    requestError.value = 'The channel could not be deleted. Please try again.'
    console.error(error)
  }
}

watch(previewDialog, (isOpen) => {
  if (!isOpen) {
    previewHls?.destroy()
    previewHls = null
  }
})
</script>

<template>
  <q-page class="q-pa-md q-pb-xl">
    <div class="row items-center justify-between q-mb-lg">
      <div>
        <div class="text-h4 text-weight-bold">Channels</div>
        <div class="text-grey-5">Import, test, and publish channels for viewers.</div>
      </div>
      <div class="row q-gutter-sm">
        <q-btn outline color="primary" icon="playlist_add" label="Import playlist" @click="openImportDialog" />
        <q-btn color="primary" icon="add" label="Create channel" @click="changeTab('custom')" />
      </div>
    </div>

    <q-banner v-if="requestError" rounded class="bg-negative text-white q-mb-md">{{ requestError }}</q-banner>
    <q-banner v-if="notice" rounded class="bg-positive text-white q-mb-md">{{ notice }}</q-banner>

    <q-tabs
      :model-value="currentTab"
      active-color="primary"
      indicator-color="primary"
      align="left"
      class="q-mb-md"
      @update:model-value="changeTab"
    >
      <q-tab name="live" icon="live_tv" :label="`Live (${liveChannels.length})`" />
      <q-tab name="imported" icon="playlist_add" :label="`Imported (${importedChannels.length})`" />
      <q-tab name="custom" icon="library_add" :label="`Custom (${customChannels.length})`" />
    </q-tabs>

    <div v-if="currentTab === 'custom'" class="row q-col-gutter-lg q-mb-lg">
      <div class="col-12 col-lg-5">
        <q-card flat class="bg-dark-2 q-pa-lg">
          <div class="text-h5 text-weight-bold q-mb-md">{{ editingChannel ? 'Edit custom channel' : 'Create a custom channel' }}</div>
          <q-form class="q-gutter-md" @submit.prevent="submitForm">
            <q-input v-model="form.name" outlined label="Channel name" dense :error="!!formErrors.name" :error-message="formErrors.name" />
            <q-input v-model="form.slug" outlined label="Slug" dense hint="Optional; generated from the name if blank" :error="!!formErrors.slug" :error-message="formErrors.slug" />
            <q-input v-model="form.category" outlined label="Category" dense :error="!!formErrors.category" :error-message="formErrors.category" />
            <q-input v-model="form.description" outlined type="textarea" label="Description" autogrow :error="!!formErrors.description" :error-message="formErrors.description" />
            <q-input v-model="form.logo_url" outlined label="Logo URL (optional)" dense :error="!!formErrors.logo_url" :error-message="formErrors.logo_url" />
            <q-input v-model="form.stream_url" outlined label="Stream URL (.m3u8)" dense :error="!!formErrors.stream_url" :error-message="formErrors.stream_url" />
            <q-toggle v-model="form.is_featured" label="Featured" />
            <div class="row q-gutter-sm">
              <q-btn type="submit" color="primary" :loading="saving" :label="editingChannel ? 'Save changes' : 'Create draft'" />
              <q-btn v-if="editingChannel" flat label="Cancel" @click="resetForm" />
            </div>
          </q-form>
        </q-card>
      </div>
      <div class="col-12 col-lg-7">
        <q-card flat class="bg-dark-2 q-pa-lg">
          <div class="text-h6 text-weight-bold q-mb-md">Unpublished custom channels</div>
          <div v-if="!customChannels.length" class="text-grey-5">Create a channel using the form. It will remain unpublished until tested.</div>
          <q-list v-else separator>
            <q-item v-for="channel in customChannels" :key="channel.id">
              <q-item-section>
                <q-item-label class="text-weight-bold">{{ channel.name }}</q-item-label>
                <q-item-label caption>{{ channel.category }}</q-item-label>
              </q-item-section>
              <q-item-section side>
                <div class="row q-gutter-xs">
                  <q-btn flat round dense icon="play_circle" color="positive" :disable="!channel.stream_url" title="Preview stream" @click="openPreview(channel)" />
                  <q-btn flat round dense icon="edit" color="primary" title="Edit" @click="populateForm(channel)" />
                  <q-btn flat round dense icon="delete" color="negative" title="Delete" @click="removeChannel(channel)" />
                </div>
              </q-item-section>
            </q-item>
          </q-list>
        </q-card>
      </div>
    </div>

    <div v-else class="row items-center q-col-gutter-md q-mb-md">
      <div class="col-12 col-md-6">
        <q-input v-model="search" outlined dense clearable placeholder="Search channels">
          <template #prepend><q-icon name="search" /></template>
        </q-input>
      </div>
    </div>

    <div v-if="currentTab !== 'custom'">
      <div v-if="loading" class="text-center q-pa-xl"><q-spinner color="primary" size="3em" /></div>
      <q-card v-else-if="!visibleChannels.length" flat class="bg-dark-2 q-pa-xl text-center text-grey-5">
        <q-icon :name="currentTab === 'live' ? 'live_tv' : 'playlist_add'" size="42px" class="q-mb-sm" />
        <div class="text-h6">{{ currentTab === 'live' ? 'No channels are live yet' : 'No imported drafts yet' }}</div>
        <div>{{ currentTab === 'live' ? 'Test and publish channels from Imported or Custom.' : 'Use Import playlist to bring in an M3U playlist.' }}</div>
      </q-card>
      <q-list v-else bordered separator class="bg-dark-2 rounded-borders">
        <q-item v-for="channel in visibleChannels" :key="channel.id" class="q-py-md">
          <q-item-section avatar>
            <q-avatar rounded size="64px" color="blue-grey-9">
              <img v-if="channel.logo_url" :src="channel.logo_url" :alt="`${channel.name} logo`" />
              <q-icon v-else name="live_tv" />
            </q-avatar>
          </q-item-section>
          <q-item-section>
            <q-item-label class="text-weight-bold">{{ channel.name }}</q-item-label>
            <q-item-label caption>{{ channel.category }} · {{ channel.source === 'imported' ? 'Imported' : 'Custom' }}</q-item-label>
            <q-item-label caption :class="channel.is_active ? 'text-positive' : 'text-warning'">
              {{ channel.is_active ? 'Published in Live' : isTested(channel) ? 'Tested · ready to publish' : 'Unpublished · playback test required' }}
            </q-item-label>
            <q-item-label v-if="channel.stream_url" caption class="ellipsis">{{ channel.stream_url }}</q-item-label>
          </q-item-section>
          <q-item-section side>
            <div class="row q-gutter-xs">
              <q-btn v-if="!channel.is_active" flat round dense icon="play_circle" color="positive" title="Preview and test before publishing" :disable="!channel.stream_url" @click="openPreview(channel)" />
              <q-btn
                v-if="!channel.is_active && isTested(channel)"
                flat
                round
                dense
                icon="publish"
                color="positive"
                title="Publish to Live"
                :loading="publishingSlug === channel.slug"
                @click="publishChannel(channel, true)"
              />
              <q-btn v-if="channel.is_active" flat round dense icon="visibility_off" color="warning" title="Unpublish channel" :loading="publishingSlug === channel.slug" @click="publishChannel(channel, false)" />
              <q-btn flat round dense icon="edit" color="primary" title="Edit channel" @click="populateForm(channel); changeTab('custom')" />
              <q-btn flat round dense icon="delete" color="negative" title="Delete channel" @click="removeChannel(channel)" />
            </div>
          </q-item-section>
        </q-item>
      </q-list>
    </div>

    <q-dialog v-model="importDialog">
      <q-card class="bg-dark-2 q-pa-md import-dialog">
        <q-card-section>
          <div class="text-h6 text-weight-bold">Import an M3U playlist</div>
          <div class="text-caption text-grey-5 q-mt-sm">Paste an HTTPS playlist URL. Imported channels go to Imported and remain unpublished.</div>
        </q-card-section>
        <q-card-section class="q-pt-none">
          <q-input v-model="playlistUrl" outlined autofocus label="Playlist URL" placeholder="https://iptv-org.github.io/iptv/countries/ph.m3u" />
          <div class="text-caption text-grey-6 q-mt-sm">Approved hosts are configured in backend .env with TELEPINOY_PLAYLIST_HOSTS.</div>
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat label="Cancel" v-close-popup />
          <q-btn color="primary" label="Import playlist" :loading="importing" :disable="!playlistUrl.trim()" @click="importPlaylist" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <q-dialog v-model="previewDialog" persistent @show="startPreview" @hide="closePreview">
      <q-card class="bg-dark-2 q-pa-md preview-dialog">
        <q-card-section class="row items-center justify-between">
          <div>
            <div class="text-h6 text-weight-bold">Stream preview</div>
            <div class="text-caption text-grey-5">{{ previewChannel?.name }}</div>
          </div>
          <q-btn flat round icon="close" color="grey-4" class="preview-dialog__close" aria-label="Close preview" @click="closePreview" />
        </q-card-section>
        <q-card-section>
          <video ref="videoRef" controls autoplay muted playsinline class="admin-preview-video" />
          <div v-if="previewStatus" class="text-positive q-mt-sm">{{ previewStatus }}</div>
          <div v-if="previewError" class="text-negative q-mt-sm">{{ previewError }}</div>
          <div class="text-caption text-grey-5 q-mt-sm">The channel remains unpublished until you select Publish to Live.</div>
        </q-card-section>
        <q-card-actions align="right">
          <q-btn
            v-if="previewChannel && isTested(previewChannel) && !previewChannel.is_active"
            color="positive"
            icon="publish"
            label="Publish to Live"
            :loading="publishingSlug === previewChannel.slug"
            @click="publishChannel(previewChannel, true); closePreview()"
          />
          <q-btn flat label="Close" @click="closePreview" />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<style scoped>
.import-dialog {
  width: min(94vw, 560px);
  border-radius: 18px;
}

.preview-dialog {
  width: min(94vw, 900px);
  border-radius: 18px;
}

.admin-preview-video {
  width: 100%;
  max-height: 65vh;
  min-height: 220px;
  background: #000;
  border-radius: 12px;
}
</style>
