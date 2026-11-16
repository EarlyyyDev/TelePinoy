<script setup lang="ts">
import type { Channel } from '../services/channelService'

const props = defineProps<{
  channel: Channel
  compact?: boolean
  isFavorite?: boolean
}>()

const emit = defineEmits<{
  (event: 'watch', value: Channel): void
  (event: 'favorite', value: Channel): void
}>()

const fallbackLogo = 'https://images.unsplash.com/photo-1516280440614-37939bbacd81?auto=format&fit=crop&w=900&q=80'
</script>

<template>
  <q-card flat bordered class="channel-card" :class="{ compact: props.compact }">
    <q-img :src="channel.logo_url || fallbackLogo" :ratio="16 / 9" class="channel-card__image">
      <div class="absolute-full channel-card__gradient" />
      <q-badge color="primary" class="q-ma-sm">{{ channel.category }}</q-badge>
    </q-img>

    <q-card-section>
      <div class="row items-center justify-between no-wrap">
        <div class="text-h6 text-weight-bold ellipsis">{{ channel.name }}</div>
        <q-btn
          flat
          round
          dense
          :icon="channel.is_featured ? 'star' : 'star_border'"
          :color="channel.is_featured ? 'amber-4' : 'grey-5'"
          :title="channel.is_featured ? 'Featured channel' : 'Standard channel'"
        />
      </div>
      <p class="channel-card__description q-mb-none">{{ channel.description }}</p>
    </q-card-section>

    <q-card-actions align="between">
      <q-btn color="primary" label="Watch" @click="emit('watch', channel)" />
      <q-btn
        flat
        dense
        :icon="props.isFavorite ? 'favorite' : 'favorite_border'"
        :color="props.isFavorite ? 'pink-4' : 'grey-5'"
        :title="props.isFavorite ? 'Remove from favorites' : 'Add to favorites'"
        @click="emit('favorite', channel)"
      />
    </q-card-actions>
  </q-card>
</template>
