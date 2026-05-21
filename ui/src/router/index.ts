import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    { path: '/', name: 'home', component: HomeView },
    { path: '/banks', name: 'banks', component: () => import('../views/BanksListView.vue') },
    { path: '/banks/:slug', name: 'bank-detail', component: () => import('../views/BankDetailView.vue') },
    { path: '/rates', name: 'rates', component: () => import('../views/RatesView.vue') },
    { path: '/nearest', name: 'nearest', component: () => import('../views/NearestView.vue') },
    { path: '/statistics', name: 'statistics', component: () => import('../views/StatisticsView.vue') },
    { path: '/login', name: 'login', component: () => import('../views/LoginView.vue') },
    { path: '/register', name: 'register', component: () => import('../views/RegisterView.vue') },
    {
      path: '/profile',
      name: 'profile',
      component: () => import('../views/ProfileView.vue'),
      meta: { requiresAuth: true },
    },
  ],
})

router.beforeEach(async (to) => {
  if (!to.meta.requiresAuth) return true
  const { useAuthStore } = await import('@/stores/auth')
  const auth = useAuthStore()
  if (!auth.isAuthenticated) await auth.fetchMe()
  if (!auth.isAuthenticated) return { name: 'login', query: { redirect: to.fullPath } }
  return true
})

export default router
