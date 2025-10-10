/**
 * Point d'entrée principal pour tous les services API OnlyRoll
 */

import { authApi } from './authApi';
import { chatApi } from './chatApi';
import { gameApi } from './gameApi';
import { mapApi } from './mapApi';
import { tokenApi } from './tokenApi';

// Export du client HTTP de base
export { apiClient } from './apiClient';
export type { ApiError } from './apiClient';

export { authApi } from './authApi';
export { gameApi } from './gameApi';

export { mapApi } from './mapApi';
export type { CreateMapDTO, UpdateMapDTO } from './mapApi';

export { tokenApi } from './tokenApi';
export type { CreateTokenDTO, UpdateTokenDTO, MoveTokenDTO } from './tokenApi';

export { chatApi } from './chatApi';
export type { SendMessageDTO, RollDiceDTO, GetMessagesOptions } from './chatApi';

/**
 * Objet regroupant tous les services pour un usage simplifié
 */
export const api = {
  auth: authApi,
  games: gameApi,
  maps: mapApi,
  tokens: tokenApi,
  chat: chatApi,
};