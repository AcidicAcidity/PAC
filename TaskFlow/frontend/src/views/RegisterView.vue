<template>
  <div class="auth-page">
    <div class="card auth-card">
      <h1>{{ t('auth.register') }}</h1>
      <div class="tabs">
        <button :class="{ active: isAdmin }" type="button" @click="isAdmin = true">
          {{ t('auth.iAmAdmin') }}
        </button>
        <button :class="{ active: !isAdmin }" type="button" @click="isAdmin = false">
          {{ t('auth.iAmEmployee') }}
        </button>
      </div>
      <form @submit.prevent="submit">
        <div v-if="isAdmin" class="form-group">
          <label>{{ t('auth.company') }}</label>
          <input v-model="form.company" required />
        </div>
        <div v-else class="form-group">
          <label>{{ t('auth.inviteCode') }}</label>
          <input v-model="form.invite_code" required />
        </div>
        <div class="form-group">
          <label>{{ t('auth.email') }}</label>
          <input v-model="form.email" type="email" required />
        </div>
        <div class="form-group">
          <label>{{ t('auth.username') }}</label>
          <input v-model="form.username" />
        </div>
        <div class="form-group">
          <label>{{ t('auth.password') }}</label>
          <input v-model="form.password" type="password" required minlength="6" />
        </div>
        <p v-if="error" class="error-msg">{{ error }}</p>
        <p v-if="devCode" class="dev-code">Dev code: {{ devCode }}</p>
        <button type="submit" class="btn" :disabled="loading">
          {{ loading ? t('common.loading') : t('auth.submit') }}
        </button>
      </form>
      <router-link to="/login" class="back-link">{{ t('auth.login') }}</router-link>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'

const { t } = useI18n()
const router = useRouter()
const auth = useAuthStore()

const isAdmin = ref(true)
const loading = ref(false)
const error = ref('')
const devCode = ref('')
const form = ref({ email: '', password: '', username: '', company: '', invite_code: '' })

async function submit() {
  loading.value = true
  error.value = ''
  devCode.value = ''
  try {
    const params = {
      email: form.value.email,
      password: form.value.password,
      username: form.value.username,
    }
    if (isAdmin.value) {
      params.company = form.value.company
    } else {
      params.invite_code = form.value.invite_code
    }
    const result = await auth.register(params)
    if (result.verification_code) {
      devCode.value = result.verification_code
    }
    router.push({
      name: 'verify',
      query: {
        email: form.value.email,
        ...(result.verification_code ? { devCode: result.verification_code } : {}),
      },
    })
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
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
  margin-bottom: 1rem;
}

.tabs {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1.25rem;
}

.tabs button {
  flex: 1;
  padding: 0.5rem;
  border: 1px solid var(--border);
  background: var(--bg-primary);
  border-radius: var(--radius);
  cursor: pointer;
  color: var(--text-primary);
}

.tabs button.active {
  background: var(--accent);
  color: #fff;
  border-color: var(--accent);
}

.back-link {
  display: block;
  margin-top: 1rem;
  text-align: center;
  font-size: 0.9rem;
}

.dev-code {
  background: #fef3c7;
  color: #92400e;
  padding: 0.5rem;
  border-radius: var(--radius);
  font-size: 0.85rem;
  margin-bottom: 0.5rem;
}
</style>
