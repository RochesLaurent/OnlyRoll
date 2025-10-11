/**
 * Service Mercure pour gérer la connexion SSE (Server-Sent Events)
 * Permet de recevoir les événements temps réel depuis le backend
 */

const MERCURE_URL = import.meta.env.VITE_MERCURE_URL || 'http://localhost:3000/.well-known/mercure'

/**
 * Interface pour les événements Mercure reçus
 */
export interface MercureEvent<T = unknown> {
  type: string
  gameId: number
  data: T
  timestamp: string
}

/**
 * Types d'événements supportés
 */
export type EventType = 'chat' | 'token' | 'map' | 'dice' | 'player' | 'system'

/**
 * Callback pour les événements
 */
type EventCallback<T = unknown> = (data: T) => void

/**
 * Service de gestion de la connexion Mercure
 */
export class MercureService {
  private eventSource: EventSource | null = null
  private listeners = new Map<string, Set<EventCallback<unknown>>>()
  private reconnectAttempts = 0
  private maxReconnectAttempts = 5

  /**
   * Se connecter aux événements d'une partie
   * @param gameId - ID de la partie
   * @param token - Token JWT optionnel pour les événements privés
   */
  connect(gameId: number, token?: string): void {
    // Fermer la connexion existante si présente
    if (this.eventSource) {
      this.disconnect()
    }

    // Construction de l'URL avec les topics à écouter
    const topics: EventType[] = ['chat', 'token', 'map', 'dice', 'player', 'system']
    const url = new URL(MERCURE_URL)

    topics.forEach((topic) => {
      url.searchParams.append('topic', `game/${gameId}/${topic}`)
    })

    // Ajout du token JWT si fourni (pour événements privés)
    if (token) {
      url.searchParams.append('authorization', `Bearer ${token}`)
    }

    console.log('Connexion à Mercure...', url.toString())

    // Création de la connexion EventSource
    this.eventSource = new EventSource(url.toString())

    // Gestion des messages entrants
    this.eventSource.onmessage = (event: MessageEvent) => {
      try {
        const mercureEvent: MercureEvent = JSON.parse(event.data)

        console.log('Événement Mercure reçu:', mercureEvent)

        // Notifier tous les listeners du type d'événement
        this.notifyListeners(mercureEvent.type, mercureEvent.data)

        // Réinitialiser le compteur de reconnexions après un message réussi
        this.reconnectAttempts = 0
      } catch (error) {
        console.error('Erreur parsing événement Mercure:', error)
      }
    }

    // Gestion de l'ouverture de connexion
    this.eventSource.onopen = () => {
      console.log('Connecté à Mercure pour la partie', gameId)
      this.reconnectAttempts = 0
    }

    // Gestion des erreurs
    this.eventSource.onerror = (error) => {
      console.error('Erreur connexion Mercure:', error)

      // EventSource reconnecte automatiquement, mais on peut ajouter une logique custom
      if (this.reconnectAttempts < this.maxReconnectAttempts) {
        this.reconnectAttempts++
        console.log(
          `Tentative de reconnexion ${this.reconnectAttempts}/${this.maxReconnectAttempts}`,
        )
      } else {
        console.error('Nombre maximum de tentatives de reconnexion atteint')
        this.disconnect()
      }
    }
  }

  /**
   * Se déconnecter de Mercure
   */
  disconnect(): void {
    if (this.eventSource) {
      this.eventSource.close()
      this.eventSource = null
      this.listeners.clear()
      this.reconnectAttempts = 0
      console.log('Déconnecté de Mercure')
    }
  }

  /**
   * Écouter un type d'événement spécifique
   * @param eventType - Type d'événement (chat, token, map, etc.)
   * @param callback - Fonction à appeler lors de la réception
   */
  on<T = unknown>(eventType: EventType, callback: EventCallback<T>): void {
    if (!this.listeners.has(eventType)) {
      this.listeners.set(eventType, new Set())
    }
    this.listeners.get(eventType)!.add(callback as EventCallback<unknown>)
  }

  /**
   * Arrêter d'écouter un événement
   * @param eventType - Type d'événement
   * @param callback - Fonction à retirer
   */
  off(eventType: EventType, callback: EventCallback): void {
    const listeners = this.listeners.get(eventType)
    if (listeners) {
      listeners.delete(callback as EventCallback<unknown>)
      if (listeners.size === 0) {
        this.listeners.delete(eventType)
      }
    }
  }

  /**
   * Notifier tous les listeners d'un type d'événement
   * @private
   */
  private notifyListeners(eventType: string, data: unknown): void {
    const listeners = this.listeners.get(eventType as EventType)
    if (listeners && listeners.size > 0) {
      listeners.forEach((callback) => {
        try {
          callback(data)
        } catch (error) {
          console.error(`Erreur dans le callback ${eventType}:`, error)
        }
      })
    }
  }

  /**
   * Vérifier si la connexion est active
   */
  isConnected(): boolean {
    return this.eventSource !== null && this.eventSource.readyState === EventSource.OPEN
  }

  /**
   * Obtenir l'état de la connexion
   */
  getConnectionState(): 'connecting' | 'open' | 'closed' {
    if (!this.eventSource) return 'closed'

    switch (this.eventSource.readyState) {
      case EventSource.CONNECTING:
        return 'connecting'
      case EventSource.OPEN:
        return 'open'
      case EventSource.CLOSED:
        return 'closed'
      default:
        return 'closed'
    }
  }
}

// Instance singleton pour toute l'application
export const mercureService = new MercureService()
