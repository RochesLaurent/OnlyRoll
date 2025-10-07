import { onMounted, onUnmounted, ref, readonly } from 'vue'
import { mercureService, type EventType } from '@/services/mercure'

/**
 * Composable Vue pour gérer Mercure facilement dans les composants
 *
 * Usage:
 * ```vue
 * <script setup>
 * const { onChatMessage, isConnected } = useMercure(gameId)
 *
 * onChatMessage((message) => {
 *   console.log('Nouveau message:', message)
 * })
 * </script>
 * ```
 */
export function useMercure(gameId: number, token?: string | null) {
  const isConnected = ref(false)
  const connectionState = ref<'connecting' | 'open' | 'closed'>('closed')

  /**
   * Connexion au montage du composant
   */
  onMounted(() => {
    const tokenToSend = import.meta.env.DEV ? undefined : (token ?? undefined)
    mercureService.connect(gameId, tokenToSend)

    // Mettre à jour l'état de connexion
    const checkConnection = setInterval(() => {
      isConnected.value = mercureService.isConnected()
      connectionState.value = mercureService.getConnectionState()
    }, 1000)

    // Nettoyer l'interval au démontage
    onUnmounted(() => {
      clearInterval(checkConnection)
    })
  })

  /**
   * Déconnexion au démontage du composant
   */
  onUnmounted(() => {
    mercureService.disconnect()
  })

  /**
   * Écouter les messages de chat
   *
   * @param callback - Fonction appelée à chaque nouveau message
   * @returns Fonction de nettoyage
   */
  const onChatMessage = (callback: (message: any) => void) => {
    mercureService.on('chat', callback)

    // Retourner une fonction de nettoyage
    return () => mercureService.off('chat', callback)
  }

  /**
   * Écouter les déplacements de tokens
   *
   * @param callback - Fonction appelée à chaque déplacement
   * @returns Fonction de nettoyage
   */
  const onTokenMove = (callback: (token: any) => void) => {
    mercureService.on('token', callback)
    return () => mercureService.off('token', callback)
  }

  /**
   * Écouter tous les événements de tokens (création, déplacement, suppression)
   *
   * @param callback - Fonction appelée pour chaque événement token
   * @returns Fonction de nettoyage
   */
  const onTokenEvent = (callback: (event: any) => void) => {
    mercureService.on('token', callback)
    return () => mercureService.off('token', callback)
  }

  /**
   * Écouter les changements de carte
   *
   * @param callback - Fonction appelée lors d'un changement de carte
   * @returns Fonction de nettoyage
   */
  const onMapChange = (callback: (map: any) => void) => {
    mercureService.on('map', callback)
    return () => mercureService.off('map', callback)
  }

  /**
   * Écouter les lancers de dés
   *
   * @param callback - Fonction appelée à chaque lancer
   * @returns Fonction de nettoyage
   */
  const onDiceRoll = (callback: (dice: any) => void) => {
    mercureService.on('dice', callback)
    return () => mercureService.off('dice', callback)
  }

  /**
   * Écouter les événements de joueurs (connexion/déconnexion)
   *
   * @param callback - Fonction appelée pour chaque événement joueur
   * @returns Fonction de nettoyage
   */
  const onPlayerEvent = (callback: (player: any) => void) => {
    mercureService.on('player', callback)
    return () => mercureService.off('player', callback)
  }

  /**
   * Écouter les événements système
   *
   * @param callback - Fonction appelée pour chaque événement système
   * @returns Fonction de nettoyage
   */
  const onSystemEvent = (callback: (event: any) => void) => {
    mercureService.on('system', callback)
    return () => mercureService.off('system', callback)
  }

  /**
   * Écouter un type d'événement personnalisé
   *
   * @param eventType - Type d'événement à écouter
   * @param callback - Fonction à appeler
   * @returns Fonction de nettoyage
   */
  const on = (eventType: EventType, callback: (data: any) => void) => {
    mercureService.on(eventType, callback)
    return () => mercureService.off(eventType, callback)
  }

  return {
    // État de connexion (readonly pour éviter les modifications)
    isConnected: readonly(isConnected),
    connectionState: readonly(connectionState),

    // Méthodes d'écoute d'événements
    onChatMessage,
    onTokenMove,
    onTokenEvent,
    onMapChange,
    onDiceRoll,
    onPlayerEvent,
    onSystemEvent,
    on,
  }
}
