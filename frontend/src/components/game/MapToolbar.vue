<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useMapStore } from '@/stores/mapStore'
import { PencilIcon } from '@heroicons/vue/24/outline'
import type { GameMap } from '@/types/game'

const props = defineProps<{
  isGameMaster: boolean
  gameId: number
}>()

const emit = defineEmits<{
  toolChanged: [tool: string]
  openUploadModal: []
  openEditModal: [map: GameMap]
  zoomChanged: [zoom: number]
  centerMap: []
  gridSettingsChanged: [settings: { showGrid: boolean; gridColor: string; gridOpacity: number }]
}>()

const mapStore = useMapStore()

// État local
const selectedTool = ref('select')
const zoomLevel = ref(100)
const mapSearchQuery = ref('')
const showMapDropdown = ref(false)
const isEditingZoom = ref(false)
const zoomInputValue = ref('100')

// Paramètres de grille
const showGrid = ref(true)
const gridColor = ref('#ffffff')
const gridOpacity = ref(0.1)
const showGridSettings = ref(false)

// Gestion de la deuxième barre
type SecondaryBarSection = 'maps' | 'tools' | null
const openSecondaryBar = ref<SecondaryBarSection>(null)

// Outils disponibles
const tools = [
  { id: 'select', icon: '🖱️', label: 'Sélection', gmOnly: false },
  { id: 'token', icon: '🎭', label: 'Ajouter Token', gmOnly: true },
  { id: 'fog', icon: '☁️', label: 'Brouillard', gmOnly: true },
  { id: 'measure', icon: '📏', label: 'Mesure', gmOnly: false },
  { id: 'draw', icon: '✏️', label: 'Dessin', gmOnly: false },
  { id: 'ping', icon: '📍', label: 'Ping', gmOnly: false },
]

// Filtrer les outils selon le rôle
const availableTools = computed(() => {
  return tools.filter((tool) => !tool.gmOnly || props.isGameMaster)
})

// Computed pour les cartes filtrées
const filteredMaps = computed(() => {
  const query = mapSearchQuery.value.toLowerCase().trim()

  if (!query) {
    return mapStore.allMaps
  }

  // Filtrage avancé : on recherche si tous les caractères de la query
  // apparaissent dans le nom de la carte dans l'ordre
  return mapStore.allMaps.filter((map) => {
    const mapName = map.name.toLowerCase()
    let queryIndex = 0

    for (let i = 0; i < mapName.length && queryIndex < query.length; i++) {
      if (mapName[i] === query[queryIndex]) {
        queryIndex++
      }
    }

    return queryIndex === query.length
  })
})

// Carte active
const activeMapId = computed(() => mapStore.activeMap?.id)

// ============================================
// Handlers
// ============================================
function handleClickOutside(event: MouseEvent) {
  const target = event.target as HTMLElement
  // Vérifier si le clic est à l'extérieur du dropdown
  if (!target.closest('.map-selector-container')) {
    showMapDropdown.value = false
    mapSearchQuery.value = ''
  }
}

// ============================================
// ============================================
// Gestion de la barre secondaire
// ============================================
function toggleSecondaryBar(section: SecondaryBarSection) {
  if (openSecondaryBar.value === section) {
    openSecondaryBar.value = null
  } else {
    openSecondaryBar.value = section
  }
}

// ============================================
// Gestion des paramètres de grille
// ============================================
function toggleGridSettings() {
  showGridSettings.value = !showGridSettings.value
}

function updateGridSettings() {
  emit('gridSettingsChanged', {
    showGrid: showGrid.value,
    gridColor: gridColor.value,
    gridOpacity: gridOpacity.value,
  })
}

function toggleShowGrid() {
  showGrid.value = !showGrid.value
  updateGridSettings()
}

// Synchroniser les paramètres de grille avec la carte active
function syncGridSettings() {
  if (mapStore.activeMap?.settings) {
    const settings = mapStore.activeMap.settings
    showGrid.value = settings.showGrid ?? true
    gridColor.value = settings.gridColor || '#ffffff'
    gridOpacity.value = settings.gridOpacity ?? 0.1
  }
}

// ============================================
// Lifecycle
// ============================================
onMounted(async () => {
  // Charger toutes les cartes du jeu si nécessaire
  if (props.isGameMaster && mapStore.allMaps.length === 0) {
    try {
      await mapStore.loadGameMaps(props.gameId)
    } catch (error) {
      console.error('Erreur lors du chargement des cartes:', error)
    }
  }

  // Synchroniser les paramètres de grille
  syncGridSettings()

  // Fermer le dropdown si on clique à l'extérieur
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})

// Watcher pour synchroniser les paramètres de grille quand la carte active change
watch(
  () => mapStore.activeMap,
  () => {
    syncGridSettings()
  }
)

// ============================================
// Actions
// ============================================
function selectTool(toolId: string) {
  selectedTool.value = toolId
  emit('toolChanged', toolId)
}

function adjustZoom(delta: number) {
  const newZoom = zoomLevel.value + delta
  zoomLevel.value = Math.max(25, Math.min(200, newZoom))
  emit('zoomChanged', zoomLevel.value)
}

function startEditingZoom() {
  isEditingZoom.value = true
  zoomInputValue.value = zoomLevel.value.toString()
  // Focus sur l'input après le rendu
  setTimeout(() => {
    const input = document.getElementById('zoom-input')
    if (input) {
      ;(input as HTMLInputElement).select()
    }
  }, 50)
}

function applyZoomValue() {
  const value = parseInt(zoomInputValue.value)
  if (!isNaN(value)) {
    const clampedValue = Math.max(25, Math.min(200, value))
    zoomLevel.value = clampedValue
    zoomInputValue.value = clampedValue.toString()
    emit('zoomChanged', zoomLevel.value)
  } else {
    // Si invalide, remettre la valeur actuelle
    zoomInputValue.value = zoomLevel.value.toString()
  }
  isEditingZoom.value = false
}

function handleZoomInputKeydown(event: KeyboardEvent) {
  if (event.key === 'Enter') {
    applyZoomValue()
  } else if (event.key === 'Escape') {
    isEditingZoom.value = false
    zoomInputValue.value = zoomLevel.value.toString()
  }
}

function centerMap() {
  emit('centerMap')
}

// ============================================
// Actions - Gestion des cartes (GM)
// ============================================
async function selectMap(map: GameMap) {
  if (!props.isGameMaster) return

  try {
    await mapStore.activateMap(props.gameId, map.id)
    showMapDropdown.value = false
    mapSearchQuery.value = ''
  } catch (error) {
    console.error("Erreur lors de l'activation de la carte:", error)
  }
}

function openUploadModal() {
  emit('openUploadModal')
}

function toggleMapDropdown() {
  showMapDropdown.value = !showMapDropdown.value
  if (showMapDropdown.value) {
    // Focus sur l'input de recherche
    setTimeout(() => {
      const input = document.getElementById('map-search-input')
      input?.focus()
    }, 100)
  }
}

function editMapConfirm(map: GameMap, event: Event) {
  event.stopPropagation() // Empêcher l'activation de la carte
  emit('openEditModal', map)
}

async function deleteMapConfirm(map: GameMap, event: Event) {
  event.stopPropagation() // Empêcher l'activation de la carte

  if (!confirm(`Êtes-vous sûr de vouloir supprimer la carte "${map.name}" ?`)) {
    return
  }

  try {
    await mapStore.deleteMap(map.id)
  } catch (error) {
    console.error('Erreur lors de la suppression de la carte:', error)
    alert('Erreur lors de la suppression de la carte')
  }
}
</script>

<template>
  <div class="flex-shrink-0">
    <!-- Barre principale -->
    <div class="bg-secondary-800 border-b border-secondary-700 px-4 py-2 flex items-center gap-2">
      <!-- Bouton Carte (MJ uniquement) -->
      <button
        v-if="isGameMaster"
        @click="toggleSecondaryBar('maps')"
        :class="[
          'px-4 py-2 rounded-lg font-medium transition-all flex items-center gap-2',
          openSecondaryBar === 'maps'
            ? 'bg-primary-500 text-white shadow-purple'
            : 'bg-secondary-700 text-secondary-300 hover:bg-secondary-600',
        ]"
        title="Gestion des cartes"
      >
        <span>🗺️</span>
        <span class="text-sm">Carte</span>
      </button>

      <!-- Bouton Outils -->
      <button
        @click="toggleSecondaryBar('tools')"
        :class="[
          'px-4 py-2 rounded-lg font-medium transition-all flex items-center gap-2',
          openSecondaryBar === 'tools'
            ? 'bg-primary-500 text-white shadow-purple'
            : 'bg-secondary-700 text-secondary-300 hover:bg-secondary-600',
        ]"
        title="Outils de dessin"
      >
        <span>🛠️</span>
        <span class="text-sm">Outils</span>
      </button>

      <!-- Spacer -->
      <div class="flex-1"></div>

      <!-- Contrôles de zoom -->
      <div class="flex items-center gap-2">
        <button
          @click="adjustZoom(-25)"
          class="px-3 py-2 bg-secondary-700 text-secondary-300 rounded-lg hover:bg-secondary-600 font-bold transition-colors"
          title="Dézoomer"
        >
          −
        </button>

        <!-- Input de zoom éditable -->
        <div class="relative min-w-[80px]">
          <button
            v-if="!isEditingZoom"
            @click="startEditingZoom"
            class="w-full px-4 py-2 bg-secondary-700 text-secondary-300 rounded-lg hover:bg-secondary-600 font-mono transition-colors"
            title="Cliquer pour entrer une valeur personnalisée"
          >
            {{ zoomLevel }}%
          </button>
          <input
            v-else
            id="zoom-input"
            v-model="zoomInputValue"
            type="number"
            min="25"
            max="200"
            @blur="applyZoomValue"
            @keydown="handleZoomInputKeydown"
            class="w-full px-4 py-2 bg-secondary-900 text-white border-2 border-primary-500 rounded-lg font-mono text-center focus:outline-none"
          />
        </div>

        <button
          @click="adjustZoom(25)"
          class="px-3 py-2 bg-secondary-700 text-secondary-300 rounded-lg hover:bg-secondary-600 font-bold transition-colors"
          title="Zoomer"
        >
          +
        </button>
      </div>

      <!-- Centrer -->
      <button
        @click="centerMap"
        class="px-4 py-2 bg-secondary-700 text-secondary-300 rounded-lg hover:bg-secondary-600 flex items-center gap-2 transition-colors"
        title="Centrer la vue"
      >
        <span>🎯</span>
        <span class="text-sm">Centrer</span>
      </button>
    </div>

    <!-- Barre secondaire -->
    <Transition name="slide-down">
      <div
        v-if="openSecondaryBar"
        class="bg-secondary-800/95 border-b border-secondary-700 px-4 py-2 flex items-center gap-2"
      >
        <!-- Section Carte -->
        <template v-if="openSecondaryBar === 'maps' && isGameMaster">
          <!-- Sélecteur de carte -->
          <div class="relative map-selector-container">
            <button
              @click="toggleMapDropdown"
              class="px-4 py-2 bg-secondary-700 text-secondary-300 rounded-lg hover:bg-secondary-600 flex items-center gap-2 min-w-[200px] transition-colors"
              title="Sélectionner une carte"
            >
              <span>📋</span>
              <span class="text-sm flex-1 text-left truncate">
                {{ mapStore.activeMap?.name || 'Aucune carte active' }}
              </span>
              <svg
                class="w-4 h-4 transition-transform"
                :class="{ 'rotate-180': showMapDropdown }"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M19 9l-7 7-7-7"
                />
              </svg>
            </button>

            <!-- Dropdown -->
            <Transition name="dropdown">
              <div
                v-if="showMapDropdown"
                class="absolute top-full left-0 mt-2 w-80 bg-secondary-800 border border-secondary-700 rounded-lg shadow-2xl z-50 max-h-96 overflow-hidden flex flex-col"
              >
                <!-- Input de recherche -->
                <div class="p-3 border-b border-secondary-700">
                  <input
                    id="map-search-input"
                    v-model="mapSearchQuery"
                    type="text"
                    placeholder="Filtrer les cartes..."
                    class="w-full px-3 py-2 bg-secondary-900 border border-secondary-600 rounded-lg text-white placeholder-secondary-500 focus:outline-none focus:ring-2 focus:ring-primary-500 text-sm"
                    @click.stop
                  />
                </div>

                <!-- Liste des cartes -->
                <div class="overflow-y-auto flex-1">
                  <button
                    v-for="map in filteredMaps"
                    :key="map.id"
                    @click="selectMap(map)"
                    :class="[
                      'w-full px-4 py-3 text-left hover:bg-secondary-700 transition-colors flex items-center gap-3 group',
                      map.id === activeMapId
                        ? 'bg-primary-500/20 text-primary-300'
                        : 'text-secondary-300',
                    ]"
                  >
                    <span class="text-lg">🗺️</span>
                    <div class="flex-1 min-w-0">
                      <div class="font-medium truncate">{{ map.name }}</div>
                      <div v-if="map.description" class="text-xs text-secondary-500 truncate">
                        {{ map.description }}
                      </div>
                    </div>
                    <span v-if="map.id === activeMapId" class="text-xs text-primary-400 mr-2"
                      >✓ Active</span
                    >
                    <button
                      @click="editMapConfirm(map, $event)"
                      class="p-2 rounded-lg text-secondary-400 hover:text-blue-400 hover:bg-blue-900/20 transition-colors opacity-0 group-hover:opacity-100"
                      title="Éditer cette carte"
                    >
                      <PencilIcon class="w-5 h-5" />
                    </button>
                    <button
                      @click="deleteMapConfirm(map, $event)"
                      class="p-2 rounded-lg text-secondary-400 hover:text-red-400 hover:bg-red-900/20 transition-colors opacity-0 group-hover:opacity-100"
                      title="Supprimer cette carte"
                    >
                      🗑️
                    </button>
                  </button>

                  <div
                    v-if="filteredMaps.length === 0"
                    class="px-4 py-6 text-center text-secondary-500 text-sm"
                  >
                    Aucune carte trouvée
                  </div>
                </div>
              </div>
            </Transition>
          </div>

          <!-- Bouton créer une carte -->
          <button
            @click="openUploadModal"
            class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg flex items-center gap-2 transition-colors"
            title="Créer une nouvelle carte"
          >
            <span>➕</span>
            <span class="text-sm font-medium">Nouvelle carte</span>
          </button>

          <!-- Séparateur -->
          <div class="h-8 w-px bg-secondary-600 mx-2"></div>

          <!-- Paramètres de grille -->
          <div class="relative">
            <button
              @click="toggleGridSettings"
              class="px-4 py-2 bg-secondary-700 text-secondary-300 rounded-lg hover:bg-secondary-600 flex items-center gap-2 transition-colors"
              title="Paramètres de grille"
            >
              <span>⚙️</span>
              <span class="text-sm">Grille</span>
            </button>

            <!-- Dropdown des paramètres -->
            <Transition name="dropdown">
              <div
                v-if="showGridSettings"
                class="absolute top-full right-0 mt-2 w-72 bg-secondary-800 border border-secondary-700 rounded-lg shadow-2xl z-50 p-4"
              >
                <h3 class="text-sm font-semibold text-white mb-3">Paramètres de la grille</h3>

                <!-- Toggle affichage grille -->
                <div class="flex items-center justify-between mb-4">
                  <label class="text-sm text-secondary-300">Afficher la grille</label>
                  <button
                    @click="toggleShowGrid"
                    :class="[
                      'relative w-12 h-6 rounded-full transition-colors',
                      showGrid ? 'bg-primary-500' : 'bg-secondary-600',
                    ]"
                  >
                    <span
                      :class="[
                        'absolute top-1 w-4 h-4 bg-white rounded-full transition-transform',
                        showGrid ? 'left-7' : 'left-1',
                      ]"
                    ></span>
                  </button>
                </div>

                <!-- Couleur de la grille -->
                <div class="mb-4">
                  <label class="text-sm text-secondary-300 block mb-2">Couleur</label>
                  <div class="flex items-center gap-2">
                    <input
                      v-model="gridColor"
                      type="color"
                      @change="updateGridSettings"
                      class="w-12 h-8 rounded cursor-pointer bg-secondary-900 border border-secondary-600"
                    />
                    <input
                      v-model="gridColor"
                      type="text"
                      @change="updateGridSettings"
                      class="flex-1 px-3 py-1 bg-secondary-900 border border-secondary-600 rounded text-white text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
                      placeholder="#ffffff"
                    />
                  </div>
                </div>

                <!-- Opacité de la grille -->
                <div>
                  <div class="flex items-center justify-between mb-2">
                    <label class="text-sm text-secondary-300">Opacité</label>
                    <span class="text-sm text-white font-mono"
                      >{{ Math.round(gridOpacity * 100) }}%</span
                    >
                  </div>
                  <input
                    v-model.number="gridOpacity"
                    type="range"
                    min="0"
                    max="1"
                    step="0.05"
                    @input="updateGridSettings"
                    class="w-full h-2 bg-secondary-600 rounded-lg appearance-none cursor-pointer"
                  />
                </div>
              </div>
            </Transition>
          </div>
        </template>

        <!-- Section Outils -->
        <template v-if="openSecondaryBar === 'tools'">
          <div class="flex items-center gap-1">
            <button
              v-for="tool in availableTools"
              :key="tool.id"
              @click="selectTool(tool.id)"
              :class="[
                'px-4 py-2 rounded-lg font-medium transition-all flex items-center gap-2',
                selectedTool === tool.id
                  ? 'bg-primary-500 text-white shadow-purple'
                  : 'bg-secondary-700 text-secondary-300 hover:bg-secondary-600',
              ]"
              :title="tool.label"
            >
              <span>{{ tool.icon }}</span>
              <span class="text-sm">{{ tool.label }}</span>
            </button>
          </div>
        </template>
      </div>
    </Transition>
  </div>
</template>

<style scoped>
.shadow-purple {
  box-shadow: 0 4px 14px 0 rgba(99, 102, 241, 0.39);
}

/* Transitions pour le dropdown */
.dropdown-enter-active,
.dropdown-leave-active {
  transition: all 0.2s ease;
}

.dropdown-enter-from,
.dropdown-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

/* Transition pour la barre secondaire */
.slide-down-enter-active,
.slide-down-leave-active {
  transition: all 0.3s ease;
  max-height: 200px;
}

.slide-down-enter-from,
.slide-down-leave-to {
  opacity: 0;
  max-height: 0;
  transform: translateY(-10px);
}
</style>
