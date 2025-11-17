/**
 * Tests unitaires pour le composable useMercure
 * 
 * @covers src/composables/useMercure.ts
 */

import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { useMercure } from '@/composables/useMercure'
import { mercureService } from '@/services/mercure'

// Mock du service Mercure
vi.mock('@/services/mercure', () => ({
  mercureService: {
    connect: vi.fn(),
    disconnect: vi.fn(),
    on: vi.fn(),
    off: vi.fn(),
    isConnected: vi.fn(),
    getConnectionState: vi.fn(),
  },
}))

describe('useMercure', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    vi.mocked(mercureService.isConnected).mockReturnValue(false)
    vi.mocked(mercureService.getConnectionState).mockReturnValue('closed')
  })

  afterEach(() => {
    vi.clearAllMocks()
  })

  // ========== CONNECTION ==========

  it('should connect to mercure with gameId', () => {
    const { connect } = useMercure()
    
    connect(42)

    expect(mercureService.connect).toHaveBeenCalledWith(42, undefined)
  })

  it('should connect to mercure with gameId and token', () => {
    const { connect } = useMercure()
    const token = 'jwt-token-123'
    
    connect(42, token)

    expect(mercureService.connect).toHaveBeenCalledWith(42, token)
  })

  it('should update connection state after connect', () => {
    vi.mocked(mercureService.isConnected).mockReturnValue(true)
    vi.mocked(mercureService.getConnectionState).mockReturnValue('open')

    const { connect, isConnected, connectionState } = useMercure()
    
    connect(1)

    expect(isConnected.value).toBe(true)
    expect(connectionState.value).toBe('open')
  })

  it('should disconnect from mercure', () => {
    const { disconnect } = useMercure()
    
    disconnect()

    expect(mercureService.disconnect).toHaveBeenCalled()
  })

  it('should update connection state after disconnect', () => {
    const { disconnect, isConnected, connectionState } = useMercure()
    
    vi.mocked(mercureService.isConnected).mockReturnValue(false)
    vi.mocked(mercureService.getConnectionState).mockReturnValue('closed')
    
    disconnect()

    expect(isConnected.value).toBe(false)
    expect(connectionState.value).toBe('closed')
  })

  // ========== EVENT LISTENERS ==========

  it('should register event listener', () => {
    const { on } = useMercure()
    const callback = vi.fn()
    
    on('chat', callback)

    expect(mercureService.on).toHaveBeenCalledWith('chat', callback)
  })

  it('should register multiple event listeners', () => {
    const { on } = useMercure()
    const chatCallback = vi.fn()
    const tokenCallback = vi.fn()
    
    on('chat', chatCallback)
    on('token', tokenCallback)

    expect(mercureService.on).toHaveBeenCalledWith('chat', chatCallback)
    expect(mercureService.on).toHaveBeenCalledWith('token', tokenCallback)
    expect(mercureService.on).toHaveBeenCalledTimes(2)
  })

  it('should unregister event listener', () => {
    const { on, off } = useMercure()
    const callback = vi.fn()
    
    on('chat', callback)
    off('chat', callback)

    expect(mercureService.off).toHaveBeenCalledWith('chat', callback)
  })

  it('should handle different event types', () => {
    const { on } = useMercure()
    const callbacks = {
      chat: vi.fn(),
      token: vi.fn(),
      map: vi.fn(),
      dice: vi.fn(),
      player: vi.fn(),
      system: vi.fn(),
    }
    
    on('chat', callbacks.chat)
    on('token', callbacks.token)
    on('map', callbacks.map)
    on('dice', callbacks.dice)
    on('player', callbacks.player)
    on('system', callbacks.system)

    expect(mercureService.on).toHaveBeenCalledTimes(6)
  })

  // ========== CLEANUP ==========

  it('should remove all listeners on disconnect', () => {
    const { on, disconnect } = useMercure()
    const callback1 = vi.fn()
    const callback2 = vi.fn()
    
    on('chat', callback1)
    on('token', callback2)
    
    disconnect()

    expect(mercureService.off).toHaveBeenCalledWith('chat', callback1)
    expect(mercureService.off).toHaveBeenCalledWith('token', callback2)
  })

  it('should track registered callbacks internally', () => {
    const { on, off } = useMercure()
    const callback = vi.fn()
    
    on('chat', callback)
    off('chat', callback)
    
    // Second off should still work
    off('chat', callback)

    expect(mercureService.off).toHaveBeenCalledTimes(2)
  })

  it('should handle multiple callbacks for same event type', () => {
    const { on, disconnect } = useMercure()
    const callback1 = vi.fn()
    const callback2 = vi.fn()
    
    on('chat', callback1)
    on('chat', callback2)
    
    disconnect()

    expect(mercureService.off).toHaveBeenCalledWith('chat', callback1)
    expect(mercureService.off).toHaveBeenCalledWith('chat', callback2)
  })

  // ========== CONNECTION STATE ==========

  it('should check connection status', () => {
    vi.mocked(mercureService.isConnected).mockReturnValue(true)
    vi.mocked(mercureService.getConnectionState).mockReturnValue('open')

    const { checkConnection, isConnected } = useMercure()
    
    const result = checkConnection()

    expect(mercureService.isConnected).toHaveBeenCalled()
    expect(mercureService.getConnectionState).toHaveBeenCalled()
    expect(result).toBe(true)
    expect(isConnected.value).toBe(true)
  })

  it('should start with closed connection state', () => {
    const { isConnected, connectionState } = useMercure()

    expect(isConnected.value).toBe(false)
    expect(connectionState.value).toBe('closed')
  })

  it('should handle connecting state', () => {
    vi.mocked(mercureService.isConnected).mockReturnValue(false)
    vi.mocked(mercureService.getConnectionState).mockReturnValue('connecting')

    const { connect, isConnected, connectionState } = useMercure()
    
    connect(1)

    expect(isConnected.value).toBe(false)
    expect(connectionState.value).toBe('connecting')
  })

  // ========== REACTIVE STATE ==========

  it('should expose reactive connection state', () => {
    const { isConnected, connectionState } = useMercure()

    expect(isConnected.value).toBeDefined()
    expect(connectionState.value).toBeDefined()
  })

  it('should update reactive state on check', () => {
    vi.mocked(mercureService.isConnected).mockReturnValue(true)
    vi.mocked(mercureService.getConnectionState).mockReturnValue('open')

    const { checkConnection, isConnected, connectionState } = useMercure()
    
    checkConnection()

    expect(isConnected.value).toBe(true)
    expect(connectionState.value).toBe('open')
  })

  // ========== EDGE CASES ==========

  it('should handle disconnect when no listeners registered', () => {
    const { disconnect } = useMercure()
    
    expect(() => disconnect()).not.toThrow()
    expect(mercureService.disconnect).toHaveBeenCalled()
  })

  it('should handle off without prior on', () => {
    const { off } = useMercure()
    const callback = vi.fn()
    
    expect(() => off('chat', callback)).not.toThrow()
    expect(mercureService.off).toHaveBeenCalledWith('chat', callback)
  })

  it('should allow reconnecting after disconnect', () => {
    const { connect, disconnect } = useMercure()
    
    connect(1)
    disconnect()
    connect(2)

    expect(mercureService.connect).toHaveBeenCalledTimes(2)
    expect(mercureService.disconnect).toHaveBeenCalledTimes(1)
  })

  // ========== CALLBACK HANDLING ==========

  it('should preserve callback reference', () => {
    const { on } = useMercure()
    const callback = vi.fn()
    
    on('chat', callback)

    const callArg = vi.mocked(mercureService.on).mock.calls[0][1]
    expect(callArg).toBe(callback)
  })

  it('should handle typed callbacks', () => {
    const { on } = useMercure()
    
    interface ChatData {
      message: string
      userId: number
    }
    
    const callback = (data: ChatData) => {
      console.log(data.message)
    }
    
    on<ChatData>('chat', callback)

    expect(mercureService.on).toHaveBeenCalledWith('chat', callback)
  })
})