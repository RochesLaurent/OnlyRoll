<script setup lang="ts">
import { ref, computed } from 'vue'
import { useMapStore } from '@/stores/mapStore'
import type { GameMap, GameToken } from '@/types/game'
import { TokenType } from '@/types/game'

const props = defineProps<{
  map: GameMap | null
  tokens: GameToken[]
  editable: boolean
  selectedTool: string
  zoom: number
}>()

const mapStore = useMapStore()

// État local
const selectedTokenId = ref<number | null>(null)
const draggingToken = ref<number | null>(null)
const dragStartPos = ref({ x: 0, y: 0 })

// Ref pour le conteneur scrollable
const mapContainer = ref<HTMLElement | null>(null)

// Dimensions de la grille
const gridSize = computed(() => props.map?.gridSize || 50)
const mapWidth = computed(() => (props.map?.width || 20) * gridSize.value)
const mapHeight = computed(() => (props.map?.height || 20) * gridSize.value)

// URL complète de l'image de la carte
const mapImageUrl = computed(() => {
  if (!props.map?.imageUrl) return null

  // Si l'URL commence par http:// ou https://, la retourner telle quelle
  if (props.map.imageUrl.startsWith('http://') || props.map.imageUrl.startsWith('https://')) {
    return props.map.imageUrl
  }

  // Sinon, ajouter l'URL du backend (sans /api car les uploads sont servis directement)
  // On extrait le domaine de VITE_API_URL en retirant /api
  const apiUrl = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'
  const baseUrl = apiUrl.replace(/\/api$/, '')
  return `${baseUrl}${props.map.imageUrl}`
})

// Calculer le scale du zoom (100% = 1.0, 50% = 0.5, 200% = 2.0)
const zoomScale = computed(() => props.zoom / 100)

// ============================================
// Gestion des tokens - Utilise mapStore
// ============================================
function selectToken(tokenId: number) {
  if (selectedTokenId.value === tokenId) {
    selectedTokenId.value = null
  } else {
    selectedTokenId.value = tokenId
  }
}

function handleTokenMouseDown(event: MouseEvent, token: GameToken) {
  if (!props.editable || token.isLocked || props.selectedTool !== 'select') return

  draggingToken.value = token.id
  dragStartPos.value = { x: token.x, y: token.y }

  event.preventDefault()
}

function handleMouseMove(event: MouseEvent) {
  if (!draggingToken.value || !props.editable) return

  const container = event.currentTarget as HTMLElement
  const rect = container.getBoundingClientRect()

  const x = Math.floor((event.clientX - rect.left) / gridSize.value)
  const y = Math.floor((event.clientY - rect.top) / gridSize.value)

  // Contraindre aux limites de la carte
  const constrainedX = Math.max(0, Math.min(x, (props.map?.width || 20) - 1))
  const constrainedY = Math.max(0, Math.min(y, (props.map?.height || 20) - 1))

  // Mettre à jour la position visuellement
  const tokenElement = document.querySelector(
    `[data-token-id="${draggingToken.value}"]`
  ) as HTMLElement
  if (tokenElement) {
    tokenElement.style.left = `${constrainedX * gridSize.value}px`
    tokenElement.style.top = `${constrainedY * gridSize.value}px`
  }
}

async function handleMouseUp() {
  if (!draggingToken.value) return

  const tokenElement = document.querySelector(
    `[data-token-id="${draggingToken.value}"]`
  ) as HTMLElement
  if (tokenElement) {
    const x = Math.floor(parseInt(tokenElement.style.left) / gridSize.value)
    const y = Math.floor(parseInt(tokenElement.style.top) / gridSize.value)

    // Vérifier si la position a changé
    if (x !== dragStartPos.value.x || y !== dragStartPos.value.y) {
      try {
        // Utilise la fonction moveToken du mapStore
        await mapStore.moveToken(draggingToken.value, x, y)
        console.log('Token déplacé:', { id: draggingToken.value, x, y })
      } catch (error) {
        console.error('Erreur déplacement token:', error)
        // Restaurer la position originale
        tokenElement.style.left = `${dragStartPos.value.x * gridSize.value}px`
        tokenElement.style.top = `${dragStartPos.value.y * gridSize.value}px`
      }
    }
  }

  draggingToken.value = null
}

// ============================================
// Actions sur les tokens - Utilise mapStore
// ============================================
async function toggleTokenVisibility(tokenId: number) {
  try {
    await mapStore.toggleTokenVisibility(tokenId)
    console.log('Visibilité du token changée')
  } catch (error) {
    console.error('Erreur toggle visibility:', error)
  }
}

async function toggleTokenLock(tokenId: number) {
  try {
    await mapStore.toggleTokenLock(tokenId)
    console.log('Verrouillage du token changé')
  } catch (error) {
    console.error('Erreur toggle lock:', error)
  }
}

async function deleteToken(tokenId: number) {
  if (!confirm('Supprimer ce token ?')) return

  try {
    await mapStore.deleteToken(tokenId)
    selectedTokenId.value = null
    console.log('Token supprimé')
  } catch (error) {
    console.error('Erreur suppression token:', error)
  }
}

// ============================================
// Helpers
// ============================================
function getTokenColor(type: TokenType): string {
  const colors = {
    [TokenType.CHARACTER]: '#6366f1',
    [TokenType.MONSTER]: '#ef4444',
    [TokenType.NPC]: '#10b981',
    [TokenType.OBJECT]: '#f59e0b',
  }
  return colors[type] || '#6366f1'
}

function getTokenSize(token: GameToken): number {
  return gridSize.value * (token.size || 1)
}

// ============================================
// Fonction de centrage
// ============================================
function centerView() {
  if (!mapContainer.value) return

  let targetX = 0
  let targetY = 0

  // Si un token est sélectionné, centrer sur lui
  if (selectedTokenId.value) {
    const token = props.tokens.find((t) => t.id === selectedTokenId.value)
    if (token) {
      // Position du centre du token en pixels
      const tokenCenterX = (token.x + (token.size || 1) / 2) * gridSize.value
      const tokenCenterY = (token.y + (token.size || 1) / 2) * gridSize.value

      // Appliquer le zoom
      targetX = tokenCenterX * zoomScale.value
      targetY = tokenCenterY * zoomScale.value
    }
  } else {
    // Sinon, centrer sur le centre de la carte
    targetX = (mapWidth.value / 2) * zoomScale.value
    targetY = (mapHeight.value / 2) * zoomScale.value
  }

  // Calculer la position de scroll pour centrer
  const container = mapContainer.value
  const scrollLeft = targetX - container.clientWidth / 2
  const scrollTop = targetY - container.clientHeight / 2

  // Animer le scroll
  container.scrollTo({
    left: scrollLeft,
    top: scrollTop,
    behavior: 'smooth',
  })
}

// Exposer la fonction pour l'utiliser depuis le parent
defineExpose({
  centerView,
  selectedTokenId,
})
</script>

<template>
  <div
    ref="mapContainer"
    class="w-full h-full relative overflow-auto select-none bg-secondary-900"
    @mousemove="handleMouseMove"
    @mouseup="handleMouseUp"
    @mouseleave="handleMouseUp"
  >
    <!-- Container de la carte avec dimensions et zoom -->
    <div
      class="relative bg-cover bg-center transition-transform duration-200"
      :style="{
        width: mapWidth + 'px',
        height: mapHeight + 'px',
        minWidth: '100%',
        minHeight: '100%',
        backgroundImage: mapImageUrl
          ? `url(${mapImageUrl})`
          : 'linear-gradient(135deg, #1a0b2e 0%, #0f172a 100%)',
        transform: `scale(${zoomScale})`,
        transformOrigin: 'center center',
      }"
    >
      <!-- Grille -->
      <div
        v-if="map?.gridType === 'square'"
        class="absolute inset-0 pointer-events-none"
        :style="{
          backgroundImage: `
            linear-gradient(to right, rgba(255,255,255,0.1) 1px, transparent 1px),
            linear-gradient(to bottom, rgba(255,255,255,0.1) 1px, transparent 1px)
          `,
          backgroundSize: `${gridSize}px ${gridSize}px`,
        }"
      />

      <!-- Tokens -->
      <div
        v-for="token in tokens"
        :key="token.id"
        :data-token-id="token.id"
        @mousedown="(e) => handleTokenMouseDown(e, token)"
        @click.stop="selectToken(token.id)"
        class="absolute transition-all hover:scale-110"
        :class="{
          'ring-4 ring-primary-500 scale-110 z-20': selectedTokenId === token.id,
          'cursor-move': editable && selectedTool === 'select' && !token.isLocked,
          'cursor-pointer': selectedTool === 'select',
          'opacity-50': !token.isVisible,
          'z-10': selectedTokenId !== token.id,
        }"
        :style="{
          left: `${token.x * gridSize}px`,
          top: `${token.y * gridSize}px`,
          width: `${getTokenSize(token)}px`,
          height: `${getTokenSize(token)}px`,
        }"
      >
        <!-- Avatar du token -->
        <div
          class="w-full h-full rounded-full flex items-center justify-center font-bold text-white shadow-lg border-2 border-white overflow-hidden"
          :style="{ backgroundColor: getTokenColor(token.type) }"
        >
          <img
            v-if="token.imageUrl"
            :src="token.imageUrl"
            :alt="token.name"
            class="w-full h-full object-cover"
          />
          <span v-else class="text-xl">
            {{ token.name.slice(0, 2).toUpperCase() }}
          </span>
        </div>

        <!-- Indicateurs -->
        <div class="absolute -top-1 -right-1 flex gap-1">
          <span v-if="token.isLocked" class="text-xs">🔒</span>
          <span v-if="!token.isVisible" class="text-xs">👁️</span>
        </div>

        <!-- Nom du token -->
        <div
          class="absolute -bottom-6 left-1/2 -translate-x-1/2 whitespace-nowrap text-xs font-medium px-2 py-1 rounded shadow-lg"
          :class="token.isVisible ? 'bg-black/80 text-white' : 'bg-gray-500/80 text-gray-300'"
        >
          {{ token.name }}
        </div>
      </div>

      <!-- Actions rapides pour token sélectionné -->
      <Transition name="fade">
        <div
          v-if="selectedTokenId && editable"
          class="fixed bottom-4 left-1/2 -translate-x-1/2 bg-secondary-800 border border-secondary-700 rounded-lg p-4 shadow-lg z-30"
        >
          <div class="flex gap-2">
            <button
              @click="toggleTokenVisibility(selectedTokenId)"
              class="btn-secondary text-sm"
              title="Toggle visibilité"
            >
              <span v-if="tokens.find((t) => t.id === selectedTokenId)?.isVisible">👁️</span>
              <span v-else>🚫</span>
              Visibilité
            </button>
            <button
              @click="toggleTokenLock(selectedTokenId)"
              class="btn-secondary text-sm"
              title="Verrouiller/Déverrouiller"
            >
              <span v-if="tokens.find((t) => t.id === selectedTokenId)?.isLocked">🔓</span>
              <span v-else>🔒</span>
              Verrouiller
            </button>
            <button
              @click="deleteToken(selectedTokenId)"
              class="px-3 py-2 bg-error text-white rounded-lg hover:bg-red-600 text-sm"
              title="Supprimer"
            >
              🗑️ Supprimer
            </button>
          </div>
        </div>
      </Transition>
    </div>
  </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
