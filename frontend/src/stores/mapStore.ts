/**
 * Store Pinia pour la gestion des cartes et tokens
 * Gère l'état des cartes, tokens et leur synchronisation temps réel via Mercure
 */

import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { mapApi, tokenApi } from '@/services/api'
import type { GameMap, GameToken, CreateTokenDTO, MoveTokenDTO, UpdateTokenDTO } from '@/types/game'
import { TokenType, LayerType } from '@/types/game'
import type { MercureTokenEventData, MercureMapEventData } from '@/types/websocket'

export const useMapStore = defineStore('map', () => {
  // ===========================
  // État
  // ===========================

  const activeMap = ref<GameMap | null>(null)
  const allMaps = ref<GameMap[]>([])
  const tokens = ref<GameToken[]>([])
  const currentGameId = ref<number | null>(null)

  const isLoading = ref(false)
  const error = ref<string | null>(null)

  // ===========================
  // Getters (computed)
  // ===========================

  /**
   * Tokens visibles uniquement
   */
  const visibleTokens = computed(() => {
    return tokens.value.filter((token) => token.isVisible)
  })

  /**
   * Tokens par type
   */
  const tokensByType = computed(() => {
    return (type: string) => tokens.value.filter((token) => token.type === type)
  })

  /**
   * Obtenir un token par son ID
   */
  const getTokenById = computed(() => {
    return (id: number) => tokens.value.find((token) => token.id === id)
  })

  /**
   * Vérifier si une carte est chargée
   */
  const hasActiveMap = computed(() => activeMap.value !== null)

  /**
   * Nombre total de tokens
   */
  const tokensCount = computed(() => tokens.value.length)

  /**
   * Dimensions de la carte active
   */
  const mapDimensions = computed(() => {
    if (!activeMap.value) return null
    return {
      width: activeMap.value.width,
      height: activeMap.value.height,
      gridSize: activeMap.value.gridSize,
    }
  })

  // ===========================
  // Actions - Cartes
  // ===========================

  /**
   * Charger toutes les cartes d'un jeu
   */
  async function loadGameMaps(gameId: number) {
    isLoading.value = true
    error.value = null

    try {
      allMaps.value = await mapApi.listByGame(gameId)
    } catch (e: unknown) {
      if (e && typeof e === 'object' && 'message' in e) {
        error.value = (e as { message: string }).message || 'Erreur lors du chargement des cartes'
      } else {
        error.value = 'Erreur lors du chargement des cartes'
      }
      console.error('Erreur loadGameMaps:', e)
      throw e
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Charger la carte active d'un jeu et ses tokens
   */
  async function loadActiveMap(gameId: number) {
    isLoading.value = true
    error.value = null
    currentGameId.value = gameId

    try {
      const map = await mapApi.getActive(gameId)

      if (map) {
        activeMap.value = map
        await loadMapTokens(map.id)
      } else {
        activeMap.value = null
        tokens.value = []
      }
    } catch (e: unknown) {
      if (e && typeof e === 'object' && 'message' in e) {
        error.value =
          (e as { message: string }).message || 'Erreur lors du chargement de la carte active'
      } else {
        error.value = 'Erreur lors du chargement de la carte active'
      }
      console.error('Erreur loadActiveMap:', e)
      throw e
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Charger une carte spécifique par son ID
   */
  async function loadMap(gameId: number, mapId: number) {
    isLoading.value = true
    error.value = null
    currentGameId.value = gameId

    try {
      activeMap.value = await mapApi.getById(mapId)
      await loadMapTokens(mapId)
    } catch (e: unknown) {
      if (e && typeof e === 'object' && 'message' in e) {
        error.value = (e as { message: string }).message || 'Erreur lors du chargement de la carte'
      } else {
        error.value = 'Erreur lors du chargement de la carte'
      }
      console.error('Erreur loadMap:', e)
      throw e
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Activer une carte
   */
  async function activateMap(gameId: number, mapId: number) {
    isLoading.value = true
    error.value = null
    currentGameId.value = gameId

    try {
      activeMap.value = await mapApi.activate(mapId)
      await loadMapTokens(mapId)
    } catch (e: unknown) {
      if (e && typeof e === 'object' && 'message' in e) {
        error.value =
          (e as { message: string }).message || "Erreur lors de l'activation de la carte"
      } else {
        error.value = "Erreur lors de l'activation de la carte"
      }
      console.error('Erreur activateMap:', e)
      throw e
    } finally {
      isLoading.value = false
    }
  }

  // ===========================
  // Actions - Tokens
  // ===========================

  /**
   * Charger les tokens d'une carte
   */
  async function loadMapTokens(mapId: number) {
    if (!currentGameId.value) {
      throw new Error('GameId not set. Call loadActiveMap first.')
    }

    try {
      tokens.value = await tokenApi.listVisible(currentGameId.value, mapId)
    } catch (e: unknown) {
      if (e && typeof e === 'object' && 'message' in e) {
        error.value = (e as { message: string }).message || 'Erreur lors du chargement des tokens'
      } else {
        error.value = 'Erreur lors du chargement des tokens'
      }
      console.error('Erreur loadMapTokens:', e)
      throw e
    }
  }

  /**
   * HELPER: Construire un DTO de création de token valide
   * S'assure que toutes les propriétés obligatoires sont présentes
   */
  function buildCreateTokenDTO(
    data: Partial<CreateTokenDTO> & { name: string; type: TokenType; x: number; y: number },
  ): CreateTokenDTO {
    // Construction d'un objet avec toutes les propriétés nécessaires
    const dto: CreateTokenDTO = {
      // Champs obligatoires
      name: data.name,
      type: data.type,
      x: data.x,
      y: data.y,

      // Champs optionnels avec valeurs par défaut si non fournis
      size: data.size ?? 1.0,
      rotation: data.rotation ?? 0,
      isVisible: data.isVisible ?? true,
      isLocked: data.isLocked ?? false,
      layer: data.layer ?? LayerType.TOKENS,
    }

    // Ajouter les champs vraiment optionnels seulement s'ils sont fournis
    if (data.imageUrl !== undefined) {
      dto.imageUrl = data.imageUrl
    }

    if (data.settings !== undefined) {
      dto.settings = data.settings
    }

    console.log('✅ DTO Token construit:', dto)
    return dto
  }

  /**
   * Créer un nouveau token
   * CORRECTION: Type correctement les données avec CreateTokenDTO
   */
  async function createToken(
    mapId: number,
    tokenData: Partial<CreateTokenDTO> & { name: string; type: TokenType; x: number; y: number },
  ): Promise<GameToken> {
    if (!currentGameId.value) {
      throw new Error('GameId not set')
    }

    isLoading.value = true
    error.value = null

    try {
      // Construction d'un DTO valide avec toutes les propriétés nécessaires
      const dto = buildCreateTokenDTO(tokenData)

      console.log('📤 Envoi de la requête de création de token:', {
        gameId: currentGameId.value,
        mapId,
        dto,
      })

      const newToken = await tokenApi.create(currentGameId.value, mapId, dto)

      console.log('✅ Token créé avec succès:', newToken)
      tokens.value.push(newToken)
      return newToken
    } catch (e: unknown) {
      console.error('❌ Erreur lors de la création du token:', e)

      if (e && typeof e === 'object' && 'message' in e) {
        error.value = (e as { message: string }).message || 'Erreur lors de la création du token'
      } else {
        error.value = 'Erreur lors de la création du token'
      }
      throw e
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Déplacer un token
   */
  async function moveToken(
    tokenId: number,
    x: number,
    y: number,
    rotation?: number,
  ): Promise<GameToken> {
    if (!currentGameId.value || !activeMap.value) {
      throw new Error('GameId or Map not set')
    }

    error.value = null

    try {
      const moveData: MoveTokenDTO = { x, y }
      if (rotation !== undefined) {
        moveData.rotation = rotation
      }

      const updatedToken = await tokenApi.move(
        currentGameId.value,
        activeMap.value.id,
        tokenId,
        moveData,
      )
      updateTokenInList(updatedToken)
      return updatedToken
    } catch (e: unknown) {
      if (e && typeof e === 'object' && 'message' in e) {
        error.value = (e as { message: string }).message || 'Erreur lors du déplacement du token'
      } else {
        error.value = 'Erreur lors du déplacement du token'
      }
      console.error('Erreur moveToken:', e)
      throw e
    }
  }

  /**
   * Mettre à jour un token
   */
  async function updateToken(tokenId: number, updates: Partial<UpdateTokenDTO>) {
    if (!currentGameId.value || !activeMap.value) {
      throw new Error('GameId or Map not set')
    }

    error.value = null

    try {
      const updatedToken = await tokenApi.partialUpdate(
        currentGameId.value,
        activeMap.value.id,
        tokenId,
        updates,
      )
      updateTokenInList(updatedToken)
      return updatedToken
    } catch (e: unknown) {
      if (e && typeof e === 'object' && 'message' in e) {
        error.value = (e as { message: string }).message || 'Erreur lors de la mise à jour du token'
      } else {
        error.value = 'Erreur lors de la mise à jour du token'
      }
      console.error('Erreur updateToken:', e)
      throw e
    }
  }

  /**
   * Supprimer un token
   */
  async function deleteToken(tokenId: number) {
    if (!currentGameId.value || !activeMap.value) {
      throw new Error('GameId or Map not set')
    }

    error.value = null

    try {
      await tokenApi.delete(currentGameId.value, activeMap.value.id, tokenId)
      removeTokenFromList(tokenId)
    } catch (e: unknown) {
      if (e && typeof e === 'object' && 'message' in e) {
        error.value = (e as { message: string }).message || 'Erreur lors de la suppression du token'
      } else {
        error.value = 'Erreur lors de la suppression du token'
      }
      console.error('Erreur deleteToken:', e)
      throw e
    }
  }

  /**
   * Basculer la visibilité d'un token
   */
  async function toggleTokenVisibility(tokenId: number) {
    if (!currentGameId.value || !activeMap.value) {
      throw new Error('GameId or Map not set')
    }

    const token = getTokenById.value(tokenId)
    if (!token) return

    try {
      if (token.isVisible) {
        await tokenApi.hide(currentGameId.value, activeMap.value.id, tokenId)
      } else {
        await tokenApi.show(currentGameId.value, activeMap.value.id, tokenId)
      }

      // Mettre à jour localement
      token.isVisible = !token.isVisible
    } catch (e: unknown) {
      if (e && typeof e === 'object' && 'message' in e) {
        error.value =
          (e as { message: string }).message || 'Erreur lors du changement de visibilité'
      } else {
        error.value = 'Erreur lors du changement de visibilité'
      }
      console.error('Erreur toggleTokenVisibility:', e)
      throw e
    }
  }

  /**
   * Verrouiller/Déverrouiller un token
   */
  async function toggleTokenLock(tokenId: number) {
    if (!currentGameId.value || !activeMap.value) {
      throw new Error('GameId or Map not set')
    }

    const token = getTokenById.value(tokenId)
    if (!token) return

    try {
      if (token.isLocked) {
        await tokenApi.unlock(currentGameId.value, activeMap.value.id, tokenId)
      } else {
        await tokenApi.lock(currentGameId.value, activeMap.value.id, tokenId)
      }

      // Mettre à jour localement
      token.isLocked = !token.isLocked
    } catch (e: unknown) {
      if (e && typeof e === 'object' && 'message' in e) {
        error.value =
          (e as { message: string }).message || 'Erreur lors du changement de verrouillage'
      } else {
        error.value = 'Erreur lors du changement de verrouillage'
      }
      console.error('Erreur toggleTokenLock:', e)
      throw e
    }
  }

  // ===========================
  // Actions - Synchronisation Mercure
  // ===========================

  /**
   * Gérer un événement de token reçu via Mercure
   */
  function handleTokenEvent(data: MercureTokenEventData) {
    console.log('Token event reçu:', data)

    // Structure attendue depuis le backend :
    // { type: 'created' | 'updated' | 'moved' | 'deleted', token: GameToken }

    switch (data.type) {
      case 'created':
        addTokenToList(data.token)
        break

      case 'updated':
      case 'moved':
        updateTokenInList(data.token)
        break

      case 'deleted':
        removeTokenFromList(data.token.id)
        break

      default:
        console.warn('Type de token event inconnu:', data.type)
    }
  }

  /**
   * Gérer un événement de carte reçu via Mercure
   */
  function handleMapEvent(data: MercureMapEventData) {
    console.log('Map event reçu:', data)

    switch (data.type) {
      case 'activated':
        if (data.map) {
          activeMap.value = data.map
        }
        break

      case 'updated':
        if (activeMap.value && data.map.id === activeMap.value.id) {
          activeMap.value = data.map
        }
        break

      default:
        console.warn('Type de map event inconnu:', data.type)
    }
  }

  // ===========================
  // Helpers privés
  // ===========================

  /**
   * Ajouter un token à la liste (évite les doublons)
   */
  function addTokenToList(token: GameToken) {
    const exists = tokens.value.some((t) => t.id === token.id)
    if (!exists) {
      tokens.value.push(token)
    }
  }

  /**
   * Mettre à jour un token dans la liste
   */
  function updateTokenInList(updatedToken: GameToken) {
    const index = tokens.value.findIndex((t) => t.id === updatedToken.id)
    if (index !== -1) {
      tokens.value[index] = updatedToken
    } else {
      // Si le token n'existe pas, l'ajouter
      tokens.value.push(updatedToken)
    }
  }

  /**
   * Retirer un token de la liste
   */
  function removeTokenFromList(tokenId: number) {
    const index = tokens.value.findIndex((t) => t.id === tokenId)
    if (index !== -1) {
      tokens.value.splice(index, 1)
    }
  }

  /**
   * Réinitialiser le store
   */
  function $reset() {
    activeMap.value = null
    allMaps.value = []
    tokens.value = []
    currentGameId.value = null
    isLoading.value = false
    error.value = null
  }

  // ===========================
  // Return (API publique du store)
  // ===========================

  return {
    // État
    activeMap,
    allMaps,
    tokens,
    currentGameId,
    isLoading,
    error,

    // Getters
    visibleTokens,
    tokensByType,
    getTokenById,
    hasActiveMap,
    tokensCount,
    mapDimensions,

    // Actions - Cartes
    loadGameMaps,
    loadActiveMap,
    loadMap,
    activateMap,

    // Actions - Tokens
    loadMapTokens,
    createToken,
    moveToken,
    updateToken,
    deleteToken,
    toggleTokenVisibility,
    toggleTokenLock,

    // Actions - Mercure
    handleTokenEvent,
    handleMapEvent,

    // Utils
    $reset,
  }
})
