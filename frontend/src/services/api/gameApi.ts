import { apiClient } from './apiClient'
import type { Game, CreateGameDTO, UpdateGameDTO, JoinGameDTO } from '@/types/game'

/**
 * Service de gestion des parties de jeu
 * CRUD complet + actions spécifiques (rejoindre, quitter)
 */
export const gameApi = {
  /**
   * Liste toutes les parties publiques
   * @param search - Terme de recherche optionnel
   */
  async listPublic(search?: string): Promise<Game[]> {
    const endpoint = search ? `/games?search=${encodeURIComponent(search)}` : '/games'
    return apiClient.get<Game[]>(endpoint)
  },

  /**
   * Liste les parties de l'utilisateur connecté
   */
  async myGames(): Promise<Game[]> {
    return apiClient.get<Game[]>('/games/my-games')
  },

  /**
   * Récupère les détails d'une partie par son ID
   */
  async getById(id: number): Promise<Game> {
    return apiClient.get<Game>(`/games/${id}`)
  },

  /**
   * Crée une nouvelle partie
   */
  async create(dto: CreateGameDTO): Promise<Game> {
    return apiClient.post<Game>('/games', dto)
  },

  /**
   * Met à jour une partie existante
   */
  async update(id: number, dto: UpdateGameDTO): Promise<Game> {
    return apiClient.put<Game>(`/games/${id}`, dto)
  },

  /**
   * Met à jour partiellement une partie
   */
  async partialUpdate(id: number, dto: Partial<UpdateGameDTO>): Promise<Game> {
    return apiClient.patch<Game>(`/games/${id}`, dto)
  },

  /**
   * Rejoindre une partie (avec mot de passe optionnel)
   */
  async join(id: number, dto?: JoinGameDTO): Promise<void> {
    await apiClient.post<void>(`/games/${id}/join`, dto)
  },

  /**
   * Quitter une partie
   */
  async leave(id: number): Promise<void> {
    await apiClient.post<void>(`/games/${id}/leave`)
  },

  /**
   * Supprimer ou archiver une partie
   */
  async delete(id: number): Promise<void> {
    await apiClient.delete<void>(`/games/${id}`)
  },

  /**
   * Démarrer une partie
   */
  async start(id: number): Promise<Game> {
    return apiClient.post<Game>(`/games/${id}/start`)
  },

  /**
   * Mettre en pause une partie
   */
  async pause(id: number): Promise<Game> {
    return apiClient.post<Game>(`/games/${id}/pause`)
  },

  /**
   * Terminer une partie
   */
  async complete(id: number): Promise<Game> {
    return apiClient.post<Game>(`/games/${id}/complete`)
  },
}
