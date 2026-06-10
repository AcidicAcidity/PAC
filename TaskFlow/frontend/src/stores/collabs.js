import { defineStore } from 'pinia'
import { ref } from 'vue'
import { api } from '@/api/client'

export const useCollabsStore = defineStore('collabs', () => {
  const collabs = ref([])
  const messages = ref([])
  const loading = ref(false)

  async function fetchCollabs() {
    loading.value = true
    try {
      const result = await api('collabs.list')
      collabs.value = result.collabs || []
    } finally {
      loading.value = false
    }
  }

  async function createCollab(params) {
    const result = await api('collabs.create', params)
    await fetchCollabs()
    return result.collab_id
  }

  async function fetchMessages(collabId) {
    const result = await api('collabs.getMessages', { collab_id: collabId })
    messages.value = result.messages || []
  }

  async function sendMessage(collabId, content) {
    await api('messages.send', { collab_id: collabId, content })
  }

  return { collabs, messages, loading, fetchCollabs, createCollab, fetchMessages, sendMessage }
})
