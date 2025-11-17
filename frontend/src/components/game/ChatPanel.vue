<script setup lang="ts">
import { ref, nextTick, watch, onMounted, computed } from 'vue'
import { useChatStore } from '@/stores/chatStore'
import { useAuthStore } from '@/stores/auth'
import type {
  GameMessage,
  MessageType,
  DiceResult,
  LegacyDiceResult,
  GamePlayer,
} from '@/types/game'

const props = defineProps<{
  messages: GameMessage[]
  gameId: number
  players: GamePlayer[]
}>()

const chatStore = useChatStore()
const authStore = useAuthStore()

// État local
const messageInput = ref('')
const isInCharacter = ref(false)
const chatContainer = ref<HTMLElement | null>(null)
const isAtBottom = ref(true)

// État autocomplétion
const showSuggestions = ref(false)
const selectedSuggestionIndex = ref(0)
const textareaRef = ref<HTMLTextAreaElement | null>(null)

// ============================================
// Messages filtrés (whispers et jets de dés privés)
// ============================================
const visibleMessages = computed(() => {
  const currentUserId = authStore.user?.id
  if (!currentUserId) return props.messages

  return props.messages.filter((msg) => {
    // Si le message a un destinataire (whisper ou dice_roll privé)
    // Seuls l'expéditeur et le destinataire peuvent le voir
    if (msg.recipient) {
      return msg.user.id === currentUserId || msg.recipient.id === currentUserId
    }

    // Sinon, tout le monde peut le voir
    return true
  })
})

// ============================================
// Autocomplétion des pseudos
// ============================================
const filteredPlayers = computed(() => {
  if (!showSuggestions.value) return []

  // Détecter si on est en train de taper /w ou /whisper
  const whisperMatch = messageInput.value.match(/^\/(w|whisper)\s+(\S*)$/)
  if (!whisperMatch) return []

  const searchTerm = whisperMatch[2].toLowerCase()

  // Filtrer les joueurs par pseudo (exclure l'utilisateur actuel)
  return props.players
    .filter((p) => p.user.id !== authStore.user?.id)
    .filter((p) => p.user.pseudo.toLowerCase().includes(searchTerm))
    .slice(0, 5) // Limiter à 5 suggestions
})

// Watcher pour détecter quand afficher l'autocomplétion
watch(messageInput, (newValue) => {
  const whisperMatch = newValue.match(/^\/(w|whisper)\s+(\S*)$/)
  showSuggestions.value = !!whisperMatch
  if (showSuggestions.value) {
    selectedSuggestionIndex.value = 0
  }
})

function selectPlayer(player: GamePlayer) {
  const whisperMatch = messageInput.value.match(/^\/(w|whisper)\s+/)
  if (whisperMatch) {
    messageInput.value = `${whisperMatch[0]}${player.user.pseudo} `
    showSuggestions.value = false
    // Focus sur le textarea
    nextTick(() => {
      textareaRef.value?.focus()
    })
  }
}

// ============================================
// Auto-scroll
// ============================================
watch(
  () => props.messages.length,
  async () => {
    if (isAtBottom.value) {
      await nextTick()
      scrollToBottom()
    }
  }
)

function scrollToBottom() {
  if (chatContainer.value) {
    chatContainer.value.scrollTop = chatContainer.value.scrollHeight
  }
}

function handleScroll() {
  if (chatContainer.value) {
    const { scrollTop, scrollHeight, clientHeight } = chatContainer.value
    isAtBottom.value = scrollHeight - scrollTop - clientHeight < 50
  }
}

onMounted(() => {
  scrollToBottom()
})

// ============================================
// Envoi de messages - Utilise chatStore
// ============================================
async function sendMessage() {
  if (!messageInput.value.trim()) return

  try {
    // Vérifier si c'est une commande de dés (/roll ou /r)
    if (messageInput.value.startsWith('/roll ') || messageInput.value.startsWith('/r ')) {
      const formula = messageInput.value.replace(/^\/(roll|r) /, '').trim()
      await chatStore.rollDice(props.gameId, formula, isInCharacter.value)
      console.log('Dés lancés:', formula)
    }
    // Commande whisper (/whisper ou /w)
    else if (messageInput.value.startsWith('/whisper ') || messageInput.value.startsWith('/w ')) {
      const content = messageInput.value.replace(/^\/(whisper|w) /, '').trim()

      // Parser le nom du destinataire et le message
      const firstSpaceIndex = content.indexOf(' ')
      if (firstSpaceIndex === -1) {
        console.error(
          'Format invalide. Utilisez: /whisper <pseudo> <message> ou /whisper <pseudo> /r <formule>'
        )
        return
      }

      const recipientPseudo = content.substring(0, firstSpaceIndex).trim()
      const message = content.substring(firstSpaceIndex + 1).trim()

      if (!message) {
        console.error('Le message ne peut pas être vide')
        return
      }

      // Chercher le joueur par son pseudo
      const recipient = props.players.find(
        (p) => p.user.pseudo.toLowerCase() === recipientPseudo.toLowerCase()
      )

      if (!recipient) {
        console.error(`Joueur "${recipientPseudo}" introuvable`)
        return
      }

      // Vérifier si c'est un jet de dés privé (/w pseudo /r formule)
      if (message.startsWith('/roll ') || message.startsWith('/r ')) {
        const formula = message.replace(/^\/(roll|r) /, '').trim()
        if (!formula) {
          console.error('La formule de dés ne peut pas être vide')
          return
        }
        await chatStore.rollDice(props.gameId, formula, isInCharacter.value, recipient.user.id)
        console.log('Dés privés lancés à', recipientPseudo, ':', formula)
      }
      // Sinon, c'est un whisper normal
      else {
        await chatStore.sendWhisper(props.gameId, recipient.user.id, message)
        console.log('Whisper envoyé à', recipientPseudo)
      }
    }
    // Commande emote
    else if (messageInput.value.startsWith('/me ')) {
      const content = messageInput.value.replace('/me ', '').trim()
      await chatStore.sendEmote(props.gameId, content)
      console.log('Emote envoyée')
    }
    // Message normal
    else {
      await chatStore.sendMessage(props.gameId, messageInput.value, isInCharacter.value)
      console.log('Message envoyé')
    }

    messageInput.value = ''
  } catch (error) {
    console.error('Erreur envoi message:', error)
  }
}

function handleKeyDown(event: KeyboardEvent) {
  // Gestion de l'autocomplétion
  if (showSuggestions.value && filteredPlayers.value.length > 0) {
    if (event.key === 'ArrowDown') {
      event.preventDefault()
      selectedSuggestionIndex.value = Math.min(
        selectedSuggestionIndex.value + 1,
        filteredPlayers.value.length - 1
      )
      return
    }
    if (event.key === 'ArrowUp') {
      event.preventDefault()
      selectedSuggestionIndex.value = Math.max(selectedSuggestionIndex.value - 1, 0)
      return
    }
    if (event.key === 'Tab' || (event.key === 'Enter' && !event.shiftKey)) {
      event.preventDefault()
      const selectedPlayer = filteredPlayers.value[selectedSuggestionIndex.value]
      if (selectedPlayer) {
        selectPlayer(selectedPlayer)
      }
      return
    }
    if (event.key === 'Escape') {
      event.preventDefault()
      showSuggestions.value = false
      return
    }
  }

  // Envoi de message normal
  if (event.key === 'Enter' && !event.shiftKey) {
    event.preventDefault()
    sendMessage()
  }
}

// ============================================
// Formatage des messages
// ============================================
function getMessageClass(type: MessageType) {
  const classes = {
    system: 'bg-cyan-900/50 border-l-4 border-cyan-500',
    chat: 'bg-secondary-700',
    emote: 'bg-purple-900/50 border-l-4 border-purple-500 italic',
    whisper: 'bg-yellow-900/50 border-l-4 border-yellow-500',
    dice_roll: 'bg-purple-900/50 border-l-4 border-purple-500',
  }
  return classes[type] || 'bg-secondary-700'
}

function formatTime(dateString: string) {
  return new Date(dateString).toLocaleTimeString('fr-FR', {
    hour: '2-digit',
    minute: '2-digit',
  })
}

function getMessageIcon(type: MessageType) {
  const icons = {
    system: '⚙️',
    chat: '💬',
    emote: '✨',
    whisper: '🤫',
    dice_roll: '🎲',
  }
  return icons[type] || '💬'
}

/**
 * Normalise diceResult
 *
 * Gère la compatibilité entre l'ancienne structure (fixtures) et la nouvelle :
 * - Ancienne : { config, results, total, timestamp }
 * - Nouvelle : { formula, results, total, modifier }
 *
 * Cette fonction assure que le composant fonctionne même avec d'anciennes données.
 */
function normalizeDiceResult(result: unknown): DiceResult | null {
  if (!result || typeof result !== 'object') return null

  // Type guard pour la nouvelle structure (avec 'rolls' ou 'results')
  // La nouvelle structure a 'formula' et 'modifier' directement accessibles
  if ('formula' in result && 'modifier' in result) {
    const newResult = result as {
      formula?: string
      rolls?: number[]
      results?: number[]
      total?: number
      modifier?: number
    }

    return {
      formula: newResult.formula || '',
      results: newResult.results || newResult.rolls || [],
      total: newResult.total || 0,
      modifier: newResult.modifier || 0,
    }
  }

  // Type guard pour l'ancienne structure (fixtures avec config.dice)
  if (
    'results' in result &&
    'config' in result &&
    Array.isArray((result as LegacyDiceResult).results)
  ) {
    const oldResult = result as LegacyDiceResult

    console.warn('Ancienne structure diceResult détectée, conversion en cours...')
    return {
      formula: oldResult.config?.dice || 'N/A',
      results: oldResult.results || [],
      total: oldResult.total || 0,
      modifier: 0,
    }
  }

  console.error('Structure diceResult invalide:', result)
  return null
}

// ============================================
// Helpers pour les avatars
// ============================================
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
</script>

<template>
  <div class="flex-1 flex flex-col overflow-hidden">
    <!-- Messages -->
    <div ref="chatContainer" @scroll="handleScroll" class="flex-1 overflow-y-auto p-4 space-y-3">
      <div
        v-for="msg in visibleMessages"
        :key="msg.id"
        :class="['rounded-lg p-3 transition-all hover:shadow-md', getMessageClass(msg.type)]"
      >
        <!-- Header du message -->
        <div class="flex items-center justify-between mb-1">
          <div class="flex items-center gap-2">
            <span class="text-sm">{{ getMessageIcon(msg.type) }}</span>
            <span class="font-semibold text-white text-sm">{{ msg.user.pseudo }}</span>
            <span
              v-if="msg.isInCharacter"
              class="px-2 py-0.5 bg-primary-500/30 text-primary-200 text-xs font-medium rounded"
            >
              IC
            </span>
            <span
              v-if="msg.recipient"
              class="px-2 py-0.5 bg-yellow-500/30 text-yellow-200 text-xs font-medium rounded"
            >
              → {{ msg.recipient.pseudo }}
            </span>
          </div>
          <span class="text-xs text-secondary-400">{{ formatTime(msg.createdAt) }}</span>
        </div>

        <!-- Contenu -->
        <p class="text-secondary-100 text-sm">{{ msg.content }}</p>

        <!-- Résultat de dés - Avec sécurité -->
        <div v-if="normalizeDiceResult(msg.diceResult)" class="mt-2 p-3 bg-black/30 rounded-lg">
          <div class="flex items-center justify-between">
            <div>
              <div class="text-sm text-secondary-400 mb-1">
                🎲 {{ normalizeDiceResult(msg.diceResult)!.formula }}
              </div>
              <div class="text-xs text-secondary-500">
                Lancés: {{ normalizeDiceResult(msg.diceResult)!.results.join(' + ') }}
                <span v-if="normalizeDiceResult(msg.diceResult)!.modifier !== 0">
                  {{ normalizeDiceResult(msg.diceResult)!.modifier > 0 ? '+' : ''
                  }}{{ normalizeDiceResult(msg.diceResult)!.modifier }}
                </span>
              </div>
            </div>
            <div class="text-3xl font-bold text-white">
              {{ normalizeDiceResult(msg.diceResult)!.total }}
            </div>
          </div>
        </div>
      </div>

      <!-- Message si pas de messages -->
      <div v-if="visibleMessages.length === 0" class="text-center py-8 text-secondary-400">
        <p class="text-lg mb-2">💬</p>
        <p>Aucun message pour le moment</p>
        <p class="text-sm mt-1">Soyez le premier à parler !</p>
      </div>
    </div>

    <!-- Bouton pour scroller en bas -->
    <Transition name="fade">
      <button
        v-if="!isAtBottom"
        @click="scrollToBottom"
        class="absolute bottom-24 right-8 px-3 py-2 bg-primary-500 text-white rounded-full shadow-lg hover:bg-primary-600 transition-colors"
        title="Aller en bas"
      >
        ↓
      </button>
    </Transition>

    <!-- Input -->
    <div class="border-t border-secondary-700 p-4 bg-secondary-800">
      <!-- Boutons mode -->
      <div class="flex items-center gap-2 mb-2">
        <button
          @click="isInCharacter = !isInCharacter"
          :class="[
            'px-3 py-1 text-xs rounded font-medium transition-colors',
            isInCharacter
              ? 'bg-primary-500 text-white shadow-purple'
              : 'bg-secondary-700 text-secondary-300 hover:bg-secondary-600',
          ]"
        >
          {{ isInCharacter ? '🎭 In Character' : '🗣️ Out of Character' }}
        </button>

        <div class="text-xs text-secondary-400">
          Commandes: /roll (/r) 1d20 • /whisper (/w) pseudo message • /w pseudo /r formule • /me
          action
        </div>
      </div>

      <!-- Input de message -->
      <div class="relative">
        <!-- Suggestions d'autocomplétion -->
        <Transition name="slide-up">
          <div
            v-if="showSuggestions && filteredPlayers.length > 0"
            class="absolute bottom-full left-0 right-0 mb-2 bg-secondary-700 border border-secondary-600 rounded-lg shadow-xl overflow-hidden max-h-48"
          >
            <div class="px-3 py-2 bg-secondary-800 border-b border-secondary-600">
              <span class="text-xs text-secondary-400">
                Suggestions (↑↓ pour naviguer, Tab/Enter pour sélectionner)
              </span>
            </div>
            <div class="overflow-y-auto max-h-40">
              <button
                v-for="(player, index) in filteredPlayers"
                :key="player.id"
                @click="selectPlayer(player)"
                :class="[
                  'w-full text-left px-3 py-2 hover:bg-secondary-600 transition-colors flex items-center gap-2',
                  index === selectedSuggestionIndex
                    ? 'bg-primary-500/20 border-l-2 border-primary-500'
                    : '',
                ]"
              >
                <div
                  :class="[
                    'w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold',
                    getAvatarColor(player.user.id),
                  ]"
                >
                  {{ player.user.pseudo.slice(0, 2).toUpperCase() }}
                </div>
                <span class="text-secondary-100">{{ player.user.pseudo }}</span>
              </button>
            </div>
          </div>
        </Transition>

        <textarea
          ref="textareaRef"
          v-model="messageInput"
          @keydown="handleKeyDown"
          rows="2"
          placeholder="Enter text... (Shift+Enter pour nouvelle ligne)"
          class="form-input resize-none pr-12"
          :disabled="chatStore.isSending"
        />

        <button
          @click="sendMessage"
          :disabled="!messageInput.trim() || chatStore.isSending"
          class="absolute right-2 bottom-2 p-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          title="Envoyer (Enter)"
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="w-5 h-5"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
          >
            <line x1="22" y1="2" x2="11" y2="13"></line>
            <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
          </svg>
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.shadow-purple {
  box-shadow: 0 4px 14px 0 rgba(99, 102, 241, 0.39);
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.slide-up-enter-active,
.slide-up-leave-active {
  transition: all 0.2s ease;
}

.slide-up-enter-from,
.slide-up-leave-to {
  opacity: 0;
  transform: translateY(10px);
}
</style>
