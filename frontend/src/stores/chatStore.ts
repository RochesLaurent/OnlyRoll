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
   */
  const sortedMessages = computed(() => {
    return [...messages.value].sort((a, b) => {
      return new Date(a.createdAt).getTime() - new Date(b.createdAt).getTime()
    })
  })

  /**
   * Filtrer les messages par type
   */
  const messagesByType = computed(() => {
    return (type: MessageType) => messages.value.filter((msg) => msg.type === type)
  })

  /**
   * Messages de chat uniquement (pas système, pas dés)
   */
  const chatMessages = computed(() => {
    return messages.value.filter(
      (msg) => msg.type === 'chat' || msg.type === 'emote' || msg.type === 'whisper',
    )
  })

  /**
   * Messages système uniquement
   */
  const systemMessages = computed(() => {
    return messages.value.filter((msg) => msg.type === 'system')
  })

  /**
   * Lancers de dés uniquement
   */
  const diceRolls = computed(() => {
    return messages.value.filter((msg) => msg.type === 'dice_roll')
  })

  /**
   * Nombre total de messages
   */
  const messagesCount = computed(() => messages.value.length)

  /**
   * Dernier message
   */
  const lastMessage = computed(() => {
    if (messages.value.length === 0) return null
    return sortedMessages.value[sortedMessages.value.length - 1]
  })

  // ===========================
  // Actions - Chargement
  // ===========================

  /**
   * Charger les messages récents d'un jeu
   */
  async function loadRecentMessages(gameId: number, limit: number = 50) {
    isLoading.value = true
    error.value = null

    try {
      const loadedMessages = await chatApi.listRecent(gameId, limit)
      messages.value = loadedMessages

      // Mettre à jour l'ID du plus ancien message pour la pagination
      if (loadedMessages.length > 0) {
        oldestMessageId.value = loadedMessages[0].id
      }

      // Si on a moins de messages que demandé, il n'y en a plus
      hasMore.value = loadedMessages.length === limit
    } catch (e: unknown) {
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

      // Ajouter les messages plus anciens au début
      messages.value.unshift(...olderMessages)

      // Mettre à jour l'ID du plus ancien message
      if (olderMessages.length > 0) {
        oldestMessageId.value = olderMessages[0].id
      }

      // Si on a moins de messages que demandé, il n'y en a plus
      hasMore.value = olderMessages.length === limit
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

      // Ajouter uniquement les messages qui n'existent pas déjà
      newMessages.forEach((msg) => {
        const exists = messages.value.some((m) => m.id === msg.id)
        if (!exists) {
          messages.value.push(msg)
        }
      })
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

      // Ajout optimistic
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

      // Ajout optimistic
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

      // Ajout optimistic
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

      // Ajout optimistic
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

    // Le backend envoie { messageId, userId, userName, content, type, isIC, recipientId, recipientName, createdAt }
    // On doit reconstruire un objet GameMessage complet

    if (data.messageId) {
      const message: GameMessage = {
        id: data.messageId,
        type: data.type,
        content: data.content,
        isInCharacter: data.isIC ?? false,
        createdAt: data.createdAt,
        diceResult: data.diceResult
          ? {
              config: { dice: data.diceResult.formula || '' },
              results: data.diceResult.rolls || [],
              total: data.diceResult.total,
              timestamp: data.createdAt,
            }
          : undefined,
        diceTotal: data.diceResult?.total || undefined,
        formattedContent: data.content,
        user: {
          id: data.userId,
          pseudo: data.userName,
          email: '', // Non fourni par Mercure, pas grave
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

    // Convertir les données de dés en message si nécessaire
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
   * Réinitialiser le store
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
