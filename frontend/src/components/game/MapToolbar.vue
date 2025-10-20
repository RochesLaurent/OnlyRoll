<script setup lang="ts">
import { ref, computed } from 'vue'

const props = defineProps<{
  isGameMaster: boolean
}>()

const emit = defineEmits<{
  toolChanged: [tool: string]
}>()

// État local
const selectedTool = ref('select')
const zoomLevel = ref(100)

// Outils disponibles
const tools = [
  { id: 'select', icon: '🖱️', label: 'Sélection', gmOnly: false },
  { id: 'token', icon: '🎭', label: 'Ajouter Token', gmOnly: true },
  { id: 'fog', icon: '☁️', label: 'Brouillard', gmOnly: true },
  { id: 'measure', icon: '📏', label: 'Mesure', gmOnly: false },
  { id: 'draw', icon: '✏️', label: 'Dessin', gmOnly: false },
  { id: 'ping', icon: '📍', label: 'Ping', gmOnly: false }
]

// Filtrer les outils selon le rôle
const availableTools = computed(() => {
  return tools.filter(tool => !tool.gmOnly || props.isGameMaster)
})

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
}

function resetZoom() {
  zoomLevel.value = 100
}

function centerMap() {
  // Émettre un événement pour centrer la carte
  console.log('Centrer la carte')
  // TODO: Implémenter la logique de centrage
}
</script>

<template>
  <div class="bg-secondary-800 border-b border-secondary-700 px-4 py-2 flex items-center gap-2 flex-shrink-0">
    <!-- Outils -->
    <div class="flex items-center gap-1">
      <button
        v-for="tool in availableTools"
        :key="tool.id"
        @click="selectTool(tool.id)"
        :class="[
          'px-4 py-2 rounded-lg font-medium transition-all flex items-center gap-2',
          selectedTool === tool.id
            ? 'bg-primary-500 text-white shadow-purple'
            : 'bg-secondary-700 text-secondary-300 hover:bg-secondary-600'
        ]"
        :title="tool.label"
      >
        <span>{{ tool.icon }}</span>
        <span class="text-sm">{{ tool.label }}</span>
      </button>
    </div>

    <!-- Séparateur -->
    <div class="h-8 w-px bg-secondary-700 mx-2"></div>

    <!-- Contrôles de zoom -->
    <div class="flex items-center gap-2">
      <button
        @click="adjustZoom(-25)"
        class="px-3 py-2 bg-secondary-700 text-secondary-300 rounded-lg hover:bg-secondary-600 font-bold transition-colors"
        title="Dézoomer"
      >
        −
      </button>
      
      <button
        @click="resetZoom"
        class="px-4 py-2 bg-secondary-700 text-secondary-300 rounded-lg hover:bg-secondary-600 font-mono min-w-[80px] transition-colors"
        title="Réinitialiser le zoom"
      >
        {{ zoomLevel }}%
      </button>
      
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

    <!-- Badge MJ -->
    <div v-if="isGameMaster" class="ml-auto">
      <span class="px-3 py-1.5 bg-accent-purple text-white text-sm font-medium rounded-lg flex items-center gap-2 shadow-lg">
        <span>👑</span>
        <span>Maître du Jeu</span>
      </span>
    </div>
  </div>
</template>

<style scoped>
.shadow-purple {
  box-shadow: 0 4px 14px 0 rgba(99, 102, 241, 0.39);
}
</style>