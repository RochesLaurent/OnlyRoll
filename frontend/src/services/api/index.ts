/**
 * Point d'entrée principal pour tous les services API OnlyRoll
 */
import { authApi } from './authApi'
import { chatApi } from './chatApi'
import { gameApi } from './gameApi'
import { mapApi } from './mapApi'
import { tokenApi } from './tokenApi'

// Export du client HTTP de base
export { apiClient } from './apiClient'
export type { ApiError } from './apiClient'

// Export des services
export { authApi } from './authApi'
export { gameApi } from './gameApi'
export { mapApi } from './mapApi'
export { tokenApi } from './tokenApi'
export { chatApi } from './chatApi'

export type { CreateMapDTO, UpdateMapDTO } from './mapApi'

export type { GetMessagesOptions } from './chatApi'

export type {
  CreateTokenDTO,
  UpdateTokenDTO,
  MoveTokenDTO,
  SendMessageDTO,
  RollDiceDTO,
} from '@/types/game'

/**
 * Objet regroupant tous les services pour un usage simplifié
 */
export const api = {
  auth: authApi,
  games: gameApi,
  maps: mapApi,
  tokens: tokenApi,
  chat: chatApi,
}
