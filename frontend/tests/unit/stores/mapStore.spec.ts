/**
 * Tests unitaires pour le store de cartes et tokens
 *
 * @covers src/stores/mapStore.ts
 */

import { describe, it, expect, beforeEach, vi } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useMapStore } from '@/stores/mapStore'
import { mapApi, tokenApi } from '@/services/api'
import { TokenType, LayerType, GridType } from '@/types/game'
import type { GameMap, GameToken, User, Game } from '@/types/game'

vi.mock('@/services/api', () => ({
  mapApi: {
    listByGame: vi.fn(),
    getActive: vi.fn(),
    getById: vi.fn(),
    activate: vi.fn(),
  },
  tokenApi: {
    listVisible: vi.fn(),
    create: vi.fn(),
    move: vi.fn(),
    partialUpdate: vi.fn(),
    delete: vi.fn(),
    hide: vi.fn(),
    show: vi.fn(),
    lock: vi.fn(),
    unlock: vi.fn(),
  },
}))

vi.mock('@/utils/logger', () => ({
  logger: {
    log: vi.fn(),
    error: vi.fn(),
    warn: vi.fn(),
    debug: vi.fn(),
    info: vi.fn(),
  },
}))

const mockUser: User = {
  id: 1,
  pseudo: 'TestUser',
  email: 'test@example.com',
}

const mockGame: Game = {
  id: 1,
  name: 'Test Game',
  gameMaster: mockUser,
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  status: 'in_progress' as any,
  maxPlayers: 5,
  currentPlayersCount: 2,
  isPublic: true,
  inviteCode: 'ABC123',
  createdAt: '2024-01-01',
  updatedAt: '2024-01-01',
  gamePlayers: [],
}

const createMockMap = (overrides: Partial<GameMap> = {}): GameMap => ({
  id: 1,
  name: 'Test Map',
  width: 1920,
  height: 1080,
  gridSize: 50,
  gridType: GridType.SQUARE,
  imageUrl: '/maps/test.jpg',
  isActive: true,
  game: mockGame,
  createdAt: '2024-01-01',
  updatedAt: '2024-01-01',
  ...overrides,
})

const createMockToken = (overrides: Partial<GameToken> = {}): GameToken => ({
  id: 1,
  name: 'Test Token',
  type: TokenType.CHARACTER,
  x: 100,
  y: 100,
  size: 1.0,
  rotation: 0,
  isVisible: true,
  isLocked: false,
  layer: LayerType.TOKENS,
  map: createMockMap(),
  createdAt: '2024-01-01',
  updatedAt: '2024-01-01',
  ...overrides,
})

describe('mapStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  // ========== INITIAL STATE ==========

  it('should initialize with correct default values', () => {
    const store = useMapStore()

    expect(store.activeMap).toBeNull()
    expect(store.allMaps).toEqual([])
    expect(store.tokens).toEqual([])
    expect(store.currentGameId).toBeNull()
    expect(store.isLoading).toBe(false)
    expect(store.error).toBeNull()
  })

  // ========== GETTERS ==========

  it('should return visible tokens only', () => {
    const store = useMapStore()
    store.tokens = [
      createMockToken({ id: 1, isVisible: true }),
      createMockToken({ id: 2, isVisible: false }),
      createMockToken({ id: 3, isVisible: true }),
    ]

    const visible = store.visibleTokens

    expect(visible).toHaveLength(2)
    expect(visible[0].id).toBe(1)
    expect(visible[1].id).toBe(3)
  })

  it('should filter tokens by type', () => {
    const store = useMapStore()
    store.tokens = [
      createMockToken({ id: 1, type: TokenType.CHARACTER }),
      createMockToken({ id: 2, type: TokenType.MONSTER }),
      createMockToken({ id: 3, type: TokenType.CHARACTER }),
    ]

    const characters = store.tokensByType(TokenType.CHARACTER)

    expect(characters).toHaveLength(2)
    expect(characters[0].type).toBe(TokenType.CHARACTER)
  })

  it('should get token by id', () => {
    const store = useMapStore()
    const token = createMockToken({ id: 42, name: 'Special Token' })
    store.tokens = [token]

    const found = store.getTokenById(42)

    expect(found).toEqual(token)
    expect(found?.name).toBe('Special Token')
  })

  it('should check if active map exists', () => {
    const store = useMapStore()

    expect(store.hasActiveMap).toBe(false)

    store.activeMap = createMockMap()

    expect(store.hasActiveMap).toBe(true)
  })

  it('should count tokens', () => {
    const store = useMapStore()

    expect(store.tokensCount).toBe(0)

    store.tokens = [
      createMockToken({ id: 1 }),
      createMockToken({ id: 2 }),
      createMockToken({ id: 3 }),
    ]

    expect(store.tokensCount).toBe(3)
  })

  it('should return map dimensions', () => {
    const store = useMapStore()

    expect(store.mapDimensions).toBeNull()

    store.activeMap = createMockMap({
      width: 2000,
      height: 1500,
      gridSize: 100,
    })

    expect(store.mapDimensions).toEqual({
      width: 2000,
      height: 1500,
      gridSize: 100,
    })
  })

  // ========== LOAD MAPS ==========

  it('should load game maps successfully', async () => {
    const store = useMapStore()
    const mockMaps: GameMap[] = [
      createMockMap({ id: 1, name: 'Map 1' }),
      createMockMap({ id: 2, name: 'Map 2' }),
    ]

    vi.mocked(mapApi.listByGame).mockResolvedValueOnce(mockMaps)

    await store.loadGameMaps(1)

    expect(mapApi.listByGame).toHaveBeenCalledWith(1)
    expect(store.allMaps).toEqual(mockMaps)
    expect(store.isLoading).toBe(false)
  })

  it('should handle load game maps error', async () => {
    const store = useMapStore()

    vi.mocked(mapApi.listByGame).mockRejectedValueOnce({
      message: 'Network error',
    })

    await expect(store.loadGameMaps(1)).rejects.toBeDefined()
    expect(store.error).toBe('Network error')
  })

  // ========== LOAD ACTIVE MAP ==========

  it('should load active map with tokens', async () => {
    const store = useMapStore()
    const mockMap = createMockMap({ id: 1 })
    const mockTokens: GameToken[] = [
      createMockToken({ id: 1 }),
      createMockToken({ id: 2 }),
    ]

    vi.mocked(mapApi.getActive).mockResolvedValueOnce(mockMap)
    vi.mocked(tokenApi.listVisible).mockResolvedValueOnce(mockTokens)

    await store.loadActiveMap(1)

    expect(mapApi.getActive).toHaveBeenCalledWith(1)
    expect(tokenApi.listVisible).toHaveBeenCalledWith(1, 1)
    expect(store.activeMap).toEqual(mockMap)
    expect(store.tokens).toEqual(mockTokens)
    expect(store.currentGameId).toBe(1)
  })

  it('should handle no active map', async () => {
    const store = useMapStore()

    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    vi.mocked(mapApi.getActive).mockResolvedValueOnce(null as any)

    await store.loadActiveMap(1)

    expect(store.activeMap).toBeNull()
    expect(store.tokens).toEqual([])
  })

  it('should handle load active map error', async () => {
    const store = useMapStore()

    vi.mocked(mapApi.getActive).mockRejectedValueOnce({
      message: 'Map not found',
    })

    await expect(store.loadActiveMap(1)).rejects.toBeDefined()
    expect(store.activeMap).toBeNull()
    expect(store.tokens).toEqual([])
    expect(store.error).toBe('Map not found')
  })

  // ========== LOAD SPECIFIC MAP ==========

  it('should load specific map by id', async () => {
    const store = useMapStore()
    const mockMap = createMockMap({ id: 42 })
    const mockTokens: GameToken[] = [createMockToken()]

    vi.mocked(mapApi.getById).mockResolvedValueOnce(mockMap)
    vi.mocked(tokenApi.listVisible).mockResolvedValueOnce(mockTokens)

    await store.loadMap(1, 42)

    expect(mapApi.getById).toHaveBeenCalledWith(42)
    expect(store.activeMap).toEqual(mockMap)
    expect(store.tokens).toEqual(mockTokens)
  })

  it('should handle load map error', async () => {
    const store = useMapStore()

    vi.mocked(mapApi.getById).mockRejectedValueOnce({
      message: 'Not found',
    })

    await expect(store.loadMap(1, 42)).rejects.toBeDefined()
    expect(store.activeMap).toBeNull()
    expect(store.error).toBe('Not found')
  })

  // ========== ACTIVATE MAP ==========

  it('should activate map successfully', async () => {
    const store = useMapStore()
    const mockMap = createMockMap({ id: 3, isActive: true })
    const mockTokens: GameToken[] = []

    vi.mocked(mapApi.activate).mockResolvedValueOnce(mockMap)
    vi.mocked(tokenApi.listVisible).mockResolvedValueOnce(mockTokens)

    await store.activateMap(1, 3)

    expect(mapApi.activate).toHaveBeenCalledWith(1, 3)
    expect(store.activeMap).toEqual(mockMap)
  })

  it('should handle activate map error', async () => {
    const store = useMapStore()

    vi.mocked(mapApi.activate).mockRejectedValueOnce({
      message: 'Cannot activate',
    })

    await expect(store.activateMap(1, 3)).rejects.toBeDefined()
    expect(store.error).toBe('Cannot activate')
  })

  // ========== LOAD TOKENS ==========

  it('should load map tokens successfully', async () => {
    const store = useMapStore()
    store.currentGameId = 1
    const mockTokens: GameToken[] = [
      createMockToken({ id: 1 }),
      createMockToken({ id: 2 }),
    ]

    vi.mocked(tokenApi.listVisible).mockResolvedValueOnce(mockTokens)

    await store.loadMapTokens(42)

    expect(tokenApi.listVisible).toHaveBeenCalledWith(1, 42)
    expect(store.tokens).toEqual(mockTokens)
  })

  it('should throw error if gameId not set', async () => {
    const store = useMapStore()

    await expect(store.loadMapTokens(42)).rejects.toThrow(
      'GameId not set. Call loadActiveMap first.'
    )
  })

  it('should handle invalid mapId', async () => {
    const store = useMapStore()
    store.currentGameId = 1

    await store.loadMapTokens(0)

    expect(store.tokens).toEqual([])
    expect(tokenApi.listVisible).not.toHaveBeenCalled()
  })

  it('should handle load tokens error gracefully', async () => {
    const store = useMapStore()
    store.currentGameId = 1

    vi.mocked(tokenApi.listVisible).mockRejectedValueOnce({
      message: 'Token error',
    })

    await store.loadMapTokens(42)

    expect(store.tokens).toEqual([])
    expect(store.error).toBe('Token error')
  })

  // ========== CREATE TOKEN ==========

  it('should create token successfully', async () => {
    const store = useMapStore()
    store.currentGameId = 1
    const newToken = createMockToken({ id: 99, name: 'New Token' })

    vi.mocked(tokenApi.create).mockResolvedValueOnce(newToken)

    const result = await store.createToken(1, {
      name: 'New Token',
      type: TokenType.CHARACTER,
      x: 100,
      y: 200,
    })

    expect(tokenApi.create).toHaveBeenCalled()
    expect(result).toEqual(newToken)
    expect(store.tokens).toContainEqual(newToken)
    expect(store.isLoading).toBe(false)
  })

  it('should throw error when creating token without gameId', async () => {
    const store = useMapStore()

    await expect(
      store.createToken(1, {
        name: 'Token',
        type: TokenType.CHARACTER,
        x: 0,
        y: 0,
      })
    ).rejects.toThrow('GameId not set')
  })

  it('should handle create token error', async () => {
    const store = useMapStore()
    store.currentGameId = 1

    vi.mocked(tokenApi.create).mockRejectedValueOnce({
      message: 'Creation failed',
    })

    await expect(
      store.createToken(1, {
        name: 'Token',
        type: TokenType.CHARACTER,
        x: 0,
        y: 0,
      })
    ).rejects.toBeDefined()
    expect(store.error).toBe('Creation failed')
  })

  // ========== MOVE TOKEN ==========

  it('should move token successfully', async () => {
    const store = useMapStore()
    store.currentGameId = 1
    store.activeMap = createMockMap({ id: 1 })
    const token = createMockToken({ id: 1 })
    store.tokens = [token]

    const movedToken = createMockToken({ id: 1, x: 200, y: 300 })
    vi.mocked(tokenApi.move).mockResolvedValueOnce(movedToken)

    await store.moveToken(1, 200, 300)

    expect(tokenApi.move).toHaveBeenCalledWith(1, 1, 1, { x: 200, y: 300 })
    expect(store.tokens[0].x).toBe(200)
    expect(store.tokens[0].y).toBe(300)
  })

  it('should move token with rotation', async () => {
    const store = useMapStore()
    store.currentGameId = 1
    store.activeMap = createMockMap({ id: 1 })

    const movedToken = createMockToken({ id: 1, x: 200, y: 300, rotation: 90 })
    vi.mocked(tokenApi.move).mockResolvedValueOnce(movedToken)

    await store.moveToken(1, 200, 300, 90)

    expect(tokenApi.move).toHaveBeenCalledWith(1, 1, 1, {
      x: 200,
      y: 300,
      rotation: 90,
    })
  })

  it('should throw error when moving token without gameId or map', async () => {
    const store = useMapStore()

    await expect(store.moveToken(1, 100, 100)).rejects.toThrow(
      'GameId or Map not set'
    )
  })

  // ========== UPDATE TOKEN ==========

  it('should update token successfully', async () => {
    const store = useMapStore()
    store.currentGameId = 1
    store.activeMap = createMockMap({ id: 1 })
    store.tokens = [createMockToken({ id: 1, name: 'Old Name' })]

    const updatedToken = createMockToken({ id: 1, name: 'New Name' })
    vi.mocked(tokenApi.partialUpdate).mockResolvedValueOnce(updatedToken)

    await store.updateToken(1, { name: 'New Name' })

    expect(tokenApi.partialUpdate).toHaveBeenCalledWith(1, 1, 1, {
      name: 'New Name',
    })
    expect(store.tokens[0].name).toBe('New Name')
  })

  it('should handle update token error', async () => {
    const store = useMapStore()
    store.currentGameId = 1
    store.activeMap = createMockMap({ id: 1 })

    vi.mocked(tokenApi.partialUpdate).mockRejectedValueOnce({
      message: 'Update failed',
    })

    await expect(store.updateToken(1, { name: 'Test' })).rejects.toBeDefined()
    expect(store.error).toBe('Update failed')
  })

  // ========== DELETE TOKEN ==========

  it('should delete token successfully', async () => {
    const store = useMapStore()
    store.currentGameId = 1
    store.activeMap = createMockMap({ id: 1 })
    store.tokens = [
      createMockToken({ id: 1 }),
      createMockToken({ id: 2 }),
    ]

    vi.mocked(tokenApi.delete).mockResolvedValueOnce(undefined)

    await store.deleteToken(1)

    expect(tokenApi.delete).toHaveBeenCalledWith(1, 1, 1)
    expect(store.tokens).toHaveLength(1)
    expect(store.tokens[0].id).toBe(2)
  })

  it('should handle delete token error', async () => {
    const store = useMapStore()
    store.currentGameId = 1
    store.activeMap = createMockMap({ id: 1 })

    vi.mocked(tokenApi.delete).mockRejectedValueOnce({
      message: 'Delete failed',
    })

    await expect(store.deleteToken(1)).rejects.toBeDefined()
    expect(store.error).toBe('Delete failed')
  })

  // ========== TOGGLE VISIBILITY ==========

  it('should hide visible token', async () => {
    const store = useMapStore()
    store.currentGameId = 1
    store.activeMap = createMockMap({ id: 1 })
    store.tokens = [createMockToken({ id: 1, isVisible: true })]

    const updatedToken = createMockToken({ id: 1, isVisible: false })
    vi.mocked(tokenApi.hide).mockResolvedValueOnce(updatedToken)

    await store.toggleTokenVisibility(1)

    expect(tokenApi.hide).toHaveBeenCalledWith(1, 1, 1)
    expect(store.tokens[0].isVisible).toBe(false)
  })

  it('should show hidden token', async () => {
    const store = useMapStore()
    store.currentGameId = 1
    store.activeMap = createMockMap({ id: 1 })
    store.tokens = [createMockToken({ id: 1, isVisible: false })]

    const updatedToken = createMockToken({ id: 1, isVisible: true })
    vi.mocked(tokenApi.show).mockResolvedValueOnce(updatedToken)

    await store.toggleTokenVisibility(1)

    expect(tokenApi.show).toHaveBeenCalledWith(1, 1, 1)
    expect(store.tokens[0].isVisible).toBe(true)
  })

  // ========== TOGGLE LOCK ==========

  it('should unlock locked token', async () => {
    const store = useMapStore()
    store.currentGameId = 1
    store.activeMap = createMockMap({ id: 1 })
    store.tokens = [createMockToken({ id: 1, isLocked: true })]

    const updatedToken = createMockToken({ id: 1, isLocked: false })
    vi.mocked(tokenApi.unlock).mockResolvedValueOnce(updatedToken)

    await store.toggleTokenLock(1)

    expect(tokenApi.unlock).toHaveBeenCalledWith(1, 1, 1)
    expect(store.tokens[0].isLocked).toBe(false)
  })

  it('should lock unlocked token', async () => {
    const store = useMapStore()
    store.currentGameId = 1
    store.activeMap = createMockMap({ id: 1 })
    store.tokens = [createMockToken({ id: 1, isLocked: false })]

    const updatedToken = createMockToken({ id: 1, isLocked: true })
    vi.mocked(tokenApi.lock).mockResolvedValueOnce(updatedToken)

    await store.toggleTokenLock(1)

    expect(tokenApi.lock).toHaveBeenCalledWith(1, 1, 1)
    expect(store.tokens[0].isLocked).toBe(true)
  })

  // ========== MERCURE EVENTS ==========

  it('should handle token created event', () => {
    const store = useMapStore()
    const newToken = createMockToken({ id: 99 })

    store.handleTokenEvent({
      type: 'created',
      token: newToken,
    })

    expect(store.tokens).toHaveLength(1)
    expect(store.tokens[0]).toEqual(newToken)
  })

  it('should handle token updated event', () => {
    const store = useMapStore()
    store.tokens = [createMockToken({ id: 1, name: 'Old' })]

    store.handleTokenEvent({
      type: 'updated',
      token: createMockToken({ id: 1, name: 'Updated' }),
    })

    expect(store.tokens[0].name).toBe('Updated')
  })

  it('should handle token moved event', () => {
    const store = useMapStore()
    store.tokens = [createMockToken({ id: 1, x: 0, y: 0 })]

    store.handleTokenEvent({
      type: 'moved',
      token: createMockToken({ id: 1, x: 100, y: 200 }),
    })

    expect(store.tokens[0].x).toBe(100)
    expect(store.tokens[0].y).toBe(200)
  })

  it('should handle token deleted event', () => {
    const store = useMapStore()
    store.tokens = [
      createMockToken({ id: 1 }),
      createMockToken({ id: 2 }),
    ]

    store.handleTokenEvent({
      type: 'deleted',
      token: createMockToken({ id: 1 }),
    })

    expect(store.tokens).toHaveLength(1)
    expect(store.tokens[0].id).toBe(2)
  })

  it('should handle map activated event', () => {
    const store = useMapStore()
    const newMap = createMockMap({ id: 42, name: 'Activated Map' })

    store.handleMapEvent({
      type: 'activated',
      map: newMap,
    })

    expect(store.activeMap).toEqual(newMap)
  })

  it('should handle map updated event', () => {
    const store = useMapStore()
    store.activeMap = createMockMap({ id: 1, name: 'Old Name' })

    store.handleMapEvent({
      type: 'updated',
      map: createMockMap({ id: 1, name: 'New Name' }),
    })

    expect(store.activeMap?.name).toBe('New Name')
  })

  it('should not update map if different id', () => {
    const store = useMapStore()
    store.activeMap = createMockMap({ id: 1, name: 'Map 1' })

    store.handleMapEvent({
      type: 'updated',
      map: createMockMap({ id: 2, name: 'Map 2' }),
    })

    expect(store.activeMap?.name).toBe('Map 1')
  })

  // ========== UTILS ==========

  it('should reset store', () => {
    const store = useMapStore()
    store.activeMap = createMockMap()
    store.allMaps = [createMockMap()]
    store.tokens = [createMockToken()]
    store.currentGameId = 1
    store.isLoading = true
    store.error = 'Error'

    store.$reset()

    expect(store.activeMap).toBeNull()
    expect(store.allMaps).toEqual([])
    expect(store.tokens).toEqual([])
    expect(store.currentGameId).toBeNull()
    expect(store.isLoading).toBe(false)
    expect(store.error).toBeNull()
  })

  // ========== EDGE CASES ==========

  it('should not add duplicate tokens', () => {
    const store = useMapStore()
    const token = createMockToken({ id: 1 })
    store.tokens = [token]

    store.handleTokenEvent({
      type: 'created',
      token,
    })

    expect(store.tokens).toHaveLength(1)
  })

  it('should add token to list if not found during update', () => {
    const store = useMapStore()
    const token = createMockToken({ id: 99 })

    store.handleTokenEvent({
      type: 'updated',
      token,
    })

    expect(store.tokens).toHaveLength(1)
    expect(store.tokens[0]).toEqual(token)
  })
})
