<script setup lang="ts">
import { ref, nextTick, watch, onMounted } from 'vue'
import { useChatStore } from '@/stores/chatStore'
import type { GameMessage, MessageType, DiceResult, LegacyDiceResult } from '@/types/game'

const props = defineProps<{
  messages: GameMessage[]
  gameId: number
}>()

const chatStore = useChatStore()

// État local
const messageInput = ref('')
const isInCharacter = ref(false)
const chatContainer = ref<HTMLElement | null>(null)
const isAtBottom = ref(true)

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
    // Vérifier si c'est une commande de dés
    if (messageInput.value.startsWith('/roll ')) {
      const formula = messageInput.value.replace('/roll ', '').trim()
      await chatStore.rollDice(props.gameId, formula, isInCharacter.value)
      console.log('Dés lancés:', formula)
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

  // Type guard pour la nouvelle structure
  if (
    'rolls' in result &&
    'formula' in result &&
    Array.isArray((result as { rolls: unknown }).rolls)
  ) {
    const newResult = result as {
      formula?: string
      rolls?: number[]
      total?: number
      modifier?: number
    }

    return {
      formula: newResult.formula || '',
      results: newResult.rolls || [],
      total: newResult.total || 0,
      modifier: newResult.modifier || 0,
    }
  }

  // Type guard pour l'ancienne structure
  if ('results' in result && Array.isArray((result as LegacyDiceResult).results)) {
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
</script>

<template>
  <div class="flex-1 flex flex-col overflow-hidden">
    <!-- Messages -->
    <div ref="chatContainer" @scroll="handleScroll" class="flex-1 overflow-y-auto p-4 space-y-3">
      <div
        v-for="msg in messages"
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
      <div v-if="messages.length === 0" class="text-center py-8 text-secondary-400">
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

        <div class="text-xs text-secondary-400">Commandes: /roll 1d20 • /me action</div>
      </div>

      <!-- Input de message -->
      <div class="relative">
        <textarea
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
</style>
