# Architecture Frontend OnlyRoll - Vue.js 3 + TypeScript

## Vue d'ensemble

L'architecture frontend d'OnlyRoll suit les principes de **Clean Architecture** adaptés à Vue.js 3, avec une séparation claire des responsabilités et une approche modulaire scalable.

### Stack Technique
- **Framework** : Vue.js 3.4+ avec Composition API
- **Language** : TypeScript 5.3+
- **State Management** : Pinia 2.1+
- **Router** : Vue Router 4+
- **Styling** : Tailwind CSS 3.x + Design System
- **Build Tool** : Vite 5.0+
- **HTTP Client** : Axios avec intercepteurs
- **WebSocket** : Socket.io-client
- **PWA** : Vite PWA Plugin

## Structure des Dossiers

```
src/
├── assets/                     # Ressources statiques
│   ├── icons/                  # Icônes SVG
│   ├── images/                 # Images et illustrations
│   └── sounds/                 # Sons d'ambiance et effets
├── components/                 # Composants Vue réutilisables
│   ├── base/                   # Composants de base (OnlyButton, OnlyInput, OnlyModal)
│   │   ├── OnlyButton.vue      # Bouton avec variants (primary, secondary, danger, ghost)
│   │   ├── OnlyInput.vue       # Input avec validation et icônes
│   │   ├── OnlySelect.vue      # Dropdown pour filtres
│   │   ├── OnlyModal.vue       # Modal responsive
│   │   ├── OnlyCard.vue        # Card de base avec hover
│   │   └── OnlyBadge.vue       # Badge pour statuts et labels
│   ├── layout/                 # Composants de mise en page
│   │   ├── AppHeader.vue       # Header avec navigation desktop
│   │   ├── AppFooter.vue       # Footer avec links
│   │   ├── MobileNav.vue       # Navigation mobile hamburger
│   │   ├── Sidebar.vue         # Sidebar filtres wiki
│   │   └── ThreeColumn.vue     # Layout filtres|liste|détail
│   ├── game/                   # Composants spécifiques au jeu
│   │   ├── GameCard.vue        # Card partie avec statut "En cours"
│   │   ├── GameMap.vue         # Carte tactique avec grille
│   │   ├── ChatPanel.vue       # Panel chat temps réel
│   │   ├── DiceRoller.vue      # Interface de lancer de dés
│   │   ├── TokenManager.vue    # Gestion tokens sur carte
│   │   └── PlayerList.vue      # Liste joueurs connectés
│   ├── auth/                   # Composants d'authentification
│   │   ├── LoginForm.vue       # Formulaire de connexion
│   │   ├── RegisterForm.vue    # Formulaire d'inscription
│   │   └── AuthLayout.vue      # Layout pages auth
│   ├── wiki/                   # Composants du wiki D&D
│   │   ├── WikiCard.vue        # Card navigation avec icônes
│   │   ├── WikiFilters.vue     # Panel filtres sidebar
│   │   ├── SpellCard.vue       # Card sort avec métadonnées
│   │   ├── SpellDetail.vue     # Détail complet d'un sort
│   │   ├── SpellsList.vue      # Liste paginée des sorts
│   │   ├── FilterBadge.vue     # Badge filtre amovible
│   │   └── ComponentBadge.vue  # Badge V S M sorts
│   └── common/                 # Composants transversaux
│       ├── SearchBar.vue       # Barre recherche globale
│       ├── StatusBadge.vue     # Badge "En cours", statuts
│       ├── FeatureCard.vue     # Card fonctionnalités landing
│       └── LoadingSpinner.vue  # Spinner de chargement
├── composables/                # Logique métier réutilisable
│   ├── useAuth.ts              # Authentification
│   ├── useWebSocket.ts         # WebSocket temps réel
│   ├── useGame.ts              # Gestion des parties
│   ├── useDice.ts              # Système de dés
│   ├── useWiki.ts              # Consultation SRD
│   └── useChat.ts              # Système de chat
├── stores/                     # Stores Pinia
│   ├── auth.ts                 # État d'authentification
│   ├── game.ts                 # État des parties
│   ├── chat.ts                 # Messages et historique
│   ├── wiki.ts                 # Données SRD
│   ├── ui.ts                   # Interface utilisateur
│   └── websocket.ts            # Connexions temps réel
├── router/                     # Configuration des routes
│   ├── index.ts                # Router principal
│   ├── guards.ts               # Guards d'authentification
│   └── routes/                 # Définition des routes
├── services/                   # Services API et métier
│   ├── api/                    # Clients API REST
│   ├── websocket/              # Service WebSocket
│   ├── auth/                   # Services d'authentification
│   └── storage/                # Gestion du localStorage
├── types/                      # Types TypeScript
│   ├── api.ts                  # Types des réponses API
│   ├── game.ts                 # Types liés au jeu
│   ├── user.ts                 # Types utilisateur
│   └── websocket.ts            # Types WebSocket
├── utils/                      # Fonctions utilitaires
│   ├── dice.ts                 # Parseur et calculateur de dés
│   ├── validation.ts           # Validation des formulaires
│   ├── formatting.ts           # Formatage des données
│   └── constants.ts            # Constantes de l'application
├── views/                      # Pages/Vues de l'application
│   ├── auth/                   # Pages d'authentification
│   │   ├── LoginView.vue       # Page de connexion
│   │   └── RegisterView.vue    # Page d'inscription
│   ├── dashboard/              # Tableau de bord utilisateur
│   │   └── DashboardView.vue   # Dashboard post-connexion
│   ├── games/                  # Gestion des parties
│   │   ├── GameListView.vue    # Liste parties (maquette mobile 5)
│   │   ├── GamePlayView.vue    # Interface jeu (maquette desktop 4)
│   │   └── GameCreateView.vue  # Création de partie
│   ├── wiki/                   # Consultation du wiki D&D
│   │   ├── WikiHomeView.vue    # Navigation wiki (maquette mobile 1)
│   │   ├── SpellsView.vue      # Liste sorts (maquette mobile 2)
│   │   └── SpellDetailView.vue # Détail sort (maquette desktop 6)
│   ├── profile/                # Profil utilisateur
│   │   └── ProfileView.vue     # Gestion compte utilisateur
│   └── HomeView.vue            # Landing page (maquette desktop 3)
├── styles/                     # Styles globaux
│   ├── tailwind.css            # Configuration Tailwind
│   ├── design-system.css       # Variables du design system
│   └── components.css          # Styles des composants
├── App.vue                     # Composant racine
└── main.ts                     # Point d'entrée de l'application
```

## Architecture en Couches

### 1. **Presentation Layer** (Views + Components)
```typescript
// Exemple de structure d'une vue
<template>
  <GameLayout>
    <GameTable @dice-roll="handleDiceRoll" />
    <ChatPanel :messages="messages" />
  </GameLayout>
</template>

<script setup lang="ts">
// Logique de présentation uniquement
// Délégation à la couche métier via composables
</script>
```

### 2. **Business Logic Layer** (Composables)
```typescript
// useGame.ts - Logique métier encapsulée
export const useGame = () => {
  const gameStore = useGameStore()
  const { socket } = useWebSocket()
  
  const createGame = async (gameData: CreateGameRequest) => {
    // Logique de création de partie
  }
  
  const joinGame = async (gameId: string) => {
    // Logique pour rejoindre une partie
  }
  
  return { createGame, joinGame, /* ... */ }
}
```

### 3. **Data Access Layer** (Services + Stores)
```typescript
// services/api/gameApi.ts
export const gameApi = {
  create: (data: CreateGameRequest) => api.post('/games', data),
  list: (filters?: GameFilters) => api.get('/games', { params: filters }),
  join: (gameId: string) => api.post(`/games/${gameId}/join`)
}
```

## Gestion d'État avec Pinia

### Store d'Authentification
```typescript
// stores/auth.ts
export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const token = ref<string | null>(null)
  const isAuthenticated = computed(() => !!token.value)
  
  const login = async (credentials: LoginCredentials) => {
    const response = await authApi.login(credentials)
    token.value = response.token
    user.value = response.user
    // Stockage sécurisé du token
    tokenStorage.set(response.token)
  }
  
  const logout = () => {
    user.value = null
    token.value = null
    tokenStorage.clear()
    router.push('/auth/login')
  }
  
  return { user, isAuthenticated, login, logout }
})
```

### Store de Partie
```typescript
// stores/game.ts
export const useGameStore = defineStore('game', () => {
  const currentGame = ref<Game | null>(null)
  const players = ref<Player[]>([])
  const gameStatus = ref<GameStatus>('preparation')
  
  const setCurrentGame = (game: Game) => {
    currentGame.value = game
    players.value = game.players
    gameStatus.value = game.status
  }
  
  const addPlayer = (player: Player) => {
    players.value.push(player)
  }
  
  const removePlayer = (playerId: string) => {
    const index = players.value.findIndex(p => p.id === playerId)
    if (index > -1) players.value.splice(index, 1)
  }
  
  return { currentGame, players, gameStatus, setCurrentGame, addPlayer, removePlayer }
})
```

## WebSocket et Temps Réel

### Service WebSocket Centralisé
```typescript
// services/websocket/socketService.ts
export class SocketService {
  private socket: Socket | null = null
  private reconnectAttempts = 0
  private maxReconnectAttempts = 5
  
  connect(token: string) {
    this.socket = io(WS_URL, {
      auth: { token },
      transports: ['websocket'],
      reconnection: true,
      reconnectionDelay: 1000,
      reconnectionDelayMax: 5000
    })
    
    this.setupEventListeners()
  }
  
  private setupEventListeners() {
    if (!this.socket) return
    
    // Événements de connexion
    this.socket.on('connect', this.onConnect.bind(this))
    this.socket.on('disconnect', this.onDisconnect.bind(this))
    this.socket.on('connect_error', this.onConnectError.bind(this))
    
    // Événements métier
    this.socket.on('game:player_joined', this.onPlayerJoined.bind(this))
    this.socket.on('game:message', this.onGameMessage.bind(this))
    this.socket.on('dice:result', this.onDiceResult.bind(this))
  }
  
  joinGameRoom(gameId: string) {
    this.emit('game:join', { gameId })
  }
  
  sendChatMessage(gameId: string, message: string) {
    this.emit('chat:message', { gameId, content: message })
  }
  
  rollDice(gameId: string, expression: string) {
    this.emit('dice:roll', { gameId, expression })
  }
}
```

### Composable WebSocket
```typescript
// composables/useWebSocket.ts
export const useWebSocket = () => {
  const socketService = inject('socketService') as SocketService
  const authStore = useAuthStore()
  const gameStore = useGameStore()
  const chatStore = useChatStore()
  
  const connect = () => {
    if (authStore.token) {
      socketService.connect(authStore.token)
    }
  }
  
  const joinGame = (gameId: string) => {
    socketService.joinGameRoom(gameId)
  }
  
  const sendMessage = (message: string) => {
    const currentGame = gameStore.currentGame
    if (currentGame) {
      socketService.sendChatMessage(currentGame.id, message)
    }
  }
  
  return { connect, joinGame, sendMessage }
}
```

## Design System et Composants

### Composants de Base
```typescript
// components/base/OnlyButton.vue
<template>
  <button
    :class="buttonClasses"
    :disabled="disabled || loading"
    @click="handleClick"
  >
    <OnlySpinner v-if="loading" class="mr-2" />
    <OnlyIcon v-if="icon && !loading" :name="icon" class="mr-2" />
    <slot />
  </button>
</template>

<script setup lang="ts">
interface Props {
  variant?: 'primary' | 'secondary' | 'danger' | 'ghost'
  size?: 'sm' | 'md' | 'lg'
  icon?: string
  loading?: boolean
  disabled?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'primary',
  size: 'md'
})

const buttonClasses = computed(() => [
  'inline-flex items-center justify-center font-medium rounded-md transition-colors',
  'focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2',
  {
    // Variants
    'bg-primary-500 text-white hover:bg-primary-600': props.variant === 'primary',
    'bg-secondary-700 text-secondary-100 hover:bg-secondary-600': props.variant === 'secondary',
    'bg-red-500 text-white hover:bg-red-600': props.variant === 'danger',
    'bg-transparent text-secondary-300 hover:text-secondary-100': props.variant === 'ghost',
    
    // Sizes
    'px-3 py-1.5 text-sm': props.size === 'sm',
    'px-4 py-2 text-base': props.size === 'md',
    'px-6 py-3 text-lg': props.size === 'lg',
    
    // States
    'opacity-50 cursor-not-allowed': props.disabled,
    'opacity-75 cursor-wait': props.loading
  }
])
</script>
```

### Composants Spécifiques aux Maquettes

#### **WikiCard** (Maquette Mobile 1)
```typescript
// components/wiki/WikiCard.vue
<template>
  <div 
    class="wiki-card bg-secondary-800 rounded-lg p-6 cursor-pointer 
           hover:bg-secondary-700 transition-colors"
    @click="$emit('click')"
  >
    <div class="flex flex-col items-center text-center space-y-3">
      <div class="w-12 h-12 bg-primary-500 rounded-lg flex items-center justify-center">
        <Icon :name="icon" class="text-white" size="24" />
      </div>
      <h3 class="text-white font-medium">{{ title }}</h3>
      <span v-if="count" class="text-xs text-secondary-400">{{ count }}</span>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Props {
  icon: string
  title: string
  count?: number
}

defineProps<Props>()
defineEmits<{ click: [] }>()
</script>
```

#### **GameCard** (Maquette Mobile 5)
```typescript
// components/game/GameCard.vue
<template>
  <div class="game-card bg-secondary-800 rounded-lg overflow-hidden">
    <!-- Image placeholder avec gradient -->
    <div class="h-32 bg-gradient-to-br from-primary-600 to-primary-800 relative">
      <div class="absolute top-2 right-2">
        <StatusBadge status="en-cours" />
      </div>
    </div>
    
    <!-- Contenu de la carte -->
    <div class="p-4">
      <h3 class="text-lg font-semibold text-white mb-1">{{ game.name }}</h3>
      <p class="text-secondary-400 text-sm mb-2">{{ game.masterName }}</p>
      <p class="text-secondary-300 text-sm mb-4">
        Campagne niveau : {{ game.level }}
      </p>
      
      <div class="flex items-center justify-between">
        <span class="text-sm text-secondary-400">
          {{ game.playersCount }} / {{ game.maxPlayers }} joueurs connectés
        </span>
        <OnlyButton size="sm" @click="$emit('join', game.id)">
          Jouer
        </OnlyButton>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Props {
  game: {
    id: string
    name: string
    masterName: string
    level: number
    playersCount: number
    maxPlayers: number
    status: GameStatus
  }
}

defineProps<Props>()
defineEmits<{ join: [gameId: string] }>()
</script>
```

#### **SpellCard** avec Filtres (Maquette Mobile 2)
```typescript
// components/wiki/SpellCard.vue
<template>
  <div class="spell-card bg-secondary-800 rounded-lg p-4 cursor-pointer hover:bg-secondary-700">
    <div class="flex items-start justify-between mb-3">
      <h3 class="text-white font-medium">{{ spell.name }}</h3>
      <button @click.stop="toggleFavorite" class="text-yellow-400">
        <Icon name="star" :class="{ 'fill-current': isFavorite }" />
      </button>
    </div>
    
    <!-- Badges métadonnées -->
    <div class="flex flex-wrap gap-2 mb-3">
      <OnlyBadge variant="danger">{{ spell.school }}</OnlyBadge>
      <OnlyBadge variant="secondary">{{ spell.castingTime }}</OnlyBadge>
      <OnlyBadge variant="secondary">{{ spell.range }}</OnlyBadge>
      
      <!-- Composants V S M -->
      <div class="flex space-x-1">
        <ComponentBadge v-if="spell.verbal" type="verbal">V</ComponentBadge>
        <ComponentBadge v-if="spell.somatic" type="somatic">S</ComponentBadge>
        <ComponentBadge v-if="spell.material" type="material">M</ComponentBadge>
      </div>
    </div>
    
    <!-- Niveau du sort -->
    <div class="text-right">
      <span class="text-primary-400 font-bold text-lg">{{ spell.level }}</span>
    </div>
  </div>
</template>

<script setup lang="ts">
const { toggleFavorite, isFavorite } = useWikiStore()
</script>
```
```typescript
// components/game/DiceRoller.vue
<template>
  <div class="dice-roller">
    <div class="flex items-center space-x-2">
      <OnlyInput
        v-model="expression"
        placeholder="2d6+3"
        class="flex-1"
        @keypress.enter="rollDice"
      />
      <OnlyButton
        @click="rollDice"
        :loading="isRolling"
        icon="dice"
      >
        Lancer
      </OnlyButton>
    </div>
    
    <DicePresets @select="expression = $event" />
    
    <div v-if="lastResult" class="mt-4">
      <DiceResult :result="lastResult" />
    </div>
  </div>
</template>

<script setup lang="ts">
const { rollDice: roll } = useDice()
const expression = ref('1d20')
const isRolling = ref(false)
const lastResult = ref<DiceResult | null>(null)

const rollDice = async () => {
  isRolling.value = true
  try {
    lastResult.value = await roll(expression.value)
  } finally {
    isRolling.value = false
  }
}
</script>
```

## Configuration du Router

### Routes Correspondant aux Maquettes
```typescript
// router/routes/index.ts
export const routes: RouteRecordRaw[] = [
  // Landing page (maquette desktop 3)
  {
    path: '/',
    name: 'home',
    component: () => import('@/views/HomeView.vue'),
    meta: { layout: 'desktop', requiresAuth: false }
  },
  
  // Routes d'authentification
  {
    path: '/auth',
    component: () => import('@/layouts/AuthLayout.vue'),
    children: [
      {
        path: 'login',
        name: 'login',
        component: () => import('@/views/auth/LoginView.vue')
      },
      {
        path: 'register', 
        name: 'register',
        component: () => import('@/views/auth/RegisterView.vue')
      }
    ]
  },
  
  // Wiki routes (maquettes mobile 1, 2 et desktop 6)
  {
    path: '/wiki',
    component: () => import('@/layouts/WikiLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        name: 'wiki.home',
        component: () => import('@/views/wiki/WikiHomeView.vue'),
        meta: { mobileLayout: 'cards', desktopLayout: 'navigation' }
      },
      {
        path: 'spells',
        name: 'wiki.spells',
        component: () => import('@/views/wiki/SpellsView.vue'),
        meta: { mobileLayout: 'list', desktopLayout: 'three-column' }
      }
    ]
  },
  
  // Games routes (maquettes mobile 5 et desktop 4)
  {
    path: '/games',
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        name: 'games.list',
        component: () => import('@/views/games/GameListView.vue'),
        meta: { mobileLayout: 'cards', desktopLayout: 'grid' }
      },
      {
        path: ':id',
        name: 'games.play',
        component: () => import('@/views/games/GamePlayView.vue'),
        meta: { layout: 'game-table', fullscreen: true },
        props: true
      }
    ]
  }
]
```

### Layout Detection Responsive
```typescript
// composables/useLayout.ts
export const useLayout = () => {
  const route = useRoute()
  const windowWidth = ref(window.innerWidth)
  
  const currentLayout = computed(() => {
    const isMobile = windowWidth.value < 1024
    const meta = route.meta
    
    if (meta.layout === 'game-table') return 'GameTableLayout'
    if (isMobile && meta.mobileLayout === 'cards') return 'MobileCardsLayout'  
    if (!isMobile && meta.desktopLayout === 'three-column') return 'ThreeColumnLayout'
    
    return isMobile ? 'MobileLayout' : 'DesktopLayout'
  })
  
  return { currentLayout }
}
```
```typescript
// router/routes/index.ts
export const routes: RouteRecordRaw[] = [
  {
    path: '/',
    name: 'home',
    component: () => import('@/views/HomeView.vue'),
    meta: { requiresAuth: false }
  },
  
  // Routes d'authentification
  {
    path: '/auth',
    component: () => import('@/layouts/AuthLayout.vue'),
    children: [
      {
        path: 'login',
        name: 'login',
        component: () => import('@/views/auth/LoginView.vue')
      },
      {
        path: 'register',
        name: 'register',
        component: () => import('@/views/auth/RegisterView.vue')
      }
    ]
  },
  
  // Routes protégées
  {
    path: '/dashboard',
    name: 'dashboard',
    component: () => import('@/views/dashboard/DashboardView.vue'),
    meta: { requiresAuth: true }
  },
  
  {
    path: '/games',
    component: () => import('@/layouts/GameLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        name: 'games.list',
        component: () => import('@/views/games/GameListView.vue')
      },
      {
        path: ':id',
        name: 'games.play',
        component: () => import('@/views/games/GamePlayView.vue'),
        props: true
      }
    ]
  },
  
  // Wiki D&D
  {
    path: '/wiki',
    component: () => import('@/layouts/WikiLayout.vue'),
    children: [
      {
        path: '',
        name: 'wiki.home',
        component: () => import('@/views/wiki/WikiHomeView.vue')
      },
      {
        path: 'spells',
        name: 'wiki.spells',
        component: () => import('@/views/wiki/SpellsView.vue')
      }
    ]
  }
]
```

### Guards d'Authentification
```typescript
// router/guards.ts
export const authGuard: NavigationGuard = (to, from, next) => {
  const authStore = useAuthStore()
  
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next({ name: 'login', query: { redirect: to.fullPath } })
  } else if (to.meta.requiresGuest && authStore.isAuthenticated) {
    next({ name: 'dashboard' })
  } else {
    next()
  }
}
```

## Stratégie de Tests

### Tests des Composables
```typescript
// tests/composables/useAuth.test.ts
describe('useAuth', () => {
  it('should login successfully', async () => {
    const { login } = useAuth()
    const credentials = { email: 'test@test.com', password: 'password' }
    
    await login(credentials)
    
    expect(/* assertions */).toBeTruthy()
  })
})
```

### Tests des Composants
```typescript
// tests/components/DiceRoller.test.ts
describe('DiceRoller', () => {
  it('should roll dice when button is clicked', async () => {
    const wrapper = mount(DiceRoller)
    const input = wrapper.find('input')
    const button = wrapper.find('button')
    
    await input.setValue('2d6')
    await button.trigger('click')
    
    expect(wrapper.emitted()).toHaveProperty('dice-rolled')
  })
})
```

## Configuration de Build

### Vite Configuration
```typescript
// vite.config.ts
export default defineConfig({
  plugins: [
    vue(),
    VitePWA({
      registerType: 'autoUpdate',
      workbox: {
        globPatterns: ['**/*.{js,css,html,ico,png,svg}']
      }
    })
  ],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url))
    }
  },
  build: {
    rollupOptions: {
      output: {
        manualChunks: {
          'vendor': ['vue', 'vue-router', 'pinia'],
          'ui': ['@headlessui/vue', 'lucide-vue-next']
        }
      }
    }
  }
})
```

## Progressive Web App

### Service Worker
```typescript
// src/sw/sw.ts
self.addEventListener('message', event => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting()
  }
})

// Cache des assets critiques
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open('onlyroll-v1').then(cache => {
      return cache.addAll([
        '/',
        '/manifest.json',
        '/assets/icons/icon-192.png'
      ])
    })
  )
})
```

## Points d'Extension Futurs

### Architecture Modulaire
- **Plugins système** pour fonctionnalités optionnelles
- **Micro-frontends** pour modules indépendants
- **Web Workers** pour calculs lourds (dés, combat)
- **WebRTC** pour audio/vidéo intégré

### Performance
- **Code splitting** par route et fonctionnalité
- **Lazy loading** des composants lourds
- **Virtual scrolling** pour grandes listes
- **Image optimization** avec WebP/AVIF