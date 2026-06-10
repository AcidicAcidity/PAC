<template>
  <div class="board-view">
    <div class="header">
      <h1>{{ t('board.title') }}</h1>
      <button class="btn" @click="openCreate">{{ t('board.newTask') }}</button>
    </div>
    <div v-if="loading" class="loading">{{ t('common.loading') }}</div>
    <KanbanBoard
      v-else
      :columns="columns"
      :tasks="tasks"
      @edit="openEdit"
      @move="handleMove"
    />
    <TaskModal
      v-if="showModal"
      :task="editingTask"
      :users="portalUsers"
      :default-column-id="columns[0]?.id"
      @close="showModal = false"
      @save="handleSave"
      @delete="handleDelete"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { api } from '@/api/client'
import { useBoardStore } from '@/stores/board'
import { useTasksStore } from '@/stores/tasks'
import KanbanBoard from '@/components/KanbanBoard.vue'
import TaskModal from '@/components/TaskModal.vue'

const { t } = useI18n()
const board = useBoardStore()
const tasksStore = useTasksStore()

const showModal = ref(false)
const editingTask = ref(null)
const portalUsers = ref([])
const loading = ref(true)

const columns = computed(() => board.columns)
const tasks = computed(() => tasksStore.tasks)

onMounted(async () => {
  try {
    await Promise.all([board.fetchColumns(), tasksStore.fetchTasks()])
    const result = await api('users.list')
    portalUsers.value = result.users || []
  } finally {
    loading.value = false
  }
})

function openCreate() {
  editingTask.value = null
  showModal.value = true
}

function openEdit(task) {
  editingTask.value = task
  showModal.value = true
}

async function handleSave(data) {
  try {
    if (data.id) {
      await tasksStore.updateTask(data)
    } else {
      await tasksStore.createTask(data)
    }
    showModal.value = false
  } catch (e) {
    alert(e.message)
  }
}

async function handleDelete(id) {
  if (!confirm('Delete task?')) return
  await tasksStore.deleteTask(id)
  showModal.value = false
}

async function handleMove({ task, columnId }) {
  await tasksStore.updateTask({ id: task.id, column_id: columnId })
}
</script>

<style scoped>
.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.loading {
  color: var(--text-secondary);
  padding: 2rem;
  text-align: center;
}
</style>
