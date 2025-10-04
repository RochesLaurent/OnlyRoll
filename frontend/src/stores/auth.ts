import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authApi } from '@/services/api/authApi'
import type { ApiError } from '@/services/api/apiClient'
import type { User, LoginCredentials, RegisterCredentials } from '@/types/auth'

/**
 * Store Pinia pour la gestion de l'authentification
 * Gère l'état utilisateur, le token JWT et les opérations d'auth
 */
export const useAuthStore = defineStore('auth', () => {
  // ========== STATE ==========
  const user = ref<User | null>(null)
  const token = ref<string | null>(localStorage.getItem('auth_token'))
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  // ========== GETTERS ==========

  /**
   * Vérifie si l'utilisateur est authentifié
   */
  const isAuthenticated = computed(() => !!token.value && !!user.value)

  /**
   * Retourne l'utilisateur courant
   */
  const currentUser = computed(() => user.value)

  // ========== MUTATIONS (setters privés) ==========

  /**
   * Définit le token JWT et le persiste dans localStorage
   */
  const setToken = (newToken: string | null): void => {
    token.value = newToken
    if (newToken) {
      localStorage.setItem('auth_token', newToken)
    } else {
      localStorage.removeItem('auth_token')
    }
  }

  /**
   * Définit l'utilisateur courant
   */
  const setUser = (newUser: User | null): void => {
    user.value = newUser
  }

  /**
   * Définit un message d'erreur
   */
  const setError = (newError: string | null): void => {
    error.value = newError
  }

  /**
   * Efface l'erreur courante
   */
  const clearError = (): void => {
    error.value = null
  }

  // ========== HELPERS ==========

  /**
   * Extrait un message d'erreur lisible
   */
  const getErrorMessage = (err: unknown, defaultMessage: string): string => {
    if (typeof err === 'string') return err

    if (err && typeof err === 'object') {
      const apiError = err as ApiError

      if ('message' in apiError && typeof apiError.message === 'string') {
        return apiError.message
      }

      if ('error' in apiError && typeof apiError.error === 'string') {
        return apiError.error
      }
    }

    if (err instanceof Error) {
      return err.message
    }

    return defaultMessage
  }

  // ========== ACTIONS ==========

  /**
   * Inscription d'un nouvel utilisateur
   */
  const register = async (credentials: RegisterCredentials): Promise<void> => {
    isLoading.value = true
    error.value = null

    try {
      await authApi.register(credentials)
      // Pas de connexion automatique après inscription
      // L'utilisateur devra confirmer son email ou se connecter manuellement
    } catch (err: unknown) {
      const errorMessage = getErrorMessage(err, "Erreur lors de l'inscription")
      error.value = errorMessage
      throw new Error(errorMessage)
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Connexion d'un utilisateur
   */
  const login = async (credentials: LoginCredentials): Promise<void> => {
    isLoading.value = true
    error.value = null

    try {
      // Appel à /api/login qui retourne { token: string }
      const response = await authApi.login(credentials)

      // Stocker le vrai token JWT
      setToken(response.token)

      // Récupérer les infos utilisateur avec le token
      await fetchMe()
    } catch (err: unknown) {
      const errorMessage = getErrorMessage(err, 'Erreur lors de la connexion')
      error.value = errorMessage
      throw new Error(errorMessage)
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Récupère les informations de l'utilisateur connecté
   */
  const fetchMe = async (): Promise<void> => {
    if (!token.value) return

    isLoading.value = true
    error.value = null

    try {
      const response = await authApi.me()

      const userData: User = {
        id: response.id,
        email: response.email,
        pseudo: response.pseudo,
        roles: response.roles,
        isVerified: response.isVerified,
        lastLogin: response.lastLogin,
        createdAt: response.createdAt || new Date().toISOString(),
        updatedAt: response.updatedAt || new Date().toISOString(),
      }

      setUser(userData)
    } catch (err: unknown) {
      // Si la récupération échoue (token invalide), on déconnecte
      await logout()
      const errorMessage = getErrorMessage(err, 'Session expirée')
      error.value = errorMessage
      throw new Error(errorMessage)
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Déconnexion de l'utilisateur
   */
  const logout = async (): Promise<void> => {
    try {
      // Appel API pour invalider le token côté serveur
      await authApi.logout()
    } catch (err) {
      // On déconnecte quand même côté client même si l'API échoue
      console.error('Erreur lors de la déconnexion:', err)
    } finally {
      // Nettoyage de l'état local
      setToken(null)
      setUser(null)
      clearError()
    }
  }

  /**
   * Initialise le store au démarrage de l'application
   * Charge l'utilisateur si un token existe
   */
  const initialize = async (): Promise<void> => {
    if (token.value) {
      try {
        await fetchMe()
      } catch (err) {
        // Si l'initialisation échoue, on nettoie
        console.error("Erreur lors de l'initialisation:", err)
        await logout()
      }
    }
  }

  /**
   * Réinitialise complètement le store
   */
  const reset = (): void => {
    setToken(null)
    setUser(null)
    clearError()
    isLoading.value = false
  }

  /**
   * Vérifie si l'utilisateur possède un rôle spécifique
   */
  const hasRole = (role: string): boolean => {
    return user.value?.roles.includes(role) ?? false
  }

  /**
   * Vérifie si l'utilisateur possède au moins un des rôles
   */
  const hasAnyRole = (roles: string[]): boolean => {
    if (!user.value?.roles) return false
    return roles.some((role) => user.value?.roles.includes(role))
  }

  /**
   * Vérifie si l'utilisateur possède tous les rôles
   */
  const hasAllRoles = (roles: string[]): boolean => {
    if (!user.value?.roles) return false
    return roles.every((role) => user.value?.roles.includes(role))
  }

  /**
   * Vérifie si l'utilisateur est un Game Master
   */
  const isGameMaster = computed(() => hasRole('ROLE_GM') || hasRole('ROLE_ADMIN'))

  /**
   * Vérifie si l'utilisateur est un administrateur
   */
  const isAdmin = computed(() => hasRole('ROLE_ADMIN'))

  // ========== RETURN ==========
  return {
    // State
    user,
    token,
    isLoading,
    error,

    // Getters
    isAuthenticated,
    currentUser,
    isGameMaster,
    isAdmin,

    // Actions
    register,
    login,
    logout,
    fetchMe,
    initialize,
    reset,

    // Permissions
    hasRole,
    hasAnyRole,
    hasAllRoles,

    // Utilities
    setError,
    clearError,
    setUser,
    setToken,
  }
})
