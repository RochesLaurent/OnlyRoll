<script setup lang="ts">
import { computed } from 'vue'
import type { GamePlayer } from '@/types/game'
import { PlayerRole, PlayerStatus } from '@/types/game'

const props = defineProps<{
  players: GamePlayer[]
  gameMasterId: number | undefined
}>()

// ============================================
// Computed
// ============================================
const sortedPlayers = computed(() => {
  // Trier : MJ d'abord, puis joueurs actifs, puis inactifs
  return [...props.players].sort((a, b) => {
    if (a.role === PlayerRole.GAME_MASTER) return -1
    if (b.role === PlayerRole.GAME_MASTER) return 1
    if (a.status === PlayerStatus.ACTIVE && b.status !== PlayerStatus.ACTIVE) return -1
    if (b.status === PlayerStatus.ACTIVE && a.status !== PlayerStatus.ACTIVE) return 1
    return 0
  })
})

const onlinePlayers = computed(() => {
  return props.players.filter((p) => p.status === PlayerStatus.ACTIVE)
})

const playersByRole = computed(() => {
  return {
    gameMaster: props.players.filter((p) => p.role === PlayerRole.GAME_MASTER),
    players: props.players.filter((p) => p.role === PlayerRole.PLAYER),
    spectators: props.players.filter((p) => p.role === PlayerRole.SPECTATOR),
  }
})

// ============================================
// Helpers
// ============================================
function getStatusColor(status: PlayerStatus): string {
  const colors = {
    [PlayerStatus.ACTIVE]: 'bg-success',
    [PlayerStatus.INACTIVE]: 'bg-secondary-500',
    [PlayerStatus.PENDING]: 'bg-warning',
    [PlayerStatus.KICKED]: 'bg-error',
    [PlayerStatus.LEFT]: 'bg-secondary-500',
  }
  return colors[status] || 'bg-secondary-500'
}

function getStatusLabel(status: PlayerStatus): string {
  const labels = {
    [PlayerStatus.ACTIVE]: 'En ligne',
    [PlayerStatus.INACTIVE]: 'Inactif',
    [PlayerStatus.PENDING]: 'En attente',
    [PlayerStatus.KICKED]: 'Exclu',
    [PlayerStatus.LEFT]: 'Parti',
  }
  return labels[status] || 'Inconnu'
}

function getRoleLabel(role: PlayerRole): string {
  const labels = {
    [PlayerRole.GAME_MASTER]: 'MJ',
    [PlayerRole.PLAYER]: 'Joueur',
    [PlayerRole.SPECTATOR]: 'Spectateur',
  }
  return labels[role] || 'Inconnu'
}

function getRoleColor(role: PlayerRole): string {
  const colors = {
    [PlayerRole.GAME_MASTER]: 'bg-accent-purple text-white',
    [PlayerRole.PLAYER]: 'bg-primary-500/30 text-primary-200',
    [PlayerRole.SPECTATOR]: 'bg-secondary-600 text-secondary-300',
  }
  return colors[role] || 'bg-secondary-600 text-secondary-300'
}

function getAvatarColor(userId: number): string {
  const colors = [
    'bg-primary-500',
    'bg-accent-amber',
    'bg-accent-emerald',
    'bg-accent-rose',
    'bg-accent-cyan',
    'bg-accent-purple',
  ]
  return colors[userId % colors.length]
}

function formatJoinedAt(dateString: string): string {
  const date = new Date(dateString)
  const now = new Date()
  const diffMs = now.getTime() - date.getTime()
  const diffMins = Math.floor(diffMs / 60000)

  if (diffMins < 1) return "À l'instant"
  if (diffMins < 60) return `Il y a ${diffMins} min`

  const diffHours = Math.floor(diffMins / 60)
  if (diffHours < 24) return `Il y a ${diffHours}h`

  const diffDays = Math.floor(diffHours / 24)
  return `Il y a ${diffDays}j`
}
</script>

<template>
  <div class="flex-1 overflow-y-auto p-4">
    <!-- Header avec stats -->
    <div class="mb-6">
      <h3 class="font-bold text-secondary-50 text-lg mb-2 flex items-center gap-2">
        <span>👥</span>
        Joueurs
      </h3>

      <div class="grid grid-cols-3 gap-2 text-center text-sm">
        <div class="bg-secondary-700 rounded-lg p-2">
          <div class="text-success font-bold">{{ onlinePlayers.length }}</div>
          <div class="text-secondary-400 text-xs">En ligne</div>
        </div>
        <div class="bg-secondary-700 rounded-lg p-2">
          <div class="text-secondary-50 font-bold">{{ players.length }}</div>
          <div class="text-secondary-400 text-xs">Total</div>
        </div>
        <div class="bg-secondary-700 rounded-lg p-2">
          <div class="text-accent-purple font-bold">{{ playersByRole.gameMaster.length }}</div>
          <div class="text-secondary-400 text-xs">MJ</div>
        </div>
      </div>
    </div>

    <!-- Liste des joueurs -->
    <div class="space-y-2">
      <div
        v-for="player in sortedPlayers"
        :key="player.id"
        class="card p-3 hover:bg-secondary-700 transition-all cursor-pointer group"
      >
        <div class="flex items-center gap-3">
          <!-- Avatar -->
          <div
            :class="[
              'w-12 h-12 rounded-full flex items-center justify-center text-white font-bold text-lg flex-shrink-0 relative',
              getAvatarColor(player.user.id),
            ]"
          >
            <span>{{ player.user.pseudo.slice(0, 2).toUpperCase() }}</span>

            <!-- Indicateur de statut -->
            <div
              :class="[
                'absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 rounded-full border-2 border-secondary-800',
                getStatusColor(player.status),
              ]"
              :title="getStatusLabel(player.status)"
            ></div>
          </div>

          <!-- Infos -->
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 mb-1">
              <span class="font-semibold text-secondary-50 truncate">
                {{ player.user.pseudo }}
              </span>

              <!-- Badge rôle -->
              <span
                :class="[
                  'px-2 py-0.5 text-xs font-medium rounded whitespace-nowrap',
                  getRoleColor(player.role),
                ]"
              >
                {{ getRoleLabel(player.role) }}
              </span>
            </div>

            <!-- Détails -->
            <div class="flex items-center gap-2 text-xs text-secondary-400">
              <span>{{ getStatusLabel(player.status) }}</span>
              <span>•</span>
              <span>{{ formatJoinedAt(player.joinedAt) }}</span>
            </div>
          </div>

          <!-- Actions (visible au hover pour les MJ) -->
          <div class="opacity-0 group-hover:opacity-100 transition-opacity">
            <button class="p-1.5 hover:bg-secondary-600 rounded transition-colors" title="Options">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="w-4 h-4 text-secondary-400"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
              >
                <circle cx="12" cy="12" r="1"></circle>
                <circle cx="12" cy="5" r="1"></circle>
                <circle cx="12" cy="19" r="1"></circle>
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Message si aucun joueur -->
    <div v-if="players.length === 0" class="text-center py-12 text-secondary-400">
      <div class="text-4xl mb-3">👥</div>
      <p class="text-lg">Aucun joueur dans la partie</p>
      <p class="text-sm mt-1">Invitez vos amis à vous rejoindre</p>
    </div>

    <!-- Section Spectateurs si présents -->
    <div v-if="playersByRole.spectators.length > 0" class="mt-6 pt-6 border-t border-secondary-700">
      <h4 class="text-sm font-semibold text-secondary-400 mb-3 flex items-center gap-2">
        <span>👁️</span>
        Spectateurs ({{ playersByRole.spectators.length }})
      </h4>
      <div class="space-y-2">
        <div
          v-for="spectator in playersByRole.spectators"
          :key="spectator.id"
          class="bg-secondary-700/50 rounded-lg p-2 flex items-center gap-2"
        >
          <div
            :class="[
              'w-8 h-8 rounded-full flex items-center justify-center text-white text-sm',
              getAvatarColor(spectator.user.id),
            ]"
          >
            {{ spectator.user.pseudo.slice(0, 2).toUpperCase() }}
          </div>
          <span class="text-sm text-secondary-300">{{ spectator.user.pseudo }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Scrollbar custom */
::-webkit-scrollbar {
  width: 8px;
}

::-webkit-scrollbar-track {
  background: #1e293b;
}

::-webkit-scrollbar-thumb {
  background: #475569;
  border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
  background: #64748b;
}
</style>
