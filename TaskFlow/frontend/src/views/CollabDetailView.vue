<template>
  <div class="collab-detail">
    <router-link to="/app/collabs" class="back">← {{ t('collabs.title') }}</router-link>
    <h1>{{ collab?.name || t('collabs.chat') }}</h1>

    <div class="layout">
      <aside class="sidebar card animate-in">
        <h2>{{ t('collabs.members') }}</h2>
        <ul class="members-list">
          <li v-for="m in members" :key="m.id" class="member-item">
            <span class="avatar">{{ m.username.charAt(0).toUpperCase() }}</span>
            <div class="member-info">
              <span class="name">{{ m.username }}</span>
              <span class="role">{{ m.role }}</span>
            </div>
          </li>
        </ul>

        <div v-if="isAdmin" class="add-member">
          <h3>{{ t('collabs.addMember') }}</h3>
          <select v-model="selectedUserId" class="member-select">
            <option :value="null" disabled>{{ t('collabs.selectMember') }}</option>
            <option v-for="u in availableUsers" :key="u.id" :value="u.id">
              {{ u.username }}
            </option>
          </select>
          <button
            class="btn btn-sm"
            :disabled="!selectedUserId || adding"
            @click="addMember"
          >
            {{ adding ? t('common.loading') : t('collabs.addMember') }}
          </button>
          <p v-if="memberMsg" class="member-msg">{{ memberMsg }}</p>
        </div>
      </aside>

      <div class="chat-wrap animate-in delay-1">
        <ChatPanel
          :messages="messages"
          :current-user-id="auth.user?.id"
          :placeholder="t('messages.placeholder')"
          :send-label="t('collabs.send')"
          @send="sendMessage"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import { useCollabsStore } from '@/stores/collabs'
import { api, getWsUrl } from '@/api/client'
import ChatPanel from '@/components/ChatPanel.vue'

const { t } = useI18n()
const route = useRoute()
const auth = useAuthStore()
const store = useCollabsStore()

const collabId = computed(() => Number(route.params.id))
const collab = computed(() => store.collabs.find((c) => c.id === collabId.value))
const messages = computed(() => store.messages)
const isAdmin = computed(() => collab.value?.member_role === 'admin')

const members = ref([])
const portalUsers = ref([])
const selectedUserId = ref(null)
const adding = ref(false)
const memberMsg = ref('')

let ws = null

const availableUsers = computed(() =>
  portalUsers.value.filter((u) => !members.value.some((m) => m.id === u.id))
)

onMounted(async () => {
  if (!store.collabs.length) await store.fetchCollabs()
  await Promise.all([store.fetchMessages(collabId.value), loadMembers(), loadPortalUsers()])
  connectWs()
})

onUnmounted(() => {
  ws?.close()
})

async function loadMembers() {
  const result = await api('collabs.members.list', { collab_id: collabId.value })
  members.value = result.members || []
}

async function loadPortalUsers() {
  const result = await api('users.list')
  portalUsers.value = (result.users || []).filter((u) => u.id !== auth.user?.id)
}

async function addMember() {
  if (!selectedUserId.value) return
  adding.value = true
  memberMsg.value = ''
  try {
    await api('collabs.addMember', { collab_id: collabId.value, user_id: selectedUserId.value })
    memberMsg.value = t('collabs.memberAdded')
    selectedUserId.value = null
    await loadMembers()
  } catch (e) {
    memberMsg.value = e.message
  } finally {
    adding.value = false
  }
}

function connectWs() {
  ws = new WebSocket(getWsUrl())
  ws.onmessage = (event) => {
    const data = JSON.parse(event.data)
    if (data.type !== 'collab' || data.collab_id !== collabId.value) return
    if (data.sender_id === auth.user?.id) return
    const exists = store.messages.some(
      (m) => m.id === data.id || (m.content === data.content && m.sender_id === data.sender_id && m.created_at === data.created_at)
    )
    if (!exists) store.messages.push(data)
  }
}

async function sendMessage(content) {
  await store.sendMessage(collabId.value, content)
  await store.fetchMessages(collabId.value)
}
</script>

<style scoped>
.back {
  display: inline-block;
  margin-bottom: 1rem;
  font-size: 0.9rem;
}

h1 {
  margin-bottom: 1.25rem;
}

.layout {
  display: grid;
  grid-template-columns: 280px 1fr;
  gap: 1.25rem;
  align-items: start;
}

.sidebar {
  padding: 1rem;
}

.sidebar h2 {
  font-size: 1rem;
  margin-bottom: 0.75rem;
}

.members-list {
  list-style: none;
  margin-bottom: 1rem;
}

.member-item {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  padding: 0.5rem 0;
  border-bottom: 1px solid var(--border);
}

.member-item:last-child {
  border-bottom: none;
}

.avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: var(--accent);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 0.85rem;
  flex-shrink: 0;
}

.member-info {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.name {
  font-weight: 500;
  font-size: 0.9rem;
}

.role {
  font-size: 0.75rem;
  color: var(--text-secondary);
  text-transform: capitalize;
}

.add-member h3 {
  font-size: 0.9rem;
  margin-bottom: 0.5rem;
}

.member-select {
  width: 100%;
  padding: 0.5rem 0.75rem;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  background: var(--bg-secondary);
  color: var(--text-primary);
  margin-bottom: 0.5rem;
}

.member-msg {
  font-size: 0.8rem;
  color: var(--accent);
  margin-top: 0.5rem;
}

.chat-wrap {
  min-width: 0;
}

@media (max-width: 768px) {
  .layout {
    grid-template-columns: 1fr;
  }
}
</style>
