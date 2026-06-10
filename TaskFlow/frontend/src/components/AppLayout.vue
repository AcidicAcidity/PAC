<template>
  <div class="layout">
    <nav class="sidebar" :class="{ collapsed }">
      <div class="brand">
        <div class="brand-logo" title="TaskFlow">
          <svg viewBox="0 0 32 32" width="32" height="32" aria-hidden="true">
            <rect width="32" height="32" rx="9" fill="var(--accent)" />
            <path d="M9 16.5l4.5 4.5L23 11" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" fill="none" />
          </svg>
        </div>
        <span v-if="!collapsed" class="brand-name">{{ t('app.name') }}</span>
        <button
          v-if="!collapsed"
          class="collapse-btn"
          type="button"
          :title="t('nav.collapse')"
          @click="toggleCollapse"
        >
          <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true">
            <path d="M15 6l-6 6 6 6" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" />
          </svg>
        </button>
      </div>
      <button
        v-if="collapsed"
        class="expand-btn"
        type="button"
        :title="t('nav.expand')"
        @click="toggleCollapse"
      >
        <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true">
          <path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" />
        </svg>
        <span class="expand-label">{{ t('nav.expand') }}</span>
      </button>
      <router-link
        v-for="item in navItems"
        :key="item.to"
        :to="item.to"
        class="nav-link"
        active-class="active"
        :title="item.label"
      >
        <span class="nav-icon" v-html="item.icon" />
        <span v-if="!collapsed" class="nav-label">{{ item.label }}</span>
      </router-link>
      <button class="nav-link logout" :title="t('nav.logout')" @click="handleLogout">
        <span class="nav-icon">
          <svg viewBox="0 0 24 24" width="20" height="20"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/></svg>
        </span>
        <span v-if="!collapsed" class="nav-label">{{ t('nav.logout') }}</span>
      </button>
    </nav>
    <div class="main-area">
      <header class="top-bar">
        <div class="top-bar-spacer" />
        <div class="top-bar-actions">
          <router-link v-if="auth.isAdmin" to="/app/admin" class="admin-btn">
            {{ t('admin.panel') }}
          </router-link>
          <router-link to="/app/settings" class="settings-btn" :title="t('nav.settings')">
            <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true">
              <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" fill="none"/>
              <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
          </router-link>
        </div>
      </header>
      <main class="content">
        <router-view />
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'

const { t } = useI18n()
const router = useRouter()
const auth = useAuthStore()

const collapsed = ref(localStorage.getItem('sidebar_collapsed') === '1')

const navItems = computed(() => [
  {
    to: '/app/board',
    label: t('nav.board'),
    icon: '<svg viewBox="0 0 24 24" width="20" height="20"><rect x="3" y="3" width="5" height="18" rx="1" fill="currentColor" opacity=".7"/><rect x="10" y="3" width="5" height="12" rx="1" fill="currentColor" opacity=".85"/><rect x="17" y="3" width="5" height="15" rx="1" fill="currentColor"/></svg>',
  },
  {
    to: '/app/collabs',
    label: t('nav.collabs'),
    icon: '<svg viewBox="0 0 24 24" width="20" height="20"><circle cx="9" cy="8" r="3" fill="currentColor"/><circle cx="17" cy="9" r="2.5" fill="currentColor" opacity=".7"/><path d="M3 20c0-3 3-5 6-5s6 2 6 5" fill="currentColor" opacity=".6"/><path d="M15 20c0-2 2-3.5 4-3.5" stroke="currentColor" stroke-width="2" fill="none"/></svg>',
  },
  {
    to: '/app/messages',
    label: t('nav.messages'),
    icon: '<svg viewBox="0 0 24 24" width="20" height="20"><path d="M4 6h16v10H8l-4 4V6z" stroke="currentColor" stroke-width="2" fill="none" stroke-linejoin="round"/></svg>',
  },
  {
    to: '/app/reviews',
    label: t('nav.reviews'),
    icon: '<svg viewBox="0 0 24 24" width="20" height="20"><path d="M12 3l2.4 5.5H20l-4.5 3.5 1.7 5.5L12 16.5 6.8 17.5l1.7-5.5L4 8.5h5.6L12 3z" fill="currentColor"/></svg>',
  },
])

function toggleCollapse() {
  collapsed.value = !collapsed.value
  localStorage.setItem('sidebar_collapsed', collapsed.value ? '1' : '0')
}

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
  padding: 1.25rem 0;
  display: flex;
  flex-direction: column;
  flex-shrink: 0;
  transition: width 0.25s ease;
  overflow: hidden;
}

.sidebar.collapsed {
  width: 68px;
}

.brand {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  padding: 0 1rem 1.25rem;
  font-weight: 700;
  font-size: 1.05rem;
}

.brand-logo {
  flex-shrink: 0;
}

.brand-name {
  white-space: nowrap;
  overflow: hidden;
}

.collapse-btn {
  margin-left: auto;
  background: none;
  border: none;
  color: inherit;
  cursor: pointer;
  opacity: 0.7;
  padding: 0.25rem;
  border-radius: 6px;
  display: flex;
  align-items: center;
  flex-shrink: 0;
}

.sidebar.collapsed .collapse-btn {
  margin-left: 0;
}

.expand-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.35rem;
  width: calc(100% - 1rem);
  margin: 0 0.5rem 0.75rem;
  padding: 0.55rem 0.5rem;
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: var(--radius-lg);
  background: rgba(255, 255, 255, 0.08);
  color: inherit;
  cursor: pointer;
  font: inherit;
  font-size: 0.75rem;
  opacity: 0.9;
  transition: background 0.2s ease, opacity 0.2s ease;
}

.expand-btn:hover {
  opacity: 1;
  background: rgba(255, 255, 255, 0.15);
}

.sidebar:not(.collapsed) .expand-btn {
  display: none;
}

.sidebar.collapsed .expand-label {
  display: none;
}

.collapse-btn:hover {
  opacity: 1;
  background: rgba(255, 255, 255, 0.1);
}

.nav-link {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  margin: 0 0.5rem;
  color: var(--nav-text);
  opacity: 0.8;
  border: none;
  background: none;
  width: calc(100% - 1rem);
  text-align: left;
  cursor: pointer;
  font: inherit;
  text-decoration: none;
  border-radius: var(--radius-lg);
  transition: background 0.2s ease, opacity 0.2s ease;
}

.sidebar.collapsed .nav-link {
  justify-content: center;
  padding: 0.75rem;
}

.nav-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  width: 20px;
}

.nav-label {
  white-space: nowrap;
  overflow: hidden;
}

.nav-link:hover,
.nav-link.active {
  opacity: 1;
  background: rgba(255, 255, 255, 0.12);
  text-decoration: none;
}

.logout {
  margin-top: auto;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  padding-top: 1rem;
}

.main-area {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.top-bar {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  padding: 0.75rem 2rem;
  flex-shrink: 0;
}

.top-bar-actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.admin-btn {
  display: inline-flex;
  align-items: center;
  padding: 0.45rem 1rem;
  background: var(--accent);
  color: #fff;
  border-radius: 10px;
  text-decoration: none;
  font-size: 0.85rem;
  font-weight: 600;
  transition: opacity 0.2s ease, transform 0.2s ease;
}

.admin-btn:hover {
  opacity: 0.9;
  transform: translateY(-1px);
  text-decoration: none;
  color: #fff;
}

.settings-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 38px;
  height: 38px;
  border-radius: 50%;
  background: var(--bg-secondary);
  border: 1px solid var(--border);
  color: var(--text-primary);
  text-decoration: none;
  transition: background 0.2s ease, transform 0.2s ease;
}

.settings-btn:hover {
  background: var(--bg-primary);
  transform: rotate(30deg);
  text-decoration: none;
  color: var(--text-primary);
}

.content {
  flex: 1;
  padding: 0 2rem 1.5rem;
  overflow: auto;
}
</style>
