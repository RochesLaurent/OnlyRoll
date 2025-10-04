export enum GameStatus {
  PREPARATION = 'preparation',
  IN_PROGRESS = 'in_progress',
  PAUSED = 'paused',
  COMPLETED = 'completed',
  ARCHIVED = 'archived',
}

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

export interface User {
  id: number
  pseudo: string
  email: string
  avatar?: string
}

export interface GamePlayer {
  id: number
  user: User
  role: PlayerRole
  status: PlayerStatus
  joinedAt: string
  leftAt?: string
}

export interface Game {
  id: number
  name: string
  description?: string
  gameMaster: User
  status: GameStatus
  maxPlayers: number
  currentPlayersCount: number
  isPublic: boolean
  inviteCode: string
  settings?: Record<string, unknown>
  gamePlayers: GamePlayer[]
  createdAt: string
  updatedAt: string
  startedAt?: string
  completedAt?: string
}

export interface CreateGameDTO {
  name: string
  description?: string
  maxPlayers?: number
  isPublic?: boolean
  password?: string
}

export interface UpdateGameDTO {
  name?: string
  description?: string
  maxPlayers?: number
  isPublic?: boolean
  status?: GameStatus
}

export interface JoinGameDTO {
  password?: string
}
