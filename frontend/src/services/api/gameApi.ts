/**
 * Service API pour la gestion des parties (Game)
 */
import { get, post, put, delete as del } from './apiClient'
import type {
  Game,
  CreateGameDTO,
  UpdateGameDTO,
  GameFilters,
  PaginatedGamesResponse
} from '@/types/game'

/**
 * Service pour gérer les parties de jeu
 */
export const gameApi = {
  /**
   * Récupérer toutes les parties publiques avec filtres et pagination
   */
  async listPublic(filters?: GameFilters): Promise<PaginatedGamesResponse> {
    const params = new URLSearchParams()
    
    if (filters?.search) params.append('search', filters.search)
    if (filters?.title) params.append('title', filters.title)
    if (filters?.gameMaster) params.append('gameMaster', filters.gameMaster)
    if (filters?.status) params.append('status', filters.status)
    if (filters?.page) params.append('page', filters.page.toString())
    if (filters?.limit) params.append('limit', filters.limit.toString())
    
    const query = params.toString()
    const url = query ? `/games?${query}` : '/games'
    
    return get<PaginatedGamesResponse>(url)
  },

  /**
   * Récupérer les parties de l'utilisateur connecté
   */
  async myGames(): Promise<Game[]> {
    return get<Game[]>('/games/my-games')
  },

  /**
   * Récupérer une partie par son ID
   */
  async getById(id: number): Promise<Game> {
    return get<Game>(`/games/${id}`)
  },

  /**
   * Créer une nouvelle partie
   */
  async create(dto: CreateGameDTO): Promise<Game> {
    return post<Game>('/games', dto)
  },

  /**
   * Mettre à jour une partie
   */
  async update(id: number, dto: UpdateGameDTO): Promise<Game> {
    return put<Game>(`/games/${id}`, dto)
  },

  /**
   * Supprimer une partie
   */
  async delete(id: number): Promise<void> {
    await del<void>(`/games/${id}`)
  },

  /**
   * Rejoindre une partie par code d'invitation
   */
  async joinByCode(inviteCode: string, password?: string): Promise<Game> {
    return post<Game>('/games/join', {
      inviteCode,
      password
    })
  },

  /**
   * Rejoindre une partie par ID (si on connaît déjà l'ID)
   */
  async join(id: number, password?: string): Promise<Game> {
    return post<Game>(`/games/${id}/join`, { password })
  },

  /**
   * Quitter une partie
   */
  async leave(id: number): Promise<void> {
    await post<void>(`/games/${id}/leave`)
  },

  /**
   * Démarrer une partie (MJ uniquement)
   */
  async start(id: number): Promise<Game> {
    return post<Game>(`/games/${id}/start`)
  },

  /**
   * Mettre en pause une partie
   */
  async pause(id: number): Promise<Game> {
    return post<Game>(`/games/${id}/pause`)
  },

  /**
   * Reprendre une partie en pause
   */
  async resume(id: number): Promise<Game> {
    return post<Game>(`/games/${id}/resume`)
  },

  /**
   * Terminer une partie
   */
  async complete(id: number): Promise<Game> {
    return post<Game>(`/games/${id}/complete`)
  },

  /**
   * Archiver une partie
   */
  async archive(id: number): Promise<Game> {
    return post<Game>(`/games/${id}/archive`)
  },
}