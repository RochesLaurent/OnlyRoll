<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed, watch } from 'vue'
import { useGameStore } from '@/stores/game'
import { useAuthStore } from '@/stores/auth'
import { usePresenceStore } from '@/stores/presenceStore'
import { mercureService } from '@/services/mercure'
import type { Game, GameFilters } from '@/types/game'
import type { MercurePresenceEventData } from '@/types/websocket'
import DashboardNav from '@/components/dashboard/DashboardNav.vue'
import GameCard from '@/components/game/GameCard.vue'
import CreateGameModal from '@/components/game/CreateGameModal.vue'
import JoinGameModal from '@/components/game/JoinGameModal.vue'
import {
  PlusIcon,
  MagnifyingGlassIcon,
  FunnelIcon,
  InboxIcon,
} from '@heroicons/vue/24/outline'

const gameStore = useGameStore()
const authStore = useAuthStore()
const presenceStore = usePresenceStore()
const showCreateModal = ref(false)
const showJoinModal = ref(false)
const selectedGame = ref<Game | null>(null)
const activeTab = ref<'public' | 'my-games'>('public')
const showFilters = ref(true)
const connectedGameIds = ref<number[]>([]) // Garder trace des parties écoutées

// Filtres
const filters = ref<GameFilters>({
  search: '',
  title: '',
  gameMaster: '',
  status: undefined,
  page: 1,
  limit: 12,
})

// Handler pour les événements de présence
function handlePresenceEvent(event: any) {
  console.log('Presence event in GameListView:', event)
  const presenceData: MercurePresenceEventData = {
    gameId: event.gameId,
    userId: event.data.userId,
    type: event.data.type,
    onlineUsers: event.data.onlineUsers,
    timestamp: event.data.timestamp,
  }
  presenceStore.handlePresenceEvent(presenceData)
}

// Connecter aux événements de présence pour les parties affichées
function connectToPresence() {
  const gameIds = displayedGames.value.map((game) => game.id)

  if (gameIds.length === 0) {
    console.log('Aucune partie à écouter')
    return
  }

  // Vérifier si les IDs ont changé
  const idsChanged =
    gameIds.length !== connectedGameIds.value.length ||
    !gameIds.every((id) => connectedGameIds.value.includes(id))

  if (!idsChanged) {
    console.log('Déjà connecté aux mêmes parties, skip reconnexion')
    return
  }

  console.log('Connexion aux événements de présence pour les parties:', gameIds)

  // Se connecter aux événements de présence
  mercureService.connectToPresence(gameIds)

  // Mémoriser les IDs connectés
  connectedGameIds.value = [...gameIds]
}

onMounted(async () => {
  // Enregistrer le listener une seule fois
  mercureService.on('presence', handlePresenceEvent)

  await loadGames()
  // Connecter aux événements de présence après le chargement des parties
  connectToPresence()
})

onUnmounted(() => {
  // Se déconnecter de Mercure
  mercureService.off('presence', handlePresenceEvent)
  mercureService.disconnect()
})

async function loadGames() {
  if (activeTab.value === 'public') {
    // Pour l'onglet "Toutes", charger à la fois myGames et les parties publiques
    // Les filtres sont appliqués côté client via les computed
    await Promise.all([
      gameStore.fetchMyGames(),
      gameStore.fetchPublicGames()
    ])
  } else {
    // Pour l'onglet "M.J.", charger uniquement myGames
    await gameStore.fetchMyGames()
  }
}

function handleTabChange(tab: 'public' | 'my-games') {
  activeTab.value = tab
  // Réinitialiser les filtres sans recharger
  filters.value = {
    search: '',
    title: '',
    gameMaster: '',
    status: undefined,
    page: 1,
    limit: 12,
  }
  // Charger les données appropriées pour l'onglet
  loadGames()
}

function resetFilters() {
  filters.value = {
    search: '',
    title: '',
    gameMaster: '',
    status: undefined,
    page: 1,
    limit: 12,
  }
  if (activeTab.value === 'public') {
    loadGames()
  }
}

function handleJoinGame(game: Game) {
  selectedGame.value = game
  showJoinModal.value = true
}

function handleJoinSuccess() {
  loadGames()
}

// ============================================
// Tri et filtrage personnalisé pour "Toutes"
// ============================================
const sortedGamesForAllTab = computed(() => {
  if (!authStore.user) return []

  // Combiner myGames et games publics
  const allGames: Game[] = []
  const seenIds = new Set<number>()

  // D'abord ajouter myGames (pour éviter les doublons)
  gameStore.myGames.forEach((game) => {
    allGames.push(game)
    seenIds.add(game.id)
  })

  // Ajouter les parties publiques qui ne sont pas déjà dans myGames
  gameStore.games.forEach((game) => {
    if (!seenIds.has(game.id)) {
      allGames.push(game)
    }
  })

  // Appliquer les filtres
  let filteredGames = allGames

  // Filtre par recherche globale (titre ou nom de campagne)
  if (filters.value.search) {
    const searchTerm = filters.value.search.toLowerCase()
    filteredGames = filteredGames.filter((game) =>
      (game.title || game.name).toLowerCase().includes(searchTerm)
    )
  }

  // Filtre par titre
  if (filters.value.title) {
    const titleFilter = filters.value.title.toLowerCase()
    filteredGames = filteredGames.filter((game) =>
      (game.title || game.name).toLowerCase().includes(titleFilter)
    )
  }

  // Filtre par maître du jeu
  if (filters.value.gameMaster) {
    const gmFilter = filters.value.gameMaster.toLowerCase()
    filteredGames = filteredGames.filter((game) =>
      game.gameMaster.pseudo.toLowerCase().includes(gmFilter)
    )
  }

  // Filtre par statut
  if (filters.value.status) {
    filteredGames = filteredGames.filter((game) => game.status === filters.value.status)
  }

  // Catégoriser les parties filtrées
  const asMaster: Game[] = []
  const asPlayer: Game[] = []
  const publicGames: Game[] = []

  filteredGames.forEach((game) => {
    // Vérifier si l'utilisateur est le MJ
    if (game.gameMaster.id === authStore.user!.id) {
      asMaster.push(game)
    }
    // Vérifier si l'utilisateur est un joueur (mais pas MJ)
    else if (game.gamePlayers?.some((gp) => gp.user.id === authStore.user!.id)) {
      asPlayer.push(game)
    }
    // Sinon c'est une partie publique
    else if (game.isPublic) {
      publicGames.push(game)
    }
  })

  // Trier chaque catégorie par ordre alphabétique du titre
  const sortByTitle = (a: Game, b: Game) => {
    const titleA = (a.title || a.name).toLowerCase()
    const titleB = (b.title || b.name).toLowerCase()
    return titleA.localeCompare(titleB)
  }

  asMaster.sort(sortByTitle)
  asPlayer.sort(sortByTitle)
  publicGames.sort(sortByTitle)

  // Combiner dans l'ordre : MJ > Joueur > Public
  return [...asMaster, ...asPlayer, ...publicGames]
})

// Computed pour les parties à afficher selon l'onglet
const displayedGames = computed(() => {
  if (!authStore.user) return []

  if (activeTab.value === 'my-games') {
    // Onglet "M.J." : uniquement les parties où l'utilisateur est MJ
    let gmGames = gameStore.myGames.filter((game) => game.gameMaster.id === authStore.user!.id)

    // Appliquer les filtres côté client
    if (filters.value.search) {
      const searchTerm = filters.value.search.toLowerCase()
      gmGames = gmGames.filter((game) =>
        (game.title || game.name).toLowerCase().includes(searchTerm)
      )
    }

    if (filters.value.title) {
      const titleFilter = filters.value.title.toLowerCase()
      gmGames = gmGames.filter((game) =>
        (game.title || game.name).toLowerCase().includes(titleFilter)
      )
    }

    if (filters.value.status) {
      gmGames = gmGames.filter((game) => game.status === filters.value.status)
    }

    // Trier par ordre alphabétique
    return gmGames.sort((a, b) => {
      const titleA = (a.title || a.name).toLowerCase()
      const titleB = (b.title || b.name).toLowerCase()
      return titleA.localeCompare(titleB)
    })
  } else {
    // Onglet "Toutes" : tri personnalisé
    return sortedGamesForAllTab.value
  }
})

// Reconnecter quand les parties affichées changent
watch(displayedGames, () => {
  if (displayedGames.value.length > 0) {
    connectToPresence()
  }
})

// Pagination désactivée - pas d'alias nécessaires
</script>

<template>
  <div class="min-h-screen bg-primary-900">
    <!-- Navigation -->
    <DashboardNav />

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
      <!-- Header -->
      <div class="flex items-center justify-between mb-8">
        <div>
          <h1 class="text-3xl font-bold text-secondary-50">
            {{ activeTab === 'public' ? 'Parties Publiques' : 'Mes Parties' }}
          </h1>
          <p class="text-secondary-400 mt-1">
            {{
              activeTab === 'public'
                ? 'Découvrez et rejoignez des parties en cours'
                : 'Gérez vos parties actives et archivées'
            }}
          </p>
        </div>
        <button
          @click="showCreateModal = true"
          class="px-6 py-3 bg-primary-500 hover:bg-primary-600 text-white rounded-lg font-medium transition-all duration-200 flex items-center gap-2 shadow-purple hover:shadow-purple-lg"
        >
          <PlusIcon class="w-5 h-5" />
          Nouvelle
        </button>
      </div>

      <!-- Tabs -->
      <div class="flex items-center justify-between mb-6">
        <div class="flex gap-2 bg-secondary-800 p-1 rounded-lg border border-secondary-700">
          <button
            @click="handleTabChange('public')"
            :class="[
              'px-6 py-2 rounded-md font-medium transition-all duration-200',
              activeTab === 'public'
                ? 'bg-primary-500 text-white shadow-purple'
                : 'text-secondary-400 hover:text-secondary-50',
            ]"
          >
            Toutes
          </button>
          <button
            @click="handleTabChange('my-games')"
            :class="[
              'px-6 py-2 rounded-md font-medium transition-all duration-200',
              activeTab === 'my-games'
                ? 'bg-primary-500 text-white shadow-purple'
                : 'text-secondary-400 hover:text-secondary-50',
            ]"
          >
            M.J.
          </button>
        </div>

        <!-- Toggle Filters (mobile) -->
        <button
          @click="showFilters = !showFilters"
          class="lg:hidden px-4 py-2 bg-secondary-800 border border-secondary-700 rounded-lg text-secondary-300 hover:text-secondary-50 transition-colors flex items-center gap-2"
        >
          <FunnelIcon class="w-5 h-5" />
          Filtres
        </button>
      </div>

      <!-- Layout: Filters + Grid -->
      <div class="flex flex-col lg:flex-row gap-6">
        <!-- Sidebar Filters -->
        <aside v-if="showFilters" class="lg:w-64 flex-shrink-0">
          <div
            class="bg-secondary-800 rounded-lg border border-secondary-700 p-4 space-y-4 sticky top-6"
          >
            <h3 class="text-lg font-semibold text-secondary-50 mb-4">Filtres de recherche</h3>

            <!-- Search globale -->
            <div>
              <label class="block text-sm font-medium text-secondary-300 mb-2"> Recherche </label>
              <div class="relative">
                <input
                  v-model="filters.search"
                  type="text"
                  placeholder="Rechercher..."
                  class="w-full px-3 py-2 pr-10 bg-secondary-700 border border-secondary-600 rounded-md text-secondary-50 placeholder-secondary-400 focus:outline-none focus:ring-2 focus:ring-primary-500"
                />
                <MagnifyingGlassIcon class="w-5 h-5 absolute right-3 top-1/2 -translate-y-1/2 text-secondary-400" />
              </div>
            </div>

            <!-- Titre -->
            <div>
              <label class="block text-sm font-medium text-secondary-300 mb-2"> Titre </label>
              <input
                v-model="filters.title"
                type="text"
                placeholder="Titre de la campagne..."
                class="w-full px-3 py-2 bg-secondary-700 border border-secondary-600 rounded-md text-secondary-50 placeholder-secondary-400 focus:outline-none focus:ring-2 focus:ring-primary-500"
              />
            </div>

            <!-- Maître du Jeu (masqué dans l'onglet M.J.) -->
            <div v-if="activeTab === 'public'">
              <label class="block text-sm font-medium text-secondary-300 mb-2">
                Maître du Jeu
              </label>
              <input
                v-model="filters.gameMaster"
                type="text"
                placeholder="Nom du MJ..."
                class="w-full px-3 py-2 bg-secondary-700 border border-secondary-600 rounded-md text-secondary-50 placeholder-secondary-400 focus:outline-none focus:ring-2 focus:ring-primary-500"
              />
            </div>

            <!-- Statut -->
            <div>
              <label class="block text-sm font-medium text-secondary-300 mb-2"> Statut </label>
              <select
                v-model="filters.status"
                class="w-full px-3 py-2 bg-secondary-700 border border-secondary-600 rounded-md text-secondary-50 focus:outline-none focus:ring-2 focus:ring-primary-500"
              >
                <option :value="undefined">Tous les statuts</option>
                <option value="preparation">En préparation</option>
                <option value="in_progress">En cours</option>
                <option value="paused">En pause</option>
                <option value="completed">Terminée</option>
              </select>
            </div>

            <!-- Bouton Reset -->
            <button
              @click="resetFilters"
              class="w-full px-4 py-2 bg-secondary-700 hover:bg-secondary-600 text-secondary-300 hover:text-secondary-50 rounded-md transition-colors text-sm"
            >
              Réinitialiser
            </button>
          </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 min-w-0">
          <!-- Results counter -->
          <div
            v-if="!gameStore.isLoading && displayedGames.length > 0"
            class="mb-4"
          >
            <p class="text-secondary-400 text-sm">
              <span class="text-secondary-50 font-medium">{{ displayedGames.length }}</span>
              {{ displayedGames.length > 1 ? 'parties trouvées' : 'partie trouvée' }}
            </p>
          </div>

          <!-- Loading State -->
          <div v-if="gameStore.isLoading" class="text-center py-20">
            <div
              class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-secondary-700 border-t-primary-500"
            ></div>
            <p class="text-secondary-400 mt-4">Chargement des parties...</p>
          </div>

          <!-- Games Grid -->
          <div
            v-else-if="displayedGames.length > 0"
            class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6"
          >
            <GameCard
              v-for="game in displayedGames"
              :key="game.id"
              :game="game"
              :show-join-button="activeTab === 'public'"
              @join="handleJoinGame"
            />
          </div>

          <!-- Empty State -->
          <div v-else-if="!gameStore.isLoading" class="text-center py-20">
            <InboxIcon class="w-24 h-24 mx-auto text-secondary-600 mb-4" />
            <h3 class="text-xl font-semibold text-secondary-300 mb-2">
              {{ activeTab === 'public' ? 'Aucune partie trouvée' : 'Aucune partie' }}
            </h3>
            <p class="text-secondary-400 mb-6">
              {{
                activeTab === 'public'
                  ? 'Aucune partie publique ne correspond à vos critères'
                  : 'Vous ne participez à aucune partie pour le moment'
              }}
            </p>
            <button
              v-if="activeTab === 'my-games'"
              @click="showCreateModal = true"
              class="px-6 py-3 bg-primary-500 hover:bg-primary-600 text-white rounded-lg transition-all duration-200 inline-flex items-center gap-2 shadow-purple hover:shadow-purple-lg"
            >
              <PlusIcon class="w-5 h-5" />
              Créer votre première partie
            </button>
            <button
              v-else
              @click="resetFilters"
              class="px-6 py-3 bg-secondary-700 hover:bg-secondary-600 text-secondary-300 hover:text-secondary-50 rounded-lg transition-colors"
            >
              Réinitialiser les filtres
            </button>
          </div>

          <!-- Pagination désactivée (tri côté client) -->

          <!-- Error State -->
          <div
            v-if="gameStore.error && !gameStore.isLoading"
            class="p-4 bg-accent-rose/10 border border-accent-rose/50 rounded-lg text-accent-rose mt-6"
          >
            {{ gameStore.error }}
          </div>
        </main>
      </div>
    </div>

    <!-- Modals -->
    <CreateGameModal v-if="showCreateModal" @close="showCreateModal = false" />

    <JoinGameModal
      v-if="showJoinModal && selectedGame"
      :game="selectedGame"
      @close="showJoinModal = false"
      @success="handleJoinSuccess"
    />
  </div>
</template>
