/**
 * Types pour les événements WebSocket via Mercure
 * Basés sur les services backend (MapService, TokenService, ChatService)
 */

import type {
  Game,
  GameMap,
  GameToken,
  GameMessage,
  GamePlayer,
  TokenPosition,
  MessageType,
} from './game'

// ===========================
// TYPES D'ÉVÉNEMENTS MERCURE
// ===========================

/**
 * Tous les types d'événements possibles dans le système
 */
export enum MercureEventType {
  // Événements de carte
  MAP_CREATED = 'map.created',
  MAP_UPDATED = 'map.updated',
  MAP_ACTIVATED = 'map.activated',
  MAP_DELETED = 'map.deleted',

  // Événements de token
  TOKEN_CREATED = 'token.created',
  TOKEN_UPDATED = 'token.updated',
  TOKEN_MOVED = 'token.moved',
  TOKEN_DELETED = 'token.deleted',
  TOKEN_VISIBILITY_CHANGED = 'token.visibility_changed',
  TOKEN_LOCKED = 'token.locked',
  TOKEN_UNLOCKED = 'token.unlocked',

  // Événements de message/chat
  MESSAGE_SENT = 'message.sent',
  MESSAGE_DELETED = 'message.deleted',

  // Événements de joueur
  PLAYER_JOINED = 'player.joined',
  PLAYER_LEFT = 'player.left',
  PLAYER_KICKED = 'player.kicked',
  PLAYER_ROLE_CHANGED = 'player.role_changed',

  // Événements de jeu
  GAME_UPDATED = 'game.updated',
  GAME_STARTED = 'game.started',
  GAME_PAUSED = 'game.paused',
  GAME_ENDED = 'game.ended',
}

// ===========================
// DONNÉES BRUTES REÇUES VIA MERCURE
// ===========================

/**
 * Données reçues lors d'un nouveau message via Mercure
 */
export interface MercureChatMessageData {
  messageId: number
  userId: number
  userName: string
  content: string
  type: MessageType
  isIC?: boolean
  recipientId?: number
  recipientName?: string
  createdAt: string
  diceResult?: {
    total: number
    rolls?: number[]
    formula?: string
    modifier?: number
  }
}

/**
 * Données reçues lors d'un lancer de dés via Mercure
 */
export interface MercureDiceRollData {
  message?: GameMessage
  messageId?: number
  userId?: number
  userName?: string
  formula?: string
  result?: number
  results?: number[]
  createdAt?: string
}

/**
 * Données reçues lors de la suppression d'un message via Mercure
 */
export interface MercureMessageDeletedData {
  messageId: number
  gameId?: number
  userId?: number
}

/**
 * Données reçues lors d'un événement de token via Mercure
 */
export interface MercureTokenEventData {
  type: 'created' | 'updated' | 'moved' | 'deleted'
  token: GameToken
  mapId?: number
  gameId?: number
  userId?: number
}

/**
 * Données reçues lors d'un événement de carte via Mercure
 */
export interface MercureMapEventData {
  type: 'activated' | 'updated' | 'deleted' | 'created'
  map: GameMap
  gameId?: number
  userId?: number
}

/**
 * Données reçues lors d'un événement de joueur via Mercure
 */
export interface MercurePlayerEventData {
  userId: number
  userName: string
  action: 'joined' | 'left' | 'disconnected' | 'kicked'
  timestamp: string
  gameId?: number
}

/**
 * Données reçues lors d'un événement système via Mercure
 */
export interface MercureSystemEventData {
  type: string
  message: string
  data?: Record<string, unknown>
  timestamp: string
  gameId?: number
}

// ===========================
// PAYLOADS DES ÉVÉNEMENTS
// ===========================

/**
 * Payload pour les événements de carte
 */
export interface MapEventPayload {
  map: GameMap
  gameId: number
  userId: number
}

/**
 * Payload pour les événements de token
 */
export interface TokenEventPayload {
  token: GameToken
  mapId: number
  gameId: number
  userId: number
}

/**
 * Payload spécifique pour le mouvement de token
 */
export interface TokenMovedPayload extends TokenEventPayload {
  oldPosition: TokenPosition
  newPosition: TokenPosition
}

/**
 * Payload pour les changements de visibilité de token
 */
export interface TokenVisibilityPayload extends TokenEventPayload {
  isVisible: boolean
}

/**
 * Payload pour les événements de message
 */
export interface MessageEventPayload {
  message: GameMessage
  gameId: number
  userId: number
}

/**
 * Payload pour les événements de joueur
 */
export interface PlayerEventPayload {
  player: GamePlayer
  gameId: number
}

/**
 * Payload pour les événements de jeu
 */
export interface GameEventPayload {
  game: Game
  gameId: number
  userId?: number
}

/**
 * Union type de tous les payloads possibles
 */
export type MercureEventPayload =
  | MapEventPayload
  | TokenEventPayload
  | TokenMovedPayload
  | TokenVisibilityPayload
  | MessageEventPayload
  | PlayerEventPayload
  | GameEventPayload

/**
 * Union de tous les types de données Mercure brutes possibles
 */
export type MercureEventData =
  | MercureChatMessageData
  | MercureDiceRollData
  | MercureMessageDeletedData
  | MercureTokenEventData
  | MercureMapEventData
  | MercurePlayerEventData
  | MercureSystemEventData

// ===========================
// STRUCTURE DES ÉVÉNEMENTS MERCURE
// ===========================

/**
 * Structure d'un événement Mercure reçu
 */
export interface MercureEvent<T = MercureEventPayload> {
  type: MercureEventType
  data: T
  timestamp: string
  gameId: number
  userId?: number
}

/**
 * Types d'événements spécifiques avec leurs payloads
 */
export type MapCreatedEvent = MercureEvent<MapEventPayload>
export type MapUpdatedEvent = MercureEvent<MapEventPayload>
export type MapActivatedEvent = MercureEvent<MapEventPayload>
export type MapDeletedEvent = MercureEvent<MapEventPayload>

export type TokenCreatedEvent = MercureEvent<TokenEventPayload>
export type TokenUpdatedEvent = MercureEvent<TokenEventPayload>
export type TokenMovedEvent = MercureEvent<TokenMovedPayload>
export type TokenDeletedEvent = MercureEvent<TokenEventPayload>
export type TokenVisibilityChangedEvent = MercureEvent<TokenVisibilityPayload>

export type MessageSentEvent = MercureEvent<MessageEventPayload>
export type MessageDeletedEvent = MercureEvent<MessageEventPayload>

export type PlayerJoinedEvent = MercureEvent<PlayerEventPayload>
export type PlayerLeftEvent = MercureEvent<PlayerEventPayload>

export type GameUpdatedEvent = MercureEvent<GameEventPayload>

// ===========================
// TOPICS MERCURE
// ===========================

/**
 * Fonction helper pour générer les topics Mercure
 */
export const MercureTopic = {
  /**
   * Topic principal pour tous les événements d'un jeu
   */
  game: (gameId: number): string => `/games/${gameId}/events`,

  /**
   * Topic pour les événements de cartes d'un jeu
   */
  maps: (gameId: number): string => `/games/${gameId}/maps`,

  /**
   * Topic pour les événements d'une carte spécifique
   */
  map: (gameId: number, mapId: number): string => `/games/${gameId}/maps/${mapId}`,

  /**
   * Topic pour les événements de tokens d'une carte
   */
  tokens: (gameId: number, mapId: number): string => `/games/${gameId}/maps/${mapId}/tokens`,

  /**
   * Topic pour les événements de chat d'un jeu
   */
  chat: (gameId: number): string => `/games/${gameId}/chat`,

  /**
   * Topic pour les chuchotements (messages privés) entre deux joueurs
   */
  whisper: (gameId: number, userId: number): string => `/games/${gameId}/whispers/${userId}`,

  /**
   * Topic pour les événements de joueurs d'un jeu
   */
  players: (gameId: number): string => `/games/${gameId}/players`,
}

// ===========================
// ÉTAT DE CONNEXION MERCURE
// ===========================

/**
 * États possibles de la connexion Mercure
 */
export enum MercureConnectionState {
  DISCONNECTED = 'disconnected',
  CONNECTING = 'connecting',
  CONNECTED = 'connected',
  RECONNECTING = 'reconnecting',
  ERROR = 'error',
}

/**
 * Configuration de connexion Mercure
 */
export interface MercureConfig {
  url: string
  topics: string[]
  jwt?: string
  withCredentials?: boolean
  reconnectInterval?: number // en ms, par défaut 3000
  maxReconnectAttempts?: number // par défaut 10
}

/**
 * État de la connexion Mercure
 */
export interface MercureConnectionStatus {
  state: MercureConnectionState
  lastConnected?: Date
  reconnectAttempts: number
  error?: string
}

// ===========================
// HANDLERS D'ÉVÉNEMENTS
// ===========================

/**
 * Type pour les handlers d'événements Mercure
 */
export type MercureEventHandler<T = MercureEventPayload> = (
  event: MercureEvent<T>
) => void | Promise<void>

/**
 * Map des handlers par type d'événement
 */
export type MercureEventHandlers = {
  [K in MercureEventType]?: MercureEventHandler
}

/**
 * Options pour l'écoute d'événements
 */
export interface MercureListenOptions {
  /**
   * Filtrer les événements par gameId
   */
  gameId?: number

  /**
   * Filtrer les événements par userId (utile pour les whispers)
   */
  userId?: number

  /**
   * Handler appelé pour tous les événements (avant les handlers spécifiques)
   */
  onAnyEvent?: MercureEventHandler

  /**
   * Handler appelé en cas d'erreur
   */
  onError?: (error: Error) => void

  /**
   * Handler appelé lors du changement d'état de connexion
   */
  onConnectionChange?: (status: MercureConnectionStatus) => void
}

// ===========================
// INTERFACE DU CLIENT MERCURE
// ===========================

/**
 * Interface du client Mercure (sera implémentée dans le composable)
 */
export interface MercureClient {
  /**
   * État actuel de la connexion
   */
  status: MercureConnectionStatus

  /**
   * Se connecter au hub Mercure
   */
  connect(config: MercureConfig): Promise<void>

  /**
   * Se déconnecter du hub Mercure
   */
  disconnect(): void

  /**
   * S'abonner à un ou plusieurs topics
   */
  subscribe(topics: string | string[]): void

  /**
   * Se désabonner d'un ou plusieurs topics
   */
  unsubscribe(topics: string | string[]): void

  /**
   * Enregistrer un handler pour un type d'événement
   */
  on<T = MercureEventPayload>(eventType: MercureEventType, handler: MercureEventHandler<T>): void

  /**
   * Supprimer un handler pour un type d'événement
   */
  off(eventType: MercureEventType, handler?: MercureEventHandler): void

  /**
   * Enregistrer un handler pour tous les événements
   */
  onAny(handler: MercureEventHandler): void

  /**
   * Supprimer le handler global
   */
  offAny(): void
}

// ===========================
// TYPES UTILITAIRES
// ===========================

/**
 * Type guard pour vérifier le type d'un événement
 */
export function isMercureEvent<T = MercureEventPayload>(data: unknown): data is MercureEvent<T> {
  if (!data || typeof data !== 'object') {
    return false
  }

  return 'type' in data && 'data' in data && 'timestamp' in data && 'gameId' in data
}

/**
 * Type guard pour vérifier un type d'événement spécifique
 */
export function isEventOfType<T extends MercureEventPayload = MercureEventPayload>(
  event: MercureEvent,
  type: MercureEventType
): event is MercureEvent<T> {
  return event.type === type
}
