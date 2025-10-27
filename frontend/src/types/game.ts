/**
 * Types pour les entités du jeu OnlyRoll
 * Basés sur les entités Symfony avec serialization groups
 */

// ===========================
// ENUMS
// ===========================

export enum PlayerRole {
  GAME_MASTER = 'game_master',
  PLAYER = 'player',
  SPECTATOR = 'spectator',
}

export enum PlayerStatus {
  PENDING = 'pending',
  ACTIVE = 'active',
  INACTIVE = 'inactive',
  KICKED = 'kicked',
  LEFT = 'left',
}

export enum GameStatus {
  PREPARATION = 'preparation',
  IN_PROGRESS = 'in_progress',
  PAUSED = 'paused',
  COMPLETED = 'completed',
  ARCHIVED = 'archived',
}

/**
 * Types de tokens disponibles
 * Correspondent exactement aux valeurs acceptées par le backend
 */
export enum TokenType {
  CHARACTER = 'character',
  MONSTER = 'monster',
  NPC = 'npc',
  OBJECT = 'object',
}

/**
 * Layers (couches) pour l'affichage des tokens
 * Définit l'ordre de superposition sur la carte
 */
export enum LayerType {
  BACKGROUND = 'background',
  OBJECTS = 'objects',
  TOKENS = 'tokens',
  EFFECTS = 'effects',
}

/**
 * Types de grille pour les cartes
 */
export enum GridType {
  SQUARE = 'square',
  HEX = 'hex',
  NONE = 'none',
}

/**
 * Types de messages dans le chat
 */
export enum MessageType {
  CHAT = 'chat',
  EMOTE = 'emote',
  WHISPER = 'whisper',
  SYSTEM = 'system',
  DICE_ROLL = 'dice_roll',
}

// ===========================
// USER
// ===========================

export interface User {
  id: number
  pseudo: string
  email: string
  avatar?: string
}

// ===========================
// GAME
// ===========================

export interface Game {
  id: number
  name: string
  title?: string
  description?: string
  gameMaster: User
  status: GameStatus
  maxPlayers: number
  currentPlayersCount: number
  isPublic: boolean
  inviteCode: string
  system?: string
  imageUrl?: string
  settings?: Record<string, unknown>
  gamePlayers: GamePlayer[]
  maps?: GameMap[]
  createdAt: string
  updatedAt: string
  startedAt?: string
  completedAt?: string
}

// ===========================
// GAME PLAYER
// ===========================

export interface GamePlayer {
  id: number
  user: User
  role: PlayerRole
  status: PlayerStatus
  joinedAt: string
  leftAt?: string

  // Relations
  game?: Game
}

// ===========================
// GAME MAP
// ===========================

export interface GameMap {
  id: number
  name: string
  description?: string
  imageUrl?: string
  gridSize: number
  gridType: GridType
  width: number
  height: number
  isActive: boolean
  settings?: Record<string, unknown>
  createdAt: string
  updatedAt?: string

  // Méthodes métier exposées via serialization groups
  dimensions?: string
  tokensCount?: number
  totalCells?: number

  // Relations
  game?: Game
  tokens?: GameToken[]
}

// ===========================
// GAME TOKEN
// ===========================

export interface TokenPosition {
  x: number
  y: number
}

export interface GameToken {
  id: number
  name: string
  type: TokenType
  imageUrl?: string
  x: number
  y: number
  size: number
  rotation: number
  isVisible: boolean
  isLocked: boolean
  layer: LayerType
  settings?: Record<string, unknown>
  createdAt: string
  updatedAt?: string

  // Méthodes métier exposées
  position?: TokenPosition
  centerPosition?: { x: number; y: number }

  // Relations
  map?: GameMap
}

// ===========================
// GAME MESSAGE
// ===========================

/**
 * Constantes pour les types de messages (pour éviter les typos)
 */
export const MESSAGE_TYPES = {
  CHAT: 'chat' as const,
  EMOTE: 'emote' as const,
  WHISPER: 'whisper' as const,
  SYSTEM: 'system' as const,
  DICE_ROLL: 'dice_roll' as const,
} as const

/**
 * Structure du résultat de dés
 */
export interface DiceResult {
  formula: string
  results: number[]
  total: number
  modifier: number
}

/**
 * Structure temporaire pour migration des anciennes données de dés
 * @deprecated Sera supprimé après migration complète
 */
export interface LegacyDiceResult {
  config?: { dice?: string }
  results?: number[]
  total?: number
}

export interface GameMessage {
  id: number
  type: MessageType
  content: string
  diceResult?: DiceResult
  isInCharacter: boolean
  createdAt: string

  // Méthodes métier exposées
  diceTotal?: number
  formattedContent?: string

  // Relations
  user: User
  game?: Game
  recipient?: User // Pour les whispers
}

// ===========================
// TYPES HELPERS
// ===========================

/**
 * Type pour les réponses paginées de l'API
 */
export interface PaginatedResponse<T> {
  data: T[]
  total: number
  page: number
  limit: number
  totalPages: number
}

/**
 * Type pour les erreurs API
 */
export interface ApiError {
  message: string
  code?: string
  errors?: Record<string, string[]>
}

// ===========================
// DTOs POUR LES GAMES
// ===========================

/**
 * DTO pour créer une partie
 */
export interface CreateGameDTO {
  name: string
  description?: string
  maxPlayers?: number
  isPublic?: boolean
  password?: string
}

/**
 * DTO pour mettre à jour une partie
 */
export interface UpdateGameDTO {
  name?: string
  description?: string
  maxPlayers?: number
  isPublic?: boolean
  status?: GameStatus
}

/**
 * DTO pour rejoindre une partie
 */
export interface JoinGameDTO {
  password?: string
}

/**
 * Filtres de recherche pour les parties
 */
export interface GameFilters {
  search?: string
  title?: string
  gameMaster?: string
  status?: GameStatus
  page?: number
  limit?: number
}

/**
 * Métadonnées de pagination
 */
export interface PaginationMeta {
  total: number
  page: number
  limit: number
  totalPages: number
}

/**
 * Réponse paginée pour les parties
 */
export interface PaginatedGamesResponse {
  data: Game[]
  meta: PaginationMeta
}

// ===========================
// DTOs POUR LES MAPS (CARTES)
// ===========================

/**
 * DTO pour créer une carte
 * Correspond à CreateMapDTO.php du backend
 */
export interface CreateMapDTO {
  name: string
  description?: string
  imageUrl?: string
  gridSize?: number // Default: 50
  gridType?: GridType // Default: 'square'
  width?: number // Default: 20
  height?: number // Default: 20
  settings?: Record<string, unknown>
}

/**
 * DTO pour mettre à jour une carte
 * Correspond à UpdateMapDTO.php du backend
 */
export interface UpdateMapDTO {
  name?: string
  description?: string
  imageUrl?: string
  gridSize?: number
  gridType?: GridType
  width?: number
  height?: number
  settings?: Record<string, unknown>
}

// ===========================
// DTOs POUR LES TOKENS
// ===========================

/**
 * DTO pour créer un token
 * IMPORTANT: Correspond EXACTEMENT à CreateTokenDTO.php du backend Symfony
 *
 * Champs obligatoires:
 * - name: string (1-250 caractères)
 * - type: TokenType (valeurs: 'character', 'monster', 'npc', 'object')
 * - x: number (position sur la grille, >= 0)
 * - y: number (position sur la grille, >= 0)
 *
 * Champs optionnels avec valeurs par défaut:
 * - imageUrl: string | undefined
 * - size: number (default: 1.0, range: 0.1-10.0)
 * - rotation: number (default: 0, range: 0-359)
 * - isVisible: boolean (default: true)
 * - isLocked: boolean (default: false)
 * - layer: LayerType (default: 'tokens')
 * - settings: Record<string, unknown> | undefined
 */
export interface CreateTokenDTO {
  // Champs obligatoires
  name: string
  type: TokenType
  x: number
  y: number

  // Champs optionnels
  imageUrl?: string
  size?: number
  rotation?: number
  isVisible?: boolean
  isLocked?: boolean
  layer?: LayerType
  settings?: Record<string, unknown>
}

/**
 * DTO pour mettre à jour un token
 * Tous les champs sont optionnels (PATCH)
 */
export interface UpdateTokenDTO {
  name?: string
  type?: TokenType
  imageUrl?: string
  x?: number
  y?: number
  size?: number
  rotation?: number
  isVisible?: boolean
  isLocked?: boolean
  layer?: LayerType
  settings?: Record<string, unknown>
}

/**
 * DTO pour déplacer un token
 * Correspond à MoveTokenDTO.php du backend
 */
export interface MoveTokenDTO {
  x: number
  y: number
  rotation?: number
}

// ===========================
// DTOs POUR LES MESSAGES (CHAT)
// ===========================

/**
 * DTO pour envoyer un message dans le chat
 * Correspond à SendMessageDTO.php du backend
 */
export interface SendMessageDTO {
  type: MessageType
  content: string // 1-2000 caractères
  recipientId?: number // Pour les whispers (messages privés)
  isInCharacter?: boolean // True = message "dans la peau du personnage"
  metadata?: Record<string, unknown>
}

/**
 * DTO pour lancer des dés
 */
export interface RollDiceDTO {
  formula: string // Ex: "1d20+5", "3d6", "2d10-1"
  reason?: string // Raison du lancer (ex: "Attaque", "Perception")
  isInCharacter?: boolean // Lancer au nom du personnage ou du joueur
  isVisible?: boolean // Visible par tous ou secret (MJ uniquement)
}

// ===========================
// TYPES ADDITIONNELS UTILES
// ===========================

/**
 * Type pour les dimensions de carte
 */
export interface MapDimensions {
  width: number
  height: number
  gridSize: number
}

/**
 * Type pour les coordonnées de grille
 */
export interface GridCoordinates {
  x: number
  y: number
}

/**
 * Type pour les settings de carte (configuration flexible)
 */
export interface MapSettings {
  backgroundColor?: string
  gridColor?: string
  gridOpacity?: number
  showGrid?: boolean
  snapToGrid?: boolean
  fogOfWar?: {
    enabled: boolean
    revealedCells?: GridCoordinates[]
  }
  [key: string]: unknown
}

/**
 * Type pour les settings de token (configuration flexible)
 */
export interface TokenSettings {
  healthPoints?: number
  maxHealthPoints?: number
  armorClass?: number
  initiative?: number
  conditions?: string[] // Ex: ['poisoned', 'stunned']
  notes?: string
  aura?: {
    color: string
    radius: number
  }
  [key: string]: unknown
}

/**
 * Résultat d'un lancer de dés (réponse du backend)
 */
export interface DiceRollResult {
  formula: string
  result: number
  details: string
  rolls: number[]
}

/**
 * Statistiques du chat (réponse du backend)
 */
export interface ChatStats {
  totalMessages: number
  byType: Record<MessageType, number>
  byUser: Record<number, number>
}
