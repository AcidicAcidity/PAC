import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  {
    path: '/',
    name: 'landing',
    component: () => import('../views/LandingView.vue'),
    meta: { guest: true },
  },
  {
    path: '/register',
    name: 'register',
    component: () => import('../views/RegisterView.vue'),
    meta: { guest: true },
  },
  {
    path: '/verify',
    name: 'verify',
    component: () => import('../views/VerifyView.vue'),
    meta: { guest: true },
  },
  {
    path: '/login',
    name: 'login',
    component: () => import('../views/LoginView.vue'),
    meta: { guest: true },
  },
  {
    path: '/app',
    component: () => import('../components/AppLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      { path: '', redirect: '/app/board' },
      {
        path: 'board',
        name: 'board',
        component: () => import('../views/BoardView.vue'),
      },
      {
        path: 'collabs',
        name: 'collabs',
        component: () => import('../views/CollabsView.vue'),
      },
      {
        path: 'collabs/:id',
        name: 'collab-detail',
        component: () => import('../views/CollabDetailView.vue'),
      },
      {
        path: 'messages',
        name: 'messages',
        component: () => import('../views/MessagesView.vue'),
      },
      {
        path: 'reviews',
        name: 'reviews',
        component: () => import('../views/ReviewsView.vue'),
      },
      {
        path: 'admin',
        name: 'admin',
        component: () => import('../views/AdminView.vue'),
        meta: { requiresAdmin: true },
      },
      {
        path: 'settings',
        name: 'settings',
        component: () => import('../views/SettingsView.vue'),
      },
    ],
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to) => {
  const token = localStorage.getItem('access_token')
  const user = JSON.parse(localStorage.getItem('user') || 'null')

  if (to.meta.requiresAuth && !token) {
    return { name: 'login' }
  }
  if (to.meta.requiresAdmin && user?.role !== 'admin') {
    return { name: 'board' }
  }
  if (to.meta.guest && token && ['login', 'register', 'landing'].includes(to.name)) {
    return { name: 'board' }
  }
})

export default router
