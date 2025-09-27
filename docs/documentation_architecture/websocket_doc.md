# Spécifications WebSocket OnlyRoll

## Vue d'ensemble

Le système WebSocket d'OnlyRoll assure la communication temps réel pour toutes les interactions de jeu : chat, lancers de dés, déplacements de tokens, gestion des joueurs et synchronisation d'état.

### Technologies
- **Serveur** : Socket.io avec PHP ReactPHP/Ratchet
- **Client** : Socket.io-client 4.x
- **Transport** : WebSocket avec fallback HTTP polling
- **Authentification** : JWT tokens dans headers
- **Sérialisation** : JSON avec validation des payloads

## Architecture des Connexions

### Structure des Rooms
```typescript
// Namespace et rooms organisation
namespace: '/game'
rooms: {
  'game:{gameId}': 'Partie spécifique',
  'game:{gameId}:gm': 'Canal privé MJ/co-MJ',
  'user:{userId}': 'Notifications personnelles'
}

// Exemple de room pour partie ID 42
rooms: [
  'game:42',        // Tous les participants
  'game:42:gm',     // Seulement MJ et co-MJ
  'user:123',       // Notifications pour user 123
  'user:456'        // Notifications pour user 456
]
```

### Gestion des Sessions
```typescript
interface SocketSession {
  socketId: string
  userId: number
  gameId?: number
  userRoles: string[]
  gameRole?: 'player' | 'gm' | 'co_gm' | 'spectator'
  joinedAt: Date
  lastActivity: Date
  isAuthenticated: boolean
}
```

## Events Client → Serveur

### Authentification et Connexion

#### `auth:authenticate`
```typescript
// Client envoie
{
  token: string // JWT token
}

// Serveur répond
{
  success: boolean
  user: {
    id: number
    pseudo: string
    roles: string[]
  }
  error?: string
}
```

#### `game:join`
```typescript
// Client envoie
{
  gameId: number
}

// Serveur répond
{
  success: boolean
  game: {
    id: number
    name: string
    status: GameStatus
    players: Player[]
    currentMap?: GameMap
  }
  playerRole: 'player' | 'gm' | 'co_gm' | 'spectator'
  error?: string
}
```

#### `game:leave`
```typescript
// Client envoie
{
  gameId: number
}

// Serveur répond
{
  success: boolean
  message: string
}
```

### Système de Chat

#### `chat:message`
```typescript
// Client envoie
{
  gameId: number
  content: string
  type: 'chat' | 'emote' | 'system'
  isIC: boolean // In Character
  characterId?: number
}

// Serveur répond (broadcast à tous)
{
  messageId: number
  gameId: number
  user: {
    id: number
    pseudo: string
    avatar?: string
  }
  character?: {
    id: number
    name: string
    avatar?: string
  }
  content: string
  type: 'chat' | 'emote' | 'system'
  isIC: boolean
  timestamp: string // ISO 8601
}
```

#### `chat:whisper`
```typescript
// Client envoie
{
  gameId: number
  targetUserId: number
  content: string
}

// Serveur envoie seulement aux participants du whisper
{
  messageId: number
  gameId: number
  fromUser: UserSummary
  toUser: UserSummary
  content: string
  type: 'whisper'
  timestamp: string
}
```

### Système de Dés

#### `dice:roll`
```typescript
// Client envoie
{
  gameId: number
  expression: string // "2d6+3", "1d20", "2d20kh1"
  type?: 'attack' | 'damage' | 'saving_throw' | 'ability_check'
  context?: string // Description du lancer
  isPrivate: boolean // Visible seulement par le MJ
  characterId?: number
}

// Serveur répond (broadcast selon isPrivate)
{
  rollId: number
  gameId: number
  user: UserSummary
  character?: CharacterSummary
  expression: string
  result: {
    rolls: number[] // Résultats individuels des dés
    modifiers: number[] // Modificateurs appliqués
    total: number
    critical?: 'success' | 'failure' // Pour d20
  }
  type?: string
  context?: string
  isPrivate: boolean
  timestamp: string
}
```

#### `dice:roll_advantage`
```typescript
// Client envoie
{
  gameId: number
  expression: string // Doit être un d20
  advantageType: 'advantage' | 'disadvantage'
  context?: string
  characterId?: number
}

// Serveur répond
{
  rollId: number
  gameId: number
  user: UserSummary
  character?: CharacterSummary
  expression: string
  result: {
    roll1: number
    roll2: number
    kept: number // Le résultat retenu
    modifier: number
    total: number
    advantageType: 'advantage' | 'disadvantage'
  }
  context?: string
  timestamp: string
}
```

### Gestion de Carte et Tokens

#### `map:token:move`
```typescript
// Client envoie
{
  gameId: number
  mapId: number
  tokenId: number
  newPosition: {
    x: number
    y: number
  }
  path?: Array<{x: number, y: number}> // Chemin parcouru
}

// Serveur répond (broadcast)
{
  gameId: number
  mapId: number
  tokenId: number
  user: UserSummary
  oldPosition: Position
  newPosition: Position
  path?: Position[]
  distance?: number // En cases/mètres
  timestamp: string
}
```

#### `map:token:create`
```typescript
// Client envoie (seulement MJ)
{
  gameId: number
  mapId: number
  token: {
    name: string
    type: 'character' | 'monster' | 'npc' | 'object'
    position: {x: number, y: number}
    size: number // Multiplicateur (1.0 = 1 case)
    imageUrl?: string
    isVisible: boolean
    characterId?: number
    monsterId?: number
  }
}

// Serveur répond (broadcast)
{
  gameId: number
  mapId: number
  token: Token // Token complet avec ID généré
  createdBy: UserSummary
  timestamp: string
}
```

#### `map:token:update`
```typescript
// Client envoie (seulement MJ ou propriétaire)
{
  gameId: number
  mapId: number
  tokenId: number
  updates: {
    name?: string
    size?: number
    rotation?: number
    isVisible?: boolean
    isLocked?: boolean
    conditions?: string[] // États appliqués
    hp?: {current: number, max: number, temp: number}
  }
}

// Serveur répond (broadcast)
{
  gameId: number
  mapId: number
  tokenId: number
  updates: TokenUpdates
  updatedBy: UserSummary
  timestamp: string
}
```

#### `map:token:delete`
```typescript
// Client envoie (seulement MJ)
{
  gameId: number
  mapId: number
  tokenId: number
}

// Serveur répond (broadcast)
{
  gameId: number
  mapId: number
  tokenId: number
  deletedBy: UserSummary
  timestamp: string
}
```

### Système de Combat

#### `combat:start`
```typescript
// Client envoie (seulement MJ)
{
  gameId: number
  mapId?: number
  participants: Array<{
    type: 'character' | 'monster'
    entityId: number
    initiative: number
    hp: {current: number, max: number}
    ac: number
  }>
}

// Serveur répond (broadcast)
{
  gameId: number
  combatId: number
  participants: CombatParticipant[]
  currentTurn: number
  round: number
  status: 'preparation' | 'active'
  startedBy: UserSummary
  timestamp: string
}
```

#### `combat:next_turn`
```typescript
// Client envoie (seulement MJ)
{
  gameId: number
  combatId: number
}

// Serveur répond (broadcast)
{
  gameId: number
  combatId: number
  previousTurn: number
  currentTurn: number
  round: number
  activeParticipant: CombatParticipant
  timestamp: string
}
```

#### `combat:action`
```typescript
// Client envoie
{
  gameId: number
  combatId: number
  participantId: number
  action: {
    type: 'attack' | 'spell' | 'move' | 'dash' | 'dodge' | 'help'
    targetId?: number
    description: string
    diceRoll?: DiceResult
  }
}

// Serveur répond (broadcast)
{
  gameId: number
  combatId: number
  round: number
  actor: CombatParticipant
  action: CombatAction
  timestamp: string
}
```

## Events Serveur → Client

### Notifications et État

#### `game:player_joined`
```typescript
{
  gameId: number
  player: {
    id: number
    pseudo: string
    avatar?: string
    role: 'player' | 'gm' | 'co_gm' | 'spectator'
    character?: CharacterSummary
  }
  timestamp: string
}
```

#### `game:player_left`
```typescript
{
  gameId: number
  playerId: number
  playerPseudo: string
  reason?: 'left' | 'disconnected' | 'kicked' | 'banned'
  timestamp: string
}
```

#### `game:status_changed`
```typescript
{
  gameId: number
  oldStatus: GameStatus
  newStatus: GameStatus
  changedBy: UserSummary
  timestamp: string
}
```

#### `game:settings_updated`
```typescript
{
  gameId: number
  settings: GameSettings
  updatedBy: UserSummary
  timestamp: string
}
```

### Erreurs et Validation

#### `error`
```typescript
{
  code: string // 'UNAUTHORIZED', 'GAME_NOT_FOUND', 'INVALID_DATA'
  message: string
  details?: any
  requestId?: string // Pour traçabilité
}
```

#### `validation_error`
```typescript
{
  field: string
  message: string
  value: any
}
```

## Gestion des Rooms

### Système de Rooms Hiérarchique

```typescript
class RoomManager {
  // Rejoindre une partie
  async joinGameRoom(socket: Socket, gameId: number) {
    const gameRoom = `game:${gameId}`
    await socket.join(gameRoom)
    
    // MJ rejoint aussi la room privée
    if (this.isGameMaster(socket.userId, gameId)) {
      await socket.join(`game:${gameId}:gm`)
    }
    
    // Room personnelle pour notifications
    await socket.join(`user:${socket.userId}`)
    
    this.trackUserInGame(socket.userId, gameId)
  }
  
  // Quitter une partie
  async leaveGameRoom(socket: Socket, gameId: number) {
    await socket.leave(`game:${gameId}`)
    await socket.leave(`game:${gameId}:gm`)
    
    this.untrackUserFromGame(socket.userId, gameId)
  }
  
  // Broadcast avec ciblage précis
  broadcastToGame(gameId: number, event: string, data: any, excludeSocket?: Socket) {
    this.io.to(`game:${gameId}`).except(excludeSocket?.id || '').emit(event, data)
  }
  
  broadcastToGameMasters(gameId: number, event: string, data: any) {
    this.io.to(`game:${gameId}:gm`).emit(event, data)
  }
  
  sendToUser(userId: number, event: string, data: any) {
    this.io.to(`user:${userId}`).emit(event, data)
  }
}
```

### Auto-nettoyage des Rooms
```typescript
// Nettoyage automatique des rooms vides
setInterval(() => {
  const emptyRooms = this.getEmptyRooms()
  emptyRooms.forEach(room => {
    this.io.of('/game').adapter.del(room)
  })
}, 60000) // Chaque minute
```

## Stratégie de Reconnection

### Configuration Client
```typescript
// services/websocket/reconnectionStrategy.ts
export class ReconnectionStrategy {
  private socket: Socket | null = null
  private reconnectAttempts = 0
  private maxReconnectAttempts = 10
  private reconnectInterval = 1000 // Start at 1s
  private maxReconnectInterval = 30000 // Max 30s
  private backoffMultiplier = 1.5
  
  connect(token: string) {
    this.socket = io(WS_URL, {
      auth: { token },
      transports: ['websocket', 'polling'],
      
      // Reconnection settings
      reconnection: true,
      reconnectionAttempts: this.maxReconnectAttempts,
      reconnectionDelay: this.reconnectInterval,
      reconnectionDelayMax: this.maxReconnectInterval,
      
      // Connection timeout
      timeout: 10000,
      
      // Force new connection
      forceNew: false
    })
    
    this.setupReconnectionHandlers()
  }
  
  private setupReconnectionHandlers() {
    this.socket?.on('connect', () => {
      console.log('Connected to WebSocket')
      this.reconnectAttempts = 0
      this.reconnectInterval = 1000
      
      // Ré-authentification après reconnection
      this.reAuthenticate()
      
      // Rejoindre les rooms précédentes
      this.rejoinPreviousRooms()
    })
    
    this.socket?.on('disconnect', (reason) => {
      console.log('Disconnected:', reason)
      
      if (reason === 'io server disconnect') {
        // Déconnection côté serveur, reconnection manuelle
        this.manualReconnect()
      }
      // Sinon, reconnection automatique par Socket.io
    })
    
    this.socket?.on('reconnect', (attemptNumber) => {
      console.log(`Reconnected after ${attemptNumber} attempts`)
      this.onReconnected()
    })
    
    this.socket?.on('reconnect_attempt', (attemptNumber) => {
      console.log(`Reconnection attempt ${attemptNumber}`)
    })
    
    this.socket?.on('reconnect_failed', () => {
      console.error('Reconnection failed after maximum attempts')
      this.onReconnectionFailed()
    })
    
    this.socket?.on('connect_error', (error) => {
      console.error('Connection error:', error.message)
      this.handleConnectionError(error)
    })
  }
  
  private manualReconnect() {
    setTimeout(() => {
      if (this.reconnectAttempts < this.maxReconnectAttempts) {
        this.reconnectAttempts++
        this.socket?.connect()
        
        // Exponential backoff
        this.reconnectInterval = Math.min(
          this.reconnectInterval * this.backoffMultiplier,
          this.maxReconnectInterval
        )
      } else {
        this.onReconnectionFailed()
      }
    }, this.reconnectInterval)
  }
  
  private async reAuthenticate() {
    const authStore = useAuthStore()
    if (authStore.token) {
      this.socket?.emit('auth:authenticate', { 
        token: authStore.token 
      })
    }
  }
  
  private rejoinPreviousRooms() {
    const gameStore = useGameStore()
    if (gameStore.currentGame) {
      this.socket?.emit('game:join', { 
        gameId: gameStore.currentGame.id 
      })
    }
  }
  
  private onReconnected() {
    // Notification UI
    const uiStore = useUIStore()
    uiStore.showNotification({
      type: 'success',
      message: 'Connexion rétablie',
      duration: 3000
    })
    
    // Synchroniser l'état
    this.syncGameState()
  }
  
  private onReconnectionFailed() {
    const uiStore = useUIStore()
    uiStore.showNotification({
      type: 'error',
      message: 'Impossible de se reconnecter. Veuillez recharger la page.',
      persistent: true
    })
  }
  
  private async syncGameState() {
    // Re-synchroniser l'état du jeu après reconnection
    const gameStore = useGameStore()
    if (gameStore.currentGame) {
      await gameStore.refreshGameState()
    }
  }
}
```

### Gestion des Messages Perdus
```typescript
// Queue des messages en attente durant déconnection
class MessageQueue {
  private queue: QueuedMessage[] = []
  private isOnline = true
  
  add(event: string, data: any) {
    if (!this.isOnline) {
      this.queue.push({
        id: Date.now() + Math.random(),
        event,
        data,
        timestamp: new Date().toISOString(),
        retries: 0
      })
    }
  }
  
  async flush() {
    const messages = [...this.queue]
    this.queue = []
    
    for (const message of messages) {
      try {
        await this.sendMessage(message)
      } catch (error) {
        // Re-queue si échec
        if (message.retries < 3) {
          message.retries++
          this.queue.push(message)
        }
      }
    }
  }
  
  setOnlineStatus(online: boolean) {
    this.isOnline = online
    if (online) {
      this.flush()
    }
  }
}
```

### Indicateurs de Connexion
```typescript
// Composable pour status de connexion
export const useConnectionStatus = () => {
  const isConnected = ref(false)
  const isReconnecting = ref(false)
  const reconnectAttempts = ref(0)
  
  const { socket } = useWebSocket()
  
  watchEffect(() => {
    if (socket.value) {
      socket.value.on('connect', () => {
        isConnected.value = true
        isReconnecting.value = false
        reconnectAttempts.value = 0
      })
      
      socket.value.on('disconnect', () => {
        isConnected.value = false
      })
      
      socket.value.on('reconnect_attempt', () => {
        isReconnecting.value = true
        reconnectAttempts.value++
      })
    }
  })
  
  return {
    isConnected: readonly(isConnected),
    isReconnecting: readonly(isReconnecting),
    reconnectAttempts: readonly(reconnectAttempts)
  }
}
```

## Sécurité et Validation

### Authentification des Events
```typescript
// Middleware de validation côté serveur
const authenticateSocket = (socket: Socket, next: Function) => {
  const token = socket.handshake.auth.token
  
  try {
    const decoded = jwt.verify(token, JWT_SECRET)
    socket.userId = decoded.userId
    socket.userRoles = decoded.roles
    next()
  } catch (error) {
    next(new Error('Authentication failed'))
  }
}

// Middleware de validation par event
const validateGameAccess = async (socket: Socket, gameId: number) => {
  const hasAccess = await checkUserGameAccess(socket.userId, gameId)
  if (!hasAccess) {
    throw new Error('Unauthorized game access')
  }
}
```

### Rate Limiting
```typescript
const rateLimiter = new Map<string, number[]>()

const checkRateLimit = (socketId: string, maxRequests = 30, timeWindow = 60000) => {
  const now = Date.now()
  const requests = rateLimiter.get(socketId) || []
  
  // Nettoyer les anciennes requêtes
  const validRequests = requests.filter(time => now - time < timeWindow)
  
  if (validRequests.length >= maxRequests) {
    throw new Error('Rate limit exceeded')
  }
  
  validRequests.push(now)
  rateLimiter.set(socketId, validRequests)
}
```

## Monitoring et Logs

### Métriques WebSocket
```typescript
class WebSocketMetrics {
  private connections = 0
  private messagesPerSecond = 0
  private errors = 0
  
  trackConnection() {
    this.connections++
  }
  
  trackDisconnection() {
    this.connections--
  }
  
  trackMessage() {
    this.messagesPerSecond++
  }
  
  trackError() {
    this.errors++
  }
  
  getMetrics() {
    return {
      activeConnections: this.connections,
      messagesPerSecond: this.messagesPerSecond,
      errorRate: this.errors,
      uptime: process.uptime()
    }
  }
}
```

### Logs Structurés
```typescript
const logger = {
  connection: (userId: number, socketId: string) => {
    console.log(JSON.stringify({
      event: 'websocket_connection',
      userId,
      socketId,
      timestamp: new Date().toISOString()
    }))
  },
  
  gameEvent: (event: string, gameId: number, userId: number, data: any) => {
    console.log(JSON.stringify({
      event: 'websocket_game_event',
      type: event,
      gameId,
      userId,
      data: JSON.stringify(data),
      timestamp: new Date().toISOString()
    }))
  }
}
```