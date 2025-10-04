/**
 * Tests unitaires pour le store d'authentification
 * 
 * @covers src/stores/auth.ts
 */

import { describe, it, expect, beforeEach, vi } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useAuthStore } from '@/stores/auth'
import { authApi } from '@/services/api/authApi'
import type { User, LoginCredentials, RegisterCredentials } from '@/types/auth'

// Mock de l'API
vi.mock('@/services/api/authApi', () => ({
  authApi: {
    register: vi.fn(),
    login: vi.fn(),
    logout: vi.fn(),
    me: vi.fn(),
  },
}))

describe('Auth Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    localStorage.clear()
    vi.clearAllMocks()
  })

  describe('État initial', () => {
    it('a les bonnes valeurs par défaut', () => {
      const authStore = useAuthStore()

      expect(authStore.user).toBeNull()
      expect(authStore.token).toBeNull()
      expect(authStore.isLoading).toBe(false)
      expect(authStore.error).toBeNull()
      expect(authStore.isAuthenticated).toBe(false)
    })

    it('charge le token depuis localStorage si présent', () => {
      const mockToken = 'stored-token-123'
      localStorage.setItem('auth_token', mockToken)

      setActivePinia(createPinia())
      const authStore = useAuthStore()

      expect(authStore.token).toBe(mockToken)
    })
  })

  describe('Computed properties', () => {
    it('isAuthenticated est false quand pas de token', () => {
      const authStore = useAuthStore()
      expect(authStore.isAuthenticated).toBe(false)
    })

    it('isAuthenticated est false quand token mais pas d\'utilisateur', () => {
      const authStore = useAuthStore()
      authStore.setToken('some-token')
      expect(authStore.isAuthenticated).toBe(false)
    })

    it('isAuthenticated est true quand token ET utilisateur présents', () => {
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

      authStore.setToken('valid-token')
      authStore.setUser(mockUser)

      expect(authStore.isAuthenticated).toBe(true)
    })

    it('isGameMaster est true pour ROLE_GM', () => {
      const authStore = useAuthStore()
      authStore.setUser({
        id: 1,
        email: 'gm@onlyroll.com',
        pseudo: 'GameMaster',
        roles: ['ROLE_GM'],
        isVerified: true,
        createdAt: '',
        updatedAt: ''
      })

      expect(authStore.isGameMaster).toBe(true)
    })

    it('isAdmin est true pour ROLE_ADMIN', () => {
      const authStore = useAuthStore()
      authStore.setUser({
        id: 1,
        email: 'admin@onlyroll.com',
        pseudo: 'Admin',
        roles: ['ROLE_ADMIN'],
        isVerified: true,
        createdAt: '',
        updatedAt: ''
      })

      expect(authStore.isAdmin).toBe(true)
    })
  })

  describe('Actions - logout', () => {
    it('appelle l\'API logout et nettoie tout', async () => {
      const authStore = useAuthStore()
      const mockUser: User = {
        id: 1,
        email: 'user@onlyroll.com',
        pseudo: 'TestUser',
        roles: ['ROLE_USER'],
        isVerified: false,
        createdAt: '',
        updatedAt: ''
      }

      authStore.setToken('token-123')
      authStore.setUser(mockUser)
      authStore.setError('Une erreur')

      vi.mocked(authApi.logout).mockResolvedValue()

      await authStore.logout()

      expect(authApi.logout).toHaveBeenCalled()
      expect(authStore.user).toBeNull()
      expect(authStore.token).toBeNull()
      expect(authStore.error).toBeNull()
      expect(authStore.isAuthenticated).toBe(false)
      expect(localStorage.getItem('auth_token')).toBeNull()
    })

    it('nettoie même si l\'API logout échoue', async () => {
      const authStore = useAuthStore()
      authStore.setToken('token-123')

      vi.mocked(authApi.logout).mockRejectedValue(new Error('API error'))
      const consoleErrorSpy = vi.spyOn(console, 'error').mockImplementation(() => {})

      await authStore.logout()

      expect(authStore.token).toBeNull()
      expect(authStore.user).toBeNull()
      
      consoleErrorSpy.mockRestore()
    })
  })

  describe('Actions - hasRole', () => {
    it('vérifie correctement la présence d\'un rôle', () => {
      const authStore = useAuthStore()
      const mockUser: User = {
        id: 1,
        email: 'user@onlyroll.com',
        pseudo: 'TestUser',
        roles: ['ROLE_USER', 'ROLE_GM'],
        isVerified: false,
        createdAt: '',
        updatedAt: ''
      }

      authStore.setUser(mockUser)

      expect(authStore.hasRole('ROLE_USER')).toBe(true)
      expect(authStore.hasRole('ROLE_GM')).toBe(true)
      expect(authStore.hasRole('ROLE_ADMIN')).toBe(false)
    })

    it('retourne false si pas d\'utilisateur', () => {
      const authStore = useAuthStore()
      expect(authStore.hasRole('ROLE_USER')).toBe(false)
    })
  })

  describe('Actions - hasAnyRole', () => {
    it('retourne true si l\'utilisateur a au moins un rôle', () => {
      const authStore = useAuthStore()
      authStore.setUser({
        id: 1,
        email: 'user@onlyroll.com',
        pseudo: 'TestUser',
        roles: ['ROLE_USER'],
        isVerified: false,
        createdAt: '',
        updatedAt: ''
      })

      expect(authStore.hasAnyRole(['ROLE_USER', 'ROLE_ADMIN'])).toBe(true)
      expect(authStore.hasAnyRole(['ROLE_GM', 'ROLE_ADMIN'])).toBe(false)
    })
  })

  describe('Actions - hasAllRoles', () => {
    it('retourne true si l\'utilisateur a tous les rôles', () => {
      const authStore = useAuthStore()
      authStore.setUser({
        id: 1,
        email: 'user@onlyroll.com',
        pseudo: 'TestUser',
        roles: ['ROLE_USER', 'ROLE_GM'],
        isVerified: false,
        createdAt: '',
        updatedAt: ''
      })

      expect(authStore.hasAllRoles(['ROLE_USER', 'ROLE_GM'])).toBe(true)
      expect(authStore.hasAllRoles(['ROLE_USER', 'ROLE_ADMIN'])).toBe(false)
    })
  })

  describe('Actions - fetchMe', () => {
    it('récupère les informations avec lastLogin', async () => {
      const authStore = useAuthStore()
      const mockUser: User = {
        id: 1,
        email: 'user@onlyroll.com',
        pseudo: 'TestUser',
        roles: ['ROLE_USER'],
        isVerified: true,
        lastLogin: '2025-01-15T10:30:00Z',
        createdAt: '2025-01-01T00:00:00Z',
        updatedAt: '2025-01-15T10:30:00Z'
      }

      authStore.setToken('valid-token')
      vi.mocked(authApi.me).mockResolvedValue(mockUser)

      await authStore.fetchMe()

      expect(authApi.me).toHaveBeenCalled()
      expect(authStore.user).toEqual(mockUser)
      expect(authStore.user?.lastLogin).toBe('2025-01-15T10:30:00Z')
    })
  })

  describe('Actions - logout', () => {
    it('déconnecte l\'utilisateur et nettoie tout', () => {
      const authStore = useAuthStore()
      const mockUser: User = {
          id: 1,
          email: 'user@onlyroll.com',
          pseudo: 'TestUser',
          roles: ['ROLE_USER'],
          isVerified: false,
          createdAt: '',
          updatedAt: ''
      }

      // Simuler un utilisateur connecté
      authStore.setToken('token-123')
      authStore.setUser(mockUser)

      authStore.logout()

      expect(authStore.user).toBeNull()
      expect(authStore.token).toBeNull()
      expect(authStore.isAuthenticated).toBe(false)
      expect(localStorage.getItem('auth_token')).toBeNull()
    })

    it('efface aussi l\'erreur lors du logout', () => {
      const authStore = useAuthStore()
      authStore.setError('Une erreur')

      authStore.logout()

      expect(authStore.error).toBeNull()
    })
  })

  describe('Actions - hasRole', () => {
    it('vérifie correctement la présence d\'un rôle', () => {
      const authStore = useAuthStore()
      const mockUser: User = {
          id: 1,
          email: 'user@onlyroll.com',
          pseudo: 'TestUser',
          roles: ['ROLE_USER', 'ROLE_GM'],
          isVerified: false,
          createdAt: '',
          updatedAt: ''
      }

      authStore.setUser(mockUser)

      expect(authStore.hasRole('ROLE_USER')).toBe(true)
      expect(authStore.hasRole('ROLE_GM')).toBe(true)
      expect(authStore.hasRole('ROLE_ADMIN')).toBe(false)
    })

    it('retourne false si pas d\'utilisateur', () => {
      const authStore = useAuthStore()

      expect(authStore.hasRole('ROLE_USER')).toBe(false)
    })
  })

  describe('Scénarios complets', () => {
    it('cycle complet : register → login → fetchMe → logout', async () => {
      const authStore = useAuthStore()

      // 1. Inscription
      const registerCreds: RegisterCredentials = {
        pseudo: 'NewUser',
        email: 'new@onlyroll.com',
        password: 'Pass123!',
        confirmPassword: 'Pass123!',
      }

      vi.mocked(authApi.register).mockResolvedValue({
        message: 'Success',
        user: { id: 1, email: registerCreds.email, pseudo: registerCreds.pseudo },
      })

      await authStore.register(registerCreds)
      expect(authStore.isAuthenticated).toBe(false) // Pas encore connecté

      // 2. Connexion
      const loginCreds: LoginCredentials = {
        email: registerCreds.email,
        password: registerCreds.password,
      }

      const mockUser: User = {
          id: 1,
          email: loginCreds.email,
          pseudo: 'NewUser',
          roles: ['ROLE_USER'],
          isVerified: false,
          createdAt: '',
          updatedAt: ''
      }

      vi.mocked(authApi.login).mockResolvedValue({
        success: true,
        message: "Login successful",
        user_id: 1,
        user_email: loginCreds.email,
        user_pseudo: "NewUser",
        user_verified: false,
        user_roles: ["ROLE_USER"],
      })

      vi.mocked(authApi.me).mockResolvedValue(mockUser)

      await authStore.login(loginCreds)
      expect(authStore.isAuthenticated).toBe(true)

      // 3. Déconnexion
      authStore.logout()
      expect(authStore.isAuthenticated).toBe(false)
      expect(authStore.user).toBeNull()
      expect(authStore.token).toBeNull()
    })
  })
})