<script setup lang="ts">
import { ref, computed } from 'vue'
import { useChatStore } from '@/stores/chatStore'

const props = defineProps<{
  gameId: number
}>()

const chatStore = useChatStore()

// État local
const formula = ref('')
const modifier = ref(0)
const isInCharacter = ref(true)
const lastResult = ref<{
  formula: string
  total: number
  rolls: number[]
  timestamp: string
} | null>(null)

// ============================================
// Dés rapides prédéfinis
// ============================================
const quickDice = [
  { label: 'd20', value: '1d20', color: 'bg-primary-500', emoji: '🎲' },
  { label: 'd12', value: '1d12', color: 'bg-accent-rose', emoji: '🔶' },
  { label: 'd10', value: '1d10', color: 'bg-accent-amber', emoji: '🔟' },
  { label: 'd8', value: '1d8', color: 'bg-accent-cyan', emoji: '🔷' },
  { label: 'd6', value: '1d6', color: 'bg-accent-emerald', emoji: '🎲' },
  { label: 'd4', value: '1d4', color: 'bg-accent-purple', emoji: '🔺' },
]

const commonRolls = [
  { label: 'Initiative', formula: '1d20', icon: '⚡' },
  { label: 'Attaque', formula: '1d20+5', icon: '⚔️' },
  { label: 'Dégâts (épée)', formula: '1d8+3', icon: '🗡️' },
  { label: 'Dégâts (arc)', formula: '1d6+2', icon: '🏹' },
  { label: 'Jet de sauvegarde', formula: '1d20+2', icon: '🛡️' },
  { label: 'Soin (potion)', formula: '2d4+2', icon: '💊' },
]

// ============================================
// Computed
// ============================================
const fullFormula = computed(() => {
  if (!formula.value) return ''

  if (modifier.value === 0) return formula.value

  const sign = modifier.value > 0 ? '+' : ''
  return `${formula.value}${sign}${modifier.value}`
})

const canRoll = computed(() => {
  return formula.value.match(/^\d+d\d+/) !== null
})

// ============================================
// Actions - Utilise chatStore.rollDice
// ============================================
async function rollDice() {
  if (!canRoll.value) return

  try {
    console.log('🎲 Lancer de dés:', fullFormula.value)

    const result = await chatStore.rollDice(props.gameId, fullFormula.value, isInCharacter.value)

    // Sauvegarder le résultat pour l'affichage
    if (result.diceResult) {
      lastResult.value = {
        formula: fullFormula.value,
        total: result.diceResult.total,
        rolls: result.diceResult.results,
        timestamp: result.createdAt,
      }
      console.log('✅ Résultat:', lastResult.value)
    }

    // Réinitialiser le formulaire
    formula.value = ''
    modifier.value = 0
  } catch (error) {
    console.error('❌ Erreur lors du lancer de dés:', error)
  }
}

function useQuickDice(diceFormula: string) {
  formula.value = diceFormula
}

async function useCommonRoll(rollFormula: string) {
  formula.value = rollFormula
  await rollDice()
}

function clearFormula() {
  formula.value = ''
  modifier.value = 0
}

function addToFormula(text: string) {
  formula.value += text
}
</script>

<template>
  <div class="flex-1 overflow-y-auto p-4">
    <!-- Header -->
    <div class="mb-6">
      <h3 class="font-bold text-secondary-50 text-lg mb-2 flex items-center gap-2">
        <span>🎲</span>
        Lanceur de dés
      </h3>
      <p class="text-sm text-secondary-400">Formule personnalisée ou raccourcis rapides</p>
    </div>

    <!-- Dés rapides -->
    <div class="mb-6">
      <label class="block text-sm font-medium text-secondary-300 mb-3"> Dés rapides </label>
      <div class="grid grid-cols-3 gap-2">
        <button
          v-for="dice in quickDice"
          :key="dice.value"
          @click="useQuickDice(dice.value)"
          :class="[
            'px-4 py-3 rounded-lg font-bold text-white transition-all hover:scale-105 shadow-md',
            dice.color,
            formula === dice.value ? 'ring-2 ring-white scale-105' : '',
          ]"
        >
          <div class="text-xl mb-1">{{ dice.emoji }}</div>
          <div class="text-sm">{{ dice.label }}</div>
        </button>
      </div>
    </div>

    <!-- Formule personnalisée -->
    <div class="mb-4">
      <label class="block text-sm font-medium text-secondary-300 mb-2">
        Formule personnalisée
      </label>
      <div class="flex gap-2">
        <input
          v-model="formula"
          type="text"
          placeholder="2d6, 1d20, 3d8..."
          class="form-input flex-1 font-mono"
        />
        <button
          v-if="formula"
          @click="clearFormula"
          class="px-3 py-2 bg-secondary-700 text-secondary-300 rounded-lg hover:bg-secondary-600 transition-colors"
          title="Effacer"
        >
          ✕
        </button>
      </div>

      <!-- Boutons pour construire la formule -->
      <div class="grid grid-cols-4 gap-2 mt-2">
        <button
          v-for="num in [1, 2, 3, 4]"
          :key="num"
          @click="addToFormula(num.toString())"
          class="px-3 py-2 bg-secondary-700 text-secondary-300 rounded-lg hover:bg-secondary-600 transition-colors font-mono"
        >
          {{ num }}
        </button>
        <button
          @click="addToFormula('d')"
          class="px-3 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition-colors font-bold"
        >
          d
        </button>
        <button
          @click="addToFormula('+')"
          class="px-3 py-2 bg-secondary-700 text-secondary-300 rounded-lg hover:bg-secondary-600 transition-colors font-mono"
        >
          +
        </button>
        <button
          @click="addToFormula('-')"
          class="px-3 py-2 bg-secondary-700 text-secondary-300 rounded-lg hover:bg-secondary-600 transition-colors font-mono"
        >
          −
        </button>
        <button
          @click="formula = formula.slice(0, -1)"
          class="px-3 py-2 bg-error/80 text-white rounded-lg hover:bg-error transition-colors"
        >
          ⌫
        </button>
      </div>
    </div>

    <!-- Modificateur -->
    <div class="mb-6">
      <label class="block text-sm font-medium text-secondary-300 mb-2"> Modificateur </label>
      <div class="flex items-center gap-2">
        <button
          @click="modifier--"
          class="px-4 py-2 bg-secondary-700 text-secondary-300 rounded-lg hover:bg-secondary-600 font-bold transition-colors"
        >
          −
        </button>
        <input
          v-model.number="modifier"
          type="number"
          class="form-input text-center w-20 font-mono font-bold"
        />
        <button
          @click="modifier++"
          class="px-4 py-2 bg-secondary-700 text-secondary-300 rounded-lg hover:bg-secondary-600 font-bold transition-colors"
        >
          +
        </button>
        <div class="flex-1 text-right">
          <span class="text-secondary-50 font-mono text-lg font-bold">
            {{ fullFormula || 'Aucune formule' }}
          </span>
        </div>
      </div>
    </div>

    <!-- Options -->
    <div class="mb-6">
      <label class="flex items-center gap-2 cursor-pointer">
        <input
          v-model="isInCharacter"
          type="checkbox"
          class="w-4 h-4 rounded bg-secondary-700 border-secondary-600 text-primary-500 focus:ring-primary-500"
        />
        <span class="text-sm text-secondary-300"> Lancer en tant que personnage (IC) </span>
      </label>
    </div>

    <!-- Bouton de lancer -->
    <button
      @click="rollDice"
      :disabled="!canRoll || chatStore.isSending"
      class="btn-primary w-full py-4 text-lg font-bold shadow-purple mb-6 flex items-center justify-center gap-2"
    >
      <span class="text-2xl">🎲</span>
      <span>Lancer {{ fullFormula || 'les dés' }}</span>
    </button>

    <!-- Lancers communs -->
    <div class="mb-6">
      <label class="block text-sm font-medium text-secondary-300 mb-3"> Lancers courants </label>
      <div class="space-y-2">
        <button
          v-for="roll in commonRolls"
          :key="roll.label"
          @click="useCommonRoll(roll.formula)"
          class="w-full px-4 py-3 bg-secondary-700 text-secondary-50 rounded-lg hover:bg-secondary-600 transition-colors text-left flex items-center justify-between group"
        >
          <div class="flex items-center gap-3">
            <span class="text-xl">{{ roll.icon }}</span>
            <span class="font-medium">{{ roll.label }}</span>
          </div>
          <span class="text-secondary-400 font-mono text-sm group-hover:text-secondary-200">
            {{ roll.formula }}
          </span>
        </button>
      </div>
    </div>

    <!-- Dernier résultat -->
    <Transition name="slide-up">
      <div v-if="lastResult" class="card bg-gradient-primary p-6 shadow-purple">
        <div class="text-center">
          <div class="text-sm text-primary-100 mb-2 font-medium">
            {{ lastResult.formula }}
          </div>
          <div class="text-6xl font-bold text-white mb-3">
            {{ lastResult.total }}
          </div>
          <div class="text-sm text-primary-100">Détails: {{ lastResult.rolls.join(' + ') }}</div>
          <div class="text-xs text-primary-200 mt-2">
            {{ new Date(lastResult.timestamp).toLocaleTimeString('fr-FR') }}
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<style scoped>
.gradient-primary {
  background: linear-gradient(135deg, #6366f1, #818cf8);
}

.shadow-purple {
  box-shadow: 0 4px 14px 0 rgba(99, 102, 241, 0.39);
}

.slide-up-enter-active,
.slide-up-leave-active {
  transition: all 0.3s ease;
}

.slide-up-enter-from {
  transform: translateY(20px);
  opacity: 0;
}

.slide-up-leave-to {
  transform: translateY(-20px);
  opacity: 0;
}
</style>
