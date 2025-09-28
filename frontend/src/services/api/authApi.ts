import type {
  LoginCredentials,
  RegisterCredentials,
  AuthResponse,
  RegisterResponse,
  MeResponse,
  DebugLoginResponse
} from '@/types/auth'

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000'

class ApiClient {
    private baseURL: string

    constructor(baseURL: string) {
        this.baseURL = baseURL
    }

    private async request<T>(
        endpoint: string,
        options: RequestInit = {}
    ): Promise<T> {
        const url = `${this.baseURL}${endpoint}`
        
        const config: RequestInit = {
            headers: {
                'Content-Type': 'application/json',
                ...options.headers,
            },
            ...options,
        }

        const token = localStorage.getItem('auth_token')
        if (token) {
            config.headers = {
                ...config.headers,
                'Authorization': `Bearer ${token}`,
            }
        }

        try {
        const response = await fetch(url, config)
        
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({
                error: `HTTP ${response.status}`,
                message: response.statusText
                }))
                throw errorData
            }

            return await response.json()
        } catch (error) {
            if (error && typeof error === 'object' && 'error' in error) {
                throw error
            }
        
            throw {
                error: 'Network Error',
                message: error instanceof Error ? error.message : 'Une erreur réseau s\'est produite'
            }
        }
    }

    async get<T>(endpoint: string, options?: RequestInit): Promise<T> {
        return this.request<T>(endpoint, { ...options, method: 'GET' })
    }

    async post<T>(endpoint: string, data?: any, options?: RequestInit): Promise<T> {
        return this.request<T>(endpoint, {
        ...options,
        method: 'POST',
        body: data ? JSON.stringify(data) : undefined,
        })
    }

    async put<T>(endpoint: string, data?: any, options?: RequestInit): Promise<T> {
        return this.request<T>(endpoint, {
        ...options,
        method: 'PUT',
        body: data ? JSON.stringify(data) : undefined,
        })
    }

    async delete<T>(endpoint: string, options?: RequestInit): Promise<T> {
        return this.request<T>(endpoint, { ...options, method: 'DELETE' })
    }
}

const apiClient = new ApiClient(API_BASE_URL)

export const authApi = {

    register: (credentials: RegisterCredentials): Promise<RegisterResponse> => {
        const { confirmPassword, ...registerData } = credentials
        return apiClient.post('/api/register', registerData)
    },

    login: (credentials: LoginCredentials): Promise<DebugLoginResponse> => {
        return apiClient.post('/api/debug-login', credentials)
    },

    me: (): Promise<MeResponse> => {
        return apiClient.get('/api/me')
    },

    verifyEmail: (token: string): Promise<{ message: string }> => {
        return apiClient.get(`/api/auth/verify-email/${token}`)
    },

    forgotPassword: (email: string): Promise<{ message: string }> => {
        return apiClient.post('/api/auth/forgot-password', { email })
    },

    resetPassword: (token: string, password: string): Promise<{ message: string }> => {
        return apiClient.post('/api/auth/reset-password', { token, password })
    }
}

export { apiClient }