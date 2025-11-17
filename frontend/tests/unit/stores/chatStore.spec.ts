/**
 * Tests unitaires pour le store de chat
 * 
 * @covers src/stores/chatStore.ts
 */

import { describe, it, expect, beforeEach, vi } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useChatStore } from '@/stores/chatStore'
import { chatApi } from '@/services/api'
import { MessageType } from '@/types/game'
import type { User, GameMessage } from '@/types/game'

vi.mock('@/services/api', () => ({
  chatApi: {
    listRecent: vi.fn(),
    list: vi.fn(),
    listSince: vi.fn(),
    sendChat: vi.fn(),
    sendEmote: vi.fn(),
    sendWhisper: vi.fn(),
    sendSystem: vi.fn(),
    rollDice: vi.fn(),
    delete: vi.fn(),
  },
}))

const mockUser: User = {
  id: 1,
  pseudo: 'TestUser',
  email: 'test@example.com',
}

// Helper pour créer des messages de test
const createMockMessage = (
  overrides: Partial<GameMessage> = {}
): GameMessage => ({
  id: 1,
  type: MessageType.CHAT,
  content: 'Test message',
  isInCharacter: false,
  createdAt: '2024-01-01',
  user: mockUser,
  ...overrides,
})

describe('chatStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  // ========== INITIAL STATE ==========

  it('should initialize with correct default values', () => {
    const store = useChatStore()

    expect(store.messages).toEqual([])
    expect(store.isLoading).toBe(false)
    expect(store.isSending).toBe(false)
    expect(store.error).toBeNull()
    expect(store.hasMore).toBe(true)
  })

  // ========== LOAD MESSAGES ==========

  it('should load recent messages successfully', async () => {
    const store = useChatStore()
    const mockMessages: GameMessage[] = [
      createMockMessage({ id: 1, content: 'Message 1', createdAt: '2024-01-01' }),
      createMockMessage({ id: 2, content: 'Message 2', createdAt: '2024-01-02' }),
    ]

    vi.mocked(chatApi.listRecent).mockResolvedValueOnce(mockMessages)

    await store.loadRecentMessages(1, 50)

    expect(chatApi.listRecent).toHaveBeenCalledWith(1, 50)
    expect(store.messages).toEqual(mockMessages)
    expect(store.isLoading).toBe(false)
  })

  it('should handle load messages with non-array response', async () => {
    const store = useChatStore()

    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    vi.mocked(chatApi.listRecent).mockResolvedValueOnce(null as any)

    await store.loadRecentMessages(1)

    expect(store.messages).toEqual([])
  })

  it('should handle load messages error', async () => {
    const store = useChatStore()

    vi.mocked(chatApi.listRecent).mockRejectedValueOnce({
      message: 'Network error',
    })

    await expect(store.loadRecentMessages(1)).rejects.toBeDefined()
    expect(store.error).toBe('Network error')
    expect(store.messages).toEqual([])
  })

  // ========== PAGINATION ==========

  it('should load more messages', async () => {
    const store = useChatStore()
    store.messages = [
      createMockMessage({ id: 2, content: 'Recent', createdAt: '2024-01-02' }),
    ]

    const olderMessages: GameMessage[] = [
      createMockMessage({ id: 1, content: 'Old', createdAt: '2024-01-01' }),
    ]

    vi.mocked(chatApi.list).mockResolvedValueOnce(olderMessages)

    await store.loadMoreMessages(1, 20)

    expect(store.messages).toHaveLength(2)
    expect(store.messages[0].id).toBe(1)
  })

  it('should not load more when hasMore is false', async () => {
    const store = useChatStore()
    store.hasMore = false

    await store.loadMoreMessages(1)

    expect(chatApi.list).not.toHaveBeenCalled()
  })

  // ========== LOAD SINCE ==========

  it('should load messages since timestamp', async () => {
    const store = useChatStore()
    store.messages = [createMockMessage({ id: 1, content: 'Old', createdAt: '2024-01-01' })]

    const newMessages: GameMessage[] = [
      createMockMessage({ id: 2, content: 'New', createdAt: '2024-01-03' }),
    ]

    vi.mocked(chatApi.listSince).mockResolvedValueOnce(newMessages)

    await store.loadMessagesSince(1, '2024-01-02')

    expect(store.messages).toHaveLength(2)
    expect(store.messages[1].id).toBe(2)
  })

  it('should not duplicate messages when loading since', async () => {
    const store = useChatStore()
    const existingMessage = createMockMessage({ id: 1, content: 'Existing', createdAt: '2024-01-01' })
    store.messages = [existingMessage]

    vi.mocked(chatApi.listSince).mockResolvedValueOnce([existingMessage])

    await store.loadMessagesSince(1, '2024-01-01')

    expect(store.messages).toHaveLength(1)
  })

  // ========== SEND MESSAGES ==========

  it('should send chat message successfully', async () => {
    const store = useChatStore()
    const mockMessage = createMockMessage({
      id: 1,
      type: MessageType.CHAT,
      content: 'Hello'
    })

    vi.mocked(chatApi.sendChat).mockResolvedValueOnce(mockMessage)

    const result = await store.sendMessage(1, 'Hello', false)

    expect(chatApi.sendChat).toHaveBeenCalledWith(1, 'Hello', false)
    expect(store.messages).toContainEqual(mockMessage)
    expect(result).toEqual(mockMessage)
    expect(store.isSending).toBe(false)
  })

  it('should send emote successfully', async () => {
    const store = useChatStore()
    const mockMessage = createMockMessage({
      id: 1,
      type: MessageType.EMOTE,
      content: '*waves*'
    })

    vi.mocked(chatApi.sendEmote).mockResolvedValueOnce(mockMessage)

    await store.sendEmote(1, '*waves*')

    expect(chatApi.sendEmote).toHaveBeenCalledWith(1, '*waves*')
    expect(store.messages).toContainEqual(mockMessage)
  })

  it('should send whisper successfully', async () => {
    const store = useChatStore()
    const mockMessage = createMockMessage({
      id: 1,
      type: MessageType.WHISPER,
      content: 'Secret'
    })

    vi.mocked(chatApi.sendWhisper).mockResolvedValueOnce(mockMessage)

    await store.sendWhisper(1, 2, 'Secret')

    expect(chatApi.sendWhisper).toHaveBeenCalledWith(1, 2, 'Secret')
    expect(store.messages).toContainEqual(mockMessage)
  })

  it('should send system message successfully', async () => {
    const store = useChatStore()
    const mockMessage = createMockMessage({
      id: 1,
      type: MessageType.SYSTEM,
      content: 'System alert'
    })

    vi.mocked(chatApi.sendSystem).mockResolvedValueOnce(mockMessage)

    await store.sendSystemMessage(1, 'System alert')

    expect(chatApi.sendSystem).toHaveBeenCalledWith(1, 'System alert')
    expect(store.messages).toContainEqual(mockMessage)
  })

  // ========== DICE ROLLS ==========

  it('should roll dice successfully', async () => {
    const store = useChatStore()
    const mockMessage = createMockMessage({
      id: 1,
      type: MessageType.DICE_ROLL,
      content: '2d6',
      diceResult: {
        formula: '2d6',
        results: [3, 5],
        total: 8,
        modifier: 0,
      },
    })

    vi.mocked(chatApi.rollDice).mockResolvedValueOnce(mockMessage)

    await store.rollDice(1, '2d6', true)

    expect(chatApi.rollDice).toHaveBeenCalledWith(1, { formula: '2d6', isInCharacter: true })
    expect(store.messages).toContainEqual(mockMessage)
  })

  // ========== DELETE MESSAGE ==========

  it('should delete message successfully', async () => {
    const store = useChatStore()
    store.messages = [
      createMockMessage({ id: 1, content: 'Message 1' }),
      createMockMessage({ id: 2, content: 'Message 2' }),
    ]

    vi.mocked(chatApi.delete).mockResolvedValueOnce(undefined)

    await store.deleteMessage(1)

    expect(chatApi.delete).toHaveBeenCalledWith(1)
    expect(store.messages).toHaveLength(1)
    expect(store.messages[0].id).toBe(2)
  })

  // ========== MERCURE HANDLERS ==========

  it('should handle chat message from Mercure', () => {
    const store = useChatStore()
    const data = {
      messageId: 1,
      type: 'chat' as MessageType,
      content: 'Hello from Mercure',
      userId: 1,
      userName: 'User',
      isIC: false,
      createdAt: '2024-01-01',
    }

    store.handleChatMessage(data)

    expect(store.messages).toHaveLength(1)
    expect(store.messages[0].content).toBe('Hello from Mercure')
  })

  it('should handle dice roll from Mercure', () => {
    const store = useChatStore()
    const data = {
      message: createMockMessage({
        id: 1,
        type: MessageType.DICE_ROLL,
        content: '2d6',
        diceResult: {
          formula: '2d6',
          results: [3, 4],
          total: 7,
          modifier: 0,
        },
      }),
    }

    store.handleDiceRoll(data)

    expect(store.messages).toHaveLength(1)
    expect(store.messages[0].type).toBe(MessageType.DICE_ROLL)
  })

  it('should handle message deleted from Mercure', () => {
    const store = useChatStore()
    store.messages = [createMockMessage({ id: 1, content: 'To delete' })]

    store.handleMessageDeleted({ messageId: 1 })

    expect(store.messages).toHaveLength(0)
  })

  // ========== COMPUTED PROPERTIES ==========

  it('should compute sortedMessages correctly', () => {
    const store = useChatStore()
    store.messages = [
      createMockMessage({ id: 2, createdAt: '2024-01-02' }),
      createMockMessage({ id: 1, createdAt: '2024-01-01' }),
      createMockMessage({ id: 3, createdAt: '2024-01-03' }),
    ]

    const sorted = store.sortedMessages

    expect(sorted[0].id).toBe(1)
    expect(sorted[2].id).toBe(3)
  })

  it('should filter messages by type', () => {
    const store = useChatStore()
    store.messages = [
      createMockMessage({ id: 1, type: MessageType.CHAT }),
      createMockMessage({ id: 2, type: MessageType.SYSTEM }),
      createMockMessage({ id: 3, type: MessageType.CHAT }),
    ]

    const chatMessages = store.messagesByType(MessageType.CHAT)

    expect(chatMessages).toHaveLength(2)
  })

  it('should get chat messages only', () => {
    const store = useChatStore()
    store.messages = [
      createMockMessage({ id: 1, type: MessageType.CHAT }),
      createMockMessage({ id: 2, type: MessageType.SYSTEM }),
      createMockMessage({ id: 3, type: MessageType.EMOTE }),
      createMockMessage({ id: 4, type: MessageType.DICE_ROLL }),
    ]

    expect(store.chatMessages).toHaveLength(2)
  })

  it('should get dice rolls only', () => {
    const store = useChatStore()
    store.messages = [
      createMockMessage({ id: 1, type: MessageType.CHAT }),
      createMockMessage({ id: 2, type: MessageType.DICE_ROLL }),
      createMockMessage({ id: 3, type: MessageType.DICE_ROLL }),
    ]

    expect(store.diceRolls).toHaveLength(2)
  })

  it('should count messages correctly', () => {
    const store = useChatStore()
    store.messages = [
      createMockMessage({ id: 1 }),
      createMockMessage({ id: 2 }),
      createMockMessage({ id: 3 }),
    ]

    expect(store.messagesCount).toBe(3)
  })

  it('should get last message', () => {
    const store = useChatStore()
    store.messages = [
      createMockMessage({ id: 1, createdAt: '2024-01-01' }),
      createMockMessage({ id: 2, createdAt: '2024-01-03' }),
      createMockMessage({ id: 3, createdAt: '2024-01-02' }),
    ]

    expect(store.lastMessage?.id).toBe(2)
  })

  // ========== UTILS ==========

  it('should clear messages', () => {
    const store = useChatStore()
    store.messages = [
      createMockMessage({ id: 1 }),
      createMockMessage({ id: 2 }),
    ]

    store.clearMessages()

    expect(store.messages).toEqual([])
    expect(store.hasMore).toBe(true)
  })

  it('should reset store', () => {
    const store = useChatStore()
    store.messages = [createMockMessage({ id: 1 })]
    store.error = 'Error'
    store.isLoading = true

    store.$reset()

    expect(store.messages).toEqual([])
    expect(store.error).toBeNull()
    expect(store.isLoading).toBe(false)
  })

  // ========== EDGE CASES ==========

  it('should handle non-array messages in sortedMessages', () => {
    const store = useChatStore()
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    store.messages = null as any

    const sorted = store.sortedMessages

    expect(sorted).toEqual([])
    expect(store.messages).toEqual([])
  })

  it('should not add duplicate messages', () => {
    const store = useChatStore()
    const message = createMockMessage({ id: 1, content: 'Test' })

    store.messages = [message]
    store.handleChatMessage({
      messageId: 1,
      content: 'Test',
      type: MessageType.CHAT,
      userId: 1,
      userName: 'User',
      createdAt: '2024-01-01',
    })

    expect(store.messages).toHaveLength(1)
  })
})