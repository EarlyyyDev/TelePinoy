import axios from 'axios'
import type { Channel, ChannelPayload } from './channelService'

export interface AdminUser {
  id: number
  name: string
  email: string
}

export interface AdminDashboardStats {
  total_channels: number
  live_channels: number
  imported_channels: number
  custom_drafts: number
  viewer_starts_this_month: number
}

export function getAdminDashboard() {
  return axios.get<{ data: AdminDashboardStats }>('/admin/dashboard').then((response) => response.data.data)
}

export function loginAdmin(email: string, password: string) {
  return axios.post<{ token: string; user: AdminUser }>('/admin/login', { email, password }).then((response) => response.data)
}

export function getAdminUser() {
  return axios.get<{ user: AdminUser }>('/admin/user').then((response) => response.data.user)
}

export function logoutAdmin() {
  return axios.post<{ message: string }>('/admin/logout').then((response) => response.data)
}

export function getAdminChannels() {
  return axios.get<{ data: Channel[] }>('/admin/channels').then((response) => response.data.data)
}

export function importIptvOrgChannels(url: string) {
  return axios.post<{ created: number; updated: number; skipped: number; message: string }>('/admin/channels/import-iptv-org', { url })
    .then((response) => response.data)
}

export function markChannelTested(slug: string) {
  return axios.post<{ data: Channel }>(`/admin/channels/${encodeURIComponent(slug)}/test-success`)
    .then((response) => response.data.data)
}

export function setChannelPlayable(slug: string, isActive: boolean) {
  return axios.put<{ data: Channel }>(`/admin/channels/${encodeURIComponent(slug)}/playability`, { is_active: isActive })
    .then((response) => response.data.data)
}

export function createChannel(payload: ChannelPayload) {
  return axios.post<{ data: Channel }>('/admin/channels', payload).then((response) => response.data.data)
}

export function updateChannel(slug: string, payload: Partial<ChannelPayload>) {
  return axios.put<{ data: Channel }>(`/admin/channels/${encodeURIComponent(slug)}`, payload).then((response) => response.data.data)
}

export function deleteChannel(slug: string) {
  return axios.delete<{ message: string }>(`/admin/channels/${encodeURIComponent(slug)}`).then((response) => response.data)
}
