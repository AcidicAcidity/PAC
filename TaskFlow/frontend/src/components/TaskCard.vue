<template>
  <div
    class="task-card animate-card"
    :class="[`priority-${task.priority}`, { inactive: !isActive }]"
  >
    <div class="task-header">
      <div class="task-title">{{ task.title }}</div>
      <button
        class="status-btn"
        type="button"
        :title="t('board.toggleStatus')"
        @click.stop="$emit('toggle-active', task)"
      >
        {{ isActive ? t('board.active') : t('board.inactive') }}
      </button>
    </div>
    <div v-if="task.description" class="task-desc">{{ truncate(task.description) }}</div>
    <div class="task-meta">
      <span :class="['badge', `badge-${task.priority}`]">
        {{ t(`board.priorities.${task.priority}`) }}
      </span>
      <span v-if="task.assignee_name" class="assignee">{{ task.assignee_name }}</span>
      <span v-if="task.deadline" class="deadline" :class="{ overdue: isOverdue }">
        {{ formatDeadline(task.deadline) }}
      </span>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const props = defineProps({
  task: { type: Object, required: true },
})

defineEmits(['toggle-active'])

const { t } = useI18n()

const isActive = computed(() => props.task.is_active !== false && props.task.is_active !== 'f')

const isOverdue = computed(() => {
  if (!props.task.deadline) return false
  return new Date(props.task.deadline) < new Date(new Date().toDateString())
})

function truncate(text, len = 80) {
  return text.length > len ? text.slice(0, len) + '…' : text
}

function formatDeadline(date) {
  return new Date(date).toLocaleDateString()
}
</script>

<style scoped>
.task-card {
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 0.9rem 1rem;
  cursor: grab;
  transition: transform 0.25s cubic-bezier(0.22, 1, 0.36, 1), box-shadow 0.25s ease, opacity 0.25s ease;
}

.task-card.inactive {
  opacity: 0.55;
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

.task-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 0.5rem;
  margin-bottom: 0.35rem;
}

.task-title {
  font-weight: 600;
  font-size: 0.9rem;
  flex: 1;
}

.status-btn {
  flex-shrink: 0;
  font-size: 0.65rem;
  padding: 0.15rem 0.45rem;
  border-radius: 999px;
  border: 1px solid var(--border);
  background: var(--bg-primary);
  color: var(--text-secondary);
  cursor: pointer;
  transition: background 0.2s ease, color 0.2s ease;
}

.task-card.inactive .status-btn {
  background: var(--danger);
  color: #fff;
  border-color: var(--danger);
}

.status-btn:hover {
  background: var(--accent);
  color: #fff;
  border-color: var(--accent);
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

.assignee, .deadline {
  font-size: 0.75rem;
  color: var(--text-secondary);
}

.deadline.overdue {
  color: var(--danger);
  font-weight: 600;
}
</style>
