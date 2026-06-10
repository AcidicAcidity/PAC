<template>
  <div class="chat-panel card">
    <div ref="messagesEl" class="messages">
      <div v-if="!messages.length" class="empty">{{ t('common.noData') }}</div>
      <TransitionGroup name="msg" tag="div" class="messages-inner">
        <div
          v-for="msg in messages"
          :key="messageKey(msg)"
          class="message"
          :class="{ own: msg.sender_id === currentUserId }"
        >
          <div class="sender">{{ msg.sender_name || msg.username || 'User' }}</div>
          <div class="content">{{ msg.content }}</div>
          <div class="time">{{ formatTime(msg.created_at) }}</div>
        </div>
      </TransitionGroup>
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

function messageKey(msg) {
  return msg.id ? `id-${msg.id}` : `${msg.sender_id}-${msg.created_at}-${msg.content}`
}

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
  height: 450px;
  padding: 0;
  overflow: hidden;
  border-radius: var(--radius-xl);
}

.messages {
  flex: 1;
  overflow-y: auto;
  padding: 1rem;
}

.messages-inner {
  display: flex;
  flex-direction: column;
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
  padding: 0.6rem 0.9rem;
  border-radius: var(--radius-lg);
  margin-top: 0.2rem;
  line-height: 1.4;
}

.message.own .content {
  background: var(--accent);
  color: #fff;
  border-bottom-right-radius: 6px;
}

.message:not(.own) .content {
  border-bottom-left-radius: 6px;
}

.time {
  font-size: 0.7rem;
  color: var(--text-secondary);
  margin-top: 0.2rem;
}

.input-row {
  display: flex;
  gap: 0.5rem;
  padding: 0.85rem;
  border-top: 1px solid var(--border);
}

.input-row input {
  flex: 1;
  padding: 0.6rem 0.9rem;
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  background: var(--bg-secondary);
  color: var(--text-primary);
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.input-row input:focus {
  outline: none;
  border-color: var(--accent);
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent) 20%, transparent);
}

/* Message enter animation */
.msg-enter-active {
  transition: all 0.35s cubic-bezier(0.22, 1, 0.36, 1);
}

.msg-enter-from {
  opacity: 0;
  transform: translateY(10px) scale(0.96);
}
</style>
