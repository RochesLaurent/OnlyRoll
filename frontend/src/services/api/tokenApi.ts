/**
 * Service API pour la gestion des tokens (GameToken)
 */

import { apiClient } from './apiClient';
import type { GameToken, TokenType, TokenLayer } from '@/types/game';

/**
 * DTO pour la création d'un token
 */
export interface CreateTokenDTO {
  name: string;
  type: TokenType;
  imageUrl?: string;
  x?: number;
  y?: number;
  size?: number;
  rotation?: number;
  isVisible?: boolean;
  layer?: TokenLayer;
  settings?: Record<string, any>;
}

/**
 * DTO pour la mise à jour d'un token
 */
export interface UpdateTokenDTO {
  name?: string;
  type?: TokenType;
  imageUrl?: string;
  x?: number;
  y?: number;
  size?: number;
  rotation?: number;
  isVisible?: boolean;
  isLocked?: boolean;
  layer?: TokenLayer;
  settings?: Record<string, any>;
}

/**
 * DTO pour déplacer un token
 */
export interface MoveTokenDTO {
  x: number;
  y: number;
}

/**
 * Service pour gérer les tokens sur les cartes
 */
export const tokenApi = {
  /**
   * Récupérer tous les tokens d'une carte
   */
  async listByMap(mapId: number): Promise<GameToken[]> {
    return apiClient.get<GameToken[]>(`/maps/${mapId}/tokens`);
  },

  /**
   * Récupérer les tokens visibles d'une carte (pour un joueur)
   */
  async listVisible(mapId: number): Promise<GameToken[]> {
    return apiClient.get<GameToken[]>(`/maps/${mapId}/tokens/visible`);
  },

  /**
   * Récupérer les détails d'un token
   */
  async getById(tokenId: number): Promise<GameToken> {
    return apiClient.get<GameToken>(`/tokens/${tokenId}`);
  },

  /**
   * Créer un nouveau token sur une carte
   */
  async create(mapId: number, dto: CreateTokenDTO): Promise<GameToken> {
    return apiClient.post<GameToken>(`/maps/${mapId}/tokens`, dto);
  },

  /**
   * Mettre à jour un token
   */
  async update(tokenId: number, dto: UpdateTokenDTO): Promise<GameToken> {
    return apiClient.put<GameToken>(`/tokens/${tokenId}`, dto);
  },

  /**
   * Mettre à jour partiellement un token
   */
  async partialUpdate(tokenId: number, dto: Partial<UpdateTokenDTO>): Promise<GameToken> {
    return apiClient.patch<GameToken>(`/tokens/${tokenId}`, dto);
  },

  /**
   * Déplacer un token vers une nouvelle position
   */
  async move(tokenId: number, position: MoveTokenDTO): Promise<GameToken> {
    return apiClient.patch<GameToken>(`/tokens/${tokenId}/move`, position);
  },

  /**
   * Faire pivoter un token
   */
  async rotate(tokenId: number, degrees: number): Promise<GameToken> {
    return apiClient.patch<GameToken>(`/tokens/${tokenId}/rotate`, {
      degrees,
    });
  },

  /**
   * Afficher un token (le rendre visible)
   */
  async show(tokenId: number): Promise<GameToken> {
    return apiClient.patch<GameToken>(`/tokens/${tokenId}/show`);
  },

  /**
   * Masquer un token (le rendre invisible)
   */
  async hide(tokenId: number): Promise<GameToken> {
    return apiClient.patch<GameToken>(`/tokens/${tokenId}/hide`);
  },

  /**
   * Verrouiller un token (empêche le déplacement)
   */
  async lock(tokenId: number): Promise<GameToken> {
    return apiClient.patch<GameToken>(`/tokens/${tokenId}/lock`);
  },

  /**
   * Déverrouiller un token (autorise le déplacement)
   */
  async unlock(tokenId: number): Promise<GameToken> {
    return apiClient.patch<GameToken>(`/tokens/${tokenId}/unlock`);
  },

  /**
   * Supprimer un token
   */
  async delete(tokenId: number): Promise<void> {
    await apiClient.delete<void>(`/tokens/${tokenId}`);
  },

  /**
   * Dupliquer un token
   */
  async duplicate(tokenId: number, offset?: { x: number; y: number }): Promise<GameToken> {
    return apiClient.post<GameToken>(`/tokens/${tokenId}/duplicate`, {
      offset,
    });
  },

  /**
   * Déplacer plusieurs tokens en une seule requête
   */
  async moveBulk(
    movements: Array<{ tokenId: number; x: number; y: number }>
  ): Promise<GameToken[]> {
    return apiClient.patch<GameToken[]>('/tokens/move-bulk', {
      movements,
    });
  },

  /**
   * Changer la visibilité de plusieurs tokens
   */
  async toggleBulkVisibility(tokenIds: number[], isVisible: boolean): Promise<GameToken[]> {
    return apiClient.patch<GameToken[]>('/tokens/visibility-bulk', {
      tokenIds,
      isVisible,
    });
  },
};