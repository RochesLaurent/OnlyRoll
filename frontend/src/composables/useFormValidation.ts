import { ref, computed, type Ref } from 'vue'

/**
 * Interface pour une erreur de validation.
 */
export interface ValidationError {
  field: string
  message: string
}

/**
 * Interface pour les règles de validation.
 */
export interface ValidationRule {
  validator: (value: unknown) => boolean
  message: string
}

/**
 * Composable pour gérer la validation des formulaires.
 *
 * @example
 * const { validationErrors, validateField, isFormValid, clearErrors } = useFormValidation()
 *
 * // Valider un email
 * validateField('email', email.value, [
 *   { validator: (v) => !!v, message: "L'email est requis" },
 *   { validator: validators.isEmail, message: "L'email n'est pas valide" }
 * ])
 */
export function useFormValidation() {
  const validationErrors = ref<ValidationError[]>([])

  /**
   * Valide un champ avec des règles données.
   */
  const validateField = (
    field: string,
    value: unknown,
    rules: ValidationRule[]
  ): boolean => {
    // Supprimer les erreurs existantes pour ce champ
    validationErrors.value = validationErrors.value.filter((err) => err.field !== field)

    // Appliquer les règles de validation
    for (const rule of rules) {
      if (!rule.validator(value)) {
        validationErrors.value.push({
          field,
          message: rule.message,
        })
        return false
      }
    }

    return true
  }

  /**
   * Valide plusieurs champs.
   */
  const validateFields = (
    fields: Array<{ field: string; value: unknown; rules: ValidationRule[] }>
  ): boolean => {
    clearErrors()
    let isValid = true

    for (const { field, value, rules } of fields) {
      if (!validateField(field, value, rules)) {
        isValid = false
      }
    }

    return isValid
  }

  /**
   * Efface toutes les erreurs de validation.
   */
  const clearErrors = (): void => {
    validationErrors.value = []
  }

  /**
   * Efface l'erreur d'un champ spécifique.
   */
  const clearFieldError = (field: string): void => {
    validationErrors.value = validationErrors.value.filter((err) => err.field !== field)
  }

  /**
   * Vérifie si le formulaire a des erreurs.
   */
  const hasErrors = computed(() => validationErrors.value.length > 0)

  /**
   * Récupère l'erreur d'un champ spécifique.
   */
  const getFieldError = (field: string): string | undefined => {
    return validationErrors.value.find((err) => err.field === field)?.message
  }

  return {
    validationErrors,
    validateField,
    validateFields,
    clearErrors,
    clearFieldError,
    hasErrors,
    getFieldError,
  }
}

/**
 * Composable pour gérer la visibilité des mots de passe.
 */
export function usePasswordVisibility() {
  const showPassword = ref(false)

  const togglePasswordVisibility = (): void => {
    showPassword.value = !showPassword.value
  }

  return {
    showPassword,
    togglePasswordVisibility,
  }
}

/**
 * Validateurs réutilisables.
 */
export const validators = {
  /**
   * Vérifie si une valeur est requise (non vide).
   */
  required: (value: unknown): boolean => {
    if (typeof value === 'string') {
      return value.trim().length > 0
    }
    return value !== null && value !== undefined
  },

  /**
   * Vérifie si une valeur est un email valide.
   */
  isEmail: (value: unknown): boolean => {
    if (typeof value !== 'string') return false
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    return emailRegex.test(value)
  },

  /**
   * Vérifie la longueur minimale d'une chaîne.
   */
  minLength: (min: number) => (value: unknown): boolean => {
    if (typeof value !== 'string') return false
    return value.length >= min
  },

  /**
   * Vérifie la longueur maximale d'une chaîne.
   */
  maxLength: (max: number) => (value: unknown): boolean => {
    if (typeof value !== 'string') return false
    return value.length <= max
  },

  /**
   * Vérifie si le mot de passe est fort.
   * Doit contenir au moins une majuscule, une minuscule et un chiffre.
   */
  isStrongPassword: (value: unknown): boolean => {
    if (typeof value !== 'string') return false
    const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/
    return passwordRegex.test(value)
  },

  /**
   * Vérifie si deux valeurs sont identiques (pour confirmation de mot de passe).
   */
  matches: (otherValue: unknown) => (value: unknown): boolean => {
    return value === otherValue
  },

  /**
   * Vérifie si une valeur est un nombre.
   */
  isNumber: (value: unknown): boolean => {
    if (value === null || value === undefined) return false
    return !isNaN(Number(value))
  },

  /**
   * Vérifie si un nombre est dans une plage donnée.
   */
  inRange: (min: number, max: number) => (value: unknown): boolean => {
    const num = Number(value)
    return !isNaN(num) && num >= min && num <= max
  },
}
