<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useMapStore } from '@/stores/mapStore'
import { useChatStore } from '@/stores/chatStore'
import { useMercure } from '@/composables/useMercure'
// ⭐ AJOUT: Import de l'enum TokenType depuis le fichier centralisé
import { TokenType } from '@/types/game'

const props = defineProps<{
  gameId: number
}>()

// Stores
const mapStore = useMapStore()
const chatStore = useChatStore()

// État local pour les tests
const testMessage = ref('')
const testTokenName = ref('Test Token')
const testTokenX = ref(5)
const testTokenY = ref(5)
const selectedTokenId = ref<number | null>(null)
const moveX = ref(0)
const moveY = ref(0)

// Mercure
const { isConnected, connectionState, onTokenEvent, onMapChange, onChatMessage } = useMercure(
  props.gameId,
)

// Computed
const connectionStatusClass = computed(() => {
  switch (connectionState.value) {
    case 'open':
      return 'bg-green-500'
    case 'connecting':
      return 'bg-yellow-500 animate-pulse'
    case 'closed':
      return 'bg-red-500'
    default:
      return 'bg-gray-500'
  }
})

// Lifecycle
onMounted(async () => {
  console.log('🧪 Test des stores - Démarrage')
  await initializeStores()
  setupMercureListeners()
})

onUnmounted(() => {
  console.log('🧪 Test des stores - Nettoyage')
})

// Initialisation
async function initializeStores() {
  try {
    console.log('📥 Chargement des données...')

    // Charger en parallèle
    await Promise.all([
      mapStore.loadActiveMap(props.gameId),
      chatStore.loadRecentMessages(props.gameId, 20),
    ])

    console.log('✅ Données chargées avec succès')
    console.log('Carte active:', mapStore.activeMap)
    console.log('Tokens:', mapStore.tokens)
    console.log('Messages:', chatStore.messages)
  } catch (error) {
    console.error('❌ Erreur lors du chargement:', error)
  }
}

function setupMercureListeners() {
  console.log('🔌 Configuration des listeners Mercure...')

  // Écouter les événements de carte
  onMapChange((data) => {
    console.log('📡 Événement carte reçu:', data)
    mapStore.handleMapEvent(data)
  })

  // Écouter les événements de tokens
  onTokenEvent((data) => {
    console.log('📡 Événement token reçu:', data)
    mapStore.handleTokenEvent(data)
  })

  // Écouter les messages
  onChatMessage((data) => {
    console.log('📡 Message reçu:', data)
    chatStore.handleChatMessage(data)
  })

  console.log('✅ Listeners Mercure configurés')
}

// Tests MapStore
async function testCreateToken() {
  try {
    console.log('🧪 Test: Création de token')

    if (!mapStore.activeMap) {
      alert('Aucune carte active !')
      return
    }

    // ⭐ CORRECTION: Utilisation de l'enum TokenType au lieu d'une chaîne littérale
    // Cela garantit que la valeur envoyée est valide et évite les fautes de frappe
    const token = await mapStore.createToken(mapStore.activeMap.id, {
      name: testTokenName.value,
      type: TokenType.CHARACTER, // ← Utilisation de l'enum au lieu de 'character'
      x: testTokenX.value,
      y: testTokenY.value,
      // Les champs ci-dessous sont optionnels et auront des valeurs par défaut
      // si non fournis (size: 1.0, rotation: 0, isVisible: true, etc.)
      // Nous les spécifions quand même ici pour être explicite dans les tests
      size: 1.0,
      isVisible: true,
    })

    console.log('✅ Token créé:', token)
    alert(`Token "${token.name}" créé avec succès !`)
  } catch (error) {
    console.error('❌ Erreur création token:', error)
    alert('Erreur lors de la création du token')
  }
}

async function testMoveToken() {
  if (!selectedTokenId.value) {
    alert('Sélectionnez un token !')
    return
  }

  try {
    console.log('🧪 Test: Déplacement de token')

    const token = await mapStore.moveToken(selectedTokenId.value, moveX.value, moveY.value)

    console.log('✅ Token déplacé:', token)
    alert(`Token déplacé à (${token.x}, ${token.y}) !`)
  } catch (error) {
    console.error('❌ Erreur déplacement token:', error)
    alert('Erreur lors du déplacement du token')
  }
}

async function testToggleVisibility() {
  if (!selectedTokenId.value) {
    alert('Sélectionnez un token !')
    return
  }

  try {
    console.log('🧪 Test: Toggle visibilité')
    await mapStore.toggleTokenVisibility(selectedTokenId.value)
    console.log('✅ Visibilité modifiée')
  } catch (error) {
    console.error('❌ Erreur toggle visibilité:', error)
    alert('Erreur lors du changement de visibilité')
  }
}

async function testDeleteToken() {
  if (!selectedTokenId.value) {
    alert('Sélectionnez un token !')
    return
  }

  if (!confirm('Supprimer ce token ?')) return

  try {
    console.log('🧪 Test: Suppression de token')
    await mapStore.deleteToken(selectedTokenId.value)
    console.log('✅ Token supprimé')
    selectedTokenId.value = null
    alert('Token supprimé !')
  } catch (error) {
    console.error('❌ Erreur suppression token:', error)
    alert('Erreur lors de la suppression du token')
  }
}

// Tests ChatStore
async function testSendMessage() {
  if (!testMessage.value.trim()) {
    alert('Entrez un message !')
    return
  }

  try {
    console.log('🧪 Test: Envoi de message')
    await chatStore.sendMessage(props.gameId, testMessage.value, false)
    console.log('✅ Message envoyé')
    testMessage.value = ''
  } catch (error) {
    console.error('❌ Erreur envoi message:', error)
    alert("Erreur lors de l'envoi du message")
  }
}

async function testRollDice() {
  try {
    console.log('🧪 Test: Lancer de dés')
    // ⭐ Le paramètre est maintenant "formula" (cohérent avec le backend)
    await chatStore.rollDice(props.gameId, '2d6+3', true)
    console.log('✅ Dés lancés')
  } catch (error) {
    console.error('❌ Erreur lancer de dés:', error)
    alert('Erreur lors du lancer de dés')
  }
}

// Formatage
function formatTime(dateString: string) {
  const date = new Date(dateString)
  return date.toLocaleTimeString('fr-FR', {
    hour: '2-digit',
    minute: '2-digit',
  })
}
</script>

<template>
  <div class="p-6 bg-gray-900 text-white min-h-screen">
    <div class="max-w-7xl mx-auto space-y-6">
      <!-- Header -->
      <div class="bg-gray-800 rounded-lg p-6">
        <h1 class="text-3xl font-bold mb-4">🧪 Test des Stores Pinia</h1>
        <div class="flex items-center gap-4">
          <div class="flex items-center gap-2">
            <div :class="connectionStatusClass" class="w-3 h-3 rounded-full" />
            <span class="text-sm"> Mercure: {{ isConnected ? 'Connecté' : 'Déconnecté' }} </span>
          </div>
          <div class="text-sm text-gray-400">Game ID: {{ gameId }}</div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- MapStore Tests -->
        <div class="bg-gray-800 rounded-lg p-6 space-y-4">
          <h2 class="text-2xl font-bold mb-4">🗺️ MapStore</h2>

          <!-- État -->
          <div class="space-y-2">
            <h3 class="font-semibold text-lg">État</h3>
            <div class="bg-gray-700 p-3 rounded text-sm space-y-1">
              <div>Loading: {{ mapStore.isLoading ? '✅' : '❌' }}</div>
              <div>Carte active: {{ mapStore.activeMap?.name || 'Aucune' }}</div>
              <div>Tokens: {{ mapStore.tokensCount }}</div>
              <div>Tokens visibles: {{ mapStore.visibleTokens.length }}</div>
              <div v-if="mapStore.error" class="text-red-400">Erreur: {{ mapStore.error }}</div>
            </div>
          </div>

          <!-- Liste des tokens -->
          <div class="space-y-2">
            <h3 class="font-semibold text-lg">Tokens</h3>
            <div class="bg-gray-700 p-3 rounded max-h-48 overflow-y-auto">
              <div
                v-for="token in mapStore.tokens"
                :key="token.id"
                class="p-2 mb-2 bg-gray-600 rounded cursor-pointer hover:bg-gray-500"
                :class="{ 'ring-2 ring-blue-500': selectedTokenId === token.id }"
                @click="selectedTokenId = token.id"
              >
                <div class="font-semibold">{{ token.name }}</div>
                <div class="text-xs text-gray-300">
                  Position: ({{ token.x }}, {{ token.y }}) |
                  {{ token.isVisible ? '👁️ Visible' : '🚫 Caché' }} |
                  {{ token.isLocked ? '🔒 Verrouillé' : '🔓 Libre' }}
                </div>
              </div>
              <div v-if="mapStore.tokens.length === 0" class="text-gray-400 text-center py-4">
                Aucun token
              </div>
            </div>
          </div>

          <!-- Actions - Créer un token -->
          <div class="space-y-2">
            <h3 class="font-semibold text-lg">Créer un token</h3>
            <div class="space-y-2">
              <input
                v-model="testTokenName"
                type="text"
                placeholder="Nom du token"
                class="w-full px-3 py-2 bg-gray-700 rounded border border-gray-600 focus:border-blue-500 focus:outline-none"
              />
              <div class="flex gap-2">
                <input
                  v-model.number="testTokenX"
                  type="number"
                  placeholder="X"
                  class="w-1/2 px-3 py-2 bg-gray-700 rounded border border-gray-600 focus:border-blue-500 focus:outline-none"
                />
                <input
                  v-model.number="testTokenY"
                  type="number"
                  placeholder="Y"
                  class="w-1/2 px-3 py-2 bg-gray-700 rounded border border-gray-600 focus:border-blue-500 focus:outline-none"
                />
              </div>
              <button
                @click="testCreateToken"
                :disabled="mapStore.isLoading || !mapStore.activeMap"
                class="w-full px-4 py-2 bg-green-600 hover:bg-green-700 rounded disabled:opacity-50 disabled:cursor-not-allowed"
              >
                ➕ Créer Token
              </button>
            </div>
          </div>

          <!-- Actions - Token sélectionné -->
          <div v-if="selectedTokenId" class="space-y-2">
            <h3 class="font-semibold text-lg">Actions sur token sélectionné</h3>
            <div class="space-y-2">
              <div class="flex gap-2">
                <input
                  v-model.number="moveX"
                  type="number"
                  placeholder="Nouveau X"
                  class="w-1/2 px-3 py-2 bg-gray-700 rounded border border-gray-600 focus:border-blue-500 focus:outline-none"
                />
                <input
                  v-model.number="moveY"
                  type="number"
                  placeholder="Nouveau Y"
                  class="w-1/2 px-3 py-2 bg-gray-700 rounded border border-gray-600 focus:border-blue-500 focus:outline-none"
                />
              </div>
              <button
                @click="testMoveToken"
                class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded"
              >
                ➡️ Déplacer
              </button>
              <button
                @click="testToggleVisibility"
                class="w-full px-4 py-2 bg-purple-600 hover:bg-purple-700 rounded"
              >
                👁️ Toggle Visibilité
              </button>
              <button
                @click="testDeleteToken"
                class="w-full px-4 py-2 bg-red-600 hover:bg-red-700 rounded"
              >
                🗑️ Supprimer
              </button>
            </div>
          </div>
        </div>

        <!-- ChatStore Tests -->
        <div class="bg-gray-800 rounded-lg p-6 space-y-4">
          <h2 class="text-2xl font-bold mb-4">💬 ChatStore</h2>

          <!-- État -->
          <div class="space-y-2">
            <h3 class="font-semibold text-lg">État</h3>
            <div class="bg-gray-700 p-3 rounded text-sm space-y-1">
              <div>Loading: {{ chatStore.isLoading ? '✅' : '❌' }}</div>
              <div>Sending: {{ chatStore.isSending ? '✅' : '❌' }}</div>
              <div>Messages: {{ chatStore.messagesCount }}</div>
              <div>Has more: {{ chatStore.hasMore ? '✅' : '❌' }}</div>
              <div v-if="chatStore.error" class="text-red-400">Erreur: {{ chatStore.error }}</div>
            </div>
          </div>

          <!-- Liste des messages -->
          <div class="space-y-2">
            <h3 class="font-semibold text-lg">Messages</h3>
            <div class="bg-gray-700 p-3 rounded h-96 overflow-y-auto space-y-2">
              <div
                v-for="msg in chatStore.sortedMessages"
                :key="msg.id"
                class="p-2 bg-gray-600 rounded"
              >
                <div class="flex items-center justify-between text-xs text-gray-400 mb-1">
                  <span class="font-semibold">{{ msg.user.pseudo }}</span>
                  <span>{{ formatTime(msg.createdAt) }}</span>
                </div>
                <div class="text-sm">{{ msg.content }}</div>
                <div v-if="msg.type !== 'chat'" class="mt-1">
                  <span class="text-xs px-2 py-0.5 bg-purple-500/30 rounded">
                    {{ msg.type }}
                  </span>
                </div>
              </div>
              <div v-if="chatStore.messages.length === 0" class="text-gray-400 text-center py-8">
                Aucun message
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="space-y-2">
            <h3 class="font-semibold text-lg">Envoyer un message</h3>
            <div class="space-y-2">
              <textarea
                v-model="testMessage"
                placeholder="Votre message..."
                rows="3"
                class="w-full px-3 py-2 bg-gray-700 rounded border border-gray-600 focus:border-blue-500 focus:outline-none resize-none"
                @keyup.ctrl.enter="testSendMessage"
              />
              <button
                @click="testSendMessage"
                :disabled="chatStore.isSending || !testMessage.trim()"
                class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded disabled:opacity-50 disabled:cursor-not-allowed"
              >
                💬 Envoyer (Ctrl+Enter)
              </button>
              <button
                @click="testRollDice"
                :disabled="chatStore.isSending"
                class="w-full px-4 py-2 bg-purple-600 hover:bg-purple-700 rounded disabled:opacity-50 disabled:cursor-not-allowed"
              >
                🎲 Lancer 2d6+3
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Console de logs -->
      <div class="bg-gray-800 rounded-lg p-6">
        <h2 class="text-xl font-bold mb-4">📋 Instructions</h2>
        <div class="text-sm text-gray-300 space-y-2">
          <p>✅ Ouvrez la console du navigateur (F12) pour voir les logs détaillés</p>
          <p>✅ Ouvrez plusieurs onglets pour tester la synchronisation Mercure</p>
          <p>✅ Créez des tokens et déplacez-les pour voir les mises à jour en temps réel</p>
          <p>✅ Envoyez des messages pour tester le chat temps réel</p>
        </div>
      </div>
    </div>
  </div>
</template>
