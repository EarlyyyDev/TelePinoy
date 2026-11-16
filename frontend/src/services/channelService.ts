import axios from 'axios'

export interface Channel {
  id: number
  name: string
  slug: string
  category: string
  description: string
  logo_url: string | null
  stream_url: string | null
  is_featured: boolean
  is_active: boolean
  source?: 'imported' | 'custom'
  tested_stream_url?: string | null
  stream_tested_at?: string | null
  created_at?: string
  updated_at?: string
}

export interface ChannelPayload {
  name: string
  slug?: string
  category: string
  description: string
  logo_url?: string | null
  stream_url?: string | null
  is_featured?: boolean
  is_active?: boolean
}

export function getChannels(params: Record<string, string | boolean | undefined> = {}) {
  return axios.get<{ data: Channel[]; meta: { count: number } }>('/channels', { params }).then((response) => response.data)
}

export function getChannelBySlug(slug: string) {
  return axios.get<{ data: Channel }>(`/channels/${slug}`).then((response) => response.data.data)
}

export function recordChannelView(slug: string) {
  return axios.post<{ message: string }>(`/channels/${encodeURIComponent(slug)}/views`).then((response) => response.data)
}
