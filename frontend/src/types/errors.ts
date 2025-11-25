/**
 * Types pour la gestion des erreurs API
 */

/**
 * Erreur de validation d'un champ
 */
export interface ValidationError {
  field?: string
  propertyPath?: string
  message: string
  invalidValue?: unknown
}

/**
 * Erreur API générique
 * Consolidation de tous les formats d'erreurs possibles du backend
 */
export interface ApiError {
  // Format principal
  error?: string
  message?: string

  // Codes d'erreur
  code?: string
  statusCode?: number

  // Erreurs de validation (Symfony)
  violations?: ValidationError[]

  // Erreurs par champ
  errors?: Record<string, string[]>

  // Format Axios
  response?: {
    data?: {
      error?: string
      message?: string
      violations?: ValidationError[]
    }
    status?: number
    statusText?: string
  }
}

/**
 * Type guard pour vérifier si une erreur est une ApiError
 */
export function isApiError(error: unknown): error is ApiError {
  return (
    typeof error === 'object' &&
    error !== null &&
    ('error' in error || 'message' in error || 'response' in error)
  )
}

/**
 * Extrait le message d'erreur d'une ApiError
 */
export function getErrorMessage(error: unknown): string {
  if (!isApiError(error)) {
    return 'Une erreur inattendue est survenue'
  }

  // Essayer différents formats
  if (error.response?.data?.error) {
    return error.response.data.error
  }

  if (error.response?.data?.message) {
    return error.response.data.message
  }

  if (error.error) {
    return error.error
  }

  if (error.message) {
    return error.message
  }

  if (error.violations && error.violations.length > 0) {
    return error.violations.map((v) => v.message).join(', ')
  }

  return 'Une erreur est survenue'
}
