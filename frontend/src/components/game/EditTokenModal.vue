<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useMapStore } from '@/stores/mapStore'
import { TokenType } from '@/types/game'
import { ArrowUpTrayIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import type { GameToken } from '@/types/game'

const props = defineProps<{
  show: boolean
  token: GameToken | null
  gameId: number
  mapId: number
}>()

const emit = defineEmits<{
  close: []
  success: []
}>()

const mapStore = useMapStore()

// Formulaire
const form = ref({
  name: '',
  type: TokenType.CHARACTER,
  size: 1,
})

const isSubmitting = ref(false)
const error = ref<string | null>(null)

// Gestion de l'upload de fichier
const selectedFile = ref<File | null>(null)
const imagePreview = ref<string | null>(null)
const uploadError = ref<string | null>(null)

// Types de tokens disponibles
const tokenTypes = [
  { value: TokenType.CHARACTER, label: 'Personnage', icon: '🧙', color: '#6366f1' },
  { value: TokenType.MONSTER, label: 'Monstre', icon: '👹', color: '#ef4444' },
  { value: TokenType.NPC, label: 'PNJ', icon: '🧑', color: '#10b981' },
  { value: TokenType.OBJECT, label: 'Objet', icon: '📦', color: '#f59e0b' },
]

// Tailles disponibles
const tokenSizes = [
  { value: 0.5, label: 'Petit (0.5)' },
  { value: 1, label: 'Moyen (1)' },
  { value: 2, label: 'Grand (2)' },
  { value: 3, label: 'Énorme (3)' },
  { value: 4, label: 'Gigantesque (4)' },
]

// Validation
const isFormValid = computed(() => {
  return form.value.name.trim().length > 0
})

// Pré-remplir le formulaire avec les données du token
function prefillForm() {
  if (props.token) {
    form.value.name = props.token.name
    form.value.type = props.token.type
    form.value.size = props.token.size

    // Afficher l'image actuelle
    if (props.token.imageUrl) {
      const apiUrl = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'
      const baseUrl = apiUrl.replace(/\/api$/, '')
      const fullImageUrl = props.token.imageUrl.startsWith('http')
        ? props.token.imageUrl
        : `${baseUrl}${props.token.imageUrl}`
      imagePreview.value = fullImageUrl
    } else {
      imagePreview.value = null
    }

    selectedFile.value = null
    uploadError.value = null
  }
}

// Pré-remplir les champs quand la modal s'ouvre
watch(
  () => props.show,
  (isShown) => {
    if (isShown) {
      prefillForm()
    }
  },
  { immediate: true }
)

// Aussi pré-remplir si le token change
watch(
  () => props.token,
  () => {
    if (props.show) {
      prefillForm()
    }
  }
)

// Gestion de la sélection de fichier
function handleFileSelect(event: Event) {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]

  if (!file) return

  // Vérifier le type
  if (!file.type.startsWith('image/')) {
    uploadError.value = 'Veuillez sélectionner une image (JPEG, PNG, WebP, GIF)'
    return
  }

  // Vérifier la taille (5 Mo max pour les tokens)
  const maxSize = 5 * 1024 * 1024
  if (file.size > maxSize) {
    uploadError.value = "L'image est trop volumineuse (max 5 Mo)"
    return
  }

  selectedFile.value = file
  uploadError.value = null

  const reader = new FileReader()
  reader.onload = (e) => {
    imagePreview.value = e.target?.result as string
  }
  reader.readAsDataURL(file)
}

// Supprimer le fichier sélectionné
function removeFile() {
  selectedFile.value = null
  imagePreview.value = null
}

// Handlers
async function handleSubmit() {
  if (!isFormValid.value || !props.token) return

  isSubmitting.value = true
  error.value = null

  try {
    const formData = new FormData()

    // Ajouter l'image seulement si une nouvelle a été sélectionnée
    if (selectedFile.value) {
      formData.append('image', selectedFile.value)
    }

    formData.append('name', form.value.name.trim())
    formData.append('type', form.value.type)
    formData.append('size', form.value.size.toString())

    const apiUrl = import.meta.env.VITE_API_URL || 'http://localhost/api'
    const response = await fetch(
      `${apiUrl}/games/${props.gameId}/maps/${props.mapId}/tokens/${props.token.id}`,
      {
        method: 'PATCH',
        credentials: 'include',
        body: formData,
      }
    )

    if (!response.ok) {
      const errorData = await response.json()
      throw new Error(errorData.error || 'Erreur lors de la mise à jour du token')
    }

    const updatedToken = await response.json()
    console.log('Token mis à jour:', updatedToken)

    // Recharger les tokens
    await mapStore.loadMapTokens(props.mapId)

    emit('success')
    emit('close')
  } catch (e: unknown) {
    if (e && typeof e === 'object' && 'message' in e) {
      error.value = (e as { message: string }).message
    } else {
      error.value = 'Erreur lors de la mise à jour du token'
    }
    console.error('Erreur mise à jour token:', e)
  } finally {
    isSubmitting.value = false
  }
}

function handleClose() {
  if (isSubmitting.value) return
  emit('close')
}
</script>

<template>
  <Teleport to="body">
    <Transition name="modal">
      <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm"
        @click.self="handleClose"
      >
        <div
          class="bg-secondary-800 rounded-xl shadow-2xl w-full max-w-lg mx-4 border border-secondary-700"
        >
          <!-- Header -->
          <div class="flex items-center justify-between p-6 border-b border-secondary-700">
            <div>
              <h2 class="text-2xl font-bold text-white">✏️ Modifier le Token</h2>
              <p class="text-sm text-secondary-400 mt-1">
                {{ token?.name }}
              </p>
            </div>
            <button
              @click="handleClose"
              class="p-2 text-secondary-400 hover:text-white hover:bg-secondary-700 rounded-lg transition-colors"
              :disabled="isSubmitting"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M6 18L18 6M6 6l12 12"
                />
              </svg>
            </button>
          </div>

          <!-- Form -->
          <form @submit.prevent="handleSubmit" class="p-6 space-y-6">
            <!-- Erreur -->
            <div
              v-if="error"
              class="bg-error/10 border border-error/20 rounded-lg p-4 flex items-start gap-3"
            >
              <svg
                class="w-5 h-5 text-error flex-shrink-0 mt-0.5"
                fill="currentColor"
                viewBox="0 0 20 20"
              >
                <path
                  fill-rule="evenodd"
                  d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                  clip-rule="evenodd"
                />
              </svg>
              <p class="text-sm text-error">{{ error }}</p>
            </div>

            <!-- Nom -->
            <div>
              <label for="token-name" class="block text-sm font-medium text-secondary-200 mb-2">
                Nom du token *
              </label>
              <input
                id="token-name"
                v-model="form.name"
                type="text"
                required
                :disabled="isSubmitting"
                class="w-full px-4 py-3 bg-secondary-700 border border-secondary-600 rounded-lg text-white placeholder-secondary-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent disabled:opacity-50"
                placeholder="Ex: Gobelin, Coffre, etc."
                maxlength="50"
              />
            </div>

            <!-- Type -->
            <div>
              <label class="block text-sm font-medium text-secondary-200 mb-3">
                Type de token *
              </label>
              <div class="grid grid-cols-2 gap-3">
                <button
                  v-for="type in tokenTypes"
                  :key="type.value"
                  type="button"
                  @click="form.type = type.value"
                  :disabled="isSubmitting"
                  :class="[
                    'p-4 rounded-lg border-2 transition-all flex items-center gap-3',
                    form.type === type.value
                      ? 'border-primary-500 bg-primary-500/20 shadow-purple'
                      : 'border-secondary-600 bg-secondary-700 hover:border-secondary-500',
                  ]"
                >
                  <span class="text-2xl">{{ type.icon }}</span>
                  <div class="text-left flex-1">
                    <div class="font-medium text-white">{{ type.label }}</div>
                    <div
                      class="w-full h-1 rounded mt-1"
                      :style="{ backgroundColor: type.color }"
                    ></div>
                  </div>
                </button>
              </div>
            </div>

            <!-- Taille -->
            <div>
              <label for="token-size" class="block text-sm font-medium text-secondary-200 mb-2">
                Taille
              </label>
              <select
                id="token-size"
                v-model.number="form.size"
                :disabled="isSubmitting"
                class="w-full px-4 py-3 bg-secondary-700 border border-secondary-600 rounded-lg text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent disabled:opacity-50"
              >
                <option v-for="size in tokenSizes" :key="size.value" :value="size.value">
                  {{ size.label }}
                </option>
              </select>
            </div>

            <!-- Upload d'image (optionnel) -->
            <div>
              <label class="block text-sm font-medium text-secondary-200 mb-2">
                Image du token (optionnel)
              </label>

              <div
                v-if="!imagePreview"
                class="border-2 border-dashed border-secondary-600 rounded-lg p-6 text-center hover:border-primary-500 transition-colors cursor-pointer"
              >
                <input
                  type="file"
                  accept="image/*"
                  class="hidden"
                  id="token-edit-file-input"
                  @change="handleFileSelect"
                  :disabled="isSubmitting"
                />
                <label for="token-edit-file-input" class="cursor-pointer">
                  <ArrowUpTrayIcon class="w-10 h-10 mx-auto text-secondary-400 mb-2" />
                  <p class="text-secondary-300 font-medium mb-1">
                    Cliquez pour sélectionner une image
                  </p>
                  <p class="text-secondary-500 text-xs">JPEG, PNG, WebP ou GIF (max 5 Mo)</p>
                </label>
              </div>

              <!-- Aperçu de l'image -->
              <div v-else class="relative">
                <img
                  :src="imagePreview"
                  alt="Aperçu"
                  class="w-32 h-32 object-cover bg-secondary-900 rounded-lg mx-auto"
                />
                <button
                  type="button"
                  @click="removeFile"
                  class="absolute top-0 right-0 bg-blue-600 hover:bg-blue-700 text-white p-1 rounded-full transition-colors"
                  title="Changer l'image"
                  :disabled="isSubmitting"
                >
                  <XMarkIcon class="w-4 h-4" />
                </button>
                <p v-if="selectedFile" class="text-secondary-400 text-xs mt-2 text-center">
                  Nouvelle image : {{ selectedFile.name }}
                </p>
                <p v-else class="text-secondary-500 text-xs mt-2 text-center">
                  Image actuelle - Cliquez sur ✕ pour changer
                </p>
              </div>

              <p v-if="uploadError" class="text-xs text-red-400 mt-2">
                {{ uploadError }}
              </p>
            </div>

            <!-- Actions -->
            <div class="flex gap-3 pt-4">
              <button
                type="button"
                @click="handleClose"
                :disabled="isSubmitting"
                class="flex-1 px-4 py-3 bg-secondary-700 text-white rounded-lg hover:bg-secondary-600 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
              >
                Annuler
              </button>
              <button
                type="submit"
                :disabled="!isFormValid || isSubmitting"
                class="flex-1 px-4 py-3 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium"
              >
                <span v-if="isSubmitting">Mise à jour...</span>
                <span v-else>Mettre à jour</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.shadow-purple {
  box-shadow: 0 4px 14px 0 rgba(99, 102, 241, 0.39);
}

/* Transitions pour la modal */
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-active .bg-secondary-800,
.modal-leave-active .bg-secondary-800 {
  transition: transform 0.3s ease;
}

.modal-enter-from .bg-secondary-800,
.modal-leave-to .bg-secondary-800 {
  transform: scale(0.95);
}
</style>
