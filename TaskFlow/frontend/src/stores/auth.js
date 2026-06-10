import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { api } from '@/api/client'
import { useSettingsStore } from './settings'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(JSON.parse(localStorage.getItem('user') || 'null'))
  const accessToken = ref(localStorage.getItem('access_token') || '')
  const refreshToken = ref(localStorage.getItem('refresh_token') || '')

  const isAuthenticated = computed(() => !!accessToken.value)
  const isAdmin = computed(() => user.value?.role === 'admin')

  function persist() {
    if (user.value) {
      localStorage.setItem('user', JSON.stringify(user.value))
    } else {
      localStorage.removeItem('user')
    }
    if (accessToken.value) {
      localStorage.setItem('access_token', accessToken.value)
    } else {
      localStorage.removeItem('access_token')
    }
    if (refreshToken.value) {
      localStorage.setItem('refresh_token', refreshToken.value)
    } else {
      localStorage.removeItem('refresh_token')
    }
  }

  function setSession(result) {
    accessToken.value = result.access_token
    if (result.refresh_token) {
      refreshToken.value = result.refresh_token
    }
    user.value = result.user
    persist()

    const settings = useSettingsStore()
    if (result.user?.theme) settings.theme = result.user.theme
    if (result.user?.language) settings.language = result.user.language
    settings.applyTheme()
  }

  async function register(params) {
    return api('auth.register', params)
  }

  async function verify(email, code) {
    const result = await api('auth.verify', { email, code })
    setSession(result)
    return result
  }

  async function login(email, password) {
    const result = await api('auth.login', { email, password })
    setSession(result)
    return result
  }

  async function refresh() {
    if (!refreshToken.value) return false
    try {
      const result = await api('auth.refresh', { refresh_token: refreshToken.value })
      accessToken.value = result.access_token
      persist()
      return true
    } catch {
      logout()
      return false
    }
  }

  async function fetchMe() {
    const result = await api('users.me')
    user.value = result.user
    persist()
    return result.user
  }

  function logout() {
    user.value = null
    accessToken.value = ''
    refreshToken.value = ''
    persist()
  }

  return {
    user,
    accessToken,
    isAuthenticated,
    isAdmin,
    register,
    verify,
    login,
    refresh,
    fetchMe,
    logout,
    setSession,
  }
})
