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
    me: vi.fn(),
    fetchMe: vi.fn(),
  },
}))

describe('Auth Store', () => {
  beforeEach(() => {
    // Créer un nouveau Pinia pour chaque test
    setActivePinia(createPinia())
    
    // Nettoyer le localStorage
    localStorage.clear()
    
    // Reset tous les mocks
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

      // Recréer le store pour qu'il lise le localStorage
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

    it('currentUser retourne l\'utilisateur actuel', () => {
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

      authStore.setUser(mockUser)

      expect(authStore.currentUser).toEqual(mockUser)
    })
  })

  describe('Actions - setToken', () => {
    it('définit le token et le stocke dans localStorage', () => {
      const authStore = useAuthStore()
      const token = 'new-token-456'

      authStore.setToken(token)

      expect(authStore.token).toBe(token)
      expect(localStorage.getItem('auth_token')).toBe(token)
    })

    it('supprime le token du localStorage quand null', () => {
      const authStore = useAuthStore()
      localStorage.setItem('auth_token', 'old-token')

      authStore.setToken(null)

      expect(authStore.token).toBeNull()
      expect(localStorage.getItem('auth_token')).toBeNull()
    })
  })

  describe('Actions - setUser', () => {
    it('définit l\'utilisateur', () => {
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

      authStore.setUser(mockUser)

      expect(authStore.user).toEqual(mockUser)
    })

    it('peut réinitialiser l\'utilisateur à null', () => {
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

      authStore.setUser(mockUser)
      authStore.setUser(null)

      expect(authStore.user).toBeNull()
    })
  })

  describe('Actions - error management', () => {
    it('setError définit une erreur', () => {
      const authStore = useAuthStore()
      const errorMessage = 'Une erreur est survenue'

      authStore.setError(errorMessage)

      expect(authStore.error).toBe(errorMessage)
    })

    it('clearError efface l\'erreur', () => {
      const authStore = useAuthStore()
      authStore.setError('Erreur')

      authStore.clearError()

      expect(authStore.error).toBeNull()
    })
  })

  describe('Actions - register', () => {
    it('inscrit un utilisateur avec succès', async () => {
      const authStore = useAuthStore()
      const credentials: RegisterCredentials = {
        pseudo: 'NewUser',
        email: 'newuser@onlyroll.com',
        password: 'Password123!',
        confirmPassword: 'Password123!',
      }

      const mockResponse = {
        message: 'User created successfully',
        user: {
          id: 1,
          email: credentials.email,
          pseudo: credentials.pseudo,
        },
      }

      vi.mocked(authApi.register).mockResolvedValue(mockResponse)

      await authStore.register(credentials)

      expect(authApi.register).toHaveBeenCalledWith(credentials)
      expect(authStore.isLoading).toBe(false)
      expect(authStore.error).toBeNull()
    })

    it('gère les erreurs d\'inscription', async () => {
      const authStore = useAuthStore()
      const credentials: RegisterCredentials = {
        pseudo: 'NewUser',
        email: 'newuser@onlyroll.com',
        password: 'Password123!',
        confirmPassword: 'Password123!',
      }

      const errorMessage = 'Email déjà utilisé'
      vi.mocked(authApi.register).mockRejectedValue({
        error: errorMessage,
      })

      await expect(authStore.register(credentials)).rejects.toThrow(errorMessage)

      expect(authStore.error).toBe(errorMessage)
      expect(authStore.isLoading).toBe(false)
    })

    it('active isLoading pendant l\'inscription', async () => {
      const authStore = useAuthStore()
      const credentials: RegisterCredentials = {
        pseudo: 'NewUser',
        email: 'newuser@onlyroll.com',
        password: 'Password123!',
        confirmPassword: 'Password123!',
      }

      let loadingDuringCall = false
      vi.mocked(authApi.register).mockImplementation(async () => {
        loadingDuringCall = authStore.isLoading
        return Promise.resolve({
          message: 'Success',
          user: { id: 1, email: credentials.email, pseudo: credentials.pseudo },
        })
      })

      await authStore.register(credentials)

      expect(loadingDuringCall).toBe(true)
      expect(authStore.isLoading).toBe(false)
    })
  })

  describe('Actions - login', () => {
    it('connecte un utilisateur avec succès', async () => {
      const authStore = useAuthStore()
      const credentials: LoginCredentials = {
        email: 'user@onlyroll.com',
        password: 'Password123!',
      }

      const mockUser: User = {
        id: 1,
        email: credentials.email,
        pseudo: 'TestUser',
        roles: ['ROLE_USER'],
        isVerified: false,
        createdAt: expect.any(String),
        updatedAt: expect.any(String)
      }

      vi.mocked(authApi.login).mockResolvedValue({
        success: true,
        message: "Login successful",
        user_id: 1,
        user_email: credentials.email,
        user_pseudo: "TestUser",
        user_verified: false,
        user_roles: ["ROLE_USER"],
      })

      vi.mocked(authApi.me).mockResolvedValue(mockUser)

      await authStore.login(credentials)

      expect(authApi.login).toHaveBeenCalledWith(credentials)
      
      expect(authStore.token).toBeTruthy()
      expect(authStore.token).toMatch(/^mock_jwt_1_\d+$/)
      
      expect(authStore.user).toEqual(mockUser)
      expect(authStore.isAuthenticated).toBe(true)
      expect(authStore.error).toBeNull()
      
      expect(localStorage.getItem('auth_token')).toBe(authStore.token)
    })

    it('gère les erreurs de connexion', async () => {
      const authStore = useAuthStore()
      const credentials: LoginCredentials = {
        email: 'wrong@onlyroll.com',
        password: 'WrongPassword!',
      }

      const errorMessage = 'Identifiants invalides'
      vi.mocked(authApi.login).mockRejectedValue({
        error: errorMessage,
      })

      await expect(authStore.login(credentials)).rejects.toThrow(errorMessage)

      expect(authStore.error).toBe(errorMessage)
      expect(authStore.token).toBeNull()
      expect(authStore.user).toBeNull()
      expect(authStore.isAuthenticated).toBe(false)
    })

    it('stocke le token avant de récupérer l\'utilisateur', async () => {
      const authStore = useAuthStore()
      const credentials: LoginCredentials = {
        email: 'user@onlyroll.com',
        password: 'Password123!',
      }

      let tokenSetBeforeFetchMe = false

      vi.mocked(authApi.login).mockResolvedValue({
        success: true,
        message: "Login successful",
        user_id: 1,
        user_email: credentials.email,
        user_pseudo: "TestUser",
        user_verified: false,
        user_roles: ["ROLE_USER"],
      })

      vi.mocked(authApi.me).mockImplementation(async () => {
        tokenSetBeforeFetchMe = !!authStore.token
        return {
          id: 1,
          email: credentials.email,
          pseudo: 'User',
          roles: ['ROLE_USER'],
          isVerified: false,
          createdAt: '',
          updatedAt: ''
        }
      })

      await authStore.login(credentials)

      expect(tokenSetBeforeFetchMe).toBe(true)
    })
  })

  describe('Actions - fetchMe', () => {
    it('récupère les informations de l\'utilisateur connecté', async () => {
      const authStore = useAuthStore()
      const mockUser: User = {
          id: 1,
          email: 'user@onlyroll.com',
          pseudo: 'TestUser',
          roles: ['ROLE_USER', 'ROLE_GM'],
          isVerified: true,
          createdAt: expect.any(String),
          updatedAt: expect.any(String)
      }

      authStore.setToken('valid-token')
      vi.mocked(authApi.me).mockResolvedValue(mockUser)

      await authStore.fetchMe()

      expect(authApi.me).toHaveBeenCalled()
      expect(authStore.user).toEqual(mockUser)
      expect(authStore.error).toBeNull()
    })

    it('gère les erreurs de récupération du profil', async () => {
      const authStore = useAuthStore()
      authStore.setToken('invalid-token')

      const errorMessage = 'Token invalide'
      vi.mocked(authApi.me).mockRejectedValue({
        error: errorMessage,
      })

      await expect(authStore.fetchMe()).rejects.toThrow(errorMessage)

      expect(authStore.error).toBe(errorMessage)
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