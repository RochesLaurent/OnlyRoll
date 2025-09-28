<template>
    <form @submit.prevent="handleSubmit" class="space-y-6">
        <!-- Titre du formulaire -->
        <div class="text-center mb-6">
            <h2 class="text-xl font-semibold text-secondary-50 mb-1">
                Inscription
            </h2>
            <p class="text-sm text-secondary-400">
                Rejoignez la communauté OnlyRoll
            </p>
        </div>

        <!-- Affichage des erreurs -->
        <div 
            v-if="error || validationErrors.length > 0" 
            class="bg-error/10 border border-error/20 rounded-lg p-4"
        >
            <div class="flex items-start space-x-3">
                <svg class="w-5 h-5 text-error flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
                <div class="text-sm">
                    <p v-if="error" class="text-error font-medium">{{ error }}</p>
                    <ul v-if="validationErrors.length > 0" class="text-error space-y-1">
                        <li v-for="err in validationErrors" :key="err.field">
                        {{ err.message }}
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Champ Pseudo -->
        <div class="space-y-1">
            <label for="pseudo" class="block text-sm font-medium text-secondary-200">
                Pseudo <span class="text-error">*</span>
            </label>
            <input
                id="pseudo"
                v-model="form.pseudo"
                type="text"
                autocomplete="username"
                required
                :disabled="isLoading"
                class="block w-full px-4 py-3 bg-secondary-700 border border-secondary-600 rounded-lg
                    text-secondary-50 placeholder-secondary-400
                    focus:ring-2 focus:ring-primary-500 focus:border-transparent
                    disabled:opacity-50 disabled:cursor-not-allowed
                    transition-colors"
                placeholder="Votre pseudo de joueur"
                minlength="3"
                maxlength="50"
            />
            <p class="text-xs text-secondary-500">
                Entre 3 et 50 caractères. Sera visible par les autres joueurs.
            </p>
        </div>

        <!-- Champ Email -->
        <div class="space-y-1">
            <label for="email" class="block text-sm font-medium text-secondary-200">
                Email <span class="text-error">*</span>
            </label>
            <input
                id="email"
                v-model="form.email"
                type="email"
                autocomplete="email"
                required
                :disabled="isLoading"
                class="block w-full px-4 py-3 bg-secondary-700 border border-secondary-600 rounded-lg
                    text-secondary-50 placeholder-secondary-400
                    focus:ring-2 focus:ring-primary-500 focus:border-transparent
                    disabled:opacity-50 disabled:cursor-not-allowed
                    transition-colors"
                placeholder="votre.email@exemple.com"
            />
            <p class="text-xs text-secondary-500">
                Un email de vérification vous sera envoyé.
            </p>
        </div>

        <!-- Champ Mot de passe -->
        <div class="space-y-1">
            <label for="password" class="block text-sm font-medium text-secondary-200">
                Mot de passe <span class="text-error">*</span>
            </label>
            <div class="relative">
                <input
                    id="password"
                    v-model="form.password"
                    :type="showPassword ? 'text' : 'password'"
                    autocomplete="new-password"
                    required
                    :disabled="isLoading"
                    class="block w-full px-4 py-3 pr-12 bg-secondary-700 border border-secondary-600 rounded-lg
                            text-secondary-50 placeholder-secondary-400
                            focus:ring-2 focus:ring-primary-500 focus:border-transparent
                            disabled:opacity-50 disabled:cursor-not-allowed
                            transition-colors"
                    placeholder="••••••••"
                    minlength="8"
                />
                <button
                    type="button"
                    @click="togglePasswordVisibility"
                    :disabled="isLoading"
                    class="absolute inset-y-0 right-0 pr-3 flex items-center
                            text-secondary-400 hover:text-secondary-300
                            disabled:opacity-50 disabled:cursor-not-allowed
                            transition-colors"
                >
                    <svg 
                        v-if="showPassword" 
                        class="w-5 h-5" 
                        fill="none" 
                        stroke="currentColor" 
                        viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21" />
                    </svg>
                    <svg 
                        v-else 
                        class="w-5 h-5" 
                        fill="none" 
                        stroke="currentColor" 
                        viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                </button>
            </div>
            
            <!-- Indicateur de force du mot de passe -->
            <div v-if="form.password" class="space-y-1">
                <div class="w-full bg-secondary-600 rounded-full h-2">
                    <div 
                        class="h-2 rounded-full transition-all duration-300"
                        :class="passwordStrengthColor"
                        :style="{ width: passwordStrengthWidth }"
                    ></div>
                </div>
                <p class="text-xs" :class="passwordStrengthColor.replace('bg-', 'text-')">
                    {{ passwordStrengthText }}
                </p>
            </div>
            
            <ul class="text-xs text-secondary-500 space-y-1">
                <li class="flex items-center space-x-2">
                    <span :class="passwordRules.length ? 'text-success' : 'text-secondary-500'">
                        {{ passwordRules.length ? '✓' : '○' }}
                    </span>
                    <span>Au moins 8 caractères</span>
                </li>
                <li class="flex items-center space-x-2">
                    <span :class="passwordRules.uppercase ? 'text-success' : 'text-secondary-500'">
                        {{ passwordRules.uppercase ? '✓' : '○' }}
                    </span>
                    <span>Une majuscule</span>
                </li>
                <li class="flex items-center space-x-2">
                    <span :class="passwordRules.number ? 'text-success' : 'text-secondary-500'">
                        {{ passwordRules.number ? '✓' : '○' }}
                    </span>
                    <span>Un chiffre</span>
                </li>
            </ul>
        </div>

        <!-- Confirmation mot de passe -->
        <div class="space-y-1">
            <label for="confirmPassword" class="block text-sm font-medium text-secondary-200">
                Confirmer le mot de passe <span class="text-error">*</span>
            </label>
            <div class="relative">
                <input
                    id="confirmPassword"
                    v-model="form.confirmPassword"
                    :type="showConfirmPassword ? 'text' : 'password'"
                    autocomplete="new-password"
                    required
                    :disabled="isLoading"
                    class="block w-full px-4 py-3 pr-12 bg-secondary-700 border border-secondary-600 rounded-lg
                            text-secondary-50 placeholder-secondary-400
                            focus:ring-2 focus:ring-primary-500 focus:border-transparent
                            disabled:opacity-50 disabled:cursor-not-allowed
                            transition-colors"
                    placeholder="••••••••"
                />
                <button
                    type="button"
                    @click="toggleConfirmPasswordVisibility"
                    :disabled="isLoading"
                    class="absolute inset-y-0 right-0 pr-3 flex items-center
                            text-secondary-400 hover:text-secondary-300
                            disabled:opacity-50 disabled:cursor-not-allowed
                            transition-colors"
                >
                    <svg 
                        v-if="showConfirmPassword" 
                        class="w-5 h-5" 
                        fill="none" 
                        stroke="currentColor" 
                        viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21" />
                    </svg>
                    <svg 
                        v-else 
                        class="w-5 h-5" 
                        fill="none" 
                        stroke="currentColor" 
                        viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                </button>
            </div>
            <p v-if="form.confirmPassword && !passwordsMatch" class="text-xs text-error">
                Les mots de passe ne correspondent pas
            </p>
        </div>

        <!-- Acceptation des conditions -->
        <div class="flex items-start space-x-3">
            <input
                id="acceptTerms"
                v-model="form.acceptTerms"
                type="checkbox"
                required
                :disabled="isLoading"
                class="h-4 w-4 mt-1 bg-secondary-700 border-secondary-600 rounded
                    text-primary-500 focus:ring-primary-500 focus:ring-offset-0
                    disabled:opacity-50 disabled:cursor-not-allowed"
            />
            <label for="acceptTerms" class="text-sm text-secondary-300 leading-relaxed">
                J'accepte les 
                <a href="/terms" target="_blank" class="text-primary-400 hover:text-primary-300 transition-colors">
                    conditions d'utilisation
                </a>
                et la 
                <a href="/privacy" target="_blank" class="text-primary-400 hover:text-primary-300 transition-colors">
                    politique de confidentialité
                </a>
                d'OnlyRoll.
            </label>
        </div>

        <!-- Bouton de soumission -->
        <button
            type="submit"
            :disabled="isLoading || !isFormValid"
            class="w-full flex justify-center items-center px-4 py-3 
                    bg-primary-500 hover:bg-primary-600 
                    disabled:bg-secondary-600 disabled:cursor-not-allowed
                    text-white font-medium rounded-lg
                    focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 focus:ring-offset-secondary-800
                    transition-colors duration-200"
        >
            <svg 
                v-if="isLoading" 
                class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" 
                fill="none" 
                viewBox="0 0 24 24"
            >
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ isLoading ? 'Création du compte...' : 'Créer mon compte' }}
        </button>

        <!-- Informations supplémentaires -->
        <div class="text-center pt-4 border-t border-secondary-700">
            <p class="text-xs text-secondary-500">
                Un email de vérification sera envoyé à votre adresse.<br>
                Vérifiez vos spams si nécessaire.
            </p>
        </div>
    </form>
</template>

<script setup lang="ts">
    import { ref, computed } from 'vue'
    import { useAuth } from '@/composables/useAuth'
    import type { RegisterCredentials } from '@/types/auth'

    // Composable d'authentification
    const { register, isLoading, error, clearError } = useAuth()

    // État du formulaire
    const form = ref<RegisterCredentials & { acceptTerms: boolean }>({
    pseudo: '',
    email: '',
    password: '',
    confirmPassword: '',
    acceptTerms: false
    })

    // État de l'interface
    const showPassword = ref(false)
    const showConfirmPassword = ref(false)
    const validationErrors = ref<Array<{ field: string; message: string }>>([])

    // Validation du formulaire côté client
    const isValidEmail = (email: string): boolean => {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    return emailRegex.test(email)
    }

    const passwordRules = computed(() => ({
    length: form.value.password.length >= 8,
    uppercase: /[A-Z]/.test(form.value.password),
    number: /\d/.test(form.value.password)
    }))

    const passwordsMatch = computed(() => {
    return form.value.password === form.value.confirmPassword
    })

    const passwordStrength = computed(() => {
    if (!form.value.password) return 0
    
    let score = 0
    if (passwordRules.value.length) score++
    if (passwordRules.value.uppercase) score++
    if (passwordRules.value.number) score++
    if (/[a-z]/.test(form.value.password)) score++
    if (/[^A-Za-z0-9]/.test(form.value.password)) score++
    
    return score
    })

    const passwordStrengthWidth = computed(() => {
    return `${(passwordStrength.value / 5) * 100}%`
    })

    const passwordStrengthColor = computed(() => {
    if (passwordStrength.value <= 2) return 'bg-error'
    if (passwordStrength.value <= 3) return 'bg-warning'
    return 'bg-success'
    })

    const passwordStrengthText = computed(() => {
    if (passwordStrength.value <= 2) return 'Mot de passe faible'
    if (passwordStrength.value <= 3) return 'Mot de passe moyen'
    return 'Mot de passe fort'
    })

    const isFormValid = computed(() => {
    return form.value.pseudo.length >= 3 &&
            form.value.email.length > 0 &&
            isValidEmail(form.value.email) &&
            passwordRules.value.length &&
            passwordRules.value.uppercase &&
            passwordRules.value.number &&
            passwordsMatch.value &&
            form.value.acceptTerms
    })

    // Utilitaires
    const togglePasswordVisibility = () => {
    showPassword.value = !showPassword.value
    }

    const toggleConfirmPasswordVisibility = () => {
    showConfirmPassword.value = !showConfirmPassword.value
    }

    // Validation côté client
    const validateForm = (): boolean => {
    validationErrors.value = []
    
    // Validation pseudo
    if (!form.value.pseudo) {
        validationErrors.value.push({ field: 'pseudo', message: 'Le pseudo est requis' })
    } else if (form.value.pseudo.length < 3) {
        validationErrors.value.push({ field: 'pseudo', message: 'Le pseudo doit faire au moins 3 caractères' })
    } else if (form.value.pseudo.length > 50) {
        validationErrors.value.push({ field: 'pseudo', message: 'Le pseudo ne peut pas dépasser 50 caractères' })
    }
    
    // Validation email
    if (!form.value.email) {
        validationErrors.value.push({ field: 'email', message: 'L\'email est requis' })
    } else if (!isValidEmail(form.value.email)) {
        validationErrors.value.push({ field: 'email', message: 'L\'email n\'est pas valide' })
    }
    
    // Validation mot de passe
    if (!form.value.password) {
        validationErrors.value.push({ field: 'password', message: 'Le mot de passe est requis' })
    } else {
        if (!passwordRules.value.length) {
        validationErrors.value.push({ field: 'password', message: 'Le mot de passe doit faire au moins 8 caractères' })
        }
        if (!passwordRules.value.uppercase) {
        validationErrors.value.push({ field: 'password', message: 'Le mot de passe doit contenir au moins une majuscule' })
        }
        if (!passwordRules.value.number) {
        validationErrors.value.push({ field: 'password', message: 'Le mot de passe doit contenir au moins un chiffre' })
        }
    }
    
    // Validation confirmation mot de passe
    if (!form.value.confirmPassword) {
        validationErrors.value.push({ field: 'confirmPassword', message: 'La confirmation du mot de passe est requise' })
    } else if (!passwordsMatch.value) {
        validationErrors.value.push({ field: 'confirmPassword', message: 'Les mots de passe ne correspondent pas' })
    }
    
    // Validation conditions d'utilisation
    if (!form.value.acceptTerms) {
        validationErrors.value.push({ field: 'acceptTerms', message: 'Vous devez accepter les conditions d\'utilisation' })
    }
    
    return validationErrors.value.length === 0
    }

    // Soumission du formulaire
    const handleSubmit = async () => {
    clearError()
    
    if (!validateForm()) {
        return
    }
    
    try {
        await register({
        pseudo: form.value.pseudo,
        email: form.value.email,
        password: form.value.password,
        confirmPassword: form.value.confirmPassword
        })
        
        // La redirection est gérée par le composable useAuth
        
    } catch (err) {
        // L'erreur est déjà gérée par le store/composable
        console.error('Erreur d\'inscription:', err)
    }
    }
</script>