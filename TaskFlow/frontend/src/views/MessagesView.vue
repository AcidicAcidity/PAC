<template>
  <div class="messages-view">
    <h1>{{ t('messages.title') }}</h1>
    <div class="layout">
      <div class="users-list card animate-in">
        <div
          v-for="u in users"
          :key="u.id"
          class="user-item"
          :class="{ active: selectedUser?.id === u.id }"
          @click="selectUser(u)"
        >
          <span class="avatar">{{ u.username.charAt(0).toUpperCase() }}</span>
          {{ u.username }}
        </div>
        <p v-if="!users.length" class="empty">{{ t('common.noData') }}</p>
      </div>
      <div class="chat-area animate-in delay-1">
        <p v-if="!selectedUser" class="hint">{{ t('messages.selectUser') }}</p>
        <ChatPanel
          v-else
          :messages="messages"
          :current-user-id="auth.user?.id"
          :placeholder="t('messages.placeholder')"
          :send-label="t('messages.send')"
          @send="sendMessage"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { api, getWsUrl } from '@/api/client'
import { useAuthStore } from '@/stores/auth'
import ChatPanel from '@/components/ChatPanel.vue'

const { t } = useI18n()
const auth = useAuthStore()

const users = ref([])
const selectedUser = ref(null)
const messages = ref([])
let ws = null

onMounted(async () => {
  const result = await api('users.list')
  users.value = (result.users || []).filter((u) => u.id !== auth.user?.id)
  connectWs()
})

onUnmounted(() => {
  ws?.close()
})

function connectWs() {
  ws = new WebSocket(getWsUrl())
  ws.onmessage = (event) => {
    const data = JSON.parse(event.data)
    if (data.type !== 'private' || !selectedUser.value) return
    if (data.sender_id === auth.user?.id) return

    const otherId = data.sender_id === auth.user.id ? data.receiver_id : data.sender_id
    if (otherId !== selectedUser.value.id) return

    const exists = messages.value.some(
      (m) => m.id === data.id || (m.content === data.content && m.sender_id === data.sender_id && m.created_at === data.created_at)
    )
    if (!exists) messages.value.push(data)
  }
}

async function selectUser(user) {
  selectedUser.value = user
  const result = await api('messages.private.list', { user_id: user.id })
  messages.value = result.messages || []
}

async function sendMessage(content) {
  if (!selectedUser.value) return
  await api('messages.send', { receiver_id: selectedUser.value.id, content })
  await selectUser(selectedUser.value)
}
</script>

<style scoped>
h1 {
  margin-bottom: 1.5rem;
}

.layout {
  display: grid;
  grid-template-columns: 260px 1fr;
  gap: 1.25rem;
  min-height: 450px;
}

.users-list {
  padding: 0.5rem;
  overflow-y: auto;
}

.user-item {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  padding: 0.65rem 0.75rem;
  border-radius: var(--radius-lg);
  cursor: pointer;
  transition: background 0.2s ease, transform 0.2s ease;
}

.user-item:hover {
  transform: translateX(4px);
}

.user-item.active {
  background: var(--bg-primary);
}

.avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: var(--accent);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 0.8rem;
  flex-shrink: 0;
}

.hint {
  color: var(--text-secondary);
  padding: 2rem;
  text-align: center;
}

.empty {
  color: var(--text-secondary);
  padding: 1rem;
  font-size: 0.9rem;
}
</style>
