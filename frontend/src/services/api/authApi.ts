import { apiClient } from './apiClient'
import type {
  LoginCredentials,
  RegisterCredentials,
  RegisterResponse,
  MeResponse,
} from '@/types/auth'

/**
 * Service d'authentification
 * Gère l'inscription, la connexion et la récupération de mot de passe
 */
export const authApi = {
  register: async (credentials: RegisterCredentials): Promise<RegisterResponse> => {
    const registerData = {
      pseudo: credentials.pseudo,
      email: credentials.email,
      password: credentials.password,
    }
    return apiClient.post<RegisterResponse>('/register', registerData)
  },

  login: async (credentials: LoginCredentials): Promise<{ token: string }> => {
    return apiClient.post<{ token: string }>('/login', credentials)
  },

  logout: async (): Promise<void> => {
    await apiClient.post('/logout')
  },

  me: async (): Promise<MeResponse> => {
    return apiClient.get<MeResponse>('/me')
  },

  /**
   * Vérification de l'email via token
   */
  verifyEmail: async (token: string): Promise<{ message: string }> => {
    return apiClient.get<{ message: string }>(`/auth/verify-email/${token}`)
  },

  /**
   * Demande de réinitialisation de mot de passe
   */
  forgotPassword: async (email: string): Promise<{ message: string }> => {
    return apiClient.post<{ message: string }>('/auth/forgot-password', { email })
  },

  /**
   * Réinitialisation du mot de passe avec token
   */
  resetPassword: async (token: string, password: string): Promise<{ message: string }> => {
    return apiClient.post<{ message: string }>('/auth/reset-password', { token, password })
  },
}
