import { defineStore } from 'pinia'
import { ref } from 'vue'
import { api } from '@/api/client'

const THEMES = ['light', 'dark', 'cyberpunk', 'nature']

export const useSettingsStore = defineStore('settings', () => {
  const theme = ref(localStorage.getItem('theme') || 'light')
  const language = ref(localStorage.getItem('language') || 'ru')

  function applyTheme() {
    document.documentElement.setAttribute('data-theme', theme.value)
    THEMES.forEach((t) => {
      const link = document.getElementById(`theme-${t}`)
      if (link) {
        link.disabled = t !== theme.value
      }
    })
    localStorage.setItem('theme', theme.value)
  }

  function initTheme() {
    THEMES.forEach((t) => {
      if (!document.getElementById(`theme-${t}`)) {
        const link = document.createElement('link')
        link.id = `theme-${t}`
        link.rel = 'stylesheet'
        link.href = `/themes/${t}.css`
        link.disabled = t !== theme.value
        document.head.appendChild(link)
      }
    })
    applyTheme()
  }

  async function saveProfile(params) {
    const result = await api('users.updateProfile', params)
    if (params.theme) {
      theme.value = params.theme
      applyTheme()
    }
    if (params.language) {
      language.value = params.language
      localStorage.setItem('language', params.language)
    }
    return result.user
  }

  return { theme, language, applyTheme, initTheme, saveProfile }
})
