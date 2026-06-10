import { defineStore } from 'pinia'
import { ref } from 'vue'
import { api } from '@/api/client'

export const useBoardStore = defineStore('board', () => {
  const columns = ref([])
  const loading = ref(false)

  async function fetchColumns() {
    loading.value = true
    try {
      const result = await api('board.columns.list', {})
      columns.value = result.columns || []
    } finally {
      loading.value = false
    }
  }

  return { columns, loading, fetchColumns }
})
