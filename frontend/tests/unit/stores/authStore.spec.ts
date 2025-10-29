/**
 * Tests unitaires pour le store d'authentification
 * 
 * @covers src/stores/auth.ts
 */

import { describe, it, expect, beforeEach, vi } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useAuthStore } from '@/stores/auth'
import { authApi } from '@/services/api/authApi'

// Mock de l'API
vi.mock('@/services/api/authApi', () => ({
  authApi: {
    register: vi.fn(),
    login: vi.fn(),
    logout: vi.fn(),
    me: vi.fn(),
  },
}))

describe('authStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  // ========== INITIAL STATE ==========

  it('should initialize with correct default values', () => {
    const store = useAuthStore()

    expect(store.user).toBeNull()
    expect(store.isLoading).toBe(false)
    expect(store.error).toBeNull()
    expect(store.isAuthenticated).toBe(false)
    expect(store.currentUser).toBeNull()
  })

  // ========== REGISTER ==========

  it('should register successfully', async () => {
    const store = useAuthStore()
    const credentials = {
      email: 'test@example.com',
      pseudo: 'TestUser',
      password: 'Password123!',
      confirmPassword: 'Password123!',
    }

    vi.mocked(authApi.register).mockResolvedValueOnce({ 
      message: 'User created successfully',
      user: {
        id: 1,
        email: credentials.email,
        pseudo: credentials.pseudo,
      }
    })

    await store.register(credentials)

    expect(authApi.register).toHaveBeenCalledWith(credentials)
    expect(store.error).toBeNull()
    expect(store.isLoading).toBe(false)
  })

  it('should handle registration error', async () => {
    const store = useAuthStore()
    const errorMessage = 'Email already exists'

    vi.mocked(authApi.register).mockRejectedValueOnce({
      error: errorMessage,
    })

    await expect(store.register({
      email: 'test@example.com',
      pseudo: 'TestUser',
      password: 'Password123!',
      confirmPassword: 'Password123!',
    })).rejects.toThrow(errorMessage)

    expect(store.error).toBe(errorMessage)
    expect(store.isLoading).toBe(false)
  })

  // ========== LOGIN ==========

  it('should login successfully', async () => {
    const store = useAuthStore()
    const credentials = {
      email: 'test@example.com',
      password: 'Password123!',
    }

    const mockUser = {
      id: 1,
      email: credentials.email,
      pseudo: 'TestUser',
      roles: ['ROLE_USER'],
      isVerified: true,
      createdAt: '2024-01-01T00:00:00Z',
      updatedAt: '2024-01-01T00:00:00Z',
    }

    vi.mocked(authApi.login).mockResolvedValueOnce({
      success: true,
      message: 'Login successful',
    })

    vi.mocked(authApi.me).mockResolvedValueOnce(mockUser)

    await store.login(credentials)

    expect(authApi.login).toHaveBeenCalledWith(credentials)
    expect(authApi.me).toHaveBeenCalled()
    expect(store.user).toBeTruthy()
    expect(store.user?.email).toBe(credentials.email)
    expect(store.isAuthenticated).toBe(true)
    expect(store.error).toBeNull()
  })

  it('should handle login error', async () => {
    const store = useAuthStore()
    const errorMessage = 'Invalid credentials'

    vi.mocked(authApi.login).mockRejectedValueOnce({
      error: errorMessage,
    })

    await expect(store.login({
      email: 'test@example.com',
      password: 'wrongpassword',
    })).rejects.toThrow(errorMessage)

    expect(store.error).toBe(errorMessage)
    expect(store.user).toBeNull()
    expect(store.isAuthenticated).toBe(false)
  })

  // ========== FETCH ME ==========

  it('should fetch user data successfully', async () => {
    const store = useAuthStore()
    const mockUser = {
      id: 1,
      email: 'test@example.com',
      pseudo: 'TestUser',
      roles: ['ROLE_USER'],
      isVerified: true,
      createdAt: '2024-01-01T00:00:00Z',
      updatedAt: '2024-01-01T00:00:00Z',
    }

    vi.mocked(authApi.me).mockResolvedValueOnce(mockUser)

    await store.fetchMe()

    expect(authApi.me).toHaveBeenCalled()
    expect(store.user).toBeTruthy()
    expect(store.user?.id).toBe(mockUser.id)
    expect(store.user?.email).toBe(mockUser.email)
    expect(store.user?.pseudo).toBe(mockUser.pseudo)
  })

  it('should handle fetchMe error and logout', async () => {
    const store = useAuthStore()

    vi.mocked(authApi.me).mockRejectedValueOnce({
      error: 'Session expirée',
    })

    vi.mocked(authApi.logout).mockResolvedValueOnce({
      message: 'Logged out',
    })

    await expect(store.fetchMe()).rejects.toThrow('Session expirée')

    expect(store.user).toBeNull()
    expect(store.error).toBe('Session expirée')
  })

  // ========== LOGOUT ==========

  it('should logout successfully', async () => {
    const store = useAuthStore()
    
    // Set initial user
    store.setUser({
      id: 1,
      email: 'test@example.com',
      pseudo: 'TestUser',
      roles: ['ROLE_USER'],
      isVerified: true,
      createdAt: '2024-01-01T00:00:00Z',
      updatedAt: '2024-01-01T00:00:00Z',
    })

    vi.mocked(authApi.logout).mockResolvedValueOnce({
      message: 'Logged out',
    })

    await store.logout()

    expect(authApi.logout).toHaveBeenCalled()
    expect(store.user).toBeNull()
    expect(store.isAuthenticated).toBe(false)
    expect(store.error).toBeNull()
  })

  it('should clear user data even if logout API fails', async () => {
    const store = useAuthStore()
    
    store.setUser({
      id: 1,
      email: 'test@example.com',
      pseudo: 'TestUser',
      roles: ['ROLE_USER'],
      isVerified: true,
      createdAt: '2024-01-01T00:00:00Z',
      updatedAt: '2024-01-01T00:00:00Z',
    })

    vi.mocked(authApi.logout).mockRejectedValueOnce(new Error('Network error'))

    await store.logout()

    expect(store.user).toBeNull()
    expect(store.isAuthenticated).toBe(false)
  })

  // ========== INITIALIZE ==========

  it('should initialize with valid session', async () => {
    const store = useAuthStore()
    const mockUser = {
      id: 1,
      email: 'test@example.com',
      pseudo: 'TestUser',
      roles: ['ROLE_USER'],
      isVerified: true,
      createdAt: '2024-01-01T00:00:00Z',
      updatedAt: '2024-01-01T00:00:00Z',
    }

    vi.mocked(authApi.me).mockResolvedValueOnce(mockUser)

    await store.initialize()

    expect(authApi.me).toHaveBeenCalled()
    expect(store.user).toBeTruthy()
    expect(store.isAuthenticated).toBe(true)
  })

  it('should handle initialize without valid session', async () => {
    const store = useAuthStore()

    vi.mocked(authApi.me).mockRejectedValueOnce({
      error: 'Unauthorized',
    })

    await store.initialize()

    expect(store.user).toBeNull()
    expect(store.isAuthenticated).toBe(false)
    expect(store.error).toBeNull() // Error should be cleared
  })

  // ========== RESET ==========

  it('should reset store completely', () => {
    const store = useAuthStore()
    
    store.setUser({
      id: 1,
      email: 'test@example.com',
      pseudo: 'TestUser',
      roles: ['ROLE_USER'],
      isVerified: true,
      createdAt: '2024-01-01T00:00:00Z',
      updatedAt: '2024-01-01T00:00:00Z',
    })
    store.setError('Some error')

    store.reset()

    expect(store.user).toBeNull()
    expect(store.error).toBeNull()
    expect(store.isLoading).toBe(false)
  })

  // ========== ROLE CHECKS ==========

  it('should check if user has specific role', () => {
    const store = useAuthStore()
    
    store.setUser({
      id: 1,
      email: 'test@example.com',
      pseudo: 'TestUser',
      roles: ['ROLE_USER', 'ROLE_GM'],
      isVerified: true,
      createdAt: '2024-01-01T00:00:00Z',
      updatedAt: '2024-01-01T00:00:00Z',
    })

    expect(store.hasRole('ROLE_USER')).toBe(true)
    expect(store.hasRole('ROLE_GM')).toBe(true)
    expect(store.hasRole('ROLE_ADMIN')).toBe(false)
  })

  it('should return false for hasRole when no user', () => {
    const store = useAuthStore()

    expect(store.hasRole('ROLE_USER')).toBe(false)
  })

  it('should check if user has any of the roles', () => {
    const store = useAuthStore()
    
    store.setUser({
      id: 1,
      email: 'test@example.com',
      pseudo: 'TestUser',
      roles: ['ROLE_USER', 'ROLE_GM'],
      isVerified: true,
      createdAt: '2024-01-01T00:00:00Z',
      updatedAt: '2024-01-01T00:00:00Z',
    })

    expect(store.hasAnyRole(['ROLE_GM', 'ROLE_ADMIN'])).toBe(true)
    expect(store.hasAnyRole(['ROLE_ADMIN', 'ROLE_SUPER_ADMIN'])).toBe(false)
  })

  it('should check if user has all roles', () => {
    const store = useAuthStore()
    
    store.setUser({
      id: 1,
      email: 'test@example.com',
      pseudo: 'TestUser',
      roles: ['ROLE_USER', 'ROLE_GM', 'ROLE_ADMIN'],
      isVerified: true,
      createdAt: '2024-01-01T00:00:00Z',
      updatedAt: '2024-01-01T00:00:00Z',
    })

    expect(store.hasAllRoles(['ROLE_USER', 'ROLE_GM'])).toBe(true)
    expect(store.hasAllRoles(['ROLE_USER', 'ROLE_SUPER_ADMIN'])).toBe(false)
  })

  it('should identify game master correctly', () => {
    const store = useAuthStore()
    
    store.setUser({
      id: 1,
      email: 'test@example.com',
      pseudo: 'TestUser',
      roles: ['ROLE_USER', 'ROLE_GM'],
      isVerified: true,
      createdAt: '2024-01-01T00:00:00Z',
      updatedAt: '2024-01-01T00:00:00Z',
    })

    expect(store.isGameMaster).toBe(true)
  })

  it('should identify admin correctly', () => {
    const store = useAuthStore()
    
    store.setUser({
      id: 1,
      email: 'test@example.com',
      pseudo: 'TestUser',
      roles: ['ROLE_USER', 'ROLE_ADMIN'],
      isVerified: true,
      createdAt: '2024-01-01T00:00:00Z',
      updatedAt: '2024-01-01T00:00:00Z',
    })

    expect(store.isAdmin).toBe(true)
    expect(store.isGameMaster).toBe(true) // Admin is also GM
  })

  // ========== ERROR HANDLING ==========

  it('should set and clear errors', () => {
    const store = useAuthStore()

    store.setError('Test error')
    expect(store.error).toBe('Test error')

    store.clearError()
    expect(store.error).toBeNull()
  })

  it('should handle different error formats', async () => {
    const store = useAuthStore()

    // String error
    vi.mocked(authApi.register).mockRejectedValueOnce('String error')
    await expect(store.register({
      email: 'test@example.com',
      pseudo: 'TestUser',
      password: 'Password123!',
      confirmPassword: 'Password123!',
    })).rejects.toThrow('String error')

    // Error object with message
    vi.mocked(authApi.register).mockRejectedValueOnce({
      message: 'Error message',
    })
    await expect(store.register({
      email: 'test@example.com',
      pseudo: 'TestUser',
      password: 'Password123!',
      confirmPassword: 'Password123!',
    })).rejects.toThrow('Error message')

    // Error object with error property
    vi.mocked(authApi.register).mockRejectedValueOnce({
      error: 'Error property',
    })
    await expect(store.register({
      email: 'test@example.com',
      pseudo: 'TestUser',
      password: 'Password123!',
      confirmPassword: 'Password123!',
    })).rejects.toThrow('Error property')

    // Error instance
    vi.mocked(authApi.register).mockRejectedValueOnce(
      new Error('Error instance')
    )
    await expect(store.register({
      email: 'test@example.com',
      pseudo: 'TestUser',
      password: 'Password123!',
      confirmPassword: 'Password123!',
    })).rejects.toThrow('Error instance')

    // Unknown error format - fallback to default
    vi.mocked(authApi.register).mockRejectedValueOnce(123)
    await expect(store.register({
      email: 'test@example.com',
      pseudo: 'TestUser',
      password: 'Password123!',
      confirmPassword: 'Password123!',
    })).rejects.toThrow("Erreur lors de l'inscription")
  })

  // ========== COMPUTED PROPERTIES ==========

  it('should update computed properties reactively', () => {
    const store = useAuthStore()

    expect(store.isAuthenticated).toBe(false)
    expect(store.currentUser).toBeNull()

    store.setUser({
      id: 1,
      email: 'test@example.com',
      pseudo: 'TestUser',
      roles: ['ROLE_USER'],
      isVerified: true,
      createdAt: '2024-01-01T00:00:00Z',
      updatedAt: '2024-01-01T00:00:00Z',
    })

    expect(store.isAuthenticated).toBe(true)
    expect(store.currentUser).toBeTruthy()
    expect(store.currentUser?.email).toBe('test@example.com')
  })

  // ========== LOADING STATE ==========

  it('should manage loading state during operations', async () => {
    const store = useAuthStore()

    vi.mocked(authApi.me).mockImplementation(() => {
      expect(store.isLoading).toBe(true)
      return Promise.resolve({
        id: 1,
        email: 'test@example.com',
        pseudo: 'TestUser',
        roles: ['ROLE_USER'],
        isVerified: true,
        createdAt: '2024-01-01T00:00:00Z',
        updatedAt: '2024-01-01T00:00:00Z',
      })
    })

    await store.fetchMe()

    expect(store.isLoading).toBe(false)
  })
})