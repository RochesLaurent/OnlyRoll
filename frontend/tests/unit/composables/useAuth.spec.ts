/**
 * Tests unitaires pour le composable useAuth
 * 
 * @covers src/composables/useAuth.ts
 */

import { describe, it, expect, beforeEach, vi } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useAuth } from '@/composables/useAuth'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'
import type { LoginCredentials, RegisterCredentials } from '@/types/auth'

// Mock du router
vi.mock('vue-router', () => ({
  useRouter: vi.fn(),
}))

// Mock du store
vi.mock('@/stores/auth', () => ({
  useAuthStore: vi.fn(),
}))

describe('useAuth', () => {
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  let mockRouter: any
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  let mockStore: any

  beforeEach(() => {
    setActivePinia(createPinia())

    mockRouter = {
      push: vi.fn(),
      currentRoute: {
        value: {
          fullPath: '/current-path',
        },
      },
    }

    mockStore = {
      user: null,
      isAuthenticated: false,
      isLoading: false,
      error: null,
      login: vi.fn(),
      register: vi.fn(),
      logout: vi.fn(),
      hasRole: vi.fn(),
      hasAnyRole: vi.fn(),
      hasAllRoles: vi.fn(),
      clearError: vi.fn(),
      setError: vi.fn(),
    }

    vi.mocked(useRouter).mockReturnValue(mockRouter)
    vi.mocked(useAuthStore).mockReturnValue(mockStore)
  })

  // ========== COMPUTED PROPERTIES ==========

  it('should expose reactive store states', () => {
    mockStore.user = { id: 1, email: 'test@example.com' }
    mockStore.isAuthenticated = true
    mockStore.isLoading = true
    mockStore.error = 'Test error'

    const { user, isAuthenticated, isLoading, error } = useAuth()

    expect(user.value).toEqual(mockStore.user)
    expect(isAuthenticated.value).toBe(true)
    expect(isLoading.value).toBe(true)
    expect(error.value).toBe('Test error')
  })

  // ========== LOGIN ==========

  it('should login and redirect to dashboard by default', async () => {
    const credentials: LoginCredentials = {
      email: 'test@example.com',
      password: 'password123',
    }

    mockStore.login.mockResolvedValueOnce(undefined)

    const { login } = useAuth()
    await login(credentials)

    expect(mockStore.login).toHaveBeenCalledWith(credentials)
    expect(mockRouter.push).toHaveBeenCalledWith({ name: 'dashboard' })
  })

  it('should login and redirect to custom route', async () => {
    const credentials: LoginCredentials = {
      email: 'test@example.com',
      password: 'password123',
    }

    mockStore.login.mockResolvedValueOnce(undefined)

    const { login } = useAuth()
    await login(credentials, '/custom-route')

    expect(mockRouter.push).toHaveBeenCalledWith('/custom-route')
  })

  it('should propagate login errors', async () => {
    const credentials: LoginCredentials = {
      email: 'test@example.com',
      password: 'wrongpassword',
    }

    const loginError = new Error('Invalid credentials')
    mockStore.login.mockRejectedValueOnce(loginError)

    const { login } = useAuth()

    await expect(login(credentials)).rejects.toThrow('Invalid credentials')
    expect(mockRouter.push).not.toHaveBeenCalled()
  })

  // ========== REGISTER ==========

  it('should register and redirect to success page', async () => {
    const credentials: RegisterCredentials = {
      email: 'new@example.com',
      pseudo: 'NewUser',
      password: 'password123',
      confirmPassword: 'password123',
    }

    mockStore.register.mockResolvedValueOnce(undefined)

    const { register } = useAuth()
    await register(credentials)

    expect(mockStore.register).toHaveBeenCalledWith(credentials)
    expect(mockRouter.push).toHaveBeenCalledWith({
      name: 'register-success',
      query: { email: credentials.email },
    })
  })

  it('should propagate registration errors', async () => {
    const credentials: RegisterCredentials = {
      email: 'existing@example.com',
      pseudo: 'User',
      password: 'password123',
      confirmPassword: 'password123',
    }

    const registerError = new Error('Email already exists')
    mockStore.register.mockRejectedValueOnce(registerError)

    const { register } = useAuth()

    await expect(register(credentials)).rejects.toThrow('Email already exists')
    expect(mockRouter.push).not.toHaveBeenCalled()
  })

  // ========== LOGOUT ==========

  it('should logout and redirect to home by default', async () => {
    mockStore.logout.mockResolvedValueOnce(undefined)

    const { logout } = useAuth()
    await logout()

    expect(mockStore.logout).toHaveBeenCalled()
    expect(mockRouter.push).toHaveBeenCalledWith({ name: 'home' })
  })

  it('should logout and redirect to custom route', async () => {
    mockStore.logout.mockResolvedValueOnce(undefined)

    const { logout } = useAuth()
    await logout('/login')

    expect(mockRouter.push).toHaveBeenCalledWith('/login')
  })

  it('should redirect even if logout fails', async () => {
    const consoleErrorSpy = vi.spyOn(console, 'error').mockImplementation(() => {})
    mockStore.logout.mockRejectedValueOnce(new Error('Network error'))

    const { logout } = useAuth()
    await logout()

    expect(consoleErrorSpy).toHaveBeenCalled()
    expect(mockRouter.push).toHaveBeenCalledWith({ name: 'home' })
    
    consoleErrorSpy.mockRestore()
  })

  // ========== ROLE CHECKS ==========

  it('should check if user has specific role', () => {
    mockStore.hasRole.mockReturnValue(true)

    const { hasRole } = useAuth()
    const result = hasRole('ROLE_ADMIN')

    expect(mockStore.hasRole).toHaveBeenCalledWith('ROLE_ADMIN')
    expect(result).toBe(true)
  })

  it('should check if user has any of the roles', () => {
    mockStore.hasAnyRole.mockReturnValue(true)

    const { hasAnyRole } = useAuth()
    const result = hasAnyRole(['ROLE_ADMIN', 'ROLE_GM'])

    expect(mockStore.hasAnyRole).toHaveBeenCalledWith(['ROLE_ADMIN', 'ROLE_GM'])
    expect(result).toBe(true)
  })

  it('should check if user has all roles', () => {
    mockStore.hasAllRoles.mockReturnValue(false)

    const { hasAllRoles } = useAuth()
    const result = hasAllRoles(['ROLE_USER', 'ROLE_ADMIN'])

    expect(mockStore.hasAllRoles).toHaveBeenCalledWith(['ROLE_USER', 'ROLE_ADMIN'])
    expect(result).toBe(false)
  })

  // ========== NAVIGATION GUARDS ==========

  it('should allow access when authenticated (requireAuth)', () => {
    mockStore.isAuthenticated = true

    const { requireAuth } = useAuth()
    const result = requireAuth()

    expect(result).toBe(true)
    expect(mockRouter.push).not.toHaveBeenCalled()
  })

  it('should redirect to login when not authenticated (requireAuth)', () => {
    mockStore.isAuthenticated = false

    const { requireAuth } = useAuth()
    const result = requireAuth()

    expect(result).toBe(false)
    expect(mockRouter.push).toHaveBeenCalledWith({
      name: 'login',
      query: { redirect: '/current-path' },
    })
  })

  it('should allow access when not authenticated (requireGuest)', () => {
    mockStore.isAuthenticated = false

    const { requireGuest } = useAuth()
    const result = requireGuest()

    expect(result).toBe(true)
    expect(mockRouter.push).not.toHaveBeenCalled()
  })

  it('should redirect to dashboard when authenticated (requireGuest)', () => {
    mockStore.isAuthenticated = true

    const { requireGuest } = useAuth()
    const result = requireGuest()

    expect(result).toBe(false)
    expect(mockRouter.push).toHaveBeenCalledWith({ name: 'dashboard' })
  })

  // ========== ERROR MANAGEMENT ==========

  it('should clear error from store', () => {
    const { clearError } = useAuth()
    clearError()

    expect(mockStore.clearError).toHaveBeenCalled()
  })

  it('should set error in store', () => {
    const { setError } = useAuth()
    setError('Test error message')

    expect(mockStore.setError).toHaveBeenCalledWith('Test error message')
  })

  it('should extract error message from string', () => {
    const { getErrorMessage } = useAuth()
    const result = getErrorMessage('String error')

    expect(result).toBe('String error')
  })

  it('should extract error message from ApiError with message', () => {
    const { getErrorMessage } = useAuth()
    const apiError = { message: 'API error message', error: 'fallback' }
    const result = getErrorMessage(apiError)

    expect(result).toBe('API error message')
  })

  it('should extract error message from ApiError with error property', () => {
    const { getErrorMessage } = useAuth()
    const apiError = { error: 'Error property message' }
    const result = getErrorMessage(apiError)

    expect(result).toBe('Error property message')
  })

  it('should extract error message from Error instance', () => {
    const { getErrorMessage } = useAuth()
    const error = new Error('Native error message')
    const result = getErrorMessage(error)

    expect(result).toBe('Native error message')
  })

  it('should return default message for unknown error format', () => {
    const { getErrorMessage } = useAuth()
    const result = getErrorMessage(123)

    expect(result).toBe("Une erreur inattendue s'est produite")
  })

  it('should format error with status code', () => {
    const { formatError } = useAuth()
    const error = { message: 'Error message', statusCode: 404 }
    const result = formatError(error)

    expect(result).toBe('[404] Error message')
  })

  it('should format error without status code', () => {
    const { formatError } = useAuth()
    const error = { message: 'Error message' }
    const result = formatError(error)

    expect(result).toBe('Error message')
  })

  it('should format error with status code 0', () => {
    const { formatError } = useAuth()
    const error = { message: 'Error message', statusCode: 0 }
    const result = formatError(error)

    expect(result).toBe('Error message')
  })

  it('should identify authentication error (401)', () => {
    const { isAuthError } = useAuth()
    const error = { statusCode: 401, message: 'Unauthorized' }

    expect(isAuthError(error)).toBe(true)
  })

  it('should not identify non-auth error as auth error', () => {
    const { isAuthError } = useAuth()
    const error = { statusCode: 404, message: 'Not found' }

    expect(isAuthError(error)).toBe(false)
  })

  it('should identify validation error (422)', () => {
    const { isValidationError } = useAuth()
    const error = { statusCode: 422, message: 'Validation failed' }

    expect(isValidationError(error)).toBe(true)
  })

  it('should not identify non-validation error as validation error', () => {
    const { isValidationError } = useAuth()
    const error = { statusCode: 500, message: 'Server error' }

    expect(isValidationError(error)).toBe(false)
  })

  it('should handle null error in isAuthError', () => {
    const { isAuthError } = useAuth()

    expect(isAuthError(null)).toBe(false)
  })

  it('should handle undefined error in isValidationError', () => {
    const { isValidationError } = useAuth()

    expect(isValidationError(undefined)).toBe(false)
  })
})