/**
 * Store Pinia pour la gestion du chat
 * Gère les messages, l'historique et la synchronisation temps réel via Mercure
 */

import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { chatApi } from '@/services/api'
import type { GameMessage, MessageType } from '@/types/game'
import type {
  MercureChatMessageData,
  MercureDiceRollData,
  MercureMessageDeletedData,
} from '@/types/websocket'

export const useChatStore = defineStore('chat', () => {
  // ===========================
  // État
  // ===========================

  const messages = ref<GameMessage[]>([])
  const isLoading = ref(false)
  const isSending = ref(false)
  const error = ref<string | null>(null)

  // Pagination
  const hasMore = ref(true)
  const oldestMessageId = ref<number | null>(null)

  // ===========================
  // Getters (computed)
  // ===========================

  /**
   * Messages triés par date (plus récents en bas)
   * Protection contre messages undefined/null
   */
  const sortedMessages = computed(() => {
    // Protection supplémentaire pour garantir que messages.value est un tableau
    if (!Array.isArray(messages.value)) {
      console.error('messages.value is not an array:', messages.value)
      messages.value = []
      return []
    }

    return [...messages.value].sort((a, b) => {
      return new Date(a.createdAt).getTime() - new Date(b.createdAt).getTime()
    })
  })

  /**
   * Filtrer les messages par type
   */
  const messagesByType = computed(() => {
    return (type: MessageType) => {
      if (!Array.isArray(messages.value)) return []
      return messages.value.filter((msg) => msg.type === type)
    }
  })

  /**
   * Messages de chat uniquement (pas système, pas dés)
   */
  const chatMessages = computed(() => {
    if (!Array.isArray(messages.value)) return []
    return messages.value.filter(
      (msg) => msg.type === 'chat' || msg.type === 'emote' || msg.type === 'whisper'
    )
  })

  /**
   * Messages système uniquement
   */
  const systemMessages = computed(() => {
    if (!Array.isArray(messages.value)) return []
    return messages.value.filter((msg) => msg.type === 'system')
  })

  /**
   * Lancers de dés uniquement
   */
  const diceRolls = computed(() => {
    if (!Array.isArray(messages.value)) return []
    return messages.value.filter((msg) => msg.type === 'dice_roll')
  })

  /**
   * Nombre total de messages
   */
  const messagesCount = computed(() => {
    if (!Array.isArray(messages.value)) return 0
    return messages.value.length
  })

  /**
   * Dernier message
   */
  const lastMessage = computed(() => {
    if (!Array.isArray(messages.value) || messages.value.length === 0) return null
    const sorted = sortedMessages.value
    return sorted[sorted.length - 1]
  })

  // ===========================
  // Actions - Chargement
  // ===========================

  /**
   * Charger les messages récents d'un jeu
   * Garantit que messages est toujours un tableau
   */
  async function loadRecentMessages(gameId: number, limit: number = 50) {
    isLoading.value = true
    error.value = null

    try {
      const loadedMessages = await chatApi.listRecent(gameId, limit)

      messages.value = Array.isArray(loadedMessages) ? loadedMessages : []

      // Mettre à jour l'ID du plus ancien message pour la pagination
      if (messages.value.length > 0) {
        oldestMessageId.value = messages.value[0].id
      }

      // Si on a moins de messages que demandé, il n'y en a plus
      hasMore.value = messages.value.length === limit

      console.log('Messages chargés:', messages.value.length)
    } catch (e: unknown) {
      messages.value = []

      if (e && typeof e === 'object' && 'message' in e) {
        error.value = (e as { message: string }).message || 'Erreur lors du chargement des messages'
      } else {
        error.value = 'Erreur lors du chargement des messages'
      }
      console.error('Erreur loadRecentMessages:', e)
      throw e
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Charger plus de messages (pagination)
   */
  async function loadMoreMessages(gameId: number, limit: number = 20) {
    if (!hasMore.value || isLoading.value) return

    isLoading.value = true
    error.value = null

    try {
      const olderMessages = await chatApi.list(gameId, {
        limit,
        before: oldestMessageId.value?.toString(),
      })

      if (Array.isArray(olderMessages) && olderMessages.length > 0) {
        messages.value.unshift(...olderMessages)

        oldestMessageId.value = olderMessages[0].id

        hasMore.value = olderMessages.length === limit
      } else {
        hasMore.value = false
      }
    } catch (e: unknown) {
      if (e && typeof e === 'object' && 'message' in e) {
        error.value = (e as { message: string }).message || 'Erreur lors du chargement des messages'
      } else {
        error.value = 'Erreur lors du chargement des messages'
      }
      console.error('Erreur loadMoreMessages:', e)
      throw e
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Charger les nouveaux messages depuis un timestamp (après reconnexion)
   */
  async function loadMessagesSince(gameId: number, since: string) {
    try {
      const newMessages = await chatApi.listSince(gameId, since)

      if (Array.isArray(newMessages)) {
        newMessages.forEach((msg) => {
          const exists = messages.value.some((m) => m.id === msg.id)
          if (!exists) {
            messages.value.push(msg)
          }
        })
      }
    } catch (e: unknown) {
      if (e && typeof e === 'object' && 'message' in e) {
        error.value =
          (e as { message: string }).message || 'Erreur lors du chargement des nouveaux messages'
      } else {
        error.value = 'Erreur lors du chargement des nouveaux messages'
      }
      console.error('Erreur loadMessagesSince:', e)
      throw e
    }
  }

  // ===========================
  // Actions - Envoi de messages
  // ===========================

  /**
   * Envoyer un message de chat
   */
  async function sendMessage(gameId: number, content: string, isInCharacter: boolean = false) {
    isSending.value = true
    error.value = null

    try {
      const message = await chatApi.sendChat(gameId, content, isInCharacter)
      addMessageToList(message)
      return message
    } catch (e: unknown) {
      if (e && typeof e === 'object' && 'message' in e) {
        error.value = (e as { message: string }).message || "Erreur lors de l'envoi du message"
      } else {
        error.value = "Erreur lors de l'envoi du message"
      }
      console.error('Erreur sendMessage:', e)
      throw e
    } finally {
      isSending.value = false
    }
  }

  /**
   * Envoyer une émote
   */
  async function sendEmote(gameId: number, content: string) {
    isSending.value = true
    error.value = null

    try {
      const message = await chatApi.sendEmote(gameId, content)
      addMessageToList(message)
      return message
    } catch (e: unknown) {
      if (e && typeof e === 'object' && 'message' in e) {
        error.value = (e as { message: string }).message || "Erreur lors de l'envoi de l'émote"
      } else {
        error.value = "Erreur lors de l'envoi de l'émote"
      }
      console.error('Erreur sendEmote:', e)
      throw e
    } finally {
      isSending.value = false
    }
  }

  /**
   * Envoyer un chuchotement
   */
  async function sendWhisper(gameId: number, recipientId: number, content: string) {
    isSending.value = true
    error.value = null

    try {
      const message = await chatApi.sendWhisper(gameId, recipientId, content)
      addMessageToList(message)
      return message
    } catch (e: unknown) {
      if (e && typeof e === 'object' && 'message' in e) {
        error.value = (e as { message: string }).message || "Erreur lors de l'envoi du chuchotement"
      } else {
        error.value = "Erreur lors de l'envoi du chuchotement"
      }
      console.error('Erreur sendWhisper:', e)
      throw e
    } finally {
      isSending.value = false
    }
  }

  /**
   * Envoyer un message système (MJ uniquement)
   */
  async function sendSystemMessage(gameId: number, content: string) {
    isSending.value = true
    error.value = null

    try {
      const message = await chatApi.sendSystem(gameId, content)
      addMessageToList(message)
      return message
    } catch (e: unknown) {
      if (e && typeof e === 'object' && 'message' in e) {
        error.value =
          (e as { message: string }).message || "Erreur lors de l'envoi du message système"
      } else {
        error.value = "Erreur lors de l'envoi du message système"
      }
      console.error('Erreur sendSystemMessage:', e)
      throw e
    } finally {
      isSending.value = false
    }
  }

  /**
   * Lancer des dés
   */
  async function rollDice(gameId: number, formula: string, isInCharacter: boolean = true) {
    isSending.value = true
    error.value = null

    try {
      const message = await chatApi.rollDice(gameId, {
        formula,
        isInCharacter,
      })
      addMessageToList(message)
      return message
    } catch (e: unknown) {
      if (e && typeof e === 'object' && 'message' in e) {
        error.value = (e as { message: string }).message || 'Erreur lors du lancer de dés'
      } else {
        error.value = 'Erreur lors du lancer de dés'
      }
      console.error('Erreur rollDice:', e)
      throw e
    } finally {
      isSending.value = false
    }
  }

  /**
   * Supprimer un message
   */
  async function deleteMessage(messageId: number) {
    error.value = null

    try {
      await chatApi.delete(messageId)
      removeMessageFromList(messageId)
    } catch (e: unknown) {
      if (e && typeof e === 'object' && 'message' in e) {
        error.value =
          (e as { message: string }).message || 'Erreur lors de la suppression du message'
      } else {
        error.value = 'Erreur lors de la suppression du message'
      }
      console.error('Erreur deleteMessage:', e)
      throw e
    }
  }

  // ===========================
  // Actions - Synchronisation Mercure
  // ===========================

  /**
   * Gérer un message reçu via Mercure
   */
  function handleChatMessage(data: MercureChatMessageData) {
    console.log('Message reçu via Mercure:', data)

    if (data.messageId) {
      const message: GameMessage = {
        id: data.messageId,
        type: data.type,
        content: data.content,
        isInCharacter: data.isIC ?? false,
        createdAt: data.createdAt,
        diceResult: data.diceResult
          ? {
              formula: data.diceResult.formula ?? '',
              results: data.diceResult.rolls ?? [],
              total: data.diceResult.total ?? 0,
              modifier: data.diceResult.modifier ?? 0,
            }
          : undefined,
        diceTotal: data.diceResult?.total || undefined,
        formattedContent: data.content,
        user: {
          id: data.userId,
          pseudo: data.userName,
          email: '',
        },
        recipient: data.recipientId
          ? {
              id: data.recipientId,
              pseudo: data.recipientName || 'Inconnu',
              email: '',
            }
          : undefined,
      }

      addMessageToList(message)
    }
  }

  /**
   * Gérer un lancer de dés reçu via Mercure
   */
  function handleDiceRoll(data: MercureDiceRollData) {
    console.log('Dés reçus via Mercure:', data)

    if (data.message) {
      addMessageToList(data.message)
    }
  }

  /**
   * Gérer la suppression d'un message via Mercure
   */
  function handleMessageDeleted(data: MercureMessageDeletedData) {
    console.log('Message supprimé via Mercure:', data)

    if (data.messageId) {
      removeMessageFromList(data.messageId)
    }
  }

  // ===========================
  // Helpers privés
  // ===========================

  /**
   * Ajouter un message à la liste (évite les doublons)
   */
  function addMessageToList(message: GameMessage) {
    if (!Array.isArray(messages.value)) {
      messages.value = []
    }

    const exists = messages.value.some((m) => m.id === message.id)
    if (!exists) {
      messages.value.push(message)
      console.log('Message ajouté à la liste:', message)
    } else {
      console.log('Message déjà présent dans la liste (ignoré):', message.id)
    }
  }

  /**
   * Retirer un message de la liste
   */
  function removeMessageFromList(messageId: number) {
    if (!Array.isArray(messages.value)) return

    const index = messages.value.findIndex((m) => m.id === messageId)
    if (index !== -1) {
      messages.value.splice(index, 1)
    }
  }

  /**
   * Effacer tous les messages
   */
  function clearMessages() {
    messages.value = []
    oldestMessageId.value = null
    hasMore.value = true
  }

  /**
   * Réinitialiser le store avec garantie de tableau vide
   */
  function $reset() {
    messages.value = []
    isLoading.value = false
    isSending.value = false
    error.value = null
    hasMore.value = true
    oldestMessageId.value = null
  }

  // ===========================
  // Return (API publique du store)
  // ===========================

  return {
    // État
    messages,
    isLoading,
    isSending,
    error,
    hasMore,

    // Getters
    sortedMessages,
    messagesByType,
    chatMessages,
    systemMessages,
    diceRolls,
    messagesCount,
    lastMessage,

    // Actions - Chargement
    loadRecentMessages,
    loadMoreMessages,
    loadMessagesSince,

    // Actions - Envoi
    sendMessage,
    sendEmote,
    sendWhisper,
    sendSystemMessage,
    rollDice,
    deleteMessage,

    // Actions - Mercure
    handleChatMessage,
    handleDiceRoll,
    handleMessageDeleted,

    // Utils
    clearMessages,
    $reset,
  }
})
