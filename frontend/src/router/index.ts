import { createRouter, createWebHistory } from 'vue-router'
import HomePage from '../pages/HomePage.vue'
import ChannelsPage from '../pages/ChannelsPage.vue'
import ChannelDetailPage from '../pages/ChannelDetailPage.vue'
import VideoPlayerPage from '../pages/VideoPlayerPage.vue'
import AdminPage from '../pages/AdminPage.vue'
import AdminChannelsPage from '../pages/AdminChannelsPage.vue'
import AdminLoginPage from '../pages/AdminLoginPage.vue'
import { useAdminStore } from '../stores/admin'

const routes = [
  {
    path: '/',
    component: HomePage,
    name: 'home',
  },
  {
    path: '/channels',
    component: ChannelsPage,
    name: 'channels',
  },
  {
    path: '/channel/:slug',
    component: ChannelDetailPage,
    name: 'channel-detail',
  },
  {
    path: '/watch/:slug',
    component: VideoPlayerPage,
    name: 'watch',
  },
  {
   path: '/admin/login',
   component: AdminLoginPage,
   name: 'admin-login',
   meta: { guestOnly: true },
 },
  {
    path: '/admin',
    component: AdminPage,
    name: 'admin',
    meta: { requiresAdmin: true },
  },
  {
    path: '/admin/channels',
    component: AdminChannelsPage,
    name: 'admin-channels',
    meta: { requiresAdmin: true },
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach(async (to) => {
  const admin = useAdminStore()

  if (to.meta.requiresAdmin) {
    try {
      if (!await admin.ensureAuthenticated()) {
        return { name: 'admin-login', query: { redirect: to.fullPath } }
      }
    } catch (error) {
      console.error(error)
      return { name: 'admin-login', query: { redirect: to.fullPath, error: 'unavailable' } }
    }
  }

  if (to.meta.guestOnly && admin.isAuthenticated) {
    return { name: 'admin' }
  }

  return true
})

export default router
