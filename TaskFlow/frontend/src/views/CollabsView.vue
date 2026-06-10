<template>
  <div>
    <div class="header">
      <h1>{{ t('collabs.title') }}</h1>
      <button class="btn" @click="showForm = !showForm">{{ t('collabs.create') }}</button>
    </div>
    <form v-if="showForm" class="card create-form" @submit.prevent="create">
      <div class="form-group">
        <label>{{ t('collabs.name') }}</label>
        <input v-model="form.name" required />
      </div>
      <div class="form-group">
        <label>{{ t('collabs.description') }}</label>
        <textarea v-model="form.description" />
      </div>
      <button type="submit" class="btn">{{ t('collabs.create') }}</button>
    </form>
    <div v-if="loading" class="loading">{{ t('common.loading') }}</div>
    <div v-else class="collab-list">
      <router-link
        v-for="collab in collabs"
        :key="collab.id"
        :to="`/app/collabs/${collab.id}`"
        class="card collab-item"
      >
        <h3>{{ collab.name }}</h3>
        <p>{{ collab.description || '—' }}</p>
        <span class="role">{{ collab.member_role }}</span>
      </router-link>
      <p v-if="!collabs.length" class="empty">{{ t('common.noData') }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useCollabsStore } from '@/stores/collabs'

const { t } = useI18n()
const store = useCollabsStore()
const showForm = ref(false)
const form = ref({ name: '', description: '' })

const collabs = computed(() => store.collabs)
const loading = computed(() => store.loading)

onMounted(() => store.fetchCollabs())

async function create() {
  await store.createCollab(form.value)
  form.value = { name: '', description: '' }
  showForm.value = false
}
</script>

<style scoped>
.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.create-form {
  margin-bottom: 1.5rem;
}

.collab-list {
  display: grid;
  gap: 1rem;
}

.collab-item {
  display: block;
  color: inherit;
  text-decoration: none;
  border-radius: var(--radius-xl);
  transition: transform 0.25s cubic-bezier(0.22, 1, 0.36, 1), box-shadow 0.25s ease;
  animation: fadeInUp 0.4s cubic-bezier(0.22, 1, 0.36, 1) both;
}

.collab-item:hover {
  transform: translateY(-3px);
  box-shadow: var(--shadow-hover, var(--shadow));
  text-decoration: none;
}

.collab-item h3 {
  margin-bottom: 0.35rem;
}

.collab-item p {
  color: var(--text-secondary);
  font-size: 0.9rem;
}

.role {
  display: inline-block;
  margin-top: 0.5rem;
  font-size: 0.75rem;
  color: var(--accent);
}

.empty, .loading {
  color: var(--text-secondary);
  text-align: center;
  padding: 2rem;
}
</style>
