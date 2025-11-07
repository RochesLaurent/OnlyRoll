<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, onBeforeUnmount, watch } from 'vue'
import { useRoute, useRouter, onBeforeRouteLeave } from 'vue-router'
import { useGameStore } from '@/stores/game'
import { useMapStore } from '@/stores/mapStore'
import { useChatStore } from '@/stores/chatStore'
import { usePresenceStore } from '@/stores/presenceStore'
import { mercureService } from '@/services/mercure'
import { presenceApi } from '@/services/api/presenceApi'
import type {
  MercureTokenEventData,
  MercureMapEventData,
  MercureChatMessageData,
  MercurePresenceEventData,
} from '@/types/websocket'

// Composants
import GameHeader from '@/components/game/GameHeader.vue'
import GameMap from '@/components/game/GameMap.vue'
import MapToolbar from '@/components/game/MapToolbar.vue'
import ChatPanel from '@/components/game/ChatPanel.vue'
import PlayersList from '@/components/game/PlayersList.vue'
import DiceRoller from '@/components/game/DiceRoller.vue'
import EmptyMapState from '@/components/game/EmptyMapState.vue'
import UploadMapModal from '@/components/game/UploadMapModal.vue'

const route = useRoute()
const router = useRouter()
const gameId = computed(() => Number(route.params.id))

// Stores
const gameStore = useGameStore()
const mapStore = useMapStore()
const chatStore = useChatStore()
const presenceStore = usePresenceStore()

// États locaux
const rightPanelOpen = ref(true)
const activeTab = ref<'chat' | 'players' | 'dice'>('chat')
const isLoading = ref(true)
const selectedTool = ref('select')
const mapZoom = ref(100)

// État Mercure
const isConnected = ref(false)
const connectionState = ref<'connecting' | 'open' | 'closed'>('connecting')

// État upload carte
const showUploadModal = ref(false)

// Référence au composant GameMap
const gameMapRef = ref<InstanceType<typeof GameMap> | null>(null)

// ============================================
// Fonction de déconnexion synchrone pour beforeunload
// ============================================
function notifyDisconnectionBeacon() {
  try {
    // Pour beforeunload, on utilise sendBeacon (pas d'auth mais c'est le mieux qu'on puisse faire)
    const url = `/api/games/${gameId.value}/presence/leave`
    const blob = new Blob([JSON.stringify({})], { type: 'application/json' })
    navigator.sendBeacon(url, blob)
  } catch (error) {
    console.error('Erreur sendBeacon:', error)
  }
}

// Fonction de déconnexion async pour navigation interne
async function notifyDisconnection() {
  try {
    console.log('Envoi notification de déconnexion...')
    await presenceApi.leave(gameId.value)
    console.log('Déconnexion notifiée avec succès')
  } catch (error) {
    console.error('Erreur lors de la notification de déconnexion:', error)
  }
}

// ============================================
// Lifecycle
// ============================================
onMounted(async () => {
  console.log('Initialisation de la partie', gameId.value)
  await initializeGame()
  setupMercure()
  setupBeforeUnload()
})

// Détecter la navigation interne (retour arrière, changement de route)
onBeforeRouteLeave(async (to, from) => {
  console.log('Navigation détectée - déconnexion en cours')

  // Attendre que la déconnexion soit notifiée avant de continuer
  await notifyDisconnection()

  mercureService.disconnect()
  presenceStore.clearGamePresence(gameId.value)

  return true // Permettre la navigation
})

onUnmounted(() => {
  console.log('Nettoyage de la partie')
  // Le nettoyage a déjà été fait dans onBeforeRouteLeave pour la navigation interne
  // Mais on le fait quand même au cas où le composant serait détruit autrement
  mercureService.disconnect()
  presenceStore.clearGamePresence(gameId.value)
})

// ============================================
// Initialisation
// ============================================
async function initializeGame() {
  try {
    isLoading.value = true

    // Charger les données de la partie en parallèle
    await Promise.all([
      gameStore.fetchGameById(gameId.value),
      mapStore.loadActiveMap(gameId.value),
      chatStore.loadRecentMessages(gameId.value, 50),
    ])

    console.log('Partie chargée:', {
      game: gameStore.currentGame,
      map: mapStore.activeMap,
      tokens: mapStore.tokens.length,
      messages: chatStore.messages.length,
    })

    // Notifier la présence et charger la liste des utilisateurs en ligne
    try {
      const response = await presenceApi.join(gameId.value)
      if (response.onlineUsers) {
        presenceStore.setOnlineUsers(gameId.value, response.onlineUsers)
      }
      console.log('Présence notifiée, utilisateurs en ligne:', response.onlineUsers?.length || 0)
    } catch (error) {
      console.error('Erreur lors de la notification de présence:', error)
    }
  } catch (error) {
    console.error('Erreur lors du chargement de la partie:', error)
    router.push('/games')
  } finally {
    isLoading.value = false
  }
}

// ============================================
// Setup Mercure
// ============================================
function setupMercure() {
  console.log('Configuration de Mercure pour la partie', gameId.value)

  mercureService.connect(gameId.value)

  // Vérifier l'état de connexion
  const checkConnection = setInterval(() => {
    isConnected.value = mercureService.isConnected()
    connectionState.value = mercureService.getConnectionState()

    if (isConnected.value) {
      console.log('Mercure connecté')
      clearInterval(checkConnection)
    }
  }, 500)

  // Écouter les événements de tokens
  mercureService.on('token', (event: any) => {
    console.log('Token event:', event.data)
    mapStore.handleTokenEvent(event.data as MercureTokenEventData)
  })

  // Écouter les événements de carte
  mercureService.on('map', (event: any) => {
    console.log('Map event:', event.data)
    mapStore.handleMapEvent(event.data as MercureMapEventData)
  })

  // Écouter les messages du chat
  mercureService.on('chat', (event: any) => {
    console.log('Chat message:', event.data)
    chatStore.handleChatMessage(event.data as MercureChatMessageData)
  })

  // Écouter les événements de joueurs
  mercureService.on('player', (event: any) => {
    console.log('Player event:', event.data)
    gameStore.fetchGameById(gameId.value)
  })

  // Écouter les événements de présence
  mercureService.on('presence', (event: any) => {
    console.log('Presence event:', event)
    // L'événement Mercure a gameId au niveau principal
    const presenceData: MercurePresenceEventData = {
      gameId: event.gameId,
      userId: event.data.userId,
      type: event.data.type,
      onlineUsers: event.data.onlineUsers,
      timestamp: event.data.timestamp,
    }
    presenceStore.handlePresenceEvent(presenceData)
  })

  // Envoyer un heartbeat de présence toutes les 30 secondes
  const heartbeatInterval = setInterval(async () => {
    if (mercureService.isConnected()) {
      try {
        await presenceApi.heartbeat(gameId.value)
        console.log('Heartbeat envoyé pour la partie', gameId.value)
      } catch (error) {
        console.error('Erreur lors de l\'envoi du heartbeat:', error)
      }
    }
  }, 30000)

  // Nettoyer l'interval au démontage
  onUnmounted(() => {
    clearInterval(heartbeatInterval)
  })
}

// ============================================
// Setup BeforeUnload - Notifier la déconnexion
// ============================================
function setupBeforeUnload() {
  // Ajouter les listeners pour beforeunload (fermeture navigateur/onglet)
  // On utilise sendBeacon car async n'est pas possible dans beforeunload
  window.addEventListener('beforeunload', notifyDisconnectionBeacon)

  // Nettoyer au démontage
  onUnmounted(() => {
    window.removeEventListener('beforeunload', notifyDisconnectionBeacon)
  })
}

// ============================================
// Computed
// ============================================
const currentGame = computed(() => gameStore.currentGame)
const activeMap = computed(() => mapStore.activeMap)
const tokens = computed(() => mapStore.tokens)
const messages = computed(() => chatStore.sortedMessages)
const isGameMaster = computed(() => gameStore.isGameMaster)
const hasActiveMap = computed(() => mapStore.hasActiveMap)

// ============================================
// Watchers
// ============================================
watch(isConnected, (connected) => {
  if (connected) {
    console.log('Mercure connecté')
  } else {
    console.log('Mercure déconnecté')
  }
})

// ============================================
// Handlers - Upload de carte
// ============================================
function handleCreateMap() {
  showUploadModal.value = true
}

async function handleMapCreated() {
  // Recharger la carte active
  await mapStore.loadActiveMap(gameId.value)
  showUploadModal.value = false
}

// ============================================
// Handlers - Toolbar & Navigation
// ============================================
function handleToolChanged(tool: string) {
  selectedTool.value = tool
  console.log('Outil sélectionné:', tool)
}

function handleZoomChanged(zoom: number) {
  mapZoom.value = zoom
  console.log('Zoom changé:', zoom)
}

function handleCenterMap() {
  if (gameMapRef.value) {
    gameMapRef.value.centerView()
  }
}

function handleOpenSettings() {
  console.log('Ouvrir les paramètres')
  // TODO: Implémenter le modal des paramètres
}

function handleGoBack() {
  // Simplement retourner à la liste des parties
  // La déconnexion sera gérée automatiquement par onBeforeRouteLeave
  router.push('/games')
}

async function handleLeaveGame() {
  if (
    !confirm(
      'Êtes-vous sûr de vouloir quitter cette partie ?\n\nVous serez retiré en tant que membre et ne pourrez plus y accéder.',
    )
  )
    return

  try {
    await gameStore.leaveGame(gameId.value)
    router.push('/games')
  } catch (error) {
    console.error('Erreur en quittant la partie:', error)
  }
}
</script>

<template>
  <!-- Loading state -->
  <div v-if="isLoading" class="h-screen flex items-center justify-center bg-primary-900">
    <div class="text-center">
      <div
        class="animate-spin w-16 h-16 border-4 border-primary-500 border-t-transparent rounded-full mx-auto mb-4"
      ></div>
      <p class="text-secondary-50 text-lg">Chargement de la partie...</p>
    </div>
  </div>

  <!-- Main game view -->
  <div v-else class="h-screen bg-gradient-dark flex flex-col overflow-hidden">
    <!-- Header -->
    <GameHeader
      :game="currentGame"
      :is-connected="isConnected"
      :connection-state="connectionState"
      @go-back="handleGoBack"
      @open-settings="handleOpenSettings"
      @leave-game="handleLeaveGame"
    />

    <div class="flex-1 flex overflow-hidden relative">
      <!-- Zone centrale - Carte -->
      <div class="flex-1 flex flex-col min-w-0">
        <!-- Toolbar -->
        <MapToolbar
          :is-game-master="isGameMaster"
          :game-id="gameId"
          @tool-changed="handleToolChanged"
          @open-upload-modal="handleCreateMap"
          @zoom-changed="handleZoomChanged"
          @center-map="handleCenterMap"
        />

        <div class="flex-1 relative overflow-hidden min-h-0">
          <EmptyMapState
            v-if="!hasActiveMap"
            :is-game-master="isGameMaster"
            @create-map="handleCreateMap"
          />

          <!-- Carte normale si elle existe -->
          <GameMap
            v-else
            ref="gameMapRef"
            :map="activeMap"
            :tokens="tokens"
            :editable="isGameMaster"
            :selected-tool="selectedTool"
            :zoom="mapZoom"
          />
        </div>
      </div>

      <!-- Panel droit - Chat & Joueurs -->
      <Transition name="slide-left">
        <div
          v-if="rightPanelOpen"
          class="w-96 bg-secondary-800 border-l border-secondary-700 flex flex-col"
        >
          <!-- Tabs -->
          <div class="flex border-b border-secondary-700">
            <button
              v-for="tab in ['chat', 'players', 'dice'] as const"
              :key="tab"
              @click="activeTab = tab"
              :class="[
                'flex-1 px-4 py-3 font-medium transition-colors',
                activeTab === tab
                  ? 'bg-primary-500 text-white'
                  : 'text-secondary-300 hover:bg-secondary-700',
              ]"
            >
              <span v-if="tab === 'chat'">💬 Chat</span>
              <span v-else-if="tab === 'players'">👥 Joueurs</span>
              <span v-else>🎲 Dés</span>
            </button>
          </div>

          <!-- Contenu -->
          <ChatPanel v-if="activeTab === 'chat'" :messages="messages" :game-id="gameId" />

          <PlayersList
            v-if="activeTab === 'players'"
            :players="currentGame?.gamePlayers || []"
            :game-master-id="currentGame?.gameMaster?.id"
          />

          <DiceRoller v-if="activeTab === 'dice'" :game-id="gameId" />
        </div>
      </Transition>

      <!-- Toggle panel -->
      <button
        @click="rightPanelOpen = !rightPanelOpen"
        :class="[
          'absolute top-1/2 -translate-y-1/2 bg-secondary-800 border border-secondary-700 p-3 hover:bg-secondary-700 transition-all z-20 shadow-lg',
          rightPanelOpen ? 'right-96' : 'right-0',
          rightPanelOpen ? 'rounded-l-lg' : 'rounded-l-lg'
        ]"
        :title="rightPanelOpen ? 'Masquer le panel (chat, joueurs, dés)' : 'Afficher le panel (chat, joueurs, dés)'"
      >
        <div class="flex items-center gap-2">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="w-5 h-5 text-secondary-300 transition-transform duration-300"
            :class="{ 'rotate-180': !rightPanelOpen }"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
          >
            <polyline points="9 18 15 12 9 6"></polyline>
          </svg>
          <span v-if="!rightPanelOpen" class="text-xs text-secondary-400 font-medium">
            Panel
          </span>
        </div>
      </button>
    </div>

    <UploadMapModal
      :show="showUploadModal"
      :game-id="gameId"
      @close="showUploadModal = false"
      @success="handleMapCreated"
    />
  </div>
</template>

<style scoped>
.gradient-dark {
  background: linear-gradient(135deg, #1a0b2e, #0f172a);
}

/* Transitions pour le panel */
.slide-left-enter-active,
.slide-left-leave-active {
  transition: transform 0.3s ease;
}

.slide-left-enter-from {
  transform: translateX(100%);
}

.slide-left-leave-to {
  transform: translateX(100%);
}
</style>
