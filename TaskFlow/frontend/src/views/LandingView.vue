<template>
  <div class="landing" :data-theme="settings.theme">
    <div class="bg-animation" aria-hidden="true">
      <div class="orb orb-1" />
      <div class="orb orb-2" />
      <div class="orb orb-3" />
      <div class="grid-lines" />
    </div>

    <header class="landing-header">
      <div class="logo">
        <svg viewBox="0 0 32 32" width="36" height="36" aria-hidden="true">
          <rect width="32" height="32" rx="9" fill="var(--accent)" />
          <path d="M9 16.5l4.5 4.5L23 11" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" fill="none" />
        </svg>
        <span>{{ t('app.name') }}</span>
      </div>
      <div class="landing-controls">
        <select v-model="settings.language" class="ctrl-select" @change="changeLang">
          <option value="ru">RU</option>
          <option value="en">EN</option>
        </select>
        <select v-model="settings.theme" class="ctrl-select" @change="settings.applyTheme()">
          <option value="light">{{ t('themes.light') }}</option>
          <option value="dark">{{ t('themes.dark') }}</option>
          <option value="glass">{{ t('themes.glass') }}</option>
        </select>
      </div>
    </header>

    <section class="hero">
      <h1>{{ t('app.name') }}</h1>
      <p class="tagline">{{ t('app.tagline') }}</p>
      <div class="actions">
        <router-link to="/register" class="btn btn-lg">{{ t('landing.start') }}</router-link>
        <router-link to="/login" class="btn btn-lg btn-secondary">{{ t('landing.login') }}</router-link>
      </div>
    </section>

    <section class="features">
      <h2>{{ t('landing.featuresTitle') }}</h2>
      <div class="features-grid">
        <div v-for="(feat, i) in features" :key="i" class="feature-card card">
          <div class="feature-icon">{{ feat.icon }}</div>
          <h3>{{ feat.title }}</h3>
          <p>{{ feat.desc }}</p>
        </div>
      </div>
    </section>

    <section class="reviews-section">
      <h2>{{ t('landing.reviewsTitle') }}</h2>
      <div class="reviews-grid">
        <div v-for="r in displayReviews" :key="r.id" class="review-card card">
          <div class="stars">{{ '★'.repeat(r.rating) }}{{ '☆'.repeat(5 - r.rating) }}</div>
          <p class="review-text">«{{ r.content }}»</p>
          <span class="review-author">— {{ r.username }}</span>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useSettingsStore } from '@/stores/settings'
import { api } from '@/api/client'

const { t, locale } = useI18n()
const settings = useSettingsStore()

const reviews = ref([])

const features = computed(() => [
  { icon: '📋', title: t('landing.feature1Title'), desc: t('landing.feature1Desc') },
  { icon: '👥', title: t('landing.feature2Title'), desc: t('landing.feature2Desc') },
  { icon: '💬', title: t('landing.feature3Title'), desc: t('landing.feature3Desc') },
  { icon: '🏢', title: t('landing.feature4Title'), desc: t('landing.feature4Desc') },
])

const fallbackReviews = computed(() => [
  { id: 1, rating: 5, content: locale.value === 'ru' ? 'Удобный канбан и чёткая иерархия ролей — команда работает быстрее.' : 'Handy kanban and clear role hierarchy — the team works faster.', username: 'Анна К.' },
  { id: 2, rating: 5, content: locale.value === 'ru' ? 'Коллабы и личные сообщения в одном месте. Не нужно переключаться между сервисами.' : 'Collabs and DMs in one place. No need to switch between services.', username: 'Dmitry V.' },
  { id: 3, rating: 4, content: locale.value === 'ru' ? 'Тема «Стекло» выглядит современно, задачи на доске читаются отлично.' : 'The Glass theme looks modern; tasks on the board are easy to read.', username: 'Elena M.' },
])

const displayReviews = computed(() =>
  reviews.value.length ? reviews.value.slice(0, 6) : fallbackReviews.value
)

onMounted(async () => {
  locale.value = settings.language
  try {
    const result = await api('reviews.public')
    reviews.value = result.reviews || []
  } catch {
    reviews.value = []
  }
})

function changeLang() {
  locale.value = settings.language
  localStorage.setItem('language', settings.language)
}
</script>

<style scoped>
.landing {
  min-height: 100vh;
  position: relative;
  overflow-x: hidden;
}

.bg-animation {
  position: fixed;
  inset: 0;
  z-index: 0;
  pointer-events: none;
  overflow: hidden;
}

.orb {
  position: absolute;
  border-radius: 50%;
  filter: blur(60px);
  animation: float 12s ease-in-out infinite;
}

.orb-1 {
  width: 420px;
  height: 420px;
  background: color-mix(in srgb, var(--accent) 40%, transparent);
  top: -10%;
  left: -5%;
  animation-delay: 0s;
}

.orb-2 {
  width: 350px;
  height: 350px;
  background: rgba(139, 92, 246, 0.35);
  bottom: 10%;
  right: -5%;
  animation-delay: -4s;
}

.orb-3 {
  width: 280px;
  height: 280px;
  background: rgba(34, 197, 94, 0.25);
  top: 40%;
  left: 50%;
  animation-delay: -8s;
}

.grid-lines {
  position: absolute;
  inset: 0;
  background-image:
    linear-gradient(rgba(255, 255, 255, 0.04) 1px, transparent 1px),
    linear-gradient(90deg, rgba(255, 255, 255, 0.04) 1px, transparent 1px);
  background-size: 48px 48px;
  animation: gridShift 20s linear infinite;
}

@keyframes float {
  0%, 100% { transform: translate(0, 0) scale(1); }
  33% { transform: translate(30px, -20px) scale(1.05); }
  66% { transform: translate(-20px, 25px) scale(0.95); }
}

@keyframes gridShift {
  0% { transform: translate(0, 0); }
  100% { transform: translate(48px, 48px); }
}

.landing-header,
.hero,
.features,
.reviews-section {
  position: relative;
  z-index: 1;
}

.landing-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.25rem 2rem;
}

.logo {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-weight: 700;
  font-size: 1.2rem;
}

.landing-controls {
  display: flex;
  gap: 0.5rem;
}

.ctrl-select {
  padding: 0.4rem 0.75rem;
  border: 1px solid var(--border);
  border-radius: 999px;
  background: var(--bg-secondary);
  color: var(--text-primary);
  font-size: 0.85rem;
  cursor: pointer;
}

.hero {
  text-align: center;
  padding: 4rem 2rem 3rem;
}

.hero h1 {
  font-size: clamp(2.5rem, 6vw, 4rem);
  margin-bottom: 0.75rem;
  background: linear-gradient(135deg, var(--accent), #8b5cf6);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.tagline {
  font-size: 1.25rem;
  color: var(--text-secondary);
  margin-bottom: 2rem;
  max-width: 560px;
  margin-left: auto;
  margin-right: auto;
}

.actions {
  display: flex;
  gap: 1rem;
  justify-content: center;
  flex-wrap: wrap;
}

.btn-lg {
  padding: 0.85rem 2rem;
  font-size: 1rem;
}

.features {
  padding: 3rem 2rem;
  max-width: 1100px;
  margin: 0 auto;
}

.features h2,
.reviews-section h2 {
  text-align: center;
  margin-bottom: 2rem;
  font-size: 1.75rem;
}

.features-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 1.25rem;
}

.feature-card {
  padding: 1.5rem;
  text-align: center;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.feature-card:hover {
  transform: translateY(-6px);
  box-shadow: var(--shadow-hover, var(--shadow));
}

.feature-icon {
  font-size: 2rem;
  margin-bottom: 0.75rem;
}

.feature-card h3 {
  margin-bottom: 0.5rem;
  font-size: 1.05rem;
}

.feature-card p {
  color: var(--text-secondary);
  font-size: 0.9rem;
  line-height: 1.5;
}

.reviews-section {
  padding: 3rem 2rem 4rem;
  max-width: 1100px;
  margin: 0 auto;
}

.reviews-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1.25rem;
}

.review-card {
  padding: 1.5rem;
}

.stars {
  color: #f59e0b;
  font-size: 1.1rem;
  margin-bottom: 0.75rem;
}

.review-text {
  font-style: italic;
  line-height: 1.6;
  margin-bottom: 0.75rem;
  color: var(--text-primary);
}

.review-author {
  font-size: 0.85rem;
  color: var(--text-secondary);
  font-weight: 600;
}
</style>
