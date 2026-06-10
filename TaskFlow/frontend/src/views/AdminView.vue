<template>
  <div class="admin">
    <h1>{{ t('admin.title') }}</h1>
    <p v-if="error" class="error-msg">{{ error }}</p>
    <div class="tabs" role="tablist">
      <button
        v-for="tab in tabs"
        :key="tab.id"
        :class="{ active: activeTab === tab.id }"
        type="button"
        role="tab"
        :aria-selected="activeTab === tab.id"
        @click="switchTab(tab.id)"
      >
        {{ tab.label }}
      </button>
    </div>

    <div v-if="loading" class="loading">{{ t('common.loading') }}</div>

    <!-- Users -->
    <div v-else-if="activeTab === 'users'" class="section">
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
            <td>
              <select
                :value="u.portal_role_id || ''"
                class="role-select"
                @change="setUserRole(u.id, $event.target.value)"
              >
                <option value="">—</option>
                <option v-for="r in roles" :key="r.id" :value="r.id">{{ r.name }}</option>
              </select>
            </td>
            <td>
              <button class="btn btn-sm" type="button" @click="toggleBlock(u)">
                {{ u.is_blocked ? t('admin.unblock') : t('admin.block') }}
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Invites -->
    <div v-else-if="activeTab === 'invites'" class="section">
      <button class="btn" type="button" @click="createInvite">{{ t('admin.createInvite') }}</button>
      <p v-if="inviteCode" class="invite-code">Code: <strong>{{ inviteCode }}</strong></p>
    </div>

    <!-- Roles -->
    <div v-else-if="activeTab === 'roles'" class="section">
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
    <div v-else-if="activeTab === 'collabs'" class="section">
      <table>
        <thead><tr><th>Name</th><th>Owner</th><th>Actions</th></tr></thead>
        <tbody>
          <tr v-for="c in collabs" :key="c.id">
            <td>{{ c.name }}</td>
            <td>{{ c.owner_name }}</td>
            <td>
              <button class="btn btn-sm btn-danger" type="button" @click="deleteCollab(c.id)">Delete</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Reviews -->
    <div v-else-if="activeTab === 'reviews'" class="section">
      <div v-for="r in reviews" :key="r.id" class="card review-row">
        <span>{{ r.username }}: {{ '★'.repeat(r.rating) }}</span>
        <p>{{ r.content }}</p>
        <div class="review-actions">
          <button v-if="!r.is_approved" class="btn btn-sm" type="button" @click="approveReview(r.id)">
            {{ t('admin.approve') }}
          </button>
          <button class="btn btn-sm btn-danger" type="button" @click="deleteReview(r.id)">
            {{ t('admin.deleteReview') }}
          </button>
        </div>
      </div>
      <p v-if="!reviews.length" class="empty">{{ t('common.noData') }}</p>
    </div>

    <!-- Columns -->
    <div v-else-if="activeTab === 'columns'" class="section">
      <form class="inline-form" @submit.prevent="saveColumn">
        <input v-model="columnForm.title" :placeholder="t('admin.columnTitle')" required />
        <input v-model.number="columnForm.position" type="number" :placeholder="t('admin.columnPosition')" />
        <input v-model="columnForm.color" type="color" class="color-input" :title="t('admin.columnColor')" />
        <button type="submit" class="btn btn-sm">
          {{ columnForm.id ? t('admin.editColumn') : t('admin.addColumn') }}
        </button>
        <button v-if="columnForm.id" type="button" class="btn btn-sm btn-secondary" @click="resetColumnForm">
          {{ t('board.cancel') }}
        </button>
      </form>
      <table>
        <thead><tr><th>Title</th><th>Position</th><th>Color</th><th>Actions</th></tr></thead>
        <tbody>
          <tr v-for="col in columns" :key="col.id">
            <td>{{ col.title }}</td>
            <td>{{ col.position }}</td>
            <td>
              <input
                type="color"
                class="color-input-inline"
                :value="col.color || '#808080'"
                :title="t('admin.columnColor')"
                @change="updateColumnColor(col, $event.target.value)"
              />
              <span class="color-dot" :style="{ background: col.color }" />
              {{ col.color }}
            </td>
            <td class="actions-cell">
              <button class="btn btn-sm" type="button" @click="editColumn(col)">{{ t('admin.editColumn') }}</button>
              <button class="btn btn-sm btn-danger" type="button" @click="deleteColumn(col.id)">{{ t('admin.deleteColumn') }}</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { api } from '@/api/client'

const { t } = useI18n()
const activeTab = ref('users')
const loading = ref(false)
const error = ref('')
const users = ref([])
const roles = ref([])
const collabs = ref([])
const reviews = ref([])
const columns = ref([])
const inviteCode = ref('')
const newRole = ref({ name: '', hierarchy_level: 5 })
const columnForm = ref({ id: null, title: '', position: 0, color: '#3b82f6' })

const tabs = computed(() => [
  { id: 'users', label: t('admin.users') },
  { id: 'invites', label: t('admin.invites') },
  { id: 'roles', label: t('admin.roles') },
  { id: 'collabs', label: t('admin.collabs') },
  { id: 'reviews', label: t('admin.reviews') },
  { id: 'columns', label: t('admin.columns') },
])

const tabLoaders = {
  users: async () => {
    const [u, r] = await Promise.all([api('admin.users.list'), api('admin.roles.list')])
    users.value = u.users || []
    roles.value = r.roles || []
  },
  invites: async () => {},
  roles: async () => {
    const r = await api('admin.roles.list')
    roles.value = r.roles || []
  },
  collabs: async () => {
    const c = await api('admin.collabs.list')
    collabs.value = c.collabs || []
  },
  reviews: async () => {
    const rev = await api('admin.reviews.list')
    reviews.value = rev.reviews || []
  },
  columns: async () => {
    const col = await api('admin.board.columns.list')
    columns.value = col.columns || []
    resetColumnForm()
  },
}

watch(activeTab, loadTab, { immediate: true })

async function loadTab(tab) {
  loading.value = true
  error.value = ''
  try {
    await tabLoaders[tab]?.()
  } catch (e) {
    error.value = e.message || t('admin.loadError')
  } finally {
    loading.value = false
  }
}

function switchTab(id) {
  if (activeTab.value === id) return
  activeTab.value = id
}

async function toggleBlock(user) {
  try {
    await api('admin.users.block', { user_id: user.id, block: !user.is_blocked })
    await loadTab('users')
  } catch (e) {
    error.value = e.message
  }
}

async function setUserRole(userId, roleId) {
  try {
    await api('admin.users.setRole', {
      user_id: userId,
      portal_role_id: roleId ? Number(roleId) : null,
    })
    await loadTab('users')
  } catch (e) {
    error.value = e.message
  }
}

async function createInvite() {
  try {
    const result = await api('admin.users.invite', { max_uses: 10 })
    inviteCode.value = result.invite_code
  } catch (e) {
    error.value = e.message
  }
}

async function createRole() {
  try {
    await api('admin.roles.create', newRole.value)
    newRole.value = { name: '', hierarchy_level: 5 }
    await loadTab('roles')
  } catch (e) {
    error.value = e.message
  }
}

async function deleteCollab(id) {
  if (!confirm('Delete collab?')) return
  try {
    await api('admin.collabs.delete', { id })
    await loadTab('collabs')
  } catch (e) {
    error.value = e.message
  }
}

async function approveReview(id) {
  try {
    await api('admin.reviews.approve', { id })
    await loadTab('reviews')
  } catch (e) {
    error.value = e.message
  }
}

async function deleteReview(id) {
  if (!confirm(t('admin.deleteReviewConfirm'))) return
  try {
    await api('admin.reviews.delete', { id })
    await loadTab('reviews')
  } catch (e) {
    error.value = e.message
  }
}

async function updateColumnColor(col, color) {
  try {
    await api('admin.board.columns.update', { id: col.id, color })
    col.color = color
  } catch (e) {
    error.value = e.message
  }
}

function editColumn(col) {
  columnForm.value = { id: col.id, title: col.title, position: col.position, color: col.color || '#808080' }
}

function resetColumnForm() {
  columnForm.value = { id: null, title: '', position: columns.value.length, color: '#3b82f6' }
}

async function saveColumn() {
  try {
    if (columnForm.value.id) {
      await api('admin.board.columns.update', { ...columnForm.value })
    } else {
      await api('admin.board.columns.create', {
        title: columnForm.value.title,
        position: columnForm.value.position,
        color: columnForm.value.color,
      })
    }
    resetColumnForm()
    await loadTab('columns')
  } catch (e) {
    error.value = e.message
  }
}

async function deleteColumn(id) {
  if (!confirm('Delete column?')) return
  try {
    await api('admin.board.columns.delete', { id })
    await loadTab('columns')
  } catch (e) {
    error.value = e.message
  }
}
</script>

<style scoped>
h1 {
  margin-bottom: 1rem;
}

.error-msg {
  color: var(--danger);
  margin-bottom: 1rem;
}

.loading, .empty {
  color: var(--text-secondary);
  padding: 1rem 0;
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
  transition: background 0.2s ease, color 0.2s ease;
}

.tabs button:hover {
  background: var(--bg-primary);
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
  align-items: center;
}

.inline-form input:not(.color-input) {
  padding: 0.5rem;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  background: var(--bg-secondary);
  color: var(--text-primary);
}

.color-input {
  width: 48px;
  height: 36px;
  padding: 2px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  cursor: pointer;
  background: var(--bg-secondary);
}

.role-select {
  padding: 0.35rem 0.5rem;
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

.review-actions {
  display: flex;
  gap: 0.4rem;
  flex-wrap: wrap;
  margin-top: 0.5rem;
}

.color-input-inline {
  width: 36px;
  height: 28px;
  padding: 0;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  cursor: pointer;
  vertical-align: middle;
  margin-right: 0.35rem;
  background: var(--bg-secondary);
}

.color-dot {
  display: inline-block;
  width: 28px;
  height: 28px;
  border-radius: 50%;
  vertical-align: middle;
  margin-right: 0.5rem;
  border: 2px solid var(--border);
}

.actions-cell {
  display: flex;
  gap: 0.4rem;
  flex-wrap: wrap;
}
</style>
