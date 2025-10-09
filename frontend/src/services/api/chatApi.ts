/**
 * Service API pour la gestion du chat (GameMessage)
 */

import { apiClient } from './apiClient';
import type { GameMessage, MessageType } from '@/types/game';

/**
 * DTO pour l'envoi d'un message
 */
export interface SendMessageDTO {
  type: MessageType;
  content: string;
  isInCharacter?: boolean;
  recipientId?: number; // Pour les whispers
}

/**
 * DTO pour un lancer de dés
 */
export interface RollDiceDTO {
  expression: string; // Ex: "2d6+3", "1d20"
  isInCharacter?: boolean;
}

/**
 * Options pour récupérer l'historique
 */
export interface GetMessagesOptions {
  limit?: number;
  before?: string; // ID ou timestamp du message
  after?: string; // ID ou timestamp du message
  types?: MessageType[]; // Filtrer par types de messages
}

/**
 * Service pour gérer les messages de chat d'un jeu
 */
export const chatApi = {
  /**
   * Récupérer les messages d'un jeu
   */
  async list(gameId: number, options?: GetMessagesOptions): Promise<GameMessage[]> {
    const params = new URLSearchParams();

    if (options?.limit) {
      params.append('limit', options.limit.toString());
    }

    if (options?.before) {
      params.append('before', options.before);
    }

    if (options?.after) {
      params.append('after', options.after);
    }

    if (options?.types && options.types.length > 0) {
      params.append('types', options.types.join(','));
    }

    const endpoint = params.toString()
      ? `/games/${gameId}/messages?${params.toString()}`
      : `/games/${gameId}/messages`;

    return apiClient.get<GameMessage[]>(endpoint);
  },

  /**
   * Récupérer les messages récents (X derniers messages)
   */
  async listRecent(gameId: number, limit: number = 50): Promise<GameMessage[]> {
    return this.list(gameId, { limit });
  },

  /**
   * Récupérer les messages depuis un certain timestamp
   */
  async listSince(gameId: number, since: string): Promise<GameMessage[]> {
    return apiClient.get<GameMessage[]>(`/games/${gameId}/messages/since`, {
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ since }),
    });
  },

  /**
   * Envoyer un message de chat
   */
  async send(gameId: number, dto: SendMessageDTO): Promise<GameMessage> {
    return apiClient.post<GameMessage>(`/games/${gameId}/messages`, dto);
  },

  /**
   * Envoyer un message de chat simple (type: chat)
   */
  async sendChat(
    gameId: number,
    content: string,
    isInCharacter: boolean = false
  ): Promise<GameMessage> {
    return this.send(gameId, {
      type: 'chat',
      content,
      isInCharacter,
    });
  },

  /**
   * Envoyer une émote (action du personnage)
   */
  async sendEmote(gameId: number, content: string): Promise<GameMessage> {
    return this.send(gameId, {
      type: 'emote',
      content,
      isInCharacter: true,
    });
  },

  /**
   * Envoyer un chuchotement (message privé)
   */
  async sendWhisper(gameId: number, recipientId: number, content: string): Promise<GameMessage> {
    return this.send(gameId, {
      type: 'whisper',
      content,
      recipientId,
    });
  },

  /**
   * Envoyer un message système (réservé au MJ)
   */
  async sendSystem(gameId: number, content: string): Promise<GameMessage> {
    return this.send(gameId, {
      type: 'system',
      content,
    });
  },

  /**
   * Lancer des dés et envoyer le résultat au chat
   */
  async rollDice(gameId: number, dto: RollDiceDTO): Promise<GameMessage> {
    return apiClient.post<GameMessage>(`/games/${gameId}/dice/roll`, dto);
  },

  /**
   * Supprimer un message (réservé au MJ ou auteur du message)
   */
  async delete(messageId: number): Promise<void> {
    await apiClient.delete<void>(`/messages/${messageId}`);
  },

  /**
   * Récupérer les chuchotements d'un utilisateur
   */
  async listWhispers(gameId: number, userId?: number, limit: number = 50): Promise<GameMessage[]> {
    const params = new URLSearchParams({
      limit: limit.toString(),
    });

    if (userId) {
      params.append('userId', userId.toString());
    }

    return apiClient.get<GameMessage[]>(`/games/${gameId}/messages/whispers?${params.toString()}`);
  },

  /**
   * Marquer les messages comme lus (optionnel, pour les notifications)
   */
  async markAsRead(gameId: number, messageIds: number[]): Promise<void> {
    await apiClient.post<void>(`/games/${gameId}/messages/read`, {
      messageIds,
    });
  },

  /**
   * Récupérer les statistiques du chat
   */
  async getStats(gameId: number): Promise<{
    totalMessages: number;
    messagesByType: Record<MessageType, number>;
    lastMessageAt?: string;
  }> {
    return apiClient.get(`/games/${gameId}/messages/stats`);
  },
};