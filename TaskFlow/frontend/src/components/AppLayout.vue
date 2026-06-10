<template>
  <div class="layout">
    <nav class="sidebar">
      <div class="brand">
        <span class="brand-icon">TF</span>
        <span>{{ t('app.name') }}</span>
      </div>
      <router-link
        v-for="item in navItems"
        :key="item.to"
        :to="item.to"
        class="nav-link"
        active-class="active"
      >
        {{ item.label }}
      </router-link>
      <button class="nav-link logout" @click="handleLogout">
        {{ t('nav.logout') }}
      </button>
    </nav>
    <main class="content">
      <router-view />
    </main>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'

const { t } = useI18n()
const router = useRouter()
const auth = useAuthStore()

const navItems = computed(() => {
  const items = [
    { to: '/app/board', label: t('nav.board') },
    { to: '/app/collabs', label: t('nav.collabs') },
    { to: '/app/messages', label: t('nav.messages') },
    { to: '/app/reviews', label: t('nav.reviews') },
    { to: '/app/settings', label: t('nav.settings') },
  ]
  if (auth.isAdmin) {
    items.splice(4, 0, { to: '/app/admin', label: t('nav.admin') })
  }
  return items
})

function handleLogout() {
  auth.logout()
  router.push({ name: 'landing' })
}
</script>

<style scoped>
.layout {
  display: flex;
  min-height: 100vh;
}

.sidebar {
  width: 220px;
  background: var(--nav-bg);
  color: var(--nav-text);
  padding: 1.5rem 0;
  display: flex;
  flex-direction: column;
  flex-shrink: 0;
}

.brand {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0 1.25rem 1.5rem;
  font-weight: 700;
  font-size: 1.1rem;
}

.brand-icon {
  width: 36px;
  height: 36px;
  background: var(--accent);
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.85rem;
  color: #fff;
}

.nav-link {
  display: block;
  padding: 0.75rem 1.25rem;
  color: var(--nav-text);
  opacity: 0.8;
  border: none;
  background: none;
  width: 100%;
  text-align: left;
  cursor: pointer;
  font: inherit;
  text-decoration: none;
}

.nav-link:hover,
.nav-link.active {
  opacity: 1;
  background: rgba(255, 255, 255, 0.1);
  text-decoration: none;
}

.logout {
  margin-top: auto;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.content {
  flex: 1;
  padding: 1.5rem 2rem;
  overflow: auto;
}
</style>
