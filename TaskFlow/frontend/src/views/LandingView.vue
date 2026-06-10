<template>
  <div class="landing">
    <div class="hero">
      <h1>{{ t('app.name') }}</h1>
      <p>{{ t('app.tagline') }}</p>
      <div class="actions">
        <router-link to="/register" class="btn">{{ t('landing.start') }}</router-link>
        <router-link to="/login" class="btn btn-secondary">{{ t('landing.login') }}</router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()
</script>

<style scoped>
.landing {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, var(--bg-primary), var(--bg-secondary));
}

.hero {
  text-align: center;
  padding: 2rem;
}

.hero h1 {
  font-size: 3rem;
  margin-bottom: 0.5rem;
  color: var(--accent);
}

.hero p {
  font-size: 1.25rem;
  color: var(--text-secondary);
  margin-bottom: 2rem;
}

.actions {
  display: flex;
  gap: 1rem;
  justify-content: center;
}
</style>
