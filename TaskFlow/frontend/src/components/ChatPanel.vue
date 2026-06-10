<template>
  <div class="chat-panel card">
    <div ref="messagesEl" class="messages">
      <div v-if="!messages.length" class="empty">{{ t('common.noData') }}</div>
      <div
        v-for="msg in messages"
        :key="msg.id || msg.created_at + msg.sender_id"
        class="message"
        :class="{ own: msg.sender_id === currentUserId }"
      >
        <div class="sender">{{ msg.sender_name || msg.username || 'User' }}</div>
        <div class="content">{{ msg.content }}</div>
        <div class="time">{{ formatTime(msg.created_at) }}</div>
      </div>
    </div>
    <form class="input-row" @submit.prevent="send">
      <input v-model="text" :placeholder="placeholder" />
      <button type="submit" class="btn btn-sm">{{ sendLabel }}</button>
    </form>
  </div>
</template>

<script setup>
import { ref, watch, nextTick } from 'vue'
import { useI18n } from 'vue-i18n'

const props = defineProps({
  messages: { type: Array, default: () => [] },
  currentUserId: { type: Number, required: true },
  placeholder: { type: String, default: '' },
  sendLabel: { type: String, default: 'Send' },
})

const emit = defineEmits(['send'])
const { t } = useI18n()
const text = ref('')
const messagesEl = ref(null)

watch(
  () => props.messages.length,
  async () => {
    await nextTick()
    if (messagesEl.value) {
      messagesEl.value.scrollTop = messagesEl.value.scrollHeight
    }
  }
)

function send() {
  if (!text.value.trim()) return
  emit('send', text.value.trim())
  text.value = ''
}

function formatTime(ts) {
  if (!ts) return ''
  return new Date(ts).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
}
</script>

<style scoped>
.chat-panel {
  display: flex;
  flex-direction: column;
  height: 400px;
  padding: 0;
  overflow: hidden;
}

.messages {
  flex: 1;
  overflow-y: auto;
  padding: 1rem;
}

.empty {
  color: var(--text-secondary);
  text-align: center;
  padding: 2rem;
}

.message {
  margin-bottom: 0.75rem;
  max-width: 80%;
}

.message.own {
  margin-left: auto;
  text-align: right;
}

.sender {
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--accent);
}

.content {
  background: var(--bg-primary);
  padding: 0.5rem 0.75rem;
  border-radius: var(--radius);
  margin-top: 0.15rem;
}

.message.own .content {
  background: var(--accent);
  color: #fff;
}

.time {
  font-size: 0.7rem;
  color: var(--text-secondary);
  margin-top: 0.15rem;
}

.input-row {
  display: flex;
  gap: 0.5rem;
  padding: 0.75rem;
  border-top: 1px solid var(--border);
}

.input-row input {
  flex: 1;
  padding: 0.5rem 0.75rem;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  background: var(--bg-secondary);
  color: var(--text-primary);
}
</style>
