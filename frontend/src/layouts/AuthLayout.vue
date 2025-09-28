<template>
    <div class="min-h-screen bg-gradient-to-br from-primary-900 via-primary-800 to-secondary-900 flex items-center justify-center p-4">
        <div class="w-full max-w-md">
        <!-- Logo et titre -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center mb-4">
            <!-- Logo OnlyRoll -->
            <div class="w-12 h-12 bg-gradient-to-br from-primary-500 to-primary-400 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zM9.5 16.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5zm5 0c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                </svg>
            </div>
            </div>
            <h1 class="text-2xl font-bold text-secondary-50 mb-2">OnlyRoll</h1>
            <p class="text-secondary-400 text-sm">
            {{ pageTitle }}
            </p>
        </div>

        <!-- Contenu des pages (Login/Register) -->
        <div class="bg-secondary-800 rounded-xl p-8 shadow-2xl border border-secondary-700">
            <RouterView />
        </div>

        <!-- Liens de navigation auth -->
        <div class="text-center mt-6 space-y-2">
            <div class="text-sm text-secondary-400">
            <template v-if="$route.name === 'login'">
                Pas encore de compte ?
                <RouterLink 
                to="/auth/register" 
                class="text-primary-400 hover:text-primary-300 font-medium transition-colors"
                >
                S'inscrire
                </RouterLink>
            </template>
            <template v-else>
                Déjà un compte ?
                <RouterLink 
                to="/auth/login" 
                class="text-primary-400 hover:text-primary-300 font-medium transition-colors"
                >
                Se connecter
                </RouterLink>
            </template>
            </div>
            
            <div class="text-xs text-secondary-500">
            <RouterLink 
                to="/" 
                class="hover:text-secondary-400 transition-colors"
            >
                ← Retour à l'accueil
            </RouterLink>
            </div>
        </div>
        </div>
    </div>
</template>

<script setup lang="ts">
    import { computed } from 'vue'
    import { useRoute } from 'vue-router'

    const route = useRoute()

    const pageTitle = computed(() => {
    switch (route.name) {
        case 'login':
        return 'Connectez-vous à votre compte'
        case 'register':
        return 'Créez votre compte de joueur'
        default:
        return 'Authentification'
    }
    })
</script>