# Documentation WebSocket Events - OnlyRoll

## Architecture WebSocket

Le serveur WebSocket utilise Socket.io pour la communication temps réel entre le serveur et les clients.

## Connexion et Authentification

### Connection Flow
```javascript
// 1. Client se connecte avec le token JWT
socket = io('ws://localhost:3000', {
  auth: {
    token: 'JWT_TOKEN_HERE'
  }
});

// 2. Serveur valide le token
// 3. Client rejoint automatiquement ses rooms de parties
```

## Events Client → Server

### Authentification
| Event | Payload | Description |
|-------|---------|-------------|
| `auth:login` | `{ token: string }` | Authentification initiale |
| `auth:logout` | `{}` | Déconnexion |

### Gestion des Parties
| Event | Payload | Description |
|-------|---------|-------------|
| `game:join` | `{ gameId: number }` | Rejoindre une room de partie |
| `game:leave` | `{ gameId: number }` | Quitter une room de partie |
| `game:update` | `{ gameId: number, data: object }` | Mettre à jour les infos de partie |

### Messages et Chat
| Event | Payload | Description |
|-------|---------|-------------|
| `message:send` | `{ gameId: number, content: string, type: 'chat'\|'emote'\|'system' }` | Envoyer un message |
| `message:whisper` | `{ gameId: number, targetUserId: number, content: string }` | Message privé |
| `message:ic` | `{ gameId: number, characterId: number, content: string }` | Message in-character |
| `message:delete` | `{ gameId: number, messageId: number }` | Supprimer un message |

### Lancers de Dés
| Event | Payload | Description |
|-------|---------|-------------|
| `dice:roll` | `{ gameId: number, expression: string, type?: string, context?: string }` | Lancer de dés |
| `dice:roll:private` | `{ gameId: number, expression: string }` | Lancer privé (MJ) |

### Gestion des Tokens sur Carte
| Event | Payload | Description |
|-------|---------|-------------|
| `token:create` | `{ gameId: number, mapId: number, token: TokenData }` | Créer un token |
| `token:move` | `{ gameId: number, mapId: number, tokenId: number, x: number, y: number }` | Déplacer un token |
| `token:update` | `{ gameId: number, mapId: number, tokenId: number, data: object }` | Modifier un token |
| `token:delete` | `{ gameId: number, mapId: number, tokenId: number }` | Supprimer un token |
| `token:lock` | `{ gameId: number, mapId: number, tokenId: number, locked: boolean }` | Verrouiller/déverrouiller |

### Actions de Carte
| Event | Payload | Description |
|-------|---------|-------------|
| `map:change` | `{ gameId: number, mapId: number }` | Changer de carte active |
| `map:fog:update` | `{ gameId: number, mapId: number, fogData: object }` | Mettre à jour le brouillard |
| `map:draw` | `{ gameId: number, mapId: number, drawingData: object }` | Dessiner sur la carte |

## Events Server → Client

### Notifications Système
| Event | Payload | Description |
|-------|---------|-------------|
| `connected` | `{ userId: number, games: number[] }` | Confirmation de connexion |
| `error` | `{ message: string, code: string }` | Erreur |
| `notification` | `{ type: string, message: string }` | Notification système |

### Mises à jour de Partie
| Event | Payload | Description |
|-------|---------|-------------|
| `game:player:joined` | `{ gameId: number, player: PlayerData }` | Un joueur a rejoint |
| `game:player:left` | `{ gameId: number, playerId: number }` | Un joueur a quitté |
| `game:status:changed` | `{ gameId: number, status: string }` | Statut de partie modifié |
| `game:updated` | `{ gameId: number, changes: object }` | Infos de partie modifiées |

### Broadcast Messages
| Event | Payload | Description |
|-------|---------|-------------|
| `message:new` | `{ gameId: number, message: MessageData }` | Nouveau message |
| `message:deleted` | `{ gameId: number, messageId: number }` | Message supprimé |
| `whisper:received` | `{ from: UserData, content: string }` | Whisper reçu |

### Broadcast Dés
| Event | Payload | Description |
|-------|---------|-------------|
| `dice:rolled` | `{ gameId: number, roll: DiceRollData }` | Résultat de lancer |
| `dice:rolled:private` | `{ gameId: number, rollId: number }` | Notification de lancer privé |

### Broadcast Tokens
| Event | Payload | Description |
|-------|---------|-------------|
| `token:created` | `{ gameId: number, mapId: number, token: TokenData }` | Token créé |
| `token:moved` | `{ gameId: number, mapId: number, tokenId: number, x: number, y: number }` | Token déplacé |
| `token:updated` | `{ gameId: number, mapId: number, tokenId: number, changes: object }` | Token modifié |
| `token:deleted` | `{ gameId: number, mapId: number, tokenId: number }` | Token supprimé |

### Synchronisation Carte
| Event | Payload | Description |
|-------|---------|-------------|
| `map:changed` | `{ gameId: number, mapId: number, mapData: object }` | Carte changée |
| `map:fog:updated` | `{ gameId: number, mapId: number, fogData: object }` | Brouillard mis à jour |
| `map:drawing:added` | `{ gameId: number, mapId: number, drawing: object }` | Nouveau dessin |

## Room Management

### Structure des Rooms
```
game:{gameId}           - Room principale de la partie
game:{gameId}:gm        - Room réservée au MJ et co-GM
game:{gameId}:map:{id}  - Room spécifique à une carte
user:{userId}           - Room personnelle pour notifications
```

### Rejoindre/Quitter des Rooms
```javascript
// Côté serveur
socket.join(`game:${gameId}`);
socket.leave(`game:${gameId}`);

// Broadcast à une room
io.to(`game:${gameId}`).emit('message:new', data);

// Broadcast sauf l'émetteur
socket.to(`game:${gameId}`).emit('token:moved', data);
```

## Types de Données

### TokenData
```typescript
interface TokenData {
  tokenId?: number;
  name: string;
  type: 'character' | 'monster' | 'npc' | 'object';
  imageUrl: string;
  x: number;
  y: number;
  size: number; // 0.5 à 5.0
  rotation: number; // 0 à 359
  isVisible: boolean;
  isLocked: boolean;
  layer: 'background' | 'objects' | 'tokens' | 'effects';
}
```

### MessageData
```typescript
interface MessageData {
  messageId: number;
  userId: number;
  userName: string;
  userAvatar?: string;
  content: string;
  type: 'chat' | 'emote' | 'system' | 'dice' | 'whisper';
  characterId?: number;
  characterName?: string;
  timestamp: string;
}
```

### DiceRollData
```typescript
interface DiceRollData {
  rollId: number;
  userId: number;
  userName: string;
  expression: string;
  results: {
    dice: { sides: number; result: number }[];
    modifier: number;
    total: number;
  };
  type?: 'attack' | 'damage' | 'saving' | 'ability' | 'initiative';
  context?: string;
  isPrivate: boolean;
  timestamp: string;
}
```

## Gestion des Erreurs

### Codes d'Erreur
| Code | Description | Action Client |
|------|-------------|---------------|
| `AUTH_FAILED` | Token JWT invalide | Reconnecter avec nouveau token |
| `GAME_NOT_FOUND` | Partie inexistante | Rediriger vers liste des parties |
| `PERMISSION_DENIED` | Action non autorisée | Afficher message d'erreur |
| `RATE_LIMITED` | Trop de requêtes | Attendre avant de réessayer |
| `INVALID_DATA` | Payload invalide | Vérifier les données envoyées |

## Sécurité

### Authentification
- Token JWT requis pour toute connexion
- Validation du token à chaque reconnexion
- Expiration automatique après inactivité

### Autorisations
- Vérification des permissions par action
- Validation du rôle dans la partie (MJ, joueur, spectateur)
- Rate limiting par utilisateur

### Validation des Données
- Validation de tous les payloads entrants
- Sanitization des contenus de messages
- Limites sur les tailles de données

## Configuration Serveur

### Environnement
```env
# WebSocket
WS_PORT=3000
WS_CORS_ORIGIN=http://localhost:5173
WS_JWT_SECRET=same-as-backend
WS_REDIS_URL=redis://localhost:6379

# Limites
WS_MAX_CONNECTIONS_PER_IP=10
WS_RATE_LIMIT_MESSAGES=100
WS_RATE_LIMIT_WINDOW=60000
```

### Options Socket.io
```javascript
const io = new Server(server, {
  cors: {
    origin: process.env.WS_CORS_ORIGIN,
    credentials: true
  },
  pingTimeout: 60000,
  pingInterval: 25000,
  transports: ['websocket', 'polling'],
  maxHttpBufferSize: 1e6 // 1MB
});
```