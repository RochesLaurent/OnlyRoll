/**
 * Service API pour la gestion des tokens (GameToken)
 *
 * Ce service gère toutes les opérations CRUD sur les tokens d'une carte.
 * Tous les types sont importés depuis @/types/game pour garantir la cohérence.
 */

import { get, post, patch, delete as del } from './apiClient'
import type { GameToken, CreateTokenDTO, UpdateTokenDTO, MoveTokenDTO } from '@/types/game'

/**
 * Service pour gérer les tokens sur les cartes
 * Toutes les fonctions nécessitent gameId et mapId pour construire les routes API
 */
export const tokenApi = {
  /**
   * Récupérer tous les tokens d'une carte
   * Note: Cette fonction est dépréciée, utilisez listByMapWithGame ou listVisible à la place
   * @deprecated
   */
  async listByMap(): Promise<GameToken[]> {
    throw new Error('Use listByMapWithGame instead - needs gameId')
  },

  /**
   * Récupérer tous les tokens d'une carte (avec gameId)
   * @param gameId - ID du jeu
   * @param mapId - ID de la carte
   * @returns Liste de tous les tokens de la carte
   */
  async listByMapWithGame(gameId: number, mapId: number): Promise<GameToken[]> {
    return get<GameToken[]>(`/games/${gameId}/maps/${mapId}/tokens`)
  },

  /**
   * Récupérer les tokens visibles d'une carte (pour un joueur)
   * Les joueurs normaux ne voient que les tokens avec isVisible = true
   * Le MJ voit tous les tokens
   * @param gameId - ID du jeu
   * @param mapId - ID de la carte
   * @returns Liste des tokens visibles pour l'utilisateur connecté
   */
  async listVisible(gameId: number, mapId: number): Promise<GameToken[]> {
    return get<GameToken[]>(`/games/${gameId}/maps/${mapId}/tokens`)
  },

  /**
   * Récupérer les détails d'un token spécifique
   * @param gameId - ID du jeu
   * @param mapId - ID de la carte
   * @param tokenId - ID du token
   * @returns Le token complet avec toutes ses propriétés
   */
  async getById(gameId: number, mapId: number, tokenId: number): Promise<GameToken> {
    return get<GameToken>(`/games/${gameId}/maps/${mapId}/tokens/${tokenId}`)
  },

  /**
   * Créer un nouveau token sur une carte
   * IMPORTANT: Le DTO doit contenir au minimum name, type, x, y
   * Les autres champs sont optionnels et auront des valeurs par défaut côté backend
   * @param gameId - ID du jeu
   * @param mapId - ID de la carte
   * @param dto - Données du token à créer (CreateTokenDTO)
   * @returns Le token nouvellement créé avec son ID généré
   */
  async create(gameId: number, mapId: number, dto: CreateTokenDTO): Promise<GameToken> {
    return post<GameToken>(`/games/${gameId}/maps/${mapId}/tokens`, dto)
  },

  /**
   * Mettre à jour complètement un token (PUT)
   * Remplace toutes les propriétés du token
   * @param gameId - ID du jeu
   * @param mapId - ID de la carte
   * @param tokenId - ID du token à mettre à jour
   * @param dto - Nouvelles données du token (UpdateTokenDTO)
   * @returns Le token mis à jour
   */
  async update(
    gameId: number,
    mapId: number,
    tokenId: number,
    dto: UpdateTokenDTO
  ): Promise<GameToken> {
    return post<GameToken>(`/games/${gameId}/maps/${mapId}/tokens/${tokenId}`, dto)
  },

  /**
   * Mettre à jour partiellement un token (PATCH)
   * Ne modifie que les propriétés fournies dans le DTO
   * @param gameId - ID du jeu
   * @param mapId - ID de la carte
   * @param tokenId - ID du token à mettre à jour
   * @param dto - Propriétés à modifier (partiel de UpdateTokenDTO)
   * @returns Le token mis à jour
   */
  async partialUpdate(
    gameId: number,
    mapId: number,
    tokenId: number,
    dto: Partial<UpdateTokenDTO>
  ): Promise<GameToken> {
    return patch<GameToken>(`/games/${gameId}/maps/${mapId}/tokens/${tokenId}`, dto)
  },

  /**
   * Déplacer un token vers une nouvelle position
   * Utilise un endpoint dédié pour le déplacement avec snap-to-grid
   * @param gameId - ID du jeu
   * @param mapId - ID de la carte
   * @param tokenId - ID du token à déplacer
   * @param position - Nouvelles coordonnées (MoveTokenDTO)
   * @returns Le token avec sa nouvelle position
   */
  async move(
    gameId: number,
    mapId: number,
    tokenId: number,
    position: MoveTokenDTO
  ): Promise<GameToken> {
    return post<GameToken>(`/games/${gameId}/maps/${mapId}/tokens/${tokenId}/move`, position)
  },

  /**
   * Faire pivoter un token
   * @param gameId - ID du jeu
   * @param mapId - ID de la carte
   * @param tokenId - ID du token
   * @param degrees - Nouvel angle de rotation (0-359)
   * @returns Le token avec sa nouvelle rotation
   */
  async rotate(
    gameId: number,
    mapId: number,
    tokenId: number,
    degrees: number
  ): Promise<GameToken> {
    return patch<GameToken>(`/games/${gameId}/maps/${mapId}/tokens/${tokenId}/rotate`, {
      degrees,
    })
  },

  /**
   * Afficher un token (le rendre visible)
   * Toggle la visibilité via l'endpoint toggle-visibility
   * @param gameId - ID du jeu
   * @param mapId - ID de la carte
   * @param tokenId - ID du token
   * @returns Le token avec isVisible mis à jour
   */
  async show(gameId: number, mapId: number, tokenId: number): Promise<GameToken> {
    return post<GameToken>(`/games/${gameId}/maps/${mapId}/tokens/${tokenId}/toggle-visibility`)
  },

  /**
   * Masquer un token (le rendre invisible)
   * Toggle la visibilité via l'endpoint toggle-visibility
   * @param gameId - ID du jeu
   * @param mapId - ID de la carte
   * @param tokenId - ID du token
   * @returns Le token avec isVisible mis à jour
   */
  async hide(gameId: number, mapId: number, tokenId: number): Promise<GameToken> {
    return post<GameToken>(`/games/${gameId}/maps/${mapId}/tokens/${tokenId}/toggle-visibility`)
  },

  /**
   * Verrouiller un token (empêche le déplacement)
   * Toggle le verrou via l'endpoint toggle-lock
   * @param gameId - ID du jeu
   * @param mapId - ID de la carte
   * @param tokenId - ID du token
   * @returns Le token avec isLocked mis à jour
   */
  async lock(gameId: number, mapId: number, tokenId: number): Promise<GameToken> {
    return post<GameToken>(`/games/${gameId}/maps/${mapId}/tokens/${tokenId}/toggle-lock`)
  },

  /**
   * Déverrouiller un token (autorise le déplacement)
   * Toggle le verrou via l'endpoint toggle-lock
   * @param gameId - ID du jeu
   * @param mapId - ID de la carte
   * @param tokenId - ID du token
   * @returns Le token avec isLocked mis à jour
   */
  async unlock(gameId: number, mapId: number, tokenId: number): Promise<GameToken> {
    return post<GameToken>(`/games/${gameId}/maps/${mapId}/tokens/${tokenId}/toggle-lock`)
  },

  /**
   * Supprimer un token de la carte
   * ATTENTION: Cette action est irréversible
   * @param gameId - ID du jeu
   * @param mapId - ID de la carte
   * @param tokenId - ID du token à supprimer
   */
  async delete(gameId: number, mapId: number, tokenId: number): Promise<void> {
    await del<void>(`/games/${gameId}/maps/${mapId}/tokens/${tokenId}`)
  },

  /**
   * Dupliquer un token avec un décalage optionnel
   * Crée une copie du token à une position légèrement différente
   * @param gameId - ID du jeu
   * @param mapId - ID de la carte
   * @param tokenId - ID du token à dupliquer
   * @param offset - Décalage x/y par rapport au token original (optionnel)
   * @returns Le nouveau token créé (copie)
   */
  async duplicate(
    gameId: number,
    mapId: number,
    tokenId: number,
    offset?: { x: number; y: number }
  ): Promise<GameToken> {
    return post<GameToken>(`/games/${gameId}/maps/${mapId}/tokens/${tokenId}/duplicate`, {
      offset,
    })
  },

  /**
   * Déplacer plusieurs tokens en une seule requête
   * Optimisation pour les déplacements groupés
   * @param gameId - ID du jeu
   * @param mapId - ID de la carte
   * @param movements - Liste des déplacements à effectuer
   * @returns Liste des tokens mis à jour
   */
  async moveBulk(
    gameId: number,
    mapId: number,
    movements: Array<{ tokenId: number; x: number; y: number }>
  ): Promise<GameToken[]> {
    return patch<GameToken[]>(`/games/${gameId}/maps/${mapId}/tokens/move-bulk`, {
      movements,
    })
  },

  /**
   * Changer la visibilité de plusieurs tokens simultanément
   * Utile pour montrer/cacher des groupes de tokens d'un coup
   * @param gameId - ID du jeu
   * @param mapId - ID de la carte
   * @param tokenIds - Liste des IDs de tokens à modifier
   * @param isVisible - Nouvelle valeur de visibilité pour tous les tokens
   * @returns Liste des tokens mis à jour
   */
  async toggleBulkVisibility(
    gameId: number,
    mapId: number,
    tokenIds: number[],
    isVisible: boolean
  ): Promise<GameToken[]> {
    return patch<GameToken[]>(`/games/${gameId}/maps/${mapId}/tokens/visibility-bulk`, {
      tokenIds,
      isVisible,
    })
  },
}
