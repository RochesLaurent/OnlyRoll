/**
 * Utilitaire de logging conditionnel pour l'application.
 * Les logs ne sont affichés qu'en mode développement.
 */

const isDevelopment = import.meta.env.DEV

/**
 * Logger conditionnel qui n'affiche les logs qu'en développement.
 */
export const logger = {
  /**
   * Log un message d'information.
   */
  log: (...args: unknown[]): void => {
    if (isDevelopment) {
      console.log(...args)
    }
  },

  /**
   * Log une erreur.
   */
  error: (...args: unknown[]): void => {
    if (isDevelopment) {
      console.error(...args)
    }
  },

  /**
   * Log un avertissement.
   */
  warn: (...args: unknown[]): void => {
    if (isDevelopment) {
      console.warn(...args)
    }
  },

  /**
   * Log des informations de debug.
   */
  debug: (...args: unknown[]): void => {
    if (isDevelopment) {
      console.debug(...args)
    }
  },

  /**
   * Log une information (toujours affiché, même en production).
   * À utiliser avec parcimonie.
   */
  info: (...args: unknown[]): void => {
    console.info(...args)
  },
}

/**
 * Logger pour les erreurs critiques qui doivent toujours être affichées.
 * Utilise console.error directement.
 */
export const criticalLogger = {
  error: (...args: unknown[]): void => {
    console.error(...args)
  },
}
