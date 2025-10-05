/**
 * Client API centralisé utilisant l'API native Fetch
 *
 * Fonctionnalités :
 * - Gestion automatique des tokens JWT
 * - Gestion centralisée des erreurs
 * - Timeout configurable
 * - Typage TypeScript strict
 */

interface ApiError {
  error: string
  message: string
  statusCode?: number
}

class ApiClient {
  private baseURL: string
  private defaultTimeout: number = 30000

  constructor(baseURL: string) {
    this.baseURL = baseURL
  }

  /**
   * Méthode privée pour effectuer les requêtes HTTP
   */
  private async request<T>(endpoint: string, options: RequestInit = {}): Promise<T> {
    const url = `${this.baseURL}${endpoint}`

    // Configuration du timeout avec AbortController
    const controller = new AbortController()
    const timeoutId = setTimeout(() => controller.abort(), this.defaultTimeout)

    // Configuration des headers
    const config: RequestInit = {
      headers: {
        'Content-Type': 'application/json',
        ...options.headers,
      },
      signal: controller.signal,
      ...options,
    }

    // Ajout automatique du token JWT s'il existe
    const token = localStorage.getItem('auth_token')
    if (token) {
      config.headers = {
        ...config.headers,
        Authorization: `Bearer ${token}`,
      }
    }

    try {
      const response = await fetch(url, config)
      clearTimeout(timeoutId)

      // Gestion des erreurs HTTP
      if (!response.ok) {
        const errorData = await response.json().catch(() => ({
          error: `HTTP ${response.status}`,
          message: response.statusText,
          statusCode: response.status,
        }))

        throw {
          ...errorData,
          statusCode: response.status,
        } as ApiError
      }

      // Gestion des réponses vides (204 No Content)
      if (response.status === 204) {
        return {} as T
      }

      return await response.json()
    } catch (error) {
      clearTimeout(timeoutId)

      // Gestion du timeout
      if (error instanceof Error && error.name === 'AbortError') {
        throw {
          error: 'Timeout',
          message: 'La requête a expiré. Veuillez réessayer.',
          statusCode: 408,
        } as ApiError
      }

      // Propagation des erreurs API
      if (error && typeof error === 'object' && 'error' in error) {
        throw error as ApiError
      }

      // Erreurs réseau génériques
      throw {
        error: 'Network Error',
        message: error instanceof Error ? error.message : "Une erreur réseau s'est produite",
        statusCode: 0,
      } as ApiError
    }
  }

  /**
   * Requête GET
   */
  async get<T>(endpoint: string, options?: RequestInit): Promise<T> {
    return this.request<T>(endpoint, { ...options, method: 'GET' })
  }

  /**
   * Requête POST
   */
  async post<T>(endpoint: string, data?: unknown, options?: RequestInit): Promise<T> {
    return this.request<T>(endpoint, {
      ...options,
      method: 'POST',
      body: data ? JSON.stringify(data) : undefined,
    })
  }

  /**
   * Requête PUT
   */
  async put<T>(endpoint: string, data?: unknown, options?: RequestInit): Promise<T> {
    return this.request<T>(endpoint, {
      ...options,
      method: 'PUT',
      body: data ? JSON.stringify(data) : undefined,
    })
  }

  /**
   * Requête PATCH
   */
  async patch<T>(endpoint: string, data?: unknown, options?: RequestInit): Promise<T> {
    return this.request<T>(endpoint, {
      ...options,
      method: 'PATCH',
      body: data ? JSON.stringify(data) : undefined,
    })
  }

  /**
   * Requête DELETE
   */
  async delete<T>(endpoint: string, options?: RequestInit): Promise<T> {
    return this.request<T>(endpoint, { ...options, method: 'DELETE' })
  }

  /**
   * Définir un timeout personnalisé
   */
  setTimeout(timeout: number): void {
    this.defaultTimeout = timeout
  }
}

// Instance unique du client API
const apiClient = new ApiClient(import.meta.env.VITE_API_URL || 'http://localhost:8000/api')

export { apiClient, ApiClient }
export type { ApiError }
