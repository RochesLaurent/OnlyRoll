<script setup lang="ts">
import { ref, computed } from 'vue'
import type { Game } from '@/types/game'
import { useGameStore } from '@/stores/game'
import { XMarkIcon, LockClosedIcon, GlobeAltIcon, UsersIcon } from '@heroicons/vue/24/outline'

// Type pour les erreurs API
type ApiError = {
  response?: {
    data?: {
      error?: string
    }
  }
}

interface Props {
  game: Game
}

const props = defineProps<Props>()
const emit = defineEmits<{
  close: []
  success: []
}>()

const gameStore = useGameStore()
const password = ref('')
const isSubmitting = ref(false)
const error = ref<string | null>(null)

const isFull = computed(() => props.game.currentPlayersCount >= props.game.maxPlayers)

const needsPassword = computed(() => !props.game.isPublic)

async function handleJoin() {
  if (isFull.value) return

  if (needsPassword.value && !password.value) {
    error.value = 'Le mot de passe est requis'
    return
  }

  isSubmitting.value = true
  error.value = null

  try {
    await gameStore.joinGame(
      props.game.inviteCode,
      needsPassword.value ? password.value : undefined
    )

    emit('success')
    emit('close')
  } catch (e: unknown) {
    if (e && typeof e === 'object' && 'response' in e) {
      error.value = (e as ApiError).response?.data?.error || 'Impossible de rejoindre la partie'
    } else {
      error.value = 'Impossible de rejoindre la partie'
    }
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <!-- Le template reste identique -->
  <div
    class="fixed inset-0 bg-primary-900/80 backdrop-blur-sm flex items-center justify-center z-50 p-4"
    @click="emit('close')"
  >
    <div
      class="bg-secondary-800 rounded-lg max-w-md w-full p-6 border border-secondary-700 shadow-purple-lg"
      @click.stop
    >
      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-secondary-50">Rejoindre la partie</h2>
        <button
          @click="emit('close')"
          class="text-secondary-400 hover:text-secondary-50 transition-colors p-1 hover:bg-secondary-700 rounded-md"
          aria-label="Fermer"
        >
          <XMarkIcon class="w-6 h-6" />
        </button>
      </div>

      <!-- Game Info Card -->
      <div class="bg-secondary-700/50 rounded-lg p-4 border border-secondary-600 mb-6">
        <div class="flex items-start justify-between mb-3">
          <div class="flex-1">
            <h3 class="text-lg font-semibold text-secondary-50 mb-1">
              {{ game.name }}
            </h3>
            <p class="text-secondary-400 text-sm">
              Maître du jeu : <span class="text-primary-400">{{ game.gameMaster.pseudo }}</span>
            </p>
          </div>
          <div
            class="flex items-center gap-1.5 text-xs px-2 py-1 rounded-md"
            :class="[
              game.isPublic
                ? 'bg-accent-emerald/20 text-accent-emerald'
                : 'bg-accent-amber/20 text-accent-amber',
            ]"
          >
            <GlobeAltIcon v-if="game.isPublic" class="w-3.5 h-3.5" />
            <LockClosedIcon v-else class="w-3.5 h-3.5" />
            <span>{{ game.isPublic ? 'Publique' : 'Privée' }}</span>
          </div>
        </div>

        <p v-if="game.description" class="text-secondary-300 text-sm mb-3">
          {{ game.description }}
        </p>

        <div class="flex items-center justify-between pt-3 border-t border-secondary-600">
          <div class="flex items-center gap-2 text-secondary-400 text-sm">
            <UsersIcon class="w-4 h-4" />
            <span>{{ game.currentPlayersCount }} / {{ game.maxPlayers }} joueurs</span>
          </div>
          <span v-if="isFull" class="text-accent-rose text-sm font-medium"> Partie complète </span>
        </div>
      </div>

      <!-- Password Input (if private) -->
      <div v-if="needsPassword" class="mb-6">
        <label class="block text-sm font-medium text-secondary-300 mb-2">
          <LockClosedIcon class="w-4 h-4 inline-block mr-1.5 -mt-0.5" />
          Mot de passe <span class="text-accent-rose">*</span>
        </label>
        <input
          v-model="password"
          type="password"
          placeholder="Entrez le mot de passe de la partie"
          class="w-full px-4 py-3 bg-secondary-700 border border-secondary-600 rounded-md text-secondary-50 placeholder-secondary-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
          :disabled="isFull"
          @keyup.enter="handleJoin"
        />
      </div>

      <!-- Public game info -->
      <div v-else class="mb-6">
        <div
          class="flex items-start gap-3 p-3 bg-accent-emerald/10 border border-accent-emerald/30 rounded-lg"
        >
          <GlobeAltIcon class="w-5 h-5 text-accent-emerald flex-shrink-0 mt-0.5" />
          <p class="text-secondary-300 text-sm">
            Cette partie est publique, vous pouvez la rejoindre directement sans mot de passe.
          </p>
        </div>
      </div>

      <!-- Error Message -->
      <div
        v-if="error"
        class="mb-6 p-4 bg-accent-rose/10 border border-accent-rose/50 rounded-lg text-accent-rose flex items-start gap-3"
      >
        <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
          <path
            fill-rule="evenodd"
            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
            clip-rule="evenodd"
          />
        </svg>
        <span class="text-sm">{{ error }}</span>
      </div>

      <!-- Actions -->
      <div class="flex justify-end gap-3">
        <button
          type="button"
          @click="emit('close')"
          class="px-6 py-2.5 border border-secondary-600 text-secondary-300 rounded-md hover:bg-secondary-700 hover:text-secondary-50 transition-all duration-200"
        >
          Annuler
        </button>
        <button
          @click="handleJoin"
          :disabled="isFull || isSubmitting"
          class="px-6 py-2.5 bg-primary-500 hover:bg-primary-600 disabled:bg-secondary-600 disabled:text-secondary-500 disabled:cursor-not-allowed text-white rounded-md font-medium transition-all duration-200 shadow-purple hover:shadow-purple-lg disabled:shadow-none"
        >
          <span v-if="isSubmitting" class="flex items-center gap-2">
            <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24">
              <circle
                class="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                stroke-width="4"
                fill="none"
              ></circle>
              <path
                class="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
              ></path>
            </svg>
            Connexion...
          </span>
          <span v-else-if="isFull">Partie complète</span>
          <span v-else>Rejoindre</span>
        </button>
      </div>
    </div>
  </div>
</template>
