import { defineStore } from 'pinia'
import { ref } from 'vue'
import { api } from '@/api/client'

export const useTasksStore = defineStore('tasks', () => {
  const tasks = ref([])
  const loading = ref(false)

  async function fetchTasks(params = {}) {
    loading.value = true
    try {
      const result = await api('tasks.list', params)
      tasks.value = result.tasks || []
    } finally {
      loading.value = false
    }
  }

  async function createTask(params) {
    const result = await api('tasks.create', params)
    tasks.value.push(result.task)
    return result.task
  }

  async function updateTask(params) {
    const result = await api('tasks.update', params)
    const idx = tasks.value.findIndex((t) => t.id === result.task.id)
    if (idx !== -1) tasks.value[idx] = result.task
    return result.task
  }

  async function deleteTask(id) {
    await api('tasks.delete', { id })
    tasks.value = tasks.value.filter((t) => t.id !== id)
  }

  return { tasks, loading, fetchTasks, createTask, updateTask, deleteTask }
})
