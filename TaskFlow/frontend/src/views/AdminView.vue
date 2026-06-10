<template>
  <div class="admin">
    <h1>{{ t('admin.title') }}</h1>
    <div class="tabs">
      <button
        v-for="tab in tabs"
        :key="tab.id"
        :class="{ active: activeTab === tab.id }"
        type="button"
        @click="activeTab = tab.id"
      >
        {{ tab.label }}
      </button>
    </div>

    <!-- Users -->
    <div v-if="activeTab === 'users'" class="section">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Email</th>
            <th>Username</th>
            <th>Role</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="u in users" :key="u.id">
            <td>{{ u.id }}</td>
            <td>{{ u.email }}</td>
            <td>{{ u.username }}</td>
            <td>{{ u.portal_role_name || u.role }}</td>
            <td>
              <button class="btn btn-sm" @click="toggleBlock(u)">
                {{ u.is_blocked ? t('admin.unblock') : t('admin.block') }}
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Invites -->
    <div v-if="activeTab === 'invites'" class="section">
      <button class="btn" @click="createInvite">{{ t('admin.createInvite') }}</button>
      <p v-if="inviteCode" class="invite-code">Code: <strong>{{ inviteCode }}</strong></p>
    </div>

    <!-- Roles -->
    <div v-if="activeTab === 'roles'" class="section">
      <form class="inline-form" @submit.prevent="createRole">
        <input v-model="newRole.name" :placeholder="t('admin.roleName')" required />
        <input v-model.number="newRole.hierarchy_level" type="number" :placeholder="t('admin.hierarchy')" required />
        <button type="submit" class="btn btn-sm">{{ t('admin.createRole') }}</button>
      </form>
      <table>
        <thead><tr><th>Name</th><th>Level</th></tr></thead>
        <tbody>
          <tr v-for="r in roles" :key="r.id">
            <td>{{ r.name }}</td>
            <td>{{ r.hierarchy_level }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Collabs -->
    <div v-if="activeTab === 'collabs'" class="section">
      <table>
        <thead><tr><th>Name</th><th>Owner</th><th>Actions</th></tr></thead>
        <tbody>
          <tr v-for="c in collabs" :key="c.id">
            <td>{{ c.name }}</td>
            <td>{{ c.owner_name }}</td>
            <td>
              <button class="btn btn-sm btn-danger" @click="deleteCollab(c.id)">Delete</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Reviews -->
    <div v-if="activeTab === 'reviews'" class="section">
      <div v-for="r in reviews" :key="r.id" class="card review-row">
        <span>{{ r.username }}: {{ '★'.repeat(r.rating) }}</span>
        <p>{{ r.content }}</p>
        <button v-if="!r.is_approved" class="btn btn-sm" @click="approveReview(r.id)">
          {{ t('admin.approve') }}
        </button>
      </div>
    </div>

    <!-- Columns -->
    <div v-if="activeTab === 'columns'" class="section">
      <table>
        <thead><tr><th>Title</th><th>Position</th><th>Color</th></tr></thead>
        <tbody>
          <tr v-for="col in columns" :key="col.id">
            <td>{{ col.title }}</td>
            <td>{{ col.position }}</td>
            <td><span class="color-dot" :style="{ background: col.color }" /></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { api } from '@/api/client'

const { t } = useI18n()
const activeTab = ref('users')
const users = ref([])
const roles = ref([])
const collabs = ref([])
const reviews = ref([])
const columns = ref([])
const inviteCode = ref('')
const newRole = ref({ name: '', hierarchy_level: 5 })

const tabs = computed(() => [
  { id: 'users', label: t('admin.users') },
  { id: 'invites', label: t('admin.invites') },
  { id: 'roles', label: t('admin.roles') },
  { id: 'collabs', label: t('admin.collabs') },
  { id: 'reviews', label: t('admin.reviews') },
  { id: 'columns', label: t('admin.columns') },
])

onMounted(loadAll)

async function loadAll() {
  const [u, r, c, rev, col] = await Promise.all([
    api('admin.users.list'),
    api('admin.roles.list'),
    api('admin.collabs.list'),
    api('admin.reviews.list'),
    api('admin.board.columns.list'),
  ])
  users.value = u.users || []
  roles.value = r.roles || []
  collabs.value = c.collabs || []
  reviews.value = rev.reviews || []
  columns.value = col.columns || []
}

async function toggleBlock(user) {
  await api('admin.users.block', { user_id: user.id, block: !user.is_blocked })
  await loadAll()
}

async function createInvite() {
  const result = await api('admin.users.invite', { max_uses: 10 })
  inviteCode.value = result.invite_code
}

async function createRole() {
  await api('admin.roles.create', newRole.value)
  newRole.value = { name: '', hierarchy_level: 5 }
  const r = await api('admin.roles.list')
  roles.value = r.roles || []
}

async function deleteCollab(id) {
  if (!confirm('Delete collab?')) return
  await api('admin.collabs.delete', { id })
  await loadAll()
}

async function approveReview(id) {
  await api('admin.reviews.approve', { id })
  await loadAll()
}
</script>

<style scoped>
h1 {
  margin-bottom: 1rem;
}

.tabs {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
  margin-bottom: 1.5rem;
}

.tabs button {
  padding: 0.5rem 1rem;
  border: 1px solid var(--border);
  background: var(--bg-secondary);
  border-radius: var(--radius);
  cursor: pointer;
  color: var(--text-primary);
}

.tabs button.active {
  background: var(--accent);
  color: #fff;
  border-color: var(--accent);
}

table {
  width: 100%;
  border-collapse: collapse;
}

th, td {
  padding: 0.6rem;
  text-align: left;
  border-bottom: 1px solid var(--border);
}

.inline-form {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1rem;
  flex-wrap: wrap;
}

.inline-form input {
  padding: 0.5rem;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  background: var(--bg-secondary);
  color: var(--text-primary);
}

.invite-code {
  margin-top: 1rem;
  padding: 1rem;
  background: var(--bg-primary);
  border-radius: var(--radius);
}

.review-row {
  margin-bottom: 0.75rem;
}

.color-dot {
  display: inline-block;
  width: 16px;
  height: 16px;
  border-radius: 50%;
}
</style>
