<template>
  <div class="text-center space-y-6">
    <!-- Icône de succès -->
    <div class="flex justify-center">
      <div class="w-20 h-20 bg-success/10 rounded-full flex items-center justify-center">
        <svg class="w-10 h-10 text-success" fill="currentColor" viewBox="0 0 20 20">
          <path
            fill-rule="evenodd"
            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
            clip-rule="evenodd"
          />
        </svg>
      </div>
    </div>

    <!-- Message principal -->
    <div>
      <h2 class="text-xl font-semibold text-secondary-50 mb-2">Inscription réussie !</h2>
      <p class="text-secondary-300 mb-4">Votre compte a été créé avec succès.</p>
    </div>

    <!-- Informations sur la vérification email -->
    <div class="bg-info/10 border border-info/20 rounded-lg p-4">
      <div class="flex items-start space-x-3">
        <svg class="w-5 h-5 text-info flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
          <path
            fill-rule="evenodd"
            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
            clip-rule="evenodd"
          />
        </svg>
        <div class="text-sm">
          <p class="text-info font-medium mb-1">Vérifiez votre email</p>
          <p class="text-secondary-300">Un email de vérification a été envoyé à :</p>
          <p class="font-medium text-secondary-200 mt-1">
            {{ email }}
          </p>
          <p class="text-secondary-400 mt-2 text-xs">
            Si vous ne le recevez pas dans quelques minutes, vérifiez vos spams.
          </p>
        </div>
      </div>
    </div>

    <!-- Actions -->
    <div class="space-y-3">
      <!-- Bouton de redirection vers login -->
      <RouterLink
        to="/auth/login"
        class="block w-full px-4 py-3 bg-primary-500 hover:bg-primary-600 text-white font-medium rounded-lg text-center transition-colors duration-200"
      >
        Se connecter
      </RouterLink>

      <!-- Bouton pour renvoyer l'email (futur) -->
      <button
        type="button"
        @click="resendEmail"
        :disabled="isResending || cooldown > 0"
        class="block w-full px-4 py-3 bg-secondary-700 hover:bg-secondary-600 disabled:bg-secondary-600 disabled:cursor-not-allowed text-secondary-200 font-medium rounded-lg transition-colors duration-200"
      >
        <span v-if="isResending">Envoi en cours...</span>
        <span v-else-if="cooldown > 0">Renvoyer dans {{ cooldown }}s</span>
        <span v-else>Renvoyer l'email de vérification</span>
      </button>
    </div>

    <!-- Message d'aide -->
    <div class="text-center pt-4 border-t border-secondary-700">
      <p class="text-xs text-secondary-500">
        Problème avec votre inscription ?
        <a
          href="mailto:support@onlyroll.com"
          class="text-primary-400 hover:text-primary-300 transition-colors"
        >
          Contactez le support
        </a>
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onUnmounted } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()

const email = ref((route.query.email as string) || 'votre-email@exemple.com')

const isResending = ref(false)
const cooldown = ref(0)
let cooldownTimer: number | null = null

// Fonction pour renvoyer l'email (à implémenter plus tard)
const resendEmail = async () => {
  if (isResending.value || cooldown.value > 0) return

  isResending.value = true

  try {
    await new Promise((resolve) => setTimeout(resolve, 1000))

    startCooldown()
  } catch (error) {
    console.error("Erreur lors du renvoi de l'email:", error)
  } finally {
    isResending.value = false
  }
}

const startCooldown = () => {
  cooldown.value = 60 // 60 secondes

  cooldownTimer = setInterval(() => {
    cooldown.value--
    if (cooldown.value <= 0 && cooldownTimer) {
      clearInterval(cooldownTimer)
      cooldownTimer = null
    }
  }, 1000)
}

onUnmounted(() => {
  if (cooldownTimer) {
    clearInterval(cooldownTimer)
  }
})
</script>
