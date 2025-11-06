/**
 * API pour gérer la présence en temps réel des joueurs
 */

import { post, get } from './apiClient'

/**
 * Interface pour la réponse de présence
 */
export interface PresenceResponse {
  success: boolean
  onlineUsers?: number[]
  onlineCount?: number
}

/**
 * API de gestion de la présence
 */
export const presenceApi = {
  /**
   * Notifier que l'utilisateur a rejoint la partie
   */
  async join(gameId: number): Promise<PresenceResponse> {
    return post<PresenceResponse>(`/games/${gameId}/presence/join`)
  },

  /**
   * Notifier que l'utilisateur a quitté la partie
   */
  async leave(gameId: number): Promise<PresenceResponse> {
    return post<PresenceResponse>(`/games/${gameId}/presence/leave`)
  },

  /**
   * Envoyer un heartbeat pour signaler la présence active
   */
  async heartbeat(gameId: number): Promise<PresenceResponse> {
    return post<PresenceResponse>(`/games/${gameId}/presence/heartbeat`)
  },

  /**
   * Récupérer la liste des utilisateurs en ligne
   */
  async getOnlineUsers(gameId: number): Promise<PresenceResponse> {
    return get<PresenceResponse>(`/games/${gameId}/presence/online`)
  },
}
