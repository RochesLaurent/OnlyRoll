export interface User {
  id: number
  email: string
  pseudo: string
  roles: string[]
  isVerified: boolean
  lastLogin?: string
  createdAt: string
  updatedAt: string
}

export interface LoginCredentials {
  email: string
  password: string
}

export interface RegisterCredentials {
  pseudo: string
  email: string
  password: string
  confirmPassword: string
}

export interface AuthResponse {
  message: string
  user: User
  token?: string
}

export interface ValidationError {
  propertyPath: string
  message: string
  invalidValue?: unknown
}

export interface ApiError {
  error: string
  message?: string
  violations?: ValidationError[]
}

export interface AuthState {
  user: User | null
  token: string | null
  isAuthenticated: boolean
  isLoading: boolean
  error: string | null
}

// Réponses API spécifiques selon mon controller
export interface RegisterResponse {
  message: string
  user: {
    id: number
    email: string
    pseudo: string
  }
}

export interface MeResponse {
  id: number
  email: string
  pseudo: string
  roles: string[]
  isVerified: boolean
  createdAt: string
  updatedAt: string
}

export interface DebugLoginResponse {
  success: boolean
  message: string
  user_id: number
  user_email: string
  user_pseudo: string
  user_verified: boolean
  user_roles: string[]
}
