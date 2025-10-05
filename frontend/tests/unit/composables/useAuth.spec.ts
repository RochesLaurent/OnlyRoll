/**
 * Tests unitaires pour le composable useAuth
 * 
 * @covers src/composables/useAuth.ts
 */

import { describe, it, expect, beforeEach, vi } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useAuth } from '@/composables/useAuth'
import { useAuthStore } from '@/stores/auth'
import type { User } from '@/types/auth'

// Mock du router
const mockPush = vi.fn()
const mockCurrentRoute = { value: { fullPath: '/dashboard' } }
vi.mock('vue-router', () => ({
  useRouter: () => ({
    push: mockPush,
    currentRoute: mockCurrentRoute,
  }),
}))

// Mock de l'API
vi.mock('@/services/api/authApi', () => ({
  authApi: {
    register: vi.fn(),
    login: vi.fn(),
    logout: vi.fn(),
    me: vi.fn(),
  },
}))

describe('useAuth Composable', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    localStorage.clear()
    vi.clearAllMocks()
  })

  describe('Propriétés computed', () => {
    it('expose les propriétés du store', () => {
      const { user, isAuthenticated, isLoading, error } = useAuth()

      expect(user.value).toBeNull()
      expect(isAuthenticated.value).toBe(false)
      expect(isLoading.value).toBe(false)
      expect(error.value).toBeNull()
    })

    it('réagit aux changements du store', () => {
      const { user, isAuthenticated } = useAuth()
      const authStore = useAuthStore()

      const mockUser: User = {
        id: 1,
        email: 'test@onlyroll.com',
        pseudo: 'TestUser',
        roles: ['ROLE_USER'],
        isVerified: false,
        createdAt: '',
        updatedAt: ''
      }

      authStore.setToken('token-123')
      authStore.setUser(mockUser)

      expect(user.value).toEqual(mockUser)
      expect(isAuthenticated.value).toBe(true)
    })
  })

  describe('login', () => {
    it('connecte l\'utilisateur et redirige vers le dashboard', async () => {
      const { login } = useAuth()
      const authStore = useAuthStore()

      vi.spyOn(authStore, 'login').mockResolvedValue()

      await login({ email: 'test@onlyroll.com', password: 'Pass123!' })

      expect(authStore.login).toHaveBeenCalledWith({
        email: 'test@onlyroll.com',
        password: 'Pass123!',
      })
      expect(mockPush).toHaveBeenCalledWith({ name: 'dashboard' })
    })

    it('redirige vers une route spécifique si fournie', async () => {
      const { login } = useAuth()
      const authStore = useAuthStore()

      vi.spyOn(authStore, 'login').mockResolvedValue()

      await login(
        { email: 'test@onlyroll.com', password: 'Pass123!' },
        '/games/123'
      )

      expect(mockPush).toHaveBeenCalledWith('/games/123')
    })

    it('propage les erreurs de connexion', async () => {
      const { login } = useAuth()
      const authStore = useAuthStore()

      const error = new Error('Identifiants invalides')
      vi.spyOn(authStore, 'login').mockRejectedValue(error)

      await expect(
        login({ email: 'wrong@email.com', password: 'WrongPass' })
      ).rejects.toThrow('Identifiants invalides')

      expect(mockPush).not.toHaveBeenCalled()
    })
  })

  describe('register', () => {
    it('inscrit l\'utilisateur et redirige vers register-success', async () => {
      const { register } = useAuth()
      const authStore = useAuthStore()

      vi.spyOn(authStore, 'register').mockResolvedValue()

      const credentials = {
        pseudo: 'NewUser',
        email: 'new@onlyroll.com',
        password: 'Pass123!',
        confirmPassword: 'Pass123!',
      }

      await register(credentials)

      expect(authStore.register).toHaveBeenCalledWith(credentials)
      expect(mockPush).toHaveBeenCalledWith({
        name: 'register-success',
        query: { email: credentials.email },
      })
    })

    it('propage les erreurs d\'inscription', async () => {
      const { register } = useAuth()
      const authStore = useAuthStore()

      const error = new Error('Email déjà utilisé')
      vi.spyOn(authStore, 'register').mockRejectedValue(error)

      await expect(
        register({
          pseudo: 'User',
          email: 'existing@onlyroll.com',
          password: 'Pass123!',
          confirmPassword: 'Pass123!',
        })
      ).rejects.toThrow('Email déjà utilisé')

      expect(mockPush).not.toHaveBeenCalled()
    })
  })

  describe('logout', () => {
    it('déconnecte l\'utilisateur et redirige vers home', async () => {
      const { logout } = useAuth()
      const authStore = useAuthStore()

      vi.spyOn(authStore, 'logout').mockResolvedValue()

      await logout()

      expect(authStore.logout).toHaveBeenCalled()
      expect(mockPush).toHaveBeenCalledWith({ name: 'home' })
    })

    it('redirige vers une route spécifique si fournie', async () => {
      const { logout } = useAuth()
      const authStore = useAuthStore()

      vi.spyOn(authStore, 'logout').mockResolvedValue()

      await logout('/goodbye')

      expect(mockPush).toHaveBeenCalledWith('/goodbye')
    })

    it('redirige même si logout échoue', async () => {
      const { logout } = useAuth()
      const authStore = useAuthStore()

      vi.spyOn(authStore, 'logout').mockRejectedValue(new Error('API error'))
      const consoleErrorSpy = vi.spyOn(console, 'error').mockImplementation(() => {})

      await logout()

      expect(mockPush).toHaveBeenCalledWith({ name: 'home' })
      expect(consoleErrorSpy).toHaveBeenCalled()
      
      consoleErrorSpy.mockRestore()
    })
  })

  describe('hasRole', () => {
    it('retourne true si l\'utilisateur a le rôle', () => {
      const { hasRole } = useAuth()
      const authStore = useAuthStore()

      authStore.setUser({
        id: 1,
        email: 'test@onlyroll.com',
        pseudo: 'TestUser',
        roles: ['ROLE_USER', 'ROLE_GM'],
        isVerified: false,
        createdAt: '',
        updatedAt: ''
      })

      expect(hasRole('ROLE_USER')).toBe(true)
      expect(hasRole('ROLE_GM')).toBe(true)
    })

    it('retourne false si l\'utilisateur n\'a pas le rôle', () => {
      const { hasRole } = useAuth()
      const authStore = useAuthStore()

      authStore.setUser({
        id: 1,
        email: 'test@onlyroll.com',
        pseudo: 'TestUser',
        roles: ['ROLE_USER'],
        isVerified: false,
        createdAt: '',
        updatedAt: ''
      })

      expect(hasRole('ROLE_ADMIN')).toBe(false)
    })
  })

  describe('hasAnyRole', () => {
    it('retourne true si l\'utilisateur a au moins un des rôles', () => {
      const { hasAnyRole } = useAuth()
      const authStore = useAuthStore()

      authStore.setUser({
        id: 1,
        email: 'test@onlyroll.com',
        pseudo: 'TestUser',
        roles: ['ROLE_USER'],
        isVerified: false,
        createdAt: '',
        updatedAt: ''
      })

      expect(hasAnyRole(['ROLE_USER', 'ROLE_ADMIN'])).toBe(true)
      expect(hasAnyRole(['ROLE_GM', 'ROLE_ADMIN'])).toBe(false)
    })
  })

  describe('hasAllRoles', () => {
    it('retourne true si l\'utilisateur a tous les rôles', () => {
      const { hasAllRoles } = useAuth()
      const authStore = useAuthStore()

      authStore.setUser({
        id: 1,
        email: 'test@onlyroll.com',
        pseudo: 'TestUser',
        roles: ['ROLE_USER', 'ROLE_GM'],
        isVerified: false,
        createdAt: '',
        updatedAt: ''
      })

      expect(hasAllRoles(['ROLE_USER', 'ROLE_GM'])).toBe(true)
      expect(hasAllRoles(['ROLE_USER', 'ROLE_ADMIN'])).toBe(false)
    })
  })

  describe('requireAuth', () => {
    it('retourne true si l\'utilisateur est authentifié', () => {
      const { requireAuth } = useAuth()
      const authStore = useAuthStore()

      authStore.setToken('token')
      authStore.setUser({
        id: 1,
        email: 'test@onlyroll.com',
        pseudo: 'TestUser',
        roles: ['ROLE_USER'],
        isVerified: false,
        createdAt: '',
        updatedAt: ''
      })

      expect(requireAuth()).toBe(true)
      expect(mockPush).not.toHaveBeenCalled()
    })

    it('redirige vers login avec redirect query et retourne false si non authentifié', () => {
      const { requireAuth } = useAuth()

      expect(requireAuth()).toBe(false)
      expect(mockPush).toHaveBeenCalledWith({
        name: 'login',
        query: { redirect: '/dashboard' }
      })
    })
  })

  describe('requireGuest', () => {
    it('retourne true si l\'utilisateur n\'est pas authentifié', () => {
      const { requireGuest } = useAuth()

      expect(requireGuest()).toBe(true)
      expect(mockPush).not.toHaveBeenCalled()
    })

    it('redirige vers dashboard et retourne false si authentifié', () => {
      const { requireGuest } = useAuth()
      const authStore = useAuthStore()

      authStore.setToken('token')
      authStore.setUser({
        id: 1,
        email: 'test@onlyroll.com',
        pseudo: 'TestUser',
        roles: ['ROLE_USER'],
        isVerified: false,
        createdAt: '',
        updatedAt: ''
      })

      expect(requireGuest()).toBe(false)
      expect(mockPush).toHaveBeenCalledWith({ name: 'dashboard' })
    })
  })

  describe('Gestion des erreurs', () => {
    it('clearError efface l\'erreur du store', () => {
      const { clearError, error } = useAuth()
      const authStore = useAuthStore()

      authStore.setError('Une erreur')
      expect(error.value).toBe('Une erreur')

      clearError()
      expect(error.value).toBeNull()
    })

    it('setError définit une erreur dans le store', () => {
      const { setError, error } = useAuth()

      setError('Nouvelle erreur')
      expect(error.value).toBe('Nouvelle erreur')
    })

    it('getErrorMessage extrait le message d\'une erreur string', () => {
      const { getErrorMessage } = useAuth()

      const message = getErrorMessage('Erreur simple')
      expect(message).toBe('Erreur simple')
    })

    it('getErrorMessage extrait le message d\'un ApiError', () => {
      const { getErrorMessage } = useAuth()

      const message = getErrorMessage({ 
        error: 'Bad Request',
        message: 'Email déjà utilisé',
        statusCode: 400 
      })
      expect(message).toBe('Email déjà utilisé')
    })

    it('getErrorMessage retourne un message par défaut pour erreur inconnue', () => {
      const { getErrorMessage } = useAuth()

      const message = getErrorMessage({})
      expect(message).toBe('Une erreur inattendue s\'est produite')
    })

    it('formatError inclut le status code', () => {
      const { formatError } = useAuth()

      const formatted = formatError({
        error: 'Unauthorized',
        message: 'Token invalide',
        statusCode: 401
      })
      expect(formatted).toBe('[401] Token invalide')
    })

    it('isAuthError détecte les erreurs 401', () => {
      const { isAuthError } = useAuth()

      expect(isAuthError({ statusCode: 401 })).toBe(true)
      expect(isAuthError({ statusCode: 400 })).toBe(false)
      expect(isAuthError('error')).toBe(false)
    })

    it('isValidationError détecte les erreurs 422', () => {
      const { isValidationError } = useAuth()

      expect(isValidationError({ statusCode: 422 })).toBe(true)
      expect(isValidationError({ statusCode: 400 })).toBe(false)
    })
  })
})