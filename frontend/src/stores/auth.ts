import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authApi } from '@/services/api/authApi'
import type { ApiError } from '@/services/api/apiClient'
import type { User, LoginCredentials, RegisterCredentials } from '@/types/auth'

/**
 * Store Pinia pour la gestion de l'authentification
 */
export const useAuthStore = defineStore('auth', () => {
  // ========== STATE ==========
  const user = ref<User | null>(null)
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  // ========== GETTERS ==========

  /**
   * Vérifie si l'utilisateur est authentifié
   */
  const isAuthenticated = computed(() => !!user.value)

  /**
   * Retourne l'utilisateur courant
   */
  const currentUser = computed(() => user.value)

  // ========== MUTATIONS (setters privés) ==========

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
      // L'API /api/login place automatiquement le JWT dans un cookie HttpOnly
      // Le backend renvoie maintenant juste { success: true, message: '...' }
      await authApi.login(credentials)

      // Le cookie est automatiquement stocké par le navigateur
      // On récupère juste les infos utilisateur
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
      // Si la récupération échoue (cookie invalide/expiré), on déconnecte
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
   *
   */
  const logout = async (): Promise<void> => {
    try {
      // L'API /api/logout supprime le cookie HttpOnly
      await authApi.logout()
    } catch (err) {
      console.error('Erreur lors de la déconnexion:', err)
    } finally {
      // Nettoyage de l'état local
      setUser(null)
      clearError()
    }
  }

  /**
   * Initialise le store au démarrage de l'application
   */
  const initialize = async (): Promise<void> => {
    try {
      // Si un cookie JWT valide existe, cette requête réussira
      await fetchMe()
    } catch (err) {
      // Si ça échoue, c'est qu'il n'y a pas de session valide
      console.log('No valid session, user not authenticated')
      clearError()
    }
  }

  /**
   * Réinitialise complètement le store
   */
  const reset = (): void => {
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
  }
})