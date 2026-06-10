<template>
  <div class="auth-page">
    <div class="card auth-card">
      <h1>{{ t('auth.verify') }}</h1>
      <form @submit.prevent="submit">
        <div class="form-group">
          <label>{{ t('auth.email') }}</label>
          <input v-model="email" type="email" required />
        </div>
        <div class="form-group">
          <label>{{ t('auth.verifyCode') }}</label>
          <input v-model="code" required maxlength="6" />
        </div>
        <p v-if="error" class="error-msg">{{ error }}</p>
        <button type="submit" class="btn" :disabled="loading">
          {{ loading ? t('common.loading') : t('auth.submit') }}
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
const loading = ref(false)
const error = ref('')

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

.back-link {
  display: block;
  margin-top: 1rem;
  text-align: center;
}
</style>
