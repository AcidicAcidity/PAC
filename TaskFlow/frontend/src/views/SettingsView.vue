<template>
  <div>
    <h1>{{ t('settings.title') }}</h1>
    <form class="card settings-form" @submit.prevent="save">
      <div class="form-group">
        <label>{{ t('settings.profile') }} — {{ t('auth.username') }}</label>
        <input v-model="form.username" />
      </div>
      <div class="form-group">
        <label>{{ t('settings.theme') }}</label>
        <select v-model="form.theme">
          <option value="light">{{ t('themes.light') }}</option>
          <option value="dark">{{ t('themes.dark') }}</option>
          <option value="cyberpunk">{{ t('themes.cyberpunk') }}</option>
          <option value="nature">{{ t('themes.nature') }}</option>
        </select>
      </div>
      <div class="form-group">
        <label>{{ t('settings.language') }}</label>
        <select v-model="form.language">
          <option value="ru">Русский</option>
          <option value="en">English</option>
        </select>
      </div>
      <button type="submit" class="btn">{{ t('settings.save') }}</button>
      <p v-if="msg" class="success">{{ msg }}</p>
    </form>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import { useSettingsStore } from '@/stores/settings'

const { t, locale } = useI18n()
const auth = useAuthStore()
const settings = useSettingsStore()

const form = ref({ username: '', theme: 'light', language: 'ru' })
const msg = ref('')

onMounted(() => {
  form.value = {
    username: auth.user?.username || '',
    theme: settings.theme,
    language: settings.language,
  }
})

async function save() {
  msg.value = ''
  try {
    const user = await settings.saveProfile(form.value)
    auth.user = user
    localStorage.setItem('user', JSON.stringify(user))
    locale.value = form.value.language
    msg.value = t('common.success')
  } catch (e) {
    msg.value = e.message
  }
}
</script>

<style scoped>
h1 {
  margin-bottom: 1.5rem;
}

.settings-form {
  max-width: 480px;
}

.success {
  color: var(--accent);
  margin-top: 0.75rem;
}
</style>
