/**
 * Service API pour la gestion des cartes (GameMap)
 */

import { apiClient } from './apiClient';
import type { GameMap } from '@/types/game';

/**
 * DTO pour la création d'une carte
 */
export interface CreateMapDTO {
  name: string;
  description?: string;
  imageUrl?: string;
  gridSize?: number;
  gridType?: 'square' | 'hex' | 'none';
  width?: number;
  height?: number;
  settings?: Record<string, any>;
}

/**
 * DTO pour la mise à jour d'une carte
 */
export interface UpdateMapDTO {
  name?: string;
  description?: string;
  imageUrl?: string;
  gridSize?: number;
  gridType?: 'square' | 'hex' | 'none';
  width?: number;
  height?: number;
  isActive?: boolean;
  settings?: Record<string, any>;
}

/**
 * Service pour gérer les cartes d'un jeu
 */
export const mapApi = {
  /**
   * Récupérer toutes les cartes d'un jeu
   */
  async listByGame(gameId: number): Promise<GameMap[]> {
    return apiClient.get<GameMap[]>(`/games/${gameId}/maps`);
  },

  /**
   * Récupérer la carte active d'un jeu
   */
  async getActive(gameId: number): Promise<GameMap | null> {
    try {
      return await apiClient.get<GameMap>(`/games/${gameId}/maps/active`);
    } catch (error: any) {
      // Si pas de carte active, retourner null
      if (error.statusCode === 404) {
        return null;
      }
      throw error;
    }
  },

  /**
   * Récupérer les détails d'une carte
   */
  async getById(mapId: number): Promise<GameMap> {
    return apiClient.get<GameMap>(`/maps/${mapId}`);
  },

  /**
   * Créer une nouvelle carte pour un jeu
   */
  async create(gameId: number, dto: CreateMapDTO): Promise<GameMap> {
    return apiClient.post<GameMap>(`/games/${gameId}/maps`, dto);
  },

  /**
   * Mettre à jour une carte
   */
  async update(mapId: number, dto: UpdateMapDTO): Promise<GameMap> {
    return apiClient.put<GameMap>(`/maps/${mapId}`, dto);
  },

  /**
   * Mettre à jour partiellement une carte
   */
  async partialUpdate(mapId: number, dto: Partial<UpdateMapDTO>): Promise<GameMap> {
    return apiClient.patch<GameMap>(`/maps/${mapId}`, dto);
  },

  /**
   * Activer une carte (désactive les autres cartes du jeu)
   */
  async activate(mapId: number): Promise<GameMap> {
    return apiClient.post<GameMap>(`/maps/${mapId}/activate`);
  },

  /**
   * Désactiver une carte
   */
  async deactivate(mapId: number): Promise<GameMap> {
    return apiClient.post<GameMap>(`/maps/${mapId}/deactivate`);
  },

  /**
   * Supprimer une carte
   */
  async delete(mapId: number): Promise<void> {
    await apiClient.delete<void>(`/maps/${mapId}`);
  },

  /**
   * Dupliquer une carte
   */
  async duplicate(mapId: number, newName?: string): Promise<GameMap> {
    return apiClient.post<GameMap>(`/maps/${mapId}/duplicate`, {
      name: newName,
    });
  },
};