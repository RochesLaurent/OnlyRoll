import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import type { LoginCredentials, RegisterCredentials } from '@/types/auth'

export const useAuth = () => {
    const authStore = useAuthStore()
    const router = useRouter()

    const user = computed(() => authStore.user)
    const isAuthenticated = computed(() => authStore.isAuthenticated)
    const isLoading = computed(() => authStore.isLoading)
    const error = computed(() => authStore.error)

    const login = async (credentials: LoginCredentials, redirectTo?: string) => {
        try {
            await authStore.login(credentials)
            
            const destination = redirectTo || { name: 'dashboard' }
            await router.push(destination)
          
        } catch (error) {
            throw error
        }
    }

    const register = async (credentials: RegisterCredentials) => {
        try {
            await authStore.register(credentials)
            
            await router.push({ 
              name: 'register-success',
              query: { email: credentials.email }
            })
            
        } catch (error) {
            throw error
        }
    }

    const logout = async (redirectTo?: string) => {
        authStore.logout()
        
        const destination = redirectTo || { name: 'home' }
        await router.push(destination)
    }

    const hasRole = (role: string): boolean => {
        return authStore.hasRole(role)
    }

    const requireAuth = (): boolean => {
        if (!isAuthenticated.value) {
            router.push({ name: 'login' })
            return false
        }
        return true
    }

    const requireGuest = (): boolean => {
        if (isAuthenticated.value) {
            router.push({ name: 'dashboard' })
            return false
        }
        return true
    }

    const clearError = () => {
        authStore.clearError()
    }

    const setError = (message: string) => {
        authStore.setError(message)
    }

    const getErrorMessage = (error: any): string => {
        if (typeof error === 'string') return error
        if (error?.error) return error.error
        if (error?.message) return error.message
        return 'Une erreur inattendue s\'est produite'
    }

    return {
        user,
        isAuthenticated,
        isLoading,
        error,
        
        login,
        register,
        logout,
        
        hasRole,
        requireAuth,
        requireGuest,
        
        clearError,
        setError,
        getErrorMessage
    }
}