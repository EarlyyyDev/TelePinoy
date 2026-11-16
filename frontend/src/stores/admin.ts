import { computed, ref } from 'vue'
import axios from 'axios'
import { defineStore } from 'pinia'
import { getAdminUser, loginAdmin, logoutAdmin, type AdminUser } from '../services/adminService'

const TOKEN_KEY = 'telepinoy-admin-token'

function storedToken() {
  return typeof window === 'undefined' ? null : window.sessionStorage.getItem(TOKEN_KEY)
}

export const useAdminStore = defineStore('admin', () => {
  const token = ref<string | null>(storedToken())
  const user = ref<AdminUser | null>(null)
  const isAuthenticated = computed(() => token.value !== null)

  function applyToken(value: string | null) {
    token.value = value
    if (typeof window !== 'undefined') {
      if (value) {
        window.sessionStorage.setItem(TOKEN_KEY, value)
      } else {
        window.sessionStorage.removeItem(TOKEN_KEY)
      }
    }

    if (value) {
      axios.defaults.headers.common.Authorization = `Bearer ${value}`
    } else {
      delete axios.defaults.headers.common.Authorization
    }
  }

  if (token.value) {
    applyToken(token.value)
  }

  async function login(email: string, password: string) {
    const response = await loginAdmin(email, password)
    applyToken(response.token)
    user.value = response.user
  }

  async function ensureAuthenticated() {
    if (!token.value) {
      return false
    }

    try {
      user.value = await getAdminUser()
      return true
    } catch (error) {
      applyToken(null)
      user.value = null
      if (axios.isAxiosError(error) && error.response?.status !== 401) {
        throw error
      }

      return false
    }
  }

  async function logout() {
    try {
      if (token.value) {
        await logoutAdmin()
      }
    } finally {
      applyToken(null)
      user.value = null
    }
  }

  return { token, user, isAuthenticated, login, ensureAuthenticated, logout }
})
