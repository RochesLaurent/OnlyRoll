/**
 * Service API pour la gestion des cartes (GameMap)
 */
import { get, post, put, patch, delete as del } from './apiClient'
import type { GameMap } from '@/types/game'
import type { ApiError } from './apiClient'

/**
 * DTO pour la création d'une carte
 */
export interface CreateMapDTO {
  name: string
  description?: string
  imageUrl?: string
  gridSize?: number
  gridType?: 'square' | 'hex' | 'none'
  width?: number
  height?: number
  settings?: Record<string, unknown>
}

/**
 * DTO pour la mise à jour d'une carte
 */
export interface UpdateMapDTO {
  name?: string
  description?: string
  imageUrl?: string
  gridSize?: number
  gridType?: 'square' | 'hex' | 'none'
  width?: number
  height?: number
  isActive?: boolean
  settings?: Record<string, unknown>
}

/**
 * Service pour gérer les cartes d'un jeu
 */
export const mapApi = {
  /**
   * Récupérer toutes les cartes d'un jeu
   */
  async listByGame(gameId: number): Promise<GameMap[]> {
    return get<GameMap[]>(`/games/${gameId}/maps`)
  },

  /**
   * Récupérer la carte active d'un jeu
   */
  async getActive(gameId: number): Promise<GameMap | null> {
    try {
      return await get<GameMap>(`/games/${gameId}/maps/active`)
    } catch (error: unknown) {
      // Si pas de carte active, retourner null
      if (
        error &&
        typeof error === 'object' &&
        'statusCode' in error &&
        (error as ApiError).statusCode === 404
      ) {
        return null
      }
      throw error
    }
  },

  /**
   * Récupérer les détails d'une carte
   */
  async getById(mapId: number): Promise<GameMap> {
    return get<GameMap>(`/maps/${mapId}`)
  },

  /**
   * Créer une nouvelle carte pour un jeu
   */
  async create(gameId: number, dto: CreateMapDTO): Promise<GameMap> {
    return post<GameMap>(`/games/${gameId}/maps`, dto)
  },

  /**
   * Mettre à jour une carte
   */
  async update(mapId: number, dto: UpdateMapDTO): Promise<GameMap> {
    return put<GameMap>(`/maps/${mapId}`, dto)
  },

  /**
   * Mettre à jour partiellement une carte
   */
  async partialUpdate(mapId: number, dto: Partial<UpdateMapDTO>): Promise<GameMap> {
    return patch<GameMap>(`/maps/${mapId}`, dto)
  },

  /**
   * Activer une carte (désactive les autres cartes du jeu)
   */
  async activate(gameId: number, mapId: number): Promise<GameMap> {
    return post<GameMap>(`/games/${gameId}/maps/${mapId}/activate`)
  },

  /**
   * Désactiver une carte
   */
  async deactivate(gameId: number, mapId: number): Promise<GameMap> {
    return post<GameMap>(`/games/${gameId}/maps/${mapId}/deactivate`)
  },

  /**
   * Supprimer une carte
   */
  async delete(gameId: number, mapId: number): Promise<void> {
    await del<void>(`/games/${gameId}/maps/${mapId}`)
  },

  /**
   * Dupliquer une carte
   */
  async duplicate(mapId: number, newName?: string): Promise<GameMap> {
    return post<GameMap>(`/maps/${mapId}/duplicate`, {
      name: newName,
    })
  },
}
