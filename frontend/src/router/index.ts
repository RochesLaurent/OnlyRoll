import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    // ========== PAGE D'ACCUEIL ==========
    {
      path: '/',
      name: 'home',
      component: () => import('@/views/HomeView.vue'),
      meta: { requiresAuth: false },
    },

    // ========== AUTHENTIFICATION ==========
    {
      path: '/auth',
      component: () => import('@/layouts/AuthLayout.vue'),
      children: [
        {
          path: 'login',
          name: 'login',
          component: () => import('@/views/auth/LoginView.vue'),
          meta: { requiresGuest: true },
        },
        {
          path: 'register',
          name: 'register',
          component: () => import('@/views/auth/RegisterView.vue'),
          meta: { requiresGuest: true },
        },
      ],
    },
    {
      path: '/auth/register-success',
      name: 'register-success',
      component: () => import('@/views/auth/RegisterSuccessView.vue'),
      meta: { requiresGuest: true },
    },

    // ========== DASHBOARD (PROTÉGÉ) ==========
    {
      path: '/dashboard',
      name: 'dashboard',
      component: () => import('@/views/dashboard/DashboardView.vue'),
      meta: { requiresAuth: true },
    },

    // ========== PROFIL UTILISATEUR (PROTÉGÉ) ==========
    // Décommentez quand vous créerez cette fonctionnalité
    // {
    //   path: '/profile',
    //   name: 'profile',
    //   component: () => import('@/views/profile/ProfileView.vue'),
    //   meta: { requiresAuth: true }
    // },

    // ========== GESTION DES PARTIES (PROTÉGÉ) ==========
    // Décommentez quand vous créerez cette fonctionnalité
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

    // ========== WIKI D&D (PUBLIC) ==========
    // Décommentez quand vous créerez cette fonctionnalité
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

    // ========== PAGE 404 ==========
    {
      path: '/:pathMatch(.*)*',
      name: 'not-found',
      component: () => import('@/views/NotFoundView.vue'),
    },
  ],
})

/**
 * Guard de navigation global
 * Gère l'authentification et les redirections
 */
router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()

  // ========== VÉRIFICATION DE L'AUTHENTIFICATION ==========

  // Si la route nécessite une authentification
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    return next({
      name: 'login',
      query: { redirect: to.fullPath },
    })
  }

  // Si la route est réservée aux invités (login/register)
  if (to.meta.requiresGuest && authStore.isAuthenticated) {
    return next({ name: 'dashboard' })
  }

  // Continuer la navigation
  next()
})

/**
 * Hook après chaque navigation
 * Utile pour analytics, scroll reset, etc.
 */
router.afterEach((to) => {
  // Scroll en haut de la page après navigation
  window.scrollTo(0, 0)

  // Mettre à jour le titre de la page
  const baseTitle = 'OnlyRoll'
  document.title = to.meta.title ? `${to.meta.title} - ${baseTitle}` : baseTitle
})

export default router
