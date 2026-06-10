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
          <label>{{ t('board.assignee') }}</label>
          <select v-model="form.assignee_id">
            <option :value="null">—</option>
            <option v-for="u in users" :key="u.id" :value="u.id">{{ u.username }}</option>
          </select>
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
            v-if="task"
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
import { ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'

const props = defineProps({
  task: { type: Object, default: null },
  users: { type: Array, default: () => [] },
  defaultColumnId: { type: Number, default: null },
})

const emit = defineEmits(['close', 'save', 'delete'])
const { t } = useI18n()
const error = ref('')

const form = ref({
  title: '',
  description: '',
  priority: 'medium',
  assignee_id: null,
  is_public: false,
  column_id: null,
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
      }
    } else {
      form.value = {
        title: '',
        description: '',
        priority: 'medium',
        assignee_id: null,
        is_public: false,
        column_id: props.defaultColumnId,
      }
    }
  },
  { immediate: true }
)

function submit() {
  error.value = ''
  emit('save', { ...form.value, id: props.task?.id })
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

.actions {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
  margin-top: 1rem;
}
</style>
