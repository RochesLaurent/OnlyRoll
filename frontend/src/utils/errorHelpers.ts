/**
 * Extrait un message d'erreur lisible depuis une erreur de type unknown
 * @param error - L'erreur à parser (peut être n'importe quoi)
 * @param defaultMessage - Message par défaut si aucun message trouvé
 * @returns Un message d'erreur lisible
 */
export function getErrorMessage(
  error: unknown,
  defaultMessage = "Une erreur inattendue s'est produite",
): string {
  // Cas 1: C'est déjà une string
  if (typeof error === 'string') {
    return error
  }

  // Cas 2: C'est un objet avec des propriétés d'erreur
  if (error && typeof error === 'object') {
    if ('error' in error && typeof error.error === 'string') {
      return error.error
    }

    if ('message' in error && typeof error.message === 'string') {
      return error.message
    }
  }

  return defaultMessage
}
