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

// ===========================
// USER
// ===========================

export interface User {
  id: number;
  pseudo: string;
  email: string;
  avatar?: string;
}

// ===========================
// GAME
// ===========================

export interface Game {
  id: number;
  name: string; // Ancien: name, pas title
  title?: string; // Optionnel pour compatibilité
  description?: string;
  gameMaster: User;
  status: GameStatus;
  maxPlayers: number;
  currentPlayersCount: number;
  isPublic: boolean;
  inviteCode: string;
  system?: string; // Pour les nouvelles fonctionnalités (cartes)
  imageUrl?: string;
  settings?: Record<string, any>;
  gamePlayers: GamePlayer[];
  maps?: GameMap[]; // Pour les relations avec les cartes
  createdAt: string;
  updatedAt: string;
  startedAt?: string;
  completedAt?: string;
}

// ===========================
// GAME PLAYER
// ===========================

export interface GamePlayer {
  id: number;
  user: User;
  role: PlayerRole;
  status: PlayerStatus;
  joinedAt: string;
  leftAt?: string;
  
  // Relations
  game?: Game;
}

// ===========================
// GAME MAP
// ===========================

export type GridType = 'square' | 'hex' | 'none';

export interface GameMap {
  id: number;
  name: string;
  description?: string;
  imageUrl?: string;
  gridSize: number;
  gridType: GridType;
  width: number;
  height: number;
  isActive: boolean;
  settings?: Record<string, any>;
  createdAt: string;
  updatedAt?: string;
  
  // Méthodes métier exposées via serialization groups
  dimensions?: string; // Format: "20x20"
  tokensCount?: number;
  totalCells?: number;
  
  // Relations
  game?: Game;
  tokens?: GameToken[];
}

// ===========================
// GAME TOKEN
// ===========================

export type TokenType = 'character' | 'monster' | 'npc' | 'object';
export type TokenLayer = 'background' | 'objects' | 'tokens' | 'effects';

export interface TokenPosition {
  x: number;
  y: number;
}

export interface GameToken {
  id: number;
  name: string;
  type: TokenType;
  imageUrl?: string;
  x: number;
  y: number;
  size: number; // Décimal en PHP, number en TS
  rotation: number; // 0-359 degrés
  isVisible: boolean;
  isLocked: boolean;
  layer: TokenLayer;
  settings?: Record<string, any>;
  createdAt: string;
  updatedAt?: string;
  
  // Méthodes métier exposées
  position?: TokenPosition;
  centerPosition?: { x: number; y: number };
  
  // Relations
  map?: GameMap;
}

// ===========================
// GAME MESSAGE
// ===========================

/**
 * Types de messages possibles
 */
export type MessageType = 'chat' | 'emote' | 'whisper' | 'system' | 'dice_roll';

/**
 * Constantes pour les types de messages (pour éviter les typos)
 */
export const MESSAGE_TYPES = {
  CHAT: 'chat' as const,
  EMOTE: 'emote' as const,
  WHISPER: 'whisper' as const,
  SYSTEM: 'system' as const,
  DICE_ROLL: 'dice_roll' as const,
} as const;

export interface DiceResult {
  config: {
    dice: string; // Ex: "2d6+3"
    [key: string]: any;
  };
  results: number[]; // Résultats individuels des dés
  total: number; // Total du lancer
  timestamp: string;
}

export interface GameMessage {
  id: number;
  type: MessageType;
  content: string;
  diceResult?: DiceResult;
  isInCharacter: boolean;
  createdAt: string;
  
  // Méthodes métier exposées
  diceTotal?: number;
  formattedContent?: string;
  
  // Relations
  user: User;
  game?: Game;
  recipient?: User; // Pour les whispers
}

// ===========================
// TYPES HELPERS
// ===========================

/**
 * Type pour les réponses paginées de l'API
 */
export interface PaginatedResponse<T> {
  data: T[];
  total: number;
  page: number;
  limit: number;
  totalPages: number;
}

/**
 * Type pour les erreurs API
 */
export interface ApiError {
  message: string;
  code?: string;
  errors?: Record<string, string[]>;
}

// ===========================
// DTOs POUR LES GAMES (compatibilité gameApi.ts)
// ===========================

/**
 * DTO pour créer une partie
 */
export interface CreateGameDTO {
  name: string;
  description?: string;
  maxPlayers?: number;
  isPublic?: boolean;
  password?: string;
}

/**
 * DTO pour mettre à jour une partie
 */
export interface UpdateGameDTO {
  name?: string;
  description?: string;
  maxPlayers?: number;
  isPublic?: boolean;
  status?: GameStatus;
}

/**
 * DTO pour rejoindre une partie
 */
export interface JoinGameDTO {
  password?: string;
}

/**
 * Filtres de recherche pour les parties
 */
export interface GameFilters {
  search?: string;
  title?: string;
  gameMaster?: string;
  status?: GameStatus;
  page?: number;
  limit?: number;
}

/**
 * Métadonnées de pagination
 */
export interface PaginationMeta {
  total: number;
  page: number;
  limit: number;
  totalPages: number;
}

/**
 * Réponse paginée pour les parties
 */
export interface PaginatedGamesResponse {
  data: Game[];
  meta: PaginationMeta;
}

/**
 * Type pour les dimensions de carte
 */
export interface MapDimensions {
  width: number;
  height: number;
  gridSize: number;
}

/**
 * Type pour les coordonnées de grille
 */
export interface GridCoordinates {
  x: number;
  y: number;
}

/**
 * Type pour les settings de carte (configuration flexible)
 */
export interface MapSettings {
  backgroundColor?: string;
  gridColor?: string;
  gridOpacity?: number;
  showGrid?: boolean;
  snapToGrid?: boolean;
  fogOfWar?: {
    enabled: boolean;
    revealedCells?: GridCoordinates[];
  };
  [key: string]: any; // Permet d'ajouter d'autres propriétés
}

/**
 * Type pour les settings de token (configuration flexible)
 */
export interface TokenSettings {
  healthPoints?: number;
  maxHealthPoints?: number;
  armorClass?: number;
  initiative?: number;
  conditions?: string[]; // Ex: ['poisoned', 'stunned']
  notes?: string;
  aura?: {
    color: string;
    radius: number;
  };
  [key: string]: any;
}