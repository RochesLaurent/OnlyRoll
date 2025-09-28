// src/router/index.ts
import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'home',
      component: () => import('@/views/HomeView.vue'),
      meta: { requiresAuth: false }
    },
    
    // Routes d'authentification
    {
      path: '/auth',
      component: () => import('@/layouts/AuthLayout.vue'),
      children: [
        {
          path: 'login',
          name: 'login',
          component: () => import('@/views/auth/LoginView.vue'),
          meta: { requiresGuest: true }
        },
        {
          path: 'register',
          name: 'register',
          component: () => import('@/views/auth/RegisterView.vue'),
          meta: { requiresGuest: true }
        }
      ]
    },
    
    // Page de succès d'inscription
    {
      path: '/auth/register-success',
      name: 'register-success',
      component: () => import('@/views/auth/RegisterSuccessView.vue'),
      meta: { requiresGuest: true }
    },
    
    // Routes protégées
    {
      path: '/dashboard',
      name: 'dashboard',
      component: () => import('@/views/dashboard/DashboardView.vue'),
      meta: { requiresAuth: true }
    },
    
    // {
    //   path: '/profile',
    //   name: 'profile',
    //   component: () => import('@/views/profile/ProfileView.vue'),
    //   meta: { requiresAuth: true }
    // },
    
    // Routes des parties
    // {
    //   path: '/games',
    //   meta: { requiresAuth: true },
    //   children: [
    //     {
    //       path: '',
    //       name: 'games.list',
    //       component: () => import('@/views/games/GameListView.vue')
    //     },
    //     {
    //       path: 'create',
    //       name: 'games.create',
    //       component: () => import('@/views/games/GameCreateView.vue')
    //     },
    //     {
    //       path: ':id',
    //       name: 'games.play',
    //       component: () => import('@/views/games/GamePlayView.vue'),
    //       props: true
    //     }
    //   ]
    // },
    
    // Wiki D&D
    // {
    //   path: '/wiki',
    //   children: [
    //     {
    //       path: '',
    //       name: 'wiki.home',
    //       component: () => import('@/views/wiki/WikiHomeView.vue')
    //     },
    //     {
    //       path: 'spells',
    //       name: 'wiki.spells',
    //       component: () => import('@/views/wiki/SpellsView.vue')
    //     },
    //     {
    //       path: 'spells/:id',
    //       name: 'wiki.spell-detail',
    //       component: () => import('@/views/wiki/SpellDetailView.vue'),
    //       props: true
    //     }
    //   ]
    // },
    
    // Page 404
    {
      path: '/:pathMatch(.*)*',
      name: 'not-found',
      component: () => import('@/views/NotFoundView.vue')
    }
  ]
})

// Guard de navigation pour l'authentification
router.beforeEach(async (to) => {
  const authStore = useAuthStore()
  
  // Initialiser le store auth s'il n'est pas déjà fait
  if (authStore.token && !authStore.user) {
    try {
      await authStore.fetchMe()
    } catch (error) {
      // Le token est invalide, on déconnecte l'utilisateur
      authStore.logout()
    }
  }
  
  // Vérifier si la route nécessite une authentification
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    return {
      name: 'login',
      query: { redirect: to.fullPath }
    }
  }
  
  // Vérifier si la route nécessite d'être déconnecté
  if (to.meta.requiresGuest && authStore.isAuthenticated) {
    return { name: 'dashboard' }
  }
  
  return true
})

export default router