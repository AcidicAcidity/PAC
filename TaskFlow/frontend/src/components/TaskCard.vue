<template>
  <div class="task-card animate-card" :class="`priority-${task.priority}`">
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
  border-radius: var(--radius-lg);
  padding: 0.9rem 1rem;
  cursor: grab;
  transition: transform 0.25s cubic-bezier(0.22, 1, 0.36, 1), box-shadow 0.25s ease;
}

.task-card:hover {
  transform: translateY(-3px) scale(1.01);
  box-shadow: var(--shadow-hover, var(--shadow));
}

.task-card:active {
  cursor: grabbing;
  transform: scale(0.98);
}

.animate-card {
  animation: scaleIn 0.35s cubic-bezier(0.22, 1, 0.36, 1) both;
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
