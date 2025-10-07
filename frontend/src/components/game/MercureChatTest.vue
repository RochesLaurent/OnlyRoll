<script setup lang="ts">
console.log('🎯 MercureChatTest chargé !')
import { ref, computed } from 'vue'
import { useMercure } from '@/composables/useMercure'
import { useAuthStore } from '@/stores/auth'

const props = defineProps<{
  gameId: number
}>()

const authStore = useAuthStore()
const messages = ref<any[]>([])
const newMessage = ref('')
const isLoading = ref(false)
const error = ref<string | null>(null)

// Connexion Mercure et écoute des messages
const { isConnected, connectionState, onChatMessage, onDiceRoll } = useMercure(
  props.gameId,
  authStore.token,
)

// Écouter les nouveaux messages de chat
onChatMessage((message) => {
  console.log('📨 Nouveau message reçu:', message)
  messages.value.push({
    ...message,
    isOwn: message.userId === authStore.user?.id,
  })

  // Auto-scroll vers le bas
  setTimeout(scrollToBottom, 100)
})

// Écouter les lancers de dés
onDiceRoll((diceData) => {
  console.log('🎲 Lancer de dés reçu:', diceData)
  messages.value.push({
    messageId: diceData.messageId,
    userId: diceData.userId,
    userName: diceData.userName,
    content: `🎲 ${diceData.expression} = ${diceData.results.total}`,
    type: 'dice_roll',
    createdAt: diceData.createdAt,
    isOwn: diceData.userId === authStore.user?.id,
  })

  setTimeout(scrollToBottom, 100)
})

// Status de connexion avec indicateur visuel
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

const connectionStatusText = computed(() => {
  switch (connectionState.value) {
    case 'open':
      return 'Connecté'
    case 'connecting':
      return 'Connexion...'
    case 'closed':
      return 'Déconnecté'
    default:
      return 'Inconnu'
  }
})

// Envoyer un message via l'API REST
const sendMessage = async () => {
  if (!newMessage.value.trim() || isLoading.value) return

  error.value = null
  isLoading.value = true

  try {
    const response = await fetch(`http://localhost:8000/api/games/${props.gameId}/chat/messages`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Authorization: `Bearer ${authStore.token}`,
      },
      body: JSON.stringify({
        content: newMessage.value,
        type: 'chat',
        isIC: false,
      }),
    })

    if (!response.ok) {
      const errorData = await response.json()
      throw new Error(errorData.message || "Erreur lors de l'envoi du message")
    }

    // Nettoyer l'input (le message apparaîtra via Mercure)
    newMessage.value = ''
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Erreur inconnue'
    console.error('❌ Erreur envoi message:', err)
  } finally {
    isLoading.value = false
  }
}

// Scroll automatique vers le bas
const messagesContainer = ref<HTMLDivElement>()
const scrollToBottom = () => {
  if (messagesContainer.value) {
    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
  }
}

// Formater la date
const formatTime = (dateString: string) => {
  const date = new Date(dateString)
  return date.toLocaleTimeString('fr-FR', {
    hour: '2-digit',
    minute: '2-digit',
  })
}
</script>

<template>
  <div class="flex flex-col h-full bg-gray-900 rounded-lg shadow-xl">
    <!-- Header avec status de connexion -->
    <div class="p-4 border-b border-gray-700 flex items-center justify-between">
      <h3 class="text-xl font-bold text-white">Chat Test Mercure</h3>

      <div class="flex items-center gap-2">
        <div
          :class="connectionStatusClass"
          class="w-3 h-3 rounded-full"
          :title="connectionStatusText"
        />
        <span class="text-sm text-gray-400">
          {{ connectionStatusText }}
        </span>
      </div>
    </div>

    <!-- Liste des messages -->
    <div ref="messagesContainer" class="flex-1 overflow-y-auto p-4 space-y-3">
      <div
        v-for="msg in messages"
        :key="msg.messageId"
        :class="[
          'p-3 rounded-lg max-w-[80%]',
          msg.isOwn ? 'ml-auto bg-blue-600 text-white' : 'mr-auto bg-gray-800 text-gray-100',
        ]"
      >
        <!-- Auteur et heure -->
        <div class="flex items-center justify-between mb-1 text-xs opacity-75">
          <span class="font-semibold">{{ msg.userName }}</span>
          <span>{{ formatTime(msg.createdAt) }}</span>
        </div>

        <!-- Contenu -->
        <div class="text-sm">
          {{ msg.content }}
        </div>

        <!-- Badge pour les types spéciaux -->
        <div v-if="msg.type !== 'chat'" class="mt-1">
          <span class="text-xs px-2 py-0.5 bg-purple-500/30 rounded">
            {{ msg.type }}
          </span>
        </div>
      </div>

      <!-- Message si aucun message -->
      <div v-if="messages.length === 0" class="text-center text-gray-500 py-8">
        Aucun message. Envoyez-en un pour tester !
      </div>
    </div>

    <!-- Message d'erreur -->
    <div
      v-if="error"
      class="px-4 py-2 bg-red-500/20 border-t border-red-500/50 text-red-400 text-sm"
    >
      ⚠️ {{ error }}
    </div>

    <!-- Input pour envoyer un message -->
    <div class="p-4 border-t border-gray-700">
      <form @submit.prevent="sendMessage" class="flex gap-2">
        <input
          v-model="newMessage"
          type="text"
          placeholder="Votre message..."
          :disabled="isLoading || !isConnected"
          class="flex-1 px-4 py-2 bg-gray-800 text-white rounded-lg border border-gray-700 focus:outline-none focus:border-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
          @keyup.enter="sendMessage"
        />
        <button
          type="submit"
          :disabled="isLoading || !newMessage.trim() || !isConnected"
          class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
        >
          {{ isLoading ? '...' : 'Envoyer' }}
        </button>
      </form>

      <p class="text-xs text-gray-500 mt-2">
        💡 Ouvrez plusieurs onglets pour voir la synchronisation en temps réel
      </p>
    </div>
  </div>
</template>
