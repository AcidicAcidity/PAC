<template>
  <div>
    <router-link to="/app/collabs" class="back">← {{ t('collabs.title') }}</router-link>
    <h1>{{ collab?.name || t('collabs.chat') }}</h1>
    <ChatPanel
      :messages="messages"
      :current-user-id="auth.user?.id"
      :placeholder="t('messages.placeholder')"
      :send-label="t('collabs.send')"
      @send="sendMessage"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import { useCollabsStore } from '@/stores/collabs'
import { getWsUrl } from '@/api/client'
import ChatPanel from '@/components/ChatPanel.vue'

const { t } = useI18n()
const route = useRoute()
const auth = useAuthStore()
const store = useCollabsStore()

const collabId = computed(() => Number(route.params.id))
const collab = computed(() => store.collabs.find((c) => c.id === collabId.value))
const messages = computed(() => store.messages)

let ws = null

onMounted(async () => {
  if (!store.collabs.length) await store.fetchCollabs()
  await store.fetchMessages(collabId.value)
  connectWs()
})

onUnmounted(() => {
  ws?.close()
})

function connectWs() {
  ws = new WebSocket(getWsUrl())
  ws.onmessage = (event) => {
    const data = JSON.parse(event.data)
    if (data.type === 'collab' && data.collab_id === collabId.value) {
      store.messages.push(data)
    }
  }
}

async function sendMessage(content) {
  await store.sendMessage(collabId.value, content)
  await store.fetchMessages(collabId.value)
  if (ws?.readyState === WebSocket.OPEN) {
    ws.send(JSON.stringify({ type: 'collab', target_id: collabId.value, content }))
  }
}
</script>

<style scoped>
.back {
  display: inline-block;
  margin-bottom: 1rem;
  font-size: 0.9rem;
}

h1 {
  margin-bottom: 1rem;
}
</style>
