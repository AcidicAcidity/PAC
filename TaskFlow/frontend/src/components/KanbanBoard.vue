<template>
  <div class="kanban">
    <div
      v-for="(column, idx) in columns"
      :key="column.id"
      class="kanban-column animate-in"
      :style="{ animationDelay: `${idx * 0.06}s` }"
      @dragover.prevent
      @drop="onDrop($event, column.id)"
    >
      <div class="column-header" :style="{ borderTopColor: column.color }">
        <h3>{{ column.title }}</h3>
        <span class="count">{{ tasksInColumn(column.id).length }}</span>
      </div>
      <div class="column-body">
        <TaskCard
          v-for="task in tasksInColumn(column.id)"
          :key="task.id"
          :task="task"
          draggable="true"
          @dragstart="onDragStart($event, task)"
          @click="$emit('edit', task)"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import TaskCard from './TaskCard.vue'

const props = defineProps({
  columns: { type: Array, required: true },
  tasks: { type: Array, required: true },
})

const emit = defineEmits(['edit', 'move'])

let draggedTask = null

function tasksInColumn(columnId) {
  return props.tasks.filter((t) => t.column_id === columnId)
}

function onDragStart(event, task) {
  draggedTask = task
  event.dataTransfer.effectAllowed = 'move'
}

function onDrop(event, columnId) {
  event.preventDefault()
  if (!draggedTask || draggedTask.column_id === columnId) return
  emit('move', { task: draggedTask, columnId })
  draggedTask = null
}
</script>

<style scoped>
.kanban {
  display: flex;
  gap: 1.25rem;
  overflow-x: auto;
  padding-bottom: 1rem;
  min-height: 400px;
}

.kanban-column {
  min-width: 290px;
  max-width: 330px;
  flex-shrink: 0;
  background: var(--bg-secondary);
  border-radius: var(--radius-xl);
  border: 1px solid var(--border);
  overflow: hidden;
}

.column-header {
  padding: 0.85rem 1.1rem;
  border-top: 4px solid var(--accent);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.column-header h3 {
  font-size: 0.95rem;
  font-weight: 600;
}

.count {
  background: var(--bg-primary);
  padding: 0.2rem 0.6rem;
  border-radius: 999px;
  font-size: 0.75rem;
  color: var(--text-secondary);
}

.column-body {
  padding: 0.65rem;
  display: flex;
  flex-direction: column;
  gap: 0.65rem;
  min-height: 200px;
}
</style>
