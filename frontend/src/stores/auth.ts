import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authApi } from '@/services/api/authApi'
import { getErrorMessage } from '@/utils/errorHelpers'
import type { User, LoginCredentials, RegisterCredentials } from '@/types/auth'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const token = ref<string | null>(localStorage.getItem('auth_token'))
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  const isAuthenticated = computed(() => !!token.value && !!user.value)
  const currentUser = computed(() => user.value)
  const hasRole = computed(() => (role: string) => {
    return user.value?.roles.includes(role) ?? false
  })

  const setToken = (newToken: string | null) => {
    token.value = newToken
    if (newToken) {
      localStorage.setItem('auth_token', newToken)
    } else {
      localStorage.removeItem('auth_token')
    }
  }

  const setUser = (newUser: User | null) => {
    user.value = newUser
  }

  const setError = (newError: string | null) => {
    error.value = newError
  }

  const clearError = () => {
    error.value = null
  }

  const register = async (credentials: RegisterCredentials): Promise<void> => {
    isLoading.value = true
    error.value = null

    try {
      await authApi.register(credentials)
      return Promise.resolve()
    } catch (err: unknown) {
      const errorMessage = getErrorMessage(err, "Erreur lors de l'inscription")
      error.value = errorMessage
      throw new Error(errorMessage)
    } finally {
      isLoading.value = false
    }
  }

  const login = async (credentials: LoginCredentials): Promise<void> => {
    isLoading.value = true
    error.value = null

    try {
      const response = await authApi.login(credentials)

      if (response.success) {
        const mockToken = `mock_jwt_${response.user_id}_${Date.now()}`
        setToken(mockToken)

        await fetchMe()
      } else {
        throw new Error(response.message || 'Échec de la connexion')
      }
    } catch (err: unknown) {
      const errorMessage = getErrorMessage(err, 'Erreur lors de la connexion')
      error.value = errorMessage
      throw new Error(errorMessage)
    } finally {
      isLoading.value = false
    }
  }

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
        createdAt: response.createdAt || new Date().toISOString(),
        updatedAt: response.updatedAt || new Date().toISOString(),
      }

      setUser(userData)
    } catch (err: unknown) {
      logout()
      const errorMessage = getErrorMessage(err, 'Session expirée')
      error.value = errorMessage
      throw new Error(errorMessage)
    } finally {
      isLoading.value = false
    }
  }

  const logout = () => {
    setToken(null)
    setUser(null)
    error.value = null
  }

  const initialize = async () => {
    if (token.value) {
      await fetchMe()
    }
  }

  const reset = () => {
    logout()
  }

  return {
    user,
    token,
    isLoading,
    error,

    isAuthenticated,
    currentUser,
    hasRole,

    register,
    login,
    logout,
    fetchMe,
    initialize,
    reset,
    setError,
    clearError,
    setUser,
    setToken,
  }
})
