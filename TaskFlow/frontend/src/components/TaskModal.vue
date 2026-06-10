<template>
  <div class="modal-overlay" @click.self="$emit('close')">
    <div class="modal card">
      <h2>{{ task ? t('board.editTask') : t('board.newTask') }}</h2>
      <form @submit.prevent="submit">
        <div class="form-group">
          <label>{{ t('board.titleField') }}</label>
          <input v-model="form.title" required />
        </div>
        <div class="form-group">
          <label>{{ t('board.description') }}</label>
          <textarea v-model="form.description" />
        </div>
        <div class="form-group">
          <label>{{ t('board.priority') }}</label>
          <select v-model="form.priority">
            <option value="low">{{ t('board.priorities.low') }}</option>
            <option value="medium">{{ t('board.priorities.medium') }}</option>
            <option value="high">{{ t('board.priorities.high') }}</option>
          </select>
        </div>
        <div class="form-group">
          <label>{{ t('board.column') }}</label>
          <select v-model="form.column_id">
            <option v-for="col in columns" :key="col.id" :value="col.id">{{ col.title }}</option>
          </select>
        </div>
        <div class="form-group">
          <label>{{ t('board.deadline') }}</label>
          <input v-model="form.deadline" type="date" />
        </div>
        <div class="form-group">
          <label>{{ t('board.assignee') }}</label>
          <select v-model="form.assignee_id">
            <option :value="null">—</option>
            <option v-for="u in users" :key="u.id" :value="u.id">{{ u.username }}</option>
          </select>
        </div>
        <div class="form-group status-row">
          <label>{{ t('board.status') }}</label>
          <button
            type="button"
            class="status-toggle"
            :class="{ inactive: !form.is_active }"
            @click="form.is_active = !form.is_active"
          >
            {{ form.is_active ? t('board.active') : t('board.inactive') }}
          </button>
        </div>
        <div class="form-group checkbox">
          <label>
            <input v-model="form.is_public" type="checkbox" />
            {{ t('board.public') }}
          </label>
        </div>
        <p v-if="error" class="error-msg">{{ error }}</p>
        <div class="actions">
          <button type="submit" class="btn">{{ t('board.save') }}</button>
          <button type="button" class="btn btn-secondary" @click="$emit('close')">
            {{ t('board.cancel') }}
          </button>
          <button
            v-if="task && canDelete"
            type="button"
            class="btn btn-danger"
            @click="$emit('delete', task.id)"
          >
            {{ t('board.delete') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'

const props = defineProps({
  task: { type: Object, default: null },
  users: { type: Array, default: () => [] },
  columns: { type: Array, default: () => [] },
  defaultColumnId: { type: Number, default: null },
})

const emit = defineEmits(['close', 'save', 'delete'])
const { t } = useI18n()
const auth = useAuthStore()
const error = ref('')

const canDelete = computed(() => {
  if (!props.task) return false
  return auth.isAdmin || props.task.creator_id === auth.user?.id
})

const form = ref({
  title: '',
  description: '',
  priority: 'medium',
  assignee_id: null,
  is_public: false,
  column_id: null,
  deadline: '',
  is_active: true,
})

watch(
  () => props.task,
  (task) => {
    if (task) {
      form.value = {
        title: task.title,
        description: task.description || '',
        priority: task.priority || 'medium',
        assignee_id: task.assignee_id,
        is_public: !!task.is_public,
        column_id: task.column_id,
        deadline: task.deadline ? task.deadline.slice(0, 10) : '',
        is_active: task.is_active !== false && task.is_active !== 'f',
      }
    } else {
      form.value = {
        title: '',
        description: '',
        priority: 'medium',
        assignee_id: null,
        is_public: false,
        column_id: props.defaultColumnId,
        deadline: '',
        is_active: true,
      }
    }
  },
  { immediate: true }
)

function submit() {
  error.value = ''
  emit('save', {
    ...form.value,
    id: props.task?.id,
    deadline: form.value.deadline || null,
  })
}
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 100;
  padding: 1rem;
}

.modal {
  width: 100%;
  max-width: 480px;
}

.modal h2 {
  margin-bottom: 1rem;
}

.checkbox label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
}

.checkbox input {
  width: auto;
}

.status-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.status-toggle {
  padding: 0.4rem 0.9rem;
  border-radius: 999px;
  border: 1px solid var(--accent);
  background: color-mix(in srgb, var(--accent) 15%, transparent);
  color: var(--accent);
  cursor: pointer;
  font-weight: 600;
  font-size: 0.85rem;
}

.status-toggle.inactive {
  border-color: var(--danger);
  background: color-mix(in srgb, var(--danger) 15%, transparent);
  color: var(--danger);
}

.actions {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
  margin-top: 1rem;
}
</style>
