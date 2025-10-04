<script setup lang="ts">
import { ref, computed } from 'vue'
import type { CreateGameDTO } from '@/types/game'
import { useGameStore } from '@/stores/game'
import { useRouter } from 'vue-router'
import { XMarkIcon, UsersIcon, LockClosedIcon, GlobeAltIcon } from '@heroicons/vue/24/outline'

const emit = defineEmits<{
  close: []
}>()

const gameStore = useGameStore()
const router = useRouter()

const formData = ref<CreateGameDTO>({
  name: '',
  description: '',
  maxPlayers: 6,
  isPublic: true,
  password: '',
})

const isSubmitting = ref(false)
const errors = ref<Record<string, string>>({})

const canSubmit = computed(() => formData.value.name.length >= 3 && !isSubmitting.value)

function validateForm(): boolean {
  errors.value = {}

  if (formData.value.name.length < 3) {
    errors.value.name = 'Le nom doit faire au moins 3 caractères'
  }

  if (!formData.value.isPublic && !formData.value.password) {
    errors.value.password = 'Mot de passe requis pour une partie privée'
  }

  return Object.keys(errors.value).length === 0
}

async function handleSubmit() {
  if (!validateForm()) return

  isSubmitting.value = true

  try {
    const game = await gameStore.createGame(formData.value)
    router.push({ name: 'game-detail', params: { id: game.id } })
    emit('close')
  } catch (e) {
    console.error('Error creating game:', e)
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div
    class="fixed inset-0 bg-primary-900/80 backdrop-blur-sm flex items-center justify-center z-50 p-4"
    @click="emit('close')"
  >
    <div
      class="bg-secondary-800 rounded-lg max-w-2xl w-full p-6 border border-secondary-700 shadow-purple-lg"
      @click.stop
    >
      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-secondary-50">Créer une nouvelle partie</h2>
        <button
          @click="emit('close')"
          class="text-secondary-400 hover:text-secondary-50 transition-colors p-1 hover:bg-secondary-700 rounded-md"
          aria-label="Fermer"
        >
          <XMarkIcon class="w-6 h-6" />
        </button>
      </div>

      <!-- Form -->
      <form @submit.prevent="handleSubmit" class="space-y-5">
        <!-- Name -->
        <div>
          <label class="block text-sm font-medium text-secondary-300 mb-2">
            Nom de la partie <span class="text-accent-rose">*</span>
          </label>
          <input
            v-model="formData.name"
            type="text"
            required
            class="w-full px-4 py-3 bg-secondary-700 border border-secondary-600 rounded-md text-secondary-50 placeholder-secondary-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
            placeholder="Ex: La Campagne des Dragons Oubliés"
          />
          <p v-if="errors.name" class="text-accent-rose text-sm mt-1.5 flex items-center gap-1">
            <span class="inline-block w-1 h-1 bg-accent-rose rounded-full"></span>
            {{ errors.name }}
          </p>
        </div>

        <!-- Description -->
        <div>
          <label class="block text-sm font-medium text-secondary-300 mb-2"> Description </label>
          <textarea
            v-model="formData.description"
            rows="3"
            class="w-full px-4 py-3 bg-secondary-700 border border-secondary-600 rounded-md text-secondary-50 placeholder-secondary-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all resize-none"
            placeholder="Décrivez votre campagne, son univers, son ambiance..."
          />
        </div>

        <!-- Max Players -->
        <div>
          <label class="block text-sm font-medium text-secondary-300 mb-2">
            <UsersIcon class="w-4 h-4 inline-block mr-1.5 -mt-0.5" />
            Nombre maximum de joueurs
          </label>
          <div class="relative">
            <input
              v-model.number="formData.maxPlayers"
              type="number"
              min="1"
              max="20"
              class="w-full px-4 py-3 bg-secondary-700 border border-secondary-600 rounded-md text-secondary-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
            />
            <span
              class="absolute right-4 top-1/2 -translate-y-1/2 text-secondary-400 text-sm pointer-events-none"
            >
              joueurs max
            </span>
          </div>
        </div>

        <!-- Public/Private Toggle -->
        <div class="bg-secondary-700/50 rounded-lg p-4 border border-secondary-600">
          <label class="flex items-start cursor-pointer group">
            <div class="relative flex items-center">
              <input v-model="formData.isPublic" type="checkbox" class="sr-only peer" />
              <div
                class="w-11 h-6 bg-secondary-600 rounded-full peer peer-checked:bg-primary-500 peer-focus:ring-2 peer-focus:ring-primary-400 transition-all duration-200"
              ></div>
              <div
                class="absolute left-1 top-1 bg-white w-4 h-4 rounded-full peer-checked:translate-x-5 transition-transform duration-200"
              ></div>
            </div>
            <div class="ml-3 flex-1">
              <div class="flex items-center gap-2">
                <GlobeAltIcon v-if="formData.isPublic" class="w-5 h-5 text-accent-emerald" />
                <LockClosedIcon v-else class="w-5 h-5 text-accent-amber" />
                <span class="text-secondary-50 font-medium">
                  {{ formData.isPublic ? 'Partie publique' : 'Partie privée' }}
                </span>
              </div>
              <p class="text-secondary-400 text-sm mt-1">
                {{
                  formData.isPublic
                    ? 'Visible par tous les joueurs dans la liste publique'
                    : 'Accessible uniquement avec le mot de passe'
                }}
              </p>
            </div>
          </label>
        </div>

        <!-- Password (si privée) -->
        <div v-if="!formData.isPublic">
          <label class="block text-sm font-medium text-secondary-300 mb-2">
            <LockClosedIcon class="w-4 h-4 inline-block mr-1.5 -mt-0.5" />
            Mot de passe <span class="text-accent-rose">*</span>
          </label>
          <input
            v-model="formData.password"
            type="password"
            class="w-full px-4 py-3 bg-secondary-700 border border-secondary-600 rounded-md text-secondary-50 placeholder-secondary-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
            placeholder="Mot de passe de la partie"
          />
          <p v-if="errors.password" class="text-accent-rose text-sm mt-1.5 flex items-center gap-1">
            <span class="inline-block w-1 h-1 bg-accent-rose rounded-full"></span>
            {{ errors.password }}
          </p>
        </div>

        <!-- Error from store -->
        <div
          v-if="gameStore.error"
          class="p-4 bg-accent-rose/10 border border-accent-rose/50 rounded-lg text-accent-rose flex items-start gap-3"
        >
          <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
            <path
              fill-rule="evenodd"
              d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
              clip-rule="evenodd"
            />
          </svg>
          <span class="text-sm">{{ gameStore.error }}</span>
        </div>

        <!-- Actions -->
        <div class="flex justify-end gap-3 pt-4 border-t border-secondary-700">
          <button
            type="button"
            @click="emit('close')"
            class="px-6 py-2.5 border border-secondary-600 text-secondary-300 rounded-md hover:bg-secondary-700 hover:text-secondary-50 transition-all duration-200"
          >
            Annuler
          </button>
          <button
            type="submit"
            :disabled="!canSubmit"
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
              Création...
            </span>
            <span v-else>Créer la partie</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
