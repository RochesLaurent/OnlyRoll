/**
 * Service API pour la gestion du chat (GameMessage)
 *
 * Ce service gère toutes les opérations sur les messages de chat d'un jeu.
 * Tous les types sont importés depuis @/types/game pour garantir la cohérence.
 */

import { get, post, delete as del } from './apiClient'
import {
  type GameMessage,
  MessageType,
  type SendMessageDTO,
  type RollDiceDTO,
  type ChatStats,
} from '@/types/game'

/**
 * Options pour récupérer l'historique des messages
 * Permet de filtrer et paginer les résultats
 */
export interface GetMessagesOptions {
  limit?: number // Nombre maximum de messages à récupérer
  before?: string // ID ou timestamp du message (récupère les messages avant celui-ci)
  after?: string // ID ou timestamp du message (récupère les messages après celui-ci)
  types?: MessageType[] // Filtrer par types de messages spécifiques
}

/**
 * Service pour gérer les messages de chat d'un jeu
 * Inclut la gestion des messages normaux, emotes, whispers, système et lancers de dés
 */
export const chatApi = {
  /**
   * Récupérer les messages d'un jeu avec options de filtrage
   * @param gameId - ID du jeu
   * @param options - Options de filtrage et pagination
   * @returns Liste des messages correspondant aux critères
   */
  async list(gameId: number, options?: GetMessagesOptions): Promise<GameMessage[]> {
    const params = new URLSearchParams()

    if (options?.limit) {
      params.append('limit', options.limit.toString())
    }

    if (options?.before) {
      params.append('before', options.before)
    }

    if (options?.after) {
      params.append('after', options.after)
    }

    if (options?.types && options.types.length > 0) {
      params.append('types', options.types.join(','))
    }

    const endpoint = params.toString()
      ? `/games/${gameId}/chat/messages?${params.toString()}`
      : `/games/${gameId}/chat/messages`

    return get<GameMessage[]>(endpoint)
  },

  /**
   * Récupérer les messages récents d'un jeu
   * Raccourci pour récupérer les X derniers messages
   * @param gameId - ID du jeu
   * @param limit - Nombre de messages à récupérer (par défaut 50)
   * @returns Les messages les plus récents
   */
  async listRecent(gameId: number, limit: number = 50): Promise<GameMessage[]> {
    return get<GameMessage[]>(`/games/${gameId}/chat/messages?limit=${limit}`)
  },

  /**
   * Récupérer les messages depuis un certain timestamp
   * Utile pour la synchronisation et récupérer uniquement les nouveaux messages
   * @param gameId - ID du jeu
   * @param since - Timestamp ISO 8601 (récupère les messages après cette date)
   * @returns Liste des nouveaux messages depuis le timestamp donné
   */
  async listSince(gameId: number, since: string): Promise<GameMessage[]> {
    return get<GameMessage[]>(
      `/games/${gameId}/chat/messages/since?since=${encodeURIComponent(since)}`
    )
  },

  /**
   * Envoyer un message dans le chat
   * Méthode générique qui supporte tous les types de messages
   * @param gameId - ID du jeu
   * @param dto - Données du message (SendMessageDTO)
   * @returns Le message créé avec son ID et timestamp
   */
  async send(gameId: number, dto: SendMessageDTO): Promise<GameMessage> {
    return post<GameMessage>(`/games/${gameId}/chat/messages`, dto)
  },

  /**
   * Envoyer un message de chat simple (type: chat)
   * Raccourci pour envoyer un message texte normal
   * @param gameId - ID du jeu
   * @param content - Contenu textuel du message
   * @param isInCharacter - True si le message est prononcé par le personnage (mode RP)
   * @returns Le message créé
   */
  async sendChat(
    gameId: number,
    content: string,
    isInCharacter: boolean = false
  ): Promise<GameMessage> {
    return this.send(gameId, {
      type: MessageType.CHAT,
      content,
      isInCharacter,
    })
  },

  /**
   * Envoyer une émote (action du personnage)
   * Les emotes décrivent des actions: *sourit*, *regarde autour*, etc.
   * @param gameId - ID du jeu
   * @param content - Description de l'action
   * @returns Le message emote créé
   */
  async sendEmote(gameId: number, content: string): Promise<GameMessage> {
    return this.send(gameId, {
      type: MessageType.EMOTE,
      content,
      isInCharacter: true, // Les emotes sont toujours "in character"
    })
  },

  /**
   * Envoyer un chuchotement (message privé à un joueur)
   * Le message n'est visible que par l'émetteur, le destinataire et le MJ
   * @param gameId - ID du jeu
   * @param recipientId - ID de l'utilisateur destinataire
   * @param content - Contenu du message privé
   * @returns Le message whisper créé
   */
  async sendWhisper(gameId: number, recipientId: number, content: string): Promise<GameMessage> {
    return this.send(gameId, {
      type: MessageType.WHISPER,
      content,
      recipientId,
    })
  },

  /**
   * Envoyer un message système (réservé au MJ)
   * Messages automatiques ou administratifs visibles par tous
   * @param gameId - ID du jeu
   * @param content - Contenu du message système
   * @returns Le message système créé
   */
  async sendSystem(gameId: number, content: string): Promise<GameMessage> {
    return this.send(gameId, {
      type: MessageType.SYSTEM,
      content,
    })
  },

  /**
   * Lancer des dés et envoyer le résultat au chat
   * Utilise l'endpoint dédié qui calcule le résultat des dés côté serveur
   * @param gameId - ID du jeu
   * @param dto - Données du lancer (formule, raison, visibilité)
   * @returns Le message avec le résultat du lancer de dés intégré
   */
  async rollDice(gameId: number, dto: RollDiceDTO): Promise<GameMessage> {
    return post<GameMessage>(`/games/${gameId}/chat/roll-dice`, {
      formula: dto.formula,
      reason: dto.reason,
      isInCharacter: dto.isInCharacter,
      isVisible: dto.isVisible,
    })
  },

  /**
   * Supprimer un message (réservé au MJ ou auteur du message)
   * ATTENTION: Cette action est irréversible
   * @param messageId - ID du message à supprimer
   */
  async delete(messageId: number): Promise<void> {
    await del<void>(`/messages/${messageId}`)
  },

  /**
   * Récupérer les chuchotements (whispers) d'un utilisateur
   * Permet de filtrer uniquement les messages privés
   * @param gameId - ID du jeu
   * @param userId - ID de l'utilisateur (optionnel, filtre par utilisateur si fourni)
   * @param limit - Nombre maximum de messages (par défaut 50)
   * @returns Liste des messages de type whisper
   */
  async listWhispers(gameId: number, userId?: number, limit: number = 50): Promise<GameMessage[]> {
    const params = new URLSearchParams({
      limit: limit.toString(),
    })

    if (userId) {
      params.append('userId', userId.toString())
    }

    return get<GameMessage[]>(`/games/${gameId}/chat/messages/type/whisper?${params.toString()}`)
  },

  /**
   * Marquer des messages comme lus
   * Optionnel, utilisé pour le système de notifications
   * @param gameId - ID du jeu
   * @param messageIds - Liste des IDs de messages à marquer comme lus
   */
  async markAsRead(gameId: number, messageIds: number[]): Promise<void> {
    await post<void>(`/games/${gameId}/chat/messages/read`, {
      messageIds,
    })
  },

  /**
   * Récupérer les statistiques du chat d'un jeu
   * Nombre total de messages, répartition par type, etc.
   * @param gameId - ID du jeu
   * @returns Statistiques détaillées du chat
   */
  async getStats(gameId: number): Promise<ChatStats> {
    return get<ChatStats>(`/games/${gameId}/chat/stats`)
  },
}
