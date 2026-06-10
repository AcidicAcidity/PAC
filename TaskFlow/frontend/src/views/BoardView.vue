<template>
  <div class="board-view">
    <div class="header">
      <div class="header-left">
        <h1>{{ t('board.title') }}</h1>
        <div class="funnel-select-wrap">
          <label class="funnel-label" for="funnel-select">{{ t('board.funnel') }}</label>
          <select
            id="funnel-select"
            v-model="selectedFunnelId"
            class="funnel-select"
            @change="loadBoard"
          >
            <option v-for="f in funnels" :key="f.id" :value="f.id">{{ f.name }}</option>
          </select>
        </div>
      </div>
      <button class="btn" @click="openCreate">{{ t('board.newTask') }}</button>
    </div>
    <div v-if="loading" class="loading">{{ t('common.loading') }}</div>
    <KanbanBoard
      v-else
      :columns="columns"
      :tasks="tasks"
      @edit="openEdit"
      @move="handleMove"
      @toggle-active="handleToggleActive"
    />
    <TaskModal
      v-if="showModal"
      :task="editingTask"
      :users="portalUsers"
      :columns="columns"
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
const funnels = ref([])
const selectedFunnelId = ref(null)
const loading = ref(true)

const columns = computed(() => board.columns)
const tasks = computed(() => tasksStore.tasks)

const activeFunnel = computed(() =>
  funnels.value.find((f) => f.id === selectedFunnelId.value)
)

onMounted(async () => {
  try {
    await board.fetchColumns()
    const funnelResult = await api('funnels.list')
    funnels.value = funnelResult.funnels || []
    const mainFunnel = funnels.value.find((f) => f.is_main === true || f.is_main === 't')
    selectedFunnelId.value = mainFunnel?.id ?? funnels.value[0]?.id ?? null
    await loadTasks()
    const result = await api('users.list')
    portalUsers.value = result.users || []
  } finally {
    loading.value = false
  }
})

async function loadTasks() {
  const params = selectedFunnelId.value ? { funnel_id: selectedFunnelId.value } : {}
  await tasksStore.fetchTasks(params)
}

async function loadBoard() {
  loading.value = true
  try {
    await loadTasks()
  } finally {
    loading.value = false
  }
}

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
    const payload = { ...data }
    if (!payload.id && selectedFunnelId.value) {
      payload.funnel_id = selectedFunnelId.value
      if (activeFunnel.value?.collab_id) {
        payload.collab_id = activeFunnel.value.collab_id
      }
    }
    if (payload.id) {
      await tasksStore.updateTask(payload)
    } else {
      await tasksStore.createTask(payload)
    }
    showModal.value = false
    await loadTasks()
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
  try {
    await tasksStore.updateTask({ id: task.id, column_id: columnId })
  } catch (e) {
    alert(e.message)
    await loadTasks()
  }
}

async function handleToggleActive(task) {
  const isActive = task.is_active !== false && task.is_active !== 'f'
  try {
    await tasksStore.updateTask({ id: task.id, is_active: !isActive })
  } catch (e) {
    alert(e.message)
  }
}
</script>

<style scoped>
.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  gap: 1rem;
  flex-wrap: wrap;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 1.25rem;
  flex-wrap: wrap;
}

.funnel-select-wrap {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.funnel-label {
  font-size: 0.85rem;
  color: var(--text-secondary);
  white-space: nowrap;
}

.funnel-select {
  padding: 0.45rem 0.75rem;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  background: var(--bg-secondary);
  color: var(--text-primary);
  min-width: 160px;
}

.loading {
  color: var(--text-secondary);
  padding: 2rem;
  text-align: center;
}
</style>
