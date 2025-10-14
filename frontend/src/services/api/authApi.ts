import { apiClient } from './apiClient'
import type { LoginCredentials, RegisterCredentials, MeResponse } from '@/types/auth'

/**
 * Type de réponse adapté au nouveau format avec cookie
 */
interface LoginResponse {
  success: boolean
  message: string
  user?: {
    id: number
    email: string
    pseudo: string
  }
}

interface RegisterResponse {
  message: string
  user: {
    id: number
    email: string
    pseudo: string
  }
}

/**
 * Service API pour l'authentification
 */
export const authApi = {
  /**
   * Connexion de l'utilisateur
   */
  async login(credentials: LoginCredentials): Promise<LoginResponse> {
    return apiClient.post<LoginResponse>('/login', credentials).then((res) => res.data)
  },

  /**
   * Inscription d'un nouvel utilisateur
   */
  async register(credentials: RegisterCredentials): Promise<RegisterResponse> {
    return apiClient.post<RegisterResponse>('/register', credentials).then((res) => res.data)
  },

  /**
   * Récupération des informations de l'utilisateur connecté
   */
  async me(): Promise<MeResponse> {
    return apiClient.get<MeResponse>('/me').then((res) => res.data)
  },

  /**
   * Déconnexion de l'utilisateur
   */
  async logout(): Promise<{ message: string }> {
    return apiClient.post<{ message: string }>('/logout').then((res) => res.data)
  },
}