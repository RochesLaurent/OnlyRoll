import { apiClient } from './apiClient'
import type {
  Game,
  CreateGameDTO,
  UpdateGameDTO,
  JoinGameDTO,
  GameFilters,
  PaginatedGamesResponse,
} from '@/types/game'

/**
 * Service de gestion des parties de jeu
 * CRUD complet + actions spécifiques (rejoindre, quitter)
 */
export const gameApi = {
  /**
   * Liste toutes les parties publiques avec filtres et pagination
   * @param filters - Filtres de recherche optionnels
   */
  async listPublic(filters?: GameFilters): Promise<PaginatedGamesResponse> {
    const params = new URLSearchParams()

    if (filters?.search) params.append('search', filters.search)
    if (filters?.title) params.append('title', filters.title)
    if (filters?.gameMaster) params.append('gameMaster', filters.gameMaster)
    if (filters?.status) params.append('status', filters.status)
    if (filters?.page) params.append('page', filters.page.toString())
    if (filters?.limit) params.append('limit', filters.limit.toString())

    const endpoint = params.toString() ? `/games?${params.toString()}` : '/games'
    return apiClient.get<PaginatedGamesResponse>(endpoint)
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
