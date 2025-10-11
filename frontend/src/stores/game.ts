import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { Game, CreateGameDTO, UpdateGameDTO, GameFilters, PaginationMeta } from '@/types/game'
import { gameApi } from '@/services/api/gameApi'
import { useAuthStore } from './auth'
import { GameStatus } from '@/types/game'
import type { ApiError } from '@/services/api/apiClient'

export const useGameStore = defineStore('game', () => {
  // ========== State ==========
  const games = ref<Game[]>([])
  const currentGame = ref<Game | null>(null)
  const myGames = ref<Game[]>([])
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  // Pagination
  const pagination = ref<PaginationMeta>({
    total: 0,
    page: 1,
    limit: 12,
    totalPages: 0,
  })

  // ========== Getters ==========
  const publicGames = computed(() => games.value.filter((game) => game.isPublic))

  const isGameMaster = computed(() => {
    const authStore = useAuthStore()
    if (!currentGame.value || !authStore.user) return false
    return currentGame.value.gameMaster.id === authStore.user.id
  })

  const isPlayerInGame = computed(() => {
    const authStore = useAuthStore()
    if (!currentGame.value || !authStore.user) return false
    return currentGame.value.gamePlayers.some((gp) => gp.user.id === authStore.user!.id)
  })

  const canStartGame = computed(() => {
    return (
      isGameMaster.value &&
      currentGame.value?.status === GameStatus.PREPARATION &&
      (currentGame.value?.currentPlayersCount ?? 0) > 0
    )
  })

  const canModifyGame = computed(() => {
    return isGameMaster.value && currentGame.value?.status === GameStatus.PREPARATION
  })

  // ========== Helpers ==========

  /**
   * Extrait un message d'erreur lisible depuis une erreur API
   */
  const getErrorMessage = (err: unknown, defaultMessage: string): string => {
    if (typeof err === 'string') return err

    if (err && typeof err === 'object') {
      const apiError = err as ApiError

      if ('message' in apiError && typeof apiError.message === 'string') {
        return apiError.message
      }

      if ('error' in apiError && typeof apiError.error === 'string') {
        return apiError.error
      }
    }

    if (err instanceof Error) {
      return err.message
    }

    return defaultMessage
  }

  function updateGameInLists(updatedGame: Game) {
    const myGamesIndex = myGames.value.findIndex((g) => g.id === updatedGame.id)
    if (myGamesIndex !== -1) {
      myGames.value[myGamesIndex] = updatedGame
    }

    const gamesIndex = games.value.findIndex((g) => g.id === updatedGame.id)
    if (gamesIndex !== -1) {
      games.value[gamesIndex] = updatedGame
    }

    if (currentGame.value?.id === updatedGame.id) {
      currentGame.value = updatedGame
    }
  }

  function clearError() {
    error.value = null
  }

  function clearCurrentGame() {
    currentGame.value = null
  }

  // ========== Actions CRUD ==========
  async function fetchPublicGames(filters?: GameFilters) {
    isLoading.value = true
    error.value = null

    try {
      const response = await gameApi.listPublic(filters)
      games.value = response.data
      pagination.value = response.meta
    } catch (e: unknown) {
      error.value = getErrorMessage(e, 'Erreur lors du chargement des parties')
      console.error('Error fetching games:', e)
    } finally {
      isLoading.value = false
    }
  }

  async function fetchMyGames() {
    isLoading.value = true
    error.value = null

    try {
      myGames.value = await gameApi.myGames()
    } catch (e: unknown) {
      error.value = getErrorMessage(e, 'Erreur lors du chargement de vos parties')
      console.error('Error fetching my games:', e)
    } finally {
      isLoading.value = false
    }
  }

  async function fetchGameById(id: number) {
    isLoading.value = true
    error.value = null

    try {
      currentGame.value = await gameApi.getById(id)
    } catch (e: unknown) {
      error.value = getErrorMessage(e, 'Partie introuvable')
      console.error('Error fetching game:', e)
      throw e
    } finally {
      isLoading.value = false
    }
  }

  async function createGame(dto: CreateGameDTO) {
    isLoading.value = true
    error.value = null

    try {
      const newGame = await gameApi.create(dto)
      myGames.value.unshift(newGame)
      currentGame.value = newGame
      return newGame
    } catch (e: unknown) {
      error.value = getErrorMessage(e, 'Erreur lors de la création de la partie')
      console.error('Error creating game:', e)
      throw e
    } finally {
      isLoading.value = false
    }
  }

  async function updateGame(id: number, dto: UpdateGameDTO) {
    isLoading.value = true
    error.value = null

    try {
      const updatedGame = await gameApi.update(id, dto)
      updateGameInLists(updatedGame)
      return updatedGame
    } catch (e: unknown) {
      error.value = getErrorMessage(e, 'Erreur lors de la mise à jour')
      console.error('Error updating game:', e)
      throw e
    } finally {
      isLoading.value = false
    }
  }

  async function partialUpdateGame(id: number, dto: Partial<UpdateGameDTO>) {
    isLoading.value = true
    error.value = null

    try {
      const updatedGame = await gameApi.partialUpdate(id, dto)
      updateGameInLists(updatedGame)
      return updatedGame
    } catch (e: unknown) {
      error.value = getErrorMessage(e, 'Erreur lors de la mise à jour partielle')
      console.error('Error partial updating game:', e)
      throw e
    } finally {
      isLoading.value = false
    }
  }

  // ========== Actions métier ==========
  async function joinGame(id: number, password?: string) {
    isLoading.value = true
    error.value = null

    try {
      await gameApi.join(id, password ? { password } : undefined)
      await fetchMyGames()

      if (currentGame.value?.id === id) {
        await fetchGameById(id)
      }
    } catch (e: unknown) {
      error.value = getErrorMessage(e, 'Impossible de rejoindre la partie')
      console.error('Error joining game:', e)
      throw e
    } finally {
      isLoading.value = false
    }
  }

  async function leaveGame(id: number) {
    isLoading.value = true
    error.value = null

    try {
      await gameApi.leave(id)
      myGames.value = myGames.value.filter((g) => g.id !== id)

      if (currentGame.value?.id === id) {
        currentGame.value = null
      }
    } catch (e: unknown) {
      error.value = getErrorMessage(e, 'Erreur en quittant la partie')
      console.error('Error leaving game:', e)
      throw e
    } finally {
      isLoading.value = false
    }
  }

  async function deleteGame(id: number) {
    isLoading.value = true
    error.value = null

    try {
      await gameApi.delete(id)
      myGames.value = myGames.value.filter((g) => g.id !== id)
      games.value = games.value.filter((g) => g.id !== id)

      if (currentGame.value?.id === id) {
        currentGame.value = null
      }
    } catch (e: unknown) {
      error.value = getErrorMessage(e, 'Erreur lors de la suppression')
      console.error('Error deleting game:', e)
      throw e
    } finally {
      isLoading.value = false
    }
  }

  async function startGame(id: number) {
    isLoading.value = true
    error.value = null

    try {
      const updatedGame = await gameApi.start(id)
      updateGameInLists(updatedGame)
      return updatedGame
    } catch (e: unknown) {
      error.value = getErrorMessage(e, 'Impossible de démarrer la partie')
      console.error('Error starting game:', e)
      throw e
    } finally {
      isLoading.value = false
    }
  }

  async function pauseGame(id: number) {
    isLoading.value = true
    error.value = null

    try {
      const updatedGame = await gameApi.pause(id)
      updateGameInLists(updatedGame)
      return updatedGame
    } catch (e: unknown) {
      error.value = getErrorMessage(e, 'Impossible de mettre en pause')
      console.error('Error pausing game:', e)
      throw e
    } finally {
      isLoading.value = false
    }
  }

  async function completeGame(id: number) {
    isLoading.value = true
    error.value = null

    try {
      const updatedGame = await gameApi.complete(id)
      updateGameInLists(updatedGame)
      return updatedGame
    } catch (e: unknown) {
      error.value = getErrorMessage(e, 'Impossible de terminer la partie')
      console.error('Error completing game:', e)
      throw e
    } finally {
      isLoading.value = false
    }
  }

  // ========== Return ==========
  return {
    // State
    games,
    currentGame,
    myGames,
    isLoading,
    error,
    pagination,

    // Getters
    publicGames,
    isGameMaster,
    isPlayerInGame,
    canStartGame,
    canModifyGame,

    // Actions CRUD
    fetchPublicGames,
    fetchMyGames,
    fetchGameById,
    createGame,
    updateGame,
    partialUpdateGame,
    deleteGame,

    // Actions métier
    joinGame,
    leaveGame,
    startGame,
    pauseGame,
    completeGame,

    // Helpers
    clearError,
    clearCurrentGame,
  }
})
