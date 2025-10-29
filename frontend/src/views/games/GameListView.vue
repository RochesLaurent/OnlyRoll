<script setup lang="ts">
import { ref, onMounted, watch, computed } from 'vue'
import { useGameStore } from '@/stores/game'
import { usePagination } from '@/composables/usePagination'
import type { Game, GameFilters } from '@/types/game'
import GameCard from '@/components/game/GameCard.vue'
import CreateGameModal from '@/components/game/CreateGameModal.vue'
import JoinGameModal from '@/components/game/JoinGameModal.vue'
import {
  PlusIcon,
  MagnifyingGlassIcon,
  FunnelIcon,
  InboxIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
} from '@heroicons/vue/24/outline'

const gameStore = useGameStore()
const showCreateModal = ref(false)
const showJoinModal = ref(false)
const selectedGame = ref<Game | null>(null)
const activeTab = ref<'public' | 'my-games'>('public')
const showFilters = ref(true)

// Filtres
const filters = ref<GameFilters>({
  search: '',
  title: '',
  gameMaster: '',
  status: undefined,
  page: 1,
  limit: 12,
})

// Pagination avec composable
const paginationMeta = computed(() => gameStore.pagination)
const {
  goToPage,
  nextPage: handleNextPage,
  prevPage: handlePrevPage,
  paginationRange,
  canGoNext,
  canGoPrev,
  hasMultiplePages,
} = usePagination(paginationMeta, {
  onPageChange: async (page) => {
    filters.value.page = page
    await gameStore.fetchPublicGames(filters.value)
  },
})

// Debounce timer
let debounceTimer: ReturnType<typeof setTimeout> | null = null

onMounted(() => {
  loadGames()
})

// Watch filters pour recherche automatique (avec debounce)
watch(
  () => [filters.value.title, filters.value.gameMaster, filters.value.status],
  () => {
    if (activeTab.value === 'public') {
      // Reset à la page 1 quand on change les filtres
      filters.value.page = 1
      debouncedSearch()
    }
  }
)

async function loadGames() {
  if (activeTab.value === 'public') {
    await gameStore.fetchPublicGames(filters.value)
  } else {
    await gameStore.fetchMyGames()
  }
}

function debouncedSearch() {
  if (debounceTimer) clearTimeout(debounceTimer)

  debounceTimer = setTimeout(() => {
    loadGames()
  }, 500) // 500ms de délai
}

async function handleSearch() {
  filters.value.page = 1
  await gameStore.fetchPublicGames(filters.value)
}

function handleTabChange(tab: 'public' | 'my-games') {
  activeTab.value = tab
  resetFilters()
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

// Alias pour le template (pour compatibilité)
const previousPage = handlePrevPage
const nextPage = handleNextPage
const getPaginationRange = () => paginationRange.value
</script>

<template>
  <div class="min-h-screen bg-primary-900">
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
                  @keyup.enter="handleSearch"
                  type="text"
                  placeholder="Rechercher..."
                  class="w-full px-3 py-2 pr-10 bg-secondary-700 border border-secondary-600 rounded-md text-secondary-50 placeholder-secondary-400 focus:outline-none focus:ring-2 focus:ring-primary-500"
                />
                <button
                  @click="handleSearch"
                  class="absolute right-2 top-1/2 -translate-y-1/2 text-secondary-400 hover:text-primary-500 transition-colors"
                >
                  <MagnifyingGlassIcon class="w-5 h-5" />
                </button>
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

            <!-- Maître du Jeu -->
            <div>
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
          <!-- Results counter (public only) -->
          <div
            v-if="activeTab === 'public' && !gameStore.isLoading && gameStore.games.length > 0"
            class="mb-4"
          >
            <p class="text-secondary-400 text-sm">
              <span class="text-secondary-50 font-medium">{{ gameStore.pagination.total }}</span>
              {{ gameStore.pagination.total > 1 ? 'parties trouvées' : 'partie trouvée' }}
            </p>
          </div>

          <!-- Loading State -->
          <div v-if="gameStore.isLoading" class="text-center py-20">
            <div
              class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-secondary-700 border-t-primary-500"
            ></div>
            <p class="text-secondary-400 mt-4">Chargement des parties...</p>
          </div>

          <!-- Games Grid - Public -->
          <div
            v-else-if="activeTab === 'public' && gameStore.games.length > 0"
            class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6"
          >
            <GameCard
              v-for="game in gameStore.games"
              :key="game.id"
              :game="game"
              :show-join-button="true"
              @join="handleJoinGame"
            />
          </div>

          <!-- Games Grid - My Games -->
          <div
            v-else-if="activeTab === 'my-games' && gameStore.myGames.length > 0"
            class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6"
          >
            <GameCard
              v-for="game in gameStore.myGames"
              :key="game.id"
              :game="game"
              :show-join-button="false"
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

          <!-- Pagination (public only) -->
          <div
            v-if="
              activeTab === 'public' && !gameStore.isLoading && gameStore.pagination.totalPages > 1
            "
            class="mt-8 flex items-center justify-between"
          >
            <!-- Previous button -->
            <button
              @click="previousPage"
              :disabled="gameStore.pagination.page === 1"
              class="px-4 py-2 bg-secondary-800 border border-secondary-700 rounded-lg text-secondary-300 hover:text-secondary-50 hover:bg-secondary-700 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-secondary-800 disabled:hover:text-secondary-300 transition-all flex items-center gap-2"
            >
              <ChevronLeftIcon class="w-5 h-5" />
              Précédent
            </button>

            <!-- Page indicators -->
            <div class="flex items-center gap-2">
              <template v-for="page in getPaginationRange()" :key="page">
                <button
                  v-if="page !== '...'"
                  @click="goToPage(page as number)"
                  :class="[
                    'w-10 h-10 rounded-lg font-medium transition-all',
                    gameStore.pagination.page === page
                      ? 'bg-primary-500 text-white shadow-purple'
                      : 'bg-secondary-800 text-secondary-300 hover:bg-secondary-700 hover:text-secondary-50 border border-secondary-700',
                  ]"
                >
                  {{ page }}
                </button>
                <span v-else class="text-secondary-400 px-2">...</span>
              </template>
            </div>

            <!-- Next button -->
            <button
              @click="nextPage"
              :disabled="gameStore.pagination.page === gameStore.pagination.totalPages"
              class="px-4 py-2 bg-secondary-800 border border-secondary-700 rounded-lg text-secondary-300 hover:text-secondary-50 hover:bg-secondary-700 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-secondary-800 disabled:hover:text-secondary-300 transition-all flex items-center gap-2"
            >
              Suivant
              <ChevronRightIcon class="w-5 h-5" />
            </button>
          </div>

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
