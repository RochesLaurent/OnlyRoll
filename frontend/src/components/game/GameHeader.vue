<script setup lang="ts">
import { computed } from 'vue'
import type { Game } from '@/types/game'

const props = defineProps<{
  game: Game | null
  isConnected: boolean
  connectionState: 'connecting' | 'open' | 'closed' | 'reconnecting' | 'error'
}>()

const emit = defineEmits<{
  openSettings: []
  leaveGame: []
}>()

// ============================================
// Computed
// ============================================
const connectionStatusClass = computed(() => {
  switch (props.connectionState) {
    case 'open':
      return 'bg-success'
    case 'connecting':
    case 'reconnecting':
      return 'bg-warning animate-pulse'
    case 'closed':
    case 'error':
      return 'bg-error'
    default:
      return 'bg-secondary-500'
  }
})

const connectionStatusText = computed(() => {
  switch (props.connectionState) {
    case 'open':
      return 'Connecté'
    case 'connecting':
      return 'Connexion...'
    case 'reconnecting':
      return 'Reconnexion...'
    case 'closed':
      return 'Déconnecté'
    case 'error':
      return 'Erreur de connexion'
    default:
      return 'Inconnu'
  }
})

const playersCount = computed(() => {
  return props.game?.gamePlayers?.filter((p) => p.status === 'active').length || 0
})

const maxPlayers = computed(() => {
  return props.game?.maxPlayers || 0
})
</script>

<template>
  <header class="bg-secondary-800 border-b border-secondary-700 px-6 py-4 flex-shrink-0">
    <div class="flex items-center justify-between">
      <!-- Infos partie -->
      <div class="flex items-center gap-4">
        <!-- Logo/Icon de la partie -->
        <div
          class="w-12 h-12 rounded-full bg-gradient-primary flex items-center justify-center shadow-purple"
        >
          <span class="text-2xl">🎭</span>
        </div>

        <!-- Détails -->
        <div>
          <h1 class="text-xl font-bold text-secondary-50">
            {{ game?.name || 'Chargement...' }}
          </h1>
          <div class="flex items-center gap-3 text-sm text-secondary-400">
            <span class="flex items-center gap-1">
              <span>👑</span>
              <span>{{ game?.gameMaster?.pseudo || 'Inconnu' }}</span>
            </span>
            <span>•</span>
            <span class="flex items-center gap-1">
              <span>👥</span>
              <span>{{ playersCount }} / {{ maxPlayers }}</span>
            </span>
            <span v-if="game?.system">•</span>
            <span v-if="game?.system" class="flex items-center gap-1">
              <span>🎲</span>
              <span>{{ game.system }}</span>
            </span>
          </div>
        </div>
      </div>

      <!-- Status & Actions -->
      <div class="flex items-center gap-4">
        <!-- Statut connexion temps réel -->
        <div
          class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-secondary-700/50"
          :title="connectionStatusText"
        >
          <div :class="['w-2 h-2 rounded-full', connectionStatusClass]"></div>
          <span class="text-sm text-secondary-300">
            {{ connectionStatusText }}
          </span>
        </div>

        <!-- Boutons d'action -->
        <div class="flex items-center gap-2">
          <!-- Paramètres -->
          <button
            @click="emit('openSettings')"
            class="p-2 hover:bg-secondary-700 rounded-lg transition-colors"
            title="Paramètres de la partie"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              class="w-5 h-5 text-secondary-300"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
            >
              <circle cx="12" cy="12" r="3"></circle>
              <path
                d="M12 1v6m0 6v6m-9-9h6m6 0h6M4.93 4.93l4.24 4.24m5.66 5.66l4.24 4.24M4.93 19.07l4.24-4.24m5.66-5.66l4.24-4.24"
              ></path>
            </svg>
          </button>

          <!-- Quitter -->
          <button
            @click="emit('leaveGame')"
            class="px-4 py-2 bg-error text-white rounded-lg hover:bg-red-600 transition-colors font-medium shadow-lg"
          >
            Quitter
          </button>
        </div>
      </div>
    </div>
  </header>
</template>

<style scoped>
.gradient-primary {
  background: linear-gradient(135deg, #6366f1, #818cf8);
}
</style>
