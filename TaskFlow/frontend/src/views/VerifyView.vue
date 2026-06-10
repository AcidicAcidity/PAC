<template>
  <div class="auth-page">
    <div class="card auth-card">
      <h1>{{ t('auth.verify') }}</h1>
      <p v-if="email" class="hint">{{ t('auth.checkEmail') }}</p>
      <p class="mailpit-hint">
        {{ t('auth.devMailpit') }}
        <a href="http://localhost:8025" target="_blank" rel="noopener">localhost:8025</a>
      </p>
      <form @submit.prevent="submit">
        <div class="form-group">
          <label>{{ t('auth.email') }}</label>
          <input v-model="email" type="email" required />
        </div>
        <div class="form-group">
          <label>{{ t('auth.verifyCode') }}</label>
          <input v-model="code" required maxlength="6" inputmode="numeric" />
        </div>
        <p v-if="devCode" class="dev-code">Dev code: {{ devCode }}</p>
        <p v-if="resendMsg" class="success">{{ resendMsg }}</p>
        <p v-if="error" class="error-msg">{{ error }}</p>
        <button type="submit" class="btn" :disabled="loading">
          {{ loading ? t('common.loading') : t('auth.submit') }}
        </button>
        <button
          type="button"
          class="btn btn-secondary resend-btn"
          :disabled="resendLoading || !email"
          @click="resend"
        >
          {{ resendLoading ? t('common.loading') : t('auth.resend') }}
        </button>
      </form>
      <router-link to="/register" class="back-link">{{ t('auth.back') }}</router-link>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const email = ref(route.query.email || '')
const code = ref('')
const devCode = ref(route.query.devCode || '')
const loading = ref(false)
const resendLoading = ref(false)
const error = ref('')
const resendMsg = ref('')

async function submit() {
  loading.value = true
  error.value = ''
  try {
    await auth.verify(email.value, code.value)
    router.push({ name: 'board' })
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

async function resend() {
  resendLoading.value = true
  error.value = ''
  resendMsg.value = ''
  devCode.value = ''
  try {
    const result = await auth.resendVerification(email.value)
    resendMsg.value = t('auth.resendSuccess')
    if (result.verification_code) {
      devCode.value = result.verification_code
    }
  } catch (e) {
    error.value = e.message
  } finally {
    resendLoading.value = false
  }
}
</script>

<style scoped>
.auth-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}

.auth-card {
  width: 100%;
  max-width: 420px;
}

.auth-card h1 {
  margin-bottom: 0.75rem;
}

.hint {
  color: var(--text-secondary);
  margin-bottom: 0.5rem;
  font-size: 0.9rem;
}

.mailpit-hint {
  font-size: 0.8rem;
  color: var(--text-secondary);
  margin-bottom: 1rem;
}

.resend-btn {
  margin-top: 0.5rem;
  width: 100%;
}

.dev-code {
  background: #fef3c7;
  color: #92400e;
  padding: 0.5rem;
  border-radius: var(--radius);
  font-size: 0.85rem;
  margin-bottom: 0.5rem;
}

.success {
  color: var(--accent);
  font-size: 0.875rem;
  margin-bottom: 0.5rem;
}

.back-link {
  display: block;
  margin-top: 1rem;
  text-align: center;
}
</style>
