<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useGameStore } from '@/stores/game'
import { useMapStore } from '@/stores/mapStore'
import { useChatStore } from '@/stores/chatStore'
import { mercureService } from '@/services/mercure'

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

// États locaux
const rightPanelOpen = ref(true)
const activeTab = ref<'chat' | 'players' | 'dice'>('chat')
const isLoading = ref(true)
const selectedTool = ref('select')

// État Mercure
const isConnected = ref(false)
const connectionState = ref<'connecting' | 'open' | 'closed'>('connecting')

// État upload carte
const showUploadModal = ref(false)

// ============================================
// Lifecycle
// ============================================
onMounted(async () => {
  console.log('🎮 Initialisation de la partie', gameId.value)
  await initializeGame()
  setupMercure()
})

onUnmounted(() => {
  console.log('🎮 Nettoyage de la partie')
  mercureService.disconnect()
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

    console.log('✅ Partie chargée:', {
      game: gameStore.currentGame,
      map: mapStore.activeMap,
      tokens: mapStore.tokens.length,
      messages: chatStore.messages.length,
    })
  } catch (error) {
    console.error('❌ Erreur lors du chargement de la partie:', error)
    router.push('/games')
  } finally {
    isLoading.value = false
  }
}

// ============================================
// Setup Mercure
// ============================================
function setupMercure() {
  console.log('📡 Configuration de Mercure pour la partie', gameId.value)

  mercureService.connect(gameId.value)

  // Vérifier l'état de connexion
  const checkConnection = setInterval(() => {
    isConnected.value = mercureService.isConnected()
    connectionState.value = mercureService.getConnectionState()

    if (isConnected.value) {
      console.log('✅ Mercure connecté')
      clearInterval(checkConnection)
    }
  }, 500)

  // Écouter les événements de tokens
  mercureService.on('token', (data) => {
    console.log('🎭 Token event:', data)
    mapStore.handleTokenEvent(data as any)
  })

  // Écouter les événements de carte
  mercureService.on('map', (data) => {
    console.log('🗺️ Map event:', data)
    mapStore.handleMapEvent(data as any)
  })

  // Écouter les messages du chat
  mercureService.on('chat', (data) => {
    console.log('💬 Chat message:', data)
    chatStore.handleChatMessage(data as any)
  })

  // Écouter les événements de joueurs
  mercureService.on('player', (data) => {
    console.log('👥 Player event:', data)
    gameStore.fetchGameById(gameId.value)
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
    console.log('✅ Mercure connecté')
  } else {
    console.log('❌ Mercure déconnecté')
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

function handleOpenSettings() {
  console.log('Ouvrir les paramètres')
  // TODO: Implémenter le modal des paramètres
}

async function handleLeaveGame() {
  if (!confirm('Êtes-vous sûr de vouloir quitter cette partie ?')) return

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
      @open-settings="handleOpenSettings"
      @leave-game="handleLeaveGame"
    />

    <div class="flex-1 flex overflow-hidden relative">
      <!-- Zone centrale - Carte -->
      <div class="flex-1 flex flex-col">
        <!-- Toolbar uniquement si une carte existe -->
        <MapToolbar
          v-if="hasActiveMap"
          :is-game-master="isGameMaster"
          @tool-changed="handleToolChanged"
        />

        <div class="flex-1 relative overflow-hidden">
          <EmptyMapState
            v-if="!hasActiveMap"
            :is-game-master="isGameMaster"
            @create-map="handleCreateMap"
          />

          <!-- Carte normale si elle existe -->
          <GameMap
            v-else
            :map="activeMap"
            :tokens="tokens"
            :editable="isGameMaster"
            :selected-tool="selectedTool"
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
        class="absolute right-0 top-1/2 -translate-y-1/2 bg-secondary-800 border border-secondary-700 p-2 rounded-l-lg hover:bg-secondary-700 transition-colors z-10 shadow-lg"
        :title="rightPanelOpen ? 'Masquer le panel' : 'Afficher le panel'"
      >
        <svg
          xmlns="http://www.w3.org/2000/svg"
          class="w-5 h-5 text-secondary-300 transition-transform"
          :class="{ 'rotate-180': !rightPanelOpen }"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
        >
          <polyline points="9 18 15 12 9 6"></polyline>
        </svg>
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
