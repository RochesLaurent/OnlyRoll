/**
 * Tests unitaires pour le store de gestion des parties
 * 
 * @covers src/stores/game.ts
 */

import { describe, it, expect, beforeEach, vi } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { reactive } from 'vue'
import { useGameStore } from '@/stores/game'
import { useAuthStore } from '@/stores/auth'
import { gameApi } from '@/services/api/gameApi'
import { GameStatus } from '@/types/game'

vi.mock('@/services/api/gameApi', () => ({
  gameApi: {
    listPublic: vi.fn(),
    myGames: vi.fn(),
    getById: vi.fn(),
    create: vi.fn(),
    update: vi.fn(),
    delete: vi.fn(),
    joinByCode: vi.fn(),
    leave: vi.fn(),
    start: vi.fn(),
  },
}))

vi.mock('@/stores/auth', () => ({
  useAuthStore: vi.fn(),
}))

describe('gameStore', () => {
  let mockAuthStore: any

  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()

    mockAuthStore = reactive({
      user: { id: 1, email: 'test@test.com', pseudo: 'TestUser' },
    })
    vi.mocked(useAuthStore).mockReturnValue(mockAuthStore)
  })

  // ========== INITIAL STATE ==========

  it('should initialize with correct default values', () => {
    const store = useGameStore()

    expect(store.games).toEqual([])
    expect(store.currentGame).toBeNull()
    expect(store.myGames).toEqual([])
    expect(store.isLoading).toBe(false)
    expect(store.error).toBeNull()
    expect(store.pagination).toEqual({
      total: 0,
      page: 1,
      limit: 12,
      totalPages: 0,
    })
  })

  // ========== FETCH PUBLIC GAMES ==========

  it('should fetch public games successfully', async () => {
    const store = useGameStore()
    const mockGames = [
      { id: 1, name: 'Game 1', isPublic: true },
      { id: 2, name: 'Game 2', isPublic: true },
    ]

    vi.mocked(gameApi.listPublic).mockResolvedValueOnce({
      data: mockGames,
      meta: { total: 2, page: 1, limit: 12, totalPages: 1 },
    })

    await store.fetchPublicGames()

    expect(gameApi.listPublic).toHaveBeenCalled()
    expect(store.games).toEqual(mockGames)
    expect(store.pagination.total).toBe(2)
    expect(store.isLoading).toBe(false)
    expect(store.error).toBeNull()
  })

  it('should handle fetch public games error', async () => {
    const store = useGameStore()

    vi.mocked(gameApi.listPublic).mockRejectedValueOnce({
      response: { data: { error: 'API Error' } },
    })

    await store.fetchPublicGames()

    expect(store.games).toEqual([])
    expect(store.error).toBe('API Error')
  })

  // ========== FETCH MY GAMES ==========

  it('should fetch my games successfully', async () => {
    const store = useGameStore()
    const mockGames = [{ id: 1, name: 'My Game' }]

    vi.mocked(gameApi.myGames).mockResolvedValueOnce(mockGames)

    await store.fetchMyGames()

    expect(gameApi.myGames).toHaveBeenCalled()
    expect(store.myGames).toEqual(mockGames)
  })

  it('should handle fetch my games error', async () => {
    const store = useGameStore()

    vi.mocked(gameApi.myGames).mockRejectedValueOnce({
      response: { data: { error: 'Unauthorized' } },
    })

    await store.fetchMyGames()

    expect(store.myGames).toEqual([])
    expect(store.error).toBe('Unauthorized')
  })

  // ========== FETCH GAME BY ID ==========

  it('should fetch game by id successfully', async () => {
    const store = useGameStore()
    const mockGame = { id: 1, name: 'Test Game' }

    vi.mocked(gameApi.getById).mockResolvedValueOnce(mockGame)

    await store.fetchGameById(1)

    expect(gameApi.getById).toHaveBeenCalledWith(1)
    expect(store.currentGame).toEqual(mockGame)
  })

  it('should handle fetch game by id error', async () => {
    const store = useGameStore()

    vi.mocked(gameApi.getById).mockRejectedValueOnce({
      response: { data: { error: 'Not found' } },
    })

    await expect(store.fetchGameById(999)).rejects.toBeDefined()
    expect(store.error).toBe('Not found')
  })

  // ========== CREATE GAME ==========

  it('should create game successfully', async () => {
    const store = useGameStore()
    const dto = { name: 'New Game', maxPlayers: 5 }
    const mockGame = { id: 1, ...dto }

    vi.mocked(gameApi.create).mockResolvedValueOnce(mockGame)

    const result = await store.createGame(dto)

    expect(gameApi.create).toHaveBeenCalledWith(dto)
    expect(store.myGames).toContainEqual(mockGame)
    expect(store.currentGame).toEqual(mockGame)
    expect(result).toEqual(mockGame)
  })

  it('should handle create game error', async () => {
    const store = useGameStore()
    const dto = { name: 'New Game' }

    vi.mocked(gameApi.create).mockRejectedValueOnce({
      response: { data: { error: 'Creation failed' } },
    })

    await expect(store.createGame(dto)).rejects.toBeDefined()
    expect(store.error).toBe('Creation failed')
  })

  // ========== UPDATE GAME ==========

  it('should update game successfully', async () => {
    const store = useGameStore()
    store.games = [{ id: 1, name: 'Old Name' }]
    store.myGames = [{ id: 1, name: 'Old Name' }]
    store.currentGame = { id: 1, name: 'Old Name' }

    const updatedGame = { id: 1, name: 'New Name' }
    vi.mocked(gameApi.update).mockResolvedValueOnce(updatedGame)

    await store.updateGame(1, { name: 'New Name' })

    expect(gameApi.update).toHaveBeenCalledWith(1, { name: 'New Name' })
    expect(store.games[0]).toEqual(updatedGame)
    expect(store.myGames[0]).toEqual(updatedGame)
    expect(store.currentGame).toEqual(updatedGame)
  })

  // ========== DELETE GAME ==========

  it('should delete game successfully', async () => {
    const store = useGameStore()
    store.games = [{ id: 1, name: 'Game 1' }, { id: 2, name: 'Game 2' }]
    store.myGames = [{ id: 1, name: 'Game 1' }]
    store.currentGame = { id: 1, name: 'Game 1' }

    vi.mocked(gameApi.delete).mockResolvedValueOnce(undefined)

    await store.deleteGame(1)

    expect(gameApi.delete).toHaveBeenCalledWith(1)
    expect(store.games).toHaveLength(1)
    expect(store.myGames).toHaveLength(0)
    expect(store.currentGame).toBeNull()
  })

  // ========== JOIN GAME ==========

  it('should join game successfully', async () => {
    const store = useGameStore()
    const mockGame = { id: 1, name: 'Joined Game' }

    vi.mocked(gameApi.joinByCode).mockResolvedValueOnce(mockGame)

    const result = await store.joinGame('INVITE123', 'password')

    expect(gameApi.joinByCode).toHaveBeenCalledWith('INVITE123', 'password')
    expect(store.myGames).toContainEqual(mockGame)
    expect(result).toEqual(mockGame)
  })

  it('should handle join game error', async () => {
    const store = useGameStore()

    vi.mocked(gameApi.joinByCode).mockRejectedValueOnce({
      response: { data: { error: 'Invalid code' } },
    })

    await expect(store.joinGame('WRONG')).rejects.toBeDefined()
    expect(store.error).toBe('Invalid code')
  })

  // ========== LEAVE GAME ==========

  it('should leave game successfully', async () => {
    const store = useGameStore()
    store.myGames = [{ id: 1, name: 'Game 1' }]
    store.currentGame = { id: 1, name: 'Game 1' }

    vi.mocked(gameApi.leave).mockResolvedValueOnce(undefined)

    await store.leaveGame(1)

    expect(gameApi.leave).toHaveBeenCalledWith(1)
    expect(store.myGames).toHaveLength(0)
    expect(store.currentGame).toBeNull()
  })

  // ========== START GAME ==========

  it('should start game successfully', async () => {
    const store = useGameStore()
    const mockGame = { id: 1, name: 'Game', status: GameStatus.IN_PROGRESS }

    store.currentGame = { id: 1, name: 'Game', status: GameStatus.PREPARATION }
    store.myGames = [{ id: 1, name: 'Game', status: GameStatus.PREPARATION }]

    vi.mocked(gameApi.start).mockResolvedValueOnce(mockGame)

    await store.startGame(1)

    expect(gameApi.start).toHaveBeenCalledWith(1)
    expect(store.currentGame?.status).toBe(GameStatus.IN_PROGRESS)
    expect(store.myGames[0].status).toBe(GameStatus.IN_PROGRESS)
  })

  // ========== GETTERS ==========

  it('should compute publicGames correctly', () => {
    const store = useGameStore()
    store.games = [
      { id: 1, isPublic: true },
      { id: 2, isPublic: false },
      { id: 3, isPublic: true },
    ]

    expect(store.publicGames).toHaveLength(2)
  })

  it('should compute isGameMaster correctly', () => {
    const store = useGameStore()
    store.currentGame = {
      id: 1,
      gameMaster: { id: 1 },
    }

    expect(store.isGameMaster).toBe(true)

    store.currentGame.gameMaster = { id: 2 }
    expect(store.isGameMaster).toBe(false)
  })

  it('should compute isPlayerInGame correctly', () => {
    const store = useGameStore()
    store.currentGame = {
      id: 1,
      name: 'Test Game',
      gameMaster: mockAuthStore.user,
      status: GameStatus.IN_PROGRESS,
      maxPlayers: 5,
      currentPlayersCount: 2,
      isPublic: true,
      inviteCode: 'ABC123',
      createdAt: '2024-01-01',
      updatedAt: '2024-01-01',
      gamePlayers: [
        { id: 1, user: { id: 1, email: 'test@test.com', pseudo: 'TestUser' }, role: 'player', status: 'active' } as any,
        { id: 2, user: { id: 2, email: 'test2@test.com', pseudo: 'TestUser2' }, role: 'player', status: 'active' } as any,
      ],
    } as any

    expect(store.isPlayerInGame).toBe(true)

    mockAuthStore.user = { id: 3, email: 'test3@test.com', pseudo: 'TestUser3' }
    expect(store.isPlayerInGame).toBe(false)
  })

  it('should compute canStartGame correctly', () => {
    const store = useGameStore()
    store.currentGame = {
      id: 1,
      status: GameStatus.PREPARATION,
      currentPlayersCount: 2,
      gameMaster: { id: 1 },
    }

    expect(store.canStartGame).toBe(true)

    store.currentGame.currentPlayersCount = 0
    expect(store.canStartGame).toBe(false)
  })

  it('should compute canModifyGame correctly', () => {
    const store = useGameStore()
    store.currentGame = {
      id: 1,
      status: GameStatus.PREPARATION,
      gameMaster: { id: 1 },
    }

    expect(store.canModifyGame).toBe(true)

    store.currentGame.status = GameStatus.IN_PROGRESS
    expect(store.canModifyGame).toBe(false)
  })

  // ========== ERROR HANDLING ==========

  it('should clear error', () => {
    const store = useGameStore()
    store.error = 'Test error'

    store.clearError()

    expect(store.error).toBeNull()
  })
})