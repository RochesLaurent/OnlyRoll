import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { Game, CreateGameDTO, UpdateGameDTO, GameFilters, PaginationMeta } from '@/types/game'
import { getErrorMessage } from '@/types/errors'
import { gameApi } from '@/services/api/gameApi'
import { useAuthStore } from './auth'
import { GameStatus } from '@/types/game'
import { logger } from '@/utils/logger'

export const useGameStore = defineStore('game', () => {
  // ========== State ==========
  const games = ref<Game[]>([])
  const currentGame = ref<Game | null>(null)
  const myGames = ref<Game[]>([])
  const isLoading = ref(false)
  const error = ref<string | null>(null)

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
    if (!currentGame.value.gameMaster) return false
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

  // ========== Actions CRUD ==========
  async function fetchPublicGames(filters?: GameFilters) {
    isLoading.value = true
    error.value = null

    try {
      const response = await gameApi.listPublic(filters)
      games.value = response.data

      pagination.value = {
        total: response.meta?.total ?? 0,
        page: response.meta?.page ?? 1,
        limit: response.meta?.limit ?? 12,
        totalPages: response.meta?.totalPages ?? 0,
      }
    } catch (e: unknown) {
      games.value = []
      pagination.value = {
        total: 0,
        page: 1,
        limit: 12,
        totalPages: 0,
      }

      error.value = getErrorMessage(e) || 'Erreur lors du chargement des parties'
      logger.error('Error fetching games:', e)
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
      myGames.value = []

      error.value = getErrorMessage(e) || 'Erreur lors du chargement de vos parties'
      logger.error('Error fetching my games:', e)
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
      error.value = getErrorMessage(e) || 'Partie introuvable'
      logger.error('Error fetching game:', e)
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
      error.value = getErrorMessage(e) || 'Erreur lors de la création de la partie'
      logger.error('Error creating game:', e)
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

      const index = games.value.findIndex((g) => g.id === id)
      if (index !== -1) {
        games.value[index] = updatedGame
      }

      const myGameIndex = myGames.value.findIndex((g) => g.id === id)
      if (myGameIndex !== -1) {
        myGames.value[myGameIndex] = updatedGame
      }

      if (currentGame.value?.id === id) {
        currentGame.value = updatedGame
      }

      return updatedGame
    } catch (e: unknown) {
      error.value = getErrorMessage(e) || 'Erreur lors de la mise à jour de la partie'
      logger.error('Error updating game:', e)
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

      games.value = games.value.filter((g) => g.id !== id)
      myGames.value = myGames.value.filter((g) => g.id !== id)

      if (currentGame.value?.id === id) {
        currentGame.value = null
      }
    } catch (e: unknown) {
      error.value = getErrorMessage(e) || 'Erreur lors de la suppression de la partie'
      logger.error('Error deleting game:', e)
      throw e
    } finally {
      isLoading.value = false
    }
  }

  async function joinGame(inviteCode: string, password?: string) {
    isLoading.value = true
    error.value = null

    try {
      const game = await gameApi.joinByCode(inviteCode, password)
      myGames.value.unshift(game)
      return game
    } catch (e: unknown) {
      error.value = getErrorMessage(e) || 'Impossible de rejoindre la partie'
      logger.error('Error joining game:', e)
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
      error.value = getErrorMessage(e) || 'Impossible de quitter la partie'
      logger.error('Error leaving game:', e)
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

      // Mise à jour
      if (currentGame.value?.id === id) {
        currentGame.value = updatedGame
      }

      const myGameIndex = myGames.value.findIndex((g) => g.id === id)
      if (myGameIndex !== -1) {
        myGames.value[myGameIndex] = updatedGame
      }

      return updatedGame
    } catch (e: unknown) {
      error.value = getErrorMessage(e) || 'Impossible de démarrer la partie'
      logger.error('Error starting game:', e)
      throw e
    } finally {
      isLoading.value = false
    }
  }

  function clearError() {
    error.value = null
  }

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

    // Actions
    fetchPublicGames,
    fetchMyGames,
    fetchGameById,
    createGame,
    updateGame,
    deleteGame,
    joinGame,
    leaveGame,
    startGame,
    clearError,
  }
})
