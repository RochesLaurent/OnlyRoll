<template>
    <div class="min-h-screen bg-gradient-to-br from-primary-900 via-primary-800 to-secondary-900 flex items-center justify-center p-4">
        <div class="text-center max-w-2xl mx-auto">
            <!-- Animation du dé qui roule -->
            <div class="mb-12">
                <div class="inline-flex items-center justify-center w-32 h-32 bg-gradient-to-br from-primary-500 to-primary-400 rounded-2xl shadow-2xl animate-bounce-gentle">
                    <svg class="w-16 h-16 text-white transform rotate-12" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zM9.5 16.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5zm5 0c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                    </svg>
                </div>
            </div>

            <!-- Titre d'erreur avec style D&D -->
            <h1 class="text-8xl md:text-9xl font-bold text-primary-400 mb-4 tracking-tighter">
                404
            </h1>
            
            <!-- Message principal -->
            <h2 class="text-2xl md:text-4xl font-bold text-secondary-50 mb-6">
                Échec critique !
            </h2>
            
            <p class="text-lg md:text-xl text-secondary-300 mb-8 leading-relaxed">
                Vous avez fait un <span class="text-primary-400 font-semibold">1</span> sur votre jet de navigation...<br>
                Cette page semble avoir été dévorée par un Dragon Rouge.
            </p>

            <!-- Citations aléatoires D&D -->
            <div class="bg-secondary-800/50 rounded-xl p-6 mb-8 border border-secondary-700">
                <div class="flex items-start space-x-3">
                    <svg class="w-6 h-6 text-primary-400 flex-shrink-0 mt-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                    <div>
                        <p class="text-secondary-200 font-medium italic">
                            "{{ randomQuote.text }}"
                        </p>
                        <p class="text-secondary-400 text-sm mt-2">
                            — {{ randomQuote.author }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="space-y-4">
                <!-- Bouton retour -->
                <button
                    @click="goBack"
                    class="w-full sm:w-auto px-8 py-4 bg-primary-500 hover:bg-primary-600 text-white text-lg font-semibold rounded-lg shadow-purple transition-all duration-200 hover:shadow-purple-lg transform hover:-translate-y-1 mr-0 sm:mr-4 mb-4 sm:mb-0"
                >
                    <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Retour
                </button>

                <!-- Bouton accueil -->
                <RouterLink
                    to="/"
                    class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-4 bg-secondary-700 hover:bg-secondary-600 text-secondary-50 text-lg font-semibold rounded-lg border border-secondary-600 transition-colors"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    Accueil
                </RouterLink>
            </div>

            <!-- Suggestions -->
            <div class="mt-12 pt-8 border-t border-secondary-700">
                <p class="text-secondary-400 text-sm mb-4">Peut-être cherchez-vous :</p>
                <div class="flex flex-wrap justify-center gap-2">
                    <RouterLink
                        v-if="!isAuthenticated"
                        to="/auth/login"
                        class="px-4 py-2 text-sm bg-secondary-800 hover:bg-secondary-700 text-secondary-300 hover:text-secondary-200 rounded-lg border border-secondary-600 transition-colors"
                    >
                        Se connecter
                    </RouterLink>
                    <RouterLink
                        v-if="!isAuthenticated"
                        to="/auth/register"
                        class="px-4 py-2 text-sm bg-secondary-800 hover:bg-secondary-700 text-secondary-300 hover:text-secondary-200 rounded-lg border border-secondary-600 transition-colors"
                    >
                        S'inscrire
                    </RouterLink>
                    <RouterLink
                        v-if="isAuthenticated"
                        to="/dashboard"
                        class="px-4 py-2 text-sm bg-secondary-800 hover:bg-secondary-700 text-secondary-300 hover:text-secondary-200 rounded-lg border border-secondary-600 transition-colors"
                    >
                        Dashboard
                    </RouterLink>
                    <RouterLink
                        to="/wiki"
                        class="px-4 py-2 text-sm bg-secondary-800 hover:bg-secondary-700 text-secondary-300 hover:text-secondary-200 rounded-lg border border-secondary-600 transition-colors"
                    >
                        Wiki D&D
                    </RouterLink>
                </div>
            </div>

            <!-- Easter egg : relancer le dé -->
            <div class="mt-8">
                <button
                    @click="rollDice"
                    class="text-xs text-secondary-500 hover:text-secondary-400 transition-colors"
                >
                    Relancer le dé pour une nouvelle citation
                </button>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
    import { ref, onMounted } from 'vue'
    import { useRouter } from 'vue-router'
    import { useAuth } from '@/composables/useAuth'

    const router = useRouter()
    const { isAuthenticated } = useAuth()

    const quotes = [
        {
            text: "Un aventurier averti en vaut deux !",
            author: "Proverbe de taverne"
        },
        {
            text: "Il vaut mieux être perdu avec une carte qu'être trouvé sans direction.",
            author: "Sage Ranger"
        },
        {
            text: "Même les plus grands héros se perdent parfois en chemin.",
            author: "Chroniques d'Astarion"
        },
        {
            text: "Ce n'est pas l'erreur qui compte, c'est comment on s'en remet.",
            author: "Manuel du Maître de Donjon"
        },
        {
            text: "Parfois, se perdre mène aux plus grandes découvertes.",
            author: "Journal d'un Explorateur"
        },
        {
            text: "Un chemin fermé en cache souvent un autre.",
            author: "Proverbe elfique"
        }
    ]

    const randomQuote = ref(quotes[0])

    const rollDice = () => {
        const randomIndex = Math.floor(Math.random() * quotes.length)
        randomQuote.value = quotes[randomIndex]
    }

    const goBack = () => {
        if (window.history.length > 1) {
            router.go(-1)
        } else {
            router.push('/')
        }
    }

    onMounted(() => {
        rollDice()
    })
</script>