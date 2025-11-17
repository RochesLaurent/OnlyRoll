<script setup lang="ts">
import type { Game } from '@/types/game'
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { usePresenceStore } from '@/stores/presenceStore'
import { UsersIcon, LockClosedIcon } from '@heroicons/vue/24/outline'

interface Props {
  game: Game
  showJoinButton?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  showJoinButton: false,
})

const emit = defineEmits<{
  join: [game: Game]
}>()

const router = useRouter()
const presenceStore = usePresenceStore()

const statusConfig = computed(() => {
  const configs = {
    preparation: {
      label: 'En préparation',
      class: 'bg-info text-white',
    },
    in_progress: {
      label: 'En cours',
      class: 'bg-accent-emerald text-white',
    },
    paused: {
      label: 'En pause',
      class: 'bg-warning text-primary-900',
    },
    completed: {
      label: 'Terminée',
      class: 'bg-secondary-600 text-white',
    },
    archived: {
      label: 'Archivée',
      class: 'bg-secondary-700 text-secondary-300',
    },
  }
  return configs[props.game.status] || configs.preparation
})

const isFull = computed(() => props.game.currentPlayersCount >= props.game.maxPlayers)

// Extraction du niveau de campagne depuis settings (si disponible)
const campaignLevel = computed(() => {
  return props.game.settings?.campaignLevel || 6 // Valeur par défaut
})

// Nombre de joueurs actuellement connectés en temps réel
const onlinePlayersCount = computed(() => {
  return presenceStore.getOnlineCount(props.game.id)
})

function viewGame() {
  router.push({ name: 'game-play', params: { id: props.game.id } })
}

function handleJoin(event: Event) {
  event.stopPropagation()
  emit('join', props.game)
}
</script>

<template>
  <div
    class="bg-secondary-800 rounded-lg overflow-hidden border border-secondary-700 hover:border-primary-500 transition-all duration-300 cursor-pointer group"
    @click="viewGame"
  >
    <!-- Header Image avec gradient gris/violet pâle comme maquette -->
    <div
      class="h-32 bg-gradient-to-br from-secondary-600 via-secondary-500 to-primary-400 relative"
    >
      <!-- Badge Status en overlay top-right -->
      <div class="absolute top-3 right-3">
        <span
          :class="statusConfig.class"
          class="px-3 py-1 rounded-full text-xs font-semibold shadow-lg"
        >
          {{ statusConfig.label }}
        </span>
      </div>

      <!-- Icône privé en bottom-left (optionnel, seulement si privé) -->
      <div v-if="!game.isPublic" class="absolute bottom-3 left-3">
        <div
          class="flex items-center gap-1 text-secondary-200 text-xs bg-secondary-900/60 px-2 py-1 rounded-md backdrop-blur-sm"
        >
          <LockClosedIcon class="w-3 h-3" />
          <span>Privée</span>
        </div>
      </div>
    </div>

    <!-- Contenu de la carte -->
    <div class="p-4">
      <!-- Titre de la partie -->
      <h3
        class="text-lg font-semibold text-secondary-50 mb-1 group-hover:text-primary-400 transition-colors"
      >
        {{ game.name }}
      </h3>

      <!-- Game Master -->
      <p class="text-secondary-400 text-sm mb-1">Maître du jeu</p>

      <!-- Niveau de campagne -->
      <p class="text-secondary-300 text-sm mb-4">Campagne niveau : {{ campaignLevel }}</p>

      <!-- Players Count et Bouton -->
      <div class="flex items-center justify-between">
        <div class="flex items-center text-sm gap-2">
          <UsersIcon
            class="w-5 h-5"
            :class="onlinePlayersCount > 0 ? 'text-success' : 'text-secondary-400'"
          />
          <span class="text-secondary-400">
            <span :class="onlinePlayersCount > 0 ? 'text-success font-semibold' : ''">{{
              onlinePlayersCount
            }}</span>
            joueur(s) connecté(s)
          </span>
        </div>

        <!-- Bouton Jouer -->
        <button
          v-if="showJoinButton"
          @click="handleJoin"
          :disabled="isFull"
          :class="[
            'px-4 py-2 rounded-md text-sm font-medium transition-all duration-200',
            isFull
              ? 'bg-secondary-700 text-secondary-500 cursor-not-allowed'
              : 'bg-primary-500 hover:bg-primary-600 text-white hover:shadow-purple',
          ]"
        >
          {{ isFull ? 'Complète' : 'Jouer' }}
        </button>
      </div>
    </div>
  </div>
</template>
