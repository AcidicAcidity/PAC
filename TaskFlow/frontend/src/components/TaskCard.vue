<template>
  <div class="task-card" :class="`priority-${task.priority}`">
    <div class="task-title">{{ task.title }}</div>
    <div v-if="task.description" class="task-desc">{{ truncate(task.description) }}</div>
    <div class="task-meta">
      <span :class="['badge', `badge-${task.priority}`]">
        {{ t(`board.priorities.${task.priority}`) }}
      </span>
      <span v-if="task.assignee_name" class="assignee">{{ task.assignee_name }}</span>
    </div>
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n'

defineProps({
  task: { type: Object, required: true },
})

const { t } = useI18n()

function truncate(text, len = 80) {
  return text.length > len ? text.slice(0, len) + '…' : text
}
</script>

<style scoped>
.task-card {
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 0.75rem;
  cursor: grab;
  transition: box-shadow 0.15s;
}

.task-card:hover {
  box-shadow: var(--shadow);
}

.task-card:active {
  cursor: grabbing;
}

.task-title {
  font-weight: 600;
  font-size: 0.9rem;
  margin-bottom: 0.35rem;
}

.task-desc {
  font-size: 0.8rem;
  color: var(--text-secondary);
  margin-bottom: 0.5rem;
}

.task-meta {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.assignee {
  font-size: 0.75rem;
  color: var(--text-secondary);
}
</style>
