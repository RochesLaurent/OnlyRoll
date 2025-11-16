import { onMounted, onUnmounted, ref } from 'vue'

/**
 * Composable pour détecter le Konami Code
 * Séquence : ↑ ↑ ↓ ↓ ← → ← → B A
 *
 * @example
 * const { isActivated, reset } = useKonamiCode(() => {
 *   console.log('Konami Code activé!')
 * })
 */
export function useKonamiCode(callback?: () => void) {
  const isActivated = ref(false)

  // Séquence du Konami Code
  const konamiSequence = [
    'ArrowUp',
    'ArrowUp',
    'ArrowDown',
    'ArrowDown',
    'ArrowLeft',
    'ArrowRight',
    'ArrowLeft',
    'ArrowRight',
    (code: string) => ['KeyB', 'KeyQ'].includes(code),
    (code: string) => ['KeyA', 'KeyQ'].includes(code),
  ]

  // Historique des touches pressées
  let userSequence: string[] = []

  /**
   * Gère l'événement keydown
   */
  const handleKeydown = (event: KeyboardEvent) => {
    // Ajoute la touche pressée à la séquence utilisateur
    console.log('Touche pressée :', event.code)
    userSequence.push(event.code)

    // Limite la taille de l'historique à la longueur de la séquence
    if (userSequence.length > konamiSequence.length) {
      userSequence.shift()
    }

    // Vérifie si la séquence correspond
    const isMatch = userSequence.every((key, index) => {
      const expected = konamiSequence[index]
      if (typeof expected === 'function') {
        return expected(key)
      }
      return key === expected
    })

    if (isMatch && userSequence.length === konamiSequence.length) {
      isActivated.value = true
      callback?.()
      // Réinitialise la séquence pour permettre une nouvelle activation
      userSequence = []
    }
  }

  /**
   * Réinitialise l'état d'activation
   */
  const reset = () => {
    isActivated.value = false
    userSequence = []
  }

  onMounted(() => {
    window.addEventListener('keydown', handleKeydown)
  })

  onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown)
  })

  return {
    isActivated,
    reset,
  }
}
