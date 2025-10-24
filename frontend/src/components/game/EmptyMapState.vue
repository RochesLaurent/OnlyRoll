<script setup lang="ts">
import { MapIcon, PlusIcon } from '@heroicons/vue/24/outline'

defineProps<{
  isGameMaster: boolean
}>()

const emit = defineEmits<{
  createMap: []
}>()
</script>

<template>
  <div
    class="h-full flex items-center justify-center bg-gradient-to-br from-secondary-900 to-secondary-800"
  >
    <div class="text-center max-w-md px-6">
      <!-- Icône -->
      <div
        class="w-24 h-24 mx-auto mb-6 bg-primary-500/10 rounded-full flex items-center justify-center"
      >
        <MapIcon class="w-12 h-12 text-primary-500" />
      </div>

      <!-- Titre -->
      <h3 class="text-2xl font-bold text-white mb-3">Aucune carte active</h3>

      <!-- Message pour GM -->
      <p v-if="isGameMaster" class="text-secondary-300 mb-6">
        Cette partie n'a pas encore de carte. Uploadez une carte pour commencer à jouer et placer
        des tokens.
      </p>

      <!-- Message pour joueurs -->
      <p v-else class="text-secondary-300 mb-6">
        Le maître du jeu n'a pas encore créé de carte pour cette session. Patientez pendant qu'il
        prépare le terrain de jeu.
      </p>

      <!-- Action pour GM uniquement -->
      <button
        v-if="isGameMaster"
        @click="emit('createMap')"
        class="inline-flex items-center gap-2 px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg transition-colors shadow-lg hover:shadow-xl"
      >
        <PlusIcon class="w-5 h-5" />
        Créer une carte
      </button>

      <!-- Conseils -->
      <div
        v-if="isGameMaster"
        class="mt-8 p-4 bg-secondary-800/50 rounded-lg border border-secondary-700 text-left"
      >
        <h4 class="text-sm font-semibold text-primary-400 mb-2">💡 Conseil</h4>
        <ul class="text-sm text-secondary-400 space-y-1">
          <li>• Formats supportés : JPEG, PNG, WebP, GIF</li>
          <li>• Taille max : 10 Mo</li>
          <li>• Utilisez une résolution adaptée (1920x1080 recommandé)</li>
        </ul>
      </div>
    </div>
  </div>
</template>
