<template>
  <Teleport to="body">
    <Transition name="konami" @enter="onEnter" @after-leave="onAfterLeave">
      <div v-if="show" class="konami-overlay" @click="close">
        <div class="konami-container">
          <!-- Message principal avec effet rétro -->
          <h1 class="konami-title">KONAMI CODE</h1>
          <h2 class="konami-subtitle">VICTORY!</h2>

          <!-- Score rétro -->
          <div class="konami-score">
            <span class="score-label">SCORE</span>
            <span class="score-value">{{ animatedScore }}</span>
          </div>

          <!-- Message de félicitations -->
          <p class="konami-message">🎮 Vous avez découvert l'easter egg ! 🎮</p>

          <!-- Bouton pour fermer -->
          <button class="konami-close" @click.stop="close">[ PRESS START ]</button>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import confetti from 'canvas-confetti'

const props = defineProps<{
  isActive: boolean
}>()

const emit = defineEmits<{
  close: []
}>()

const show = ref(false)
const animatedScore = ref(0)
const targetScore = 999999

/**
 * Joue le son rétro
 */
const playSound = () => {
  try {
    const audio = new Audio('/sounds/konami.mp3')
    audio.volume = 0.3
    audio.play().catch(() => {
      // Ignore les erreurs de lecture (permissions navigateur)
    })
  } catch {
    // Son non disponible, pas grave
  }
}

/**
 * Déclenche l'animation des confettis
 */
const triggerConfetti = () => {
  const duration = 3000
  const animationEnd = Date.now() + duration
  const defaults = { startVelocity: 30, spread: 360, ticks: 60, zIndex: 10000 }

  const randomInRange = (min: number, max: number) => {
    return Math.random() * (max - min) + min
  }

  const interval = setInterval(() => {
    const timeLeft = animationEnd - Date.now()

    if (timeLeft <= 0) {
      return clearInterval(interval)
    }

    const particleCount = 50 * (timeLeft / duration)

    // Confettis depuis la gauche
    confetti({
      ...defaults,
      particleCount,
      origin: { x: randomInRange(0.1, 0.3), y: Math.random() - 0.2 },
      colors: ['#FF0000', '#00FF00', '#0000FF', '#FFFF00', '#FF00FF', '#00FFFF'],
    })

    // Confettis depuis la droite
    confetti({
      ...defaults,
      particleCount,
      origin: { x: randomInRange(0.7, 0.9), y: Math.random() - 0.2 },
      colors: ['#FF0000', '#00FF00', '#0000FF', '#FFFF00', '#FF00FF', '#00FFFF'],
    })
  }, 250)
}

/**
 * Anime le compteur de score
 */
const animateScore = () => {
  const duration = 2000
  const frameDuration = 1000 / 60
  const totalFrames = Math.round(duration / frameDuration)
  let frame = 0

  const counter = setInterval(() => {
    frame++
    const progress = frame / totalFrames
    const easeOutQuad = 1 - (1 - progress) * (1 - progress)
    animatedScore.value = Math.round(targetScore * easeOutQuad)

    if (frame === totalFrames) {
      clearInterval(counter)
    }
  }, frameDuration)
}

/**
 * Callback appelé lors de l'entrée dans la transition
 */
const onEnter = () => {
  playSound()
  triggerConfetti()
  animateScore()
}

/**
 * Callback appelé après la sortie de la transition
 */
const onAfterLeave = () => {
  animatedScore.value = 0
}

/**
 * Ferme l'overlay
 */
const close = () => {
  show.value = false
  emit('close')
}

// Observe les changements de la prop isActive
watch(
  () => props.isActive,
  (newValue) => {
    if (newValue) {
      show.value = true
    }
  },
  { immediate: true }
)
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap');

.konami-overlay {
  position: fixed;
  inset: 0;
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0, 0, 0, 0.95);
  backdrop-filter: blur(8px);
  cursor: pointer;
}

.konami-container {
  text-align: center;
  padding: 2rem;
  animation: konami-pulse 2s ease-in-out infinite;
}

.konami-title {
  font-family: 'Press Start 2P', cursive;
  font-size: clamp(2rem, 8vw, 5rem);
  color: #ff0000;
  text-shadow:
    0 0 10px #ff0000,
    0 0 20px #ff0000,
    0 0 30px #ff0000,
    0 0 40px #ff0000,
    4px 4px 0 #000,
    -4px -4px 0 #000,
    4px -4px 0 #000,
    -4px 4px 0 #000;
  margin: 0 0 1rem 0;
  animation: konami-flicker 3s linear infinite;
  letter-spacing: 0.1em;
}

.konami-subtitle {
  font-family: 'Press Start 2P', cursive;
  font-size: clamp(1.5rem, 6vw, 4rem);
  color: #ffff00;
  text-shadow:
    0 0 10px #ffff00,
    0 0 20px #ffff00,
    0 0 30px #ffff00,
    3px 3px 0 #000,
    -3px -3px 0 #000,
    3px -3px 0 #000,
    -3px 3px 0 #000;
  margin: 0 0 2rem 0;
  animation: konami-flicker 3s linear infinite 0.5s;
  letter-spacing: 0.1em;
}

.konami-score {
  display: flex;
  flex-direction: column;
  align-items: center;
  margin: 2rem 0;
  padding: 1.5rem;
  background: rgba(0, 0, 0, 0.8);
  border: 4px solid #00ff00;
  border-radius: 8px;
  box-shadow:
    0 0 20px #00ff00,
    inset 0 0 20px rgba(0, 255, 0, 0.2);
}

.score-label {
  font-family: 'Press Start 2P', cursive;
  font-size: 1rem;
  color: #00ff00;
  margin-bottom: 0.5rem;
  letter-spacing: 0.2em;
}

.score-value {
  font-family: 'Press Start 2P', cursive;
  font-size: clamp(1.5rem, 5vw, 3rem);
  color: #ffffff;
  text-shadow: 0 0 10px #00ff00;
  letter-spacing: 0.1em;
}

.konami-message {
  font-family: 'Press Start 2P', cursive;
  font-size: clamp(0.7rem, 2vw, 1rem);
  color: #00ffff;
  text-shadow:
    0 0 10px #00ffff,
    2px 2px 0 #000,
    -2px -2px 0 #000;
  margin: 2rem 0;
  line-height: 1.8;
  letter-spacing: 0.05em;
}

.konami-close {
  font-family: 'Press Start 2P', cursive;
  font-size: clamp(0.7rem, 2vw, 1rem);
  color: #ffffff;
  background: linear-gradient(45deg, #ff0000, #ff00ff);
  border: 3px solid #ffffff;
  border-radius: 8px;
  padding: 1rem 2rem;
  cursor: pointer;
  transition: all 0.3s ease;
  text-shadow: 2px 2px 0 #000;
  box-shadow:
    0 0 20px rgba(255, 0, 255, 0.5),
    0 5px 0 #660066;
  letter-spacing: 0.1em;
  margin-top: 2rem;
}

.konami-close:hover {
  transform: translateY(-2px);
  box-shadow:
    0 0 30px rgba(255, 0, 255, 0.8),
    0 7px 0 #660066;
}

.konami-close:active {
  transform: translateY(2px);
  box-shadow:
    0 0 20px rgba(255, 0, 255, 0.5),
    0 2px 0 #660066;
}

/* Animations */
@keyframes konami-flicker {
  0%,
  19%,
  21%,
  23%,
  25%,
  54%,
  56%,
  100% {
    opacity: 1;
  }
  20%,
  24%,
  55% {
    opacity: 0.8;
  }
}

@keyframes konami-pulse {
  0%,
  100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.02);
  }
}

/* Transitions */
.konami-enter-active {
  animation: konami-zoom-in 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.konami-leave-active {
  animation: konami-zoom-out 0.4s ease-in-out;
}

@keyframes konami-zoom-in {
  from {
    opacity: 0;
    transform: scale(0) rotate(-180deg);
  }
  to {
    opacity: 1;
    transform: scale(1) rotate(0deg);
  }
}

@keyframes konami-zoom-out {
  from {
    opacity: 1;
    transform: scale(1) rotate(0deg);
  }
  to {
    opacity: 0;
    transform: scale(0) rotate(180deg);
  }
}

/* Responsive */
@media (max-width: 768px) {
  .konami-container {
    padding: 1rem;
  }

  .konami-score {
    padding: 1rem;
  }

  .konami-close {
    padding: 0.8rem 1.5rem;
  }
}
</style>
