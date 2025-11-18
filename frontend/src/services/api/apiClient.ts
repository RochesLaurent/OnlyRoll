import axios, { type AxiosInstance, type AxiosError } from 'axios'
import { useAuthStore } from '@/stores/auth'

export interface ApiError {
  message: string
  statusCode?: number
  error?: string
  errors?: Record<string, string[]>
}

const apiClient: AxiosInstance = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api',
  headers: {
    'Content-Type': 'application/json',
  },
  withCredentials: true,
})

// Intercepteur de réponse pour gérer les erreurs globalement
apiClient.interceptors.response.use(
  (response) => {
    return response
  },
  async (error: AxiosError<ApiError>) => {
    // Gestion des erreurs 401 (Unauthorized)
    if (error.response?.status === 401) {
      const authStore = useAuthStore()

      // Si l'utilisateur était authentifié, le déconnecter
      if (authStore.isAuthenticated) {
        console.warn('Session expirée, déconnexion automatique')
        await authStore.logout()

        // Rediriger vers la page de login
        if (typeof window !== 'undefined') {
          window.location.href = '/auth/login'
        }
      }
    }

    // Formater l'erreur pour la rendre plus exploitable
    const apiError: ApiError = {
      message: error.response?.data?.message || error.message || 'Une erreur est survenue',
      statusCode: error.response?.status,
      error: error.response?.data?.error,
      errors: error.response?.data?.errors,
    }

    return Promise.reject(apiError)
  }
)

/**
 * Helper pour faire des requêtes GET
 */
async function get<T>(url: string): Promise<T> {
  const response = await apiClient.get<T>(url)
  return response.data
}

/**
 * Helper pour faire des requêtes POST
 */
async function post<T>(url: string, data?: unknown): Promise<T> {
  const response = await apiClient.post<T>(url, data)
  return response.data
}

/**
 * Helper pour faire des requêtes PUT
 */
async function put<T>(url: string, data?: unknown): Promise<T> {
  const response = await apiClient.put<T>(url, data)
  return response.data
}

/**
 * Helper pour faire des requêtes PATCH
 */
async function patch<T>(url: string, data?: unknown): Promise<T> {
  const response = await apiClient.patch<T>(url, data)
  return response.data
}

/**
 * Helper pour faire des requêtes DELETE
 */
async function del<T = void>(url: string): Promise<T> {
  const response = await apiClient.delete<T>(url)
  return response.data
}

export { apiClient, get, post, put, patch, del as delete }
