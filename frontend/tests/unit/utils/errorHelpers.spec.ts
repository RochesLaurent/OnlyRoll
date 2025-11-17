/**
 * Tests unitaires pour les helpers d'erreurs
 *
 * @covers src/utils/errorHelpers.ts
 */

import { describe, it, expect } from 'vitest'
import { getErrorMessage } from '@/utils/errorHelpers'

describe('getErrorMessage', () => {
  // ========== STRING ERRORS ==========

  it('should return string error directly', () => {
    const result = getErrorMessage('Simple error message')

    expect(result).toBe('Simple error message')
  })

  // ========== OBJECT WITH ERROR PROPERTY ==========

  it('should extract error property from object', () => {
    const error = {
      error: 'Error from API',
    }

    const result = getErrorMessage(error)

    expect(result).toBe('Error from API')
  })

  it('should ignore non-string error property', () => {
    const error = {
      error: 123,
    }

    const result = getErrorMessage(error)

    expect(result).toBe("Une erreur inattendue s'est produite")
  })

  // ========== OBJECT WITH MESSAGE PROPERTY ==========

  it('should extract message property from object', () => {
    const error = {
      message: 'Error message',
    }

    const result = getErrorMessage(error)

    expect(result).toBe('Error message')
  })

  it('should ignore non-string message property', () => {
    const error = {
      message: 123,
    }

    const result = getErrorMessage(error)

    expect(result).toBe("Une erreur inattendue s'est produite")
  })

  // ========== PRIORITY ==========

  it('should prioritize error over message', () => {
    const error = {
      error: 'Error property',
      message: 'Message property',
    }

    const result = getErrorMessage(error)

    expect(result).toBe('Error property')
  })

  // ========== ERROR OBJECTS ==========

  it('should handle Error instances', () => {
    const error = new Error('Something went wrong')

    const result = getErrorMessage(error)

    expect(result).toBe('Something went wrong')
  })

  it('should handle TypeError instances', () => {
    const error = new TypeError('Type error')

    const result = getErrorMessage(error)

    expect(result).toBe('Type error')
  })

  // ========== DEFAULT MESSAGE ==========

  it('should return default message for null', () => {
    const result = getErrorMessage(null)

    expect(result).toBe("Une erreur inattendue s'est produite")
  })

  it('should return default message for undefined', () => {
    const result = getErrorMessage(undefined)

    expect(result).toBe("Une erreur inattendue s'est produite")
  })

  it('should return default message for number', () => {
    const result = getErrorMessage(123)

    expect(result).toBe("Une erreur inattendue s'est produite")
  })

  it('should return default message for boolean', () => {
    const result = getErrorMessage(true)

    expect(result).toBe("Une erreur inattendue s'est produite")
  })

  it('should return default message for array', () => {
    const result = getErrorMessage(['error', 'array'])

    expect(result).toBe("Une erreur inattendue s'est produite")
  })

  it('should return default message for object without error/message', () => {
    const error = {
      code: 500,
      status: 'Internal Server Error',
    }

    const result = getErrorMessage(error)

    expect(result).toBe("Une erreur inattendue s'est produite")
  })

  // ========== CUSTOM DEFAULT MESSAGE ==========

  it('should use custom default message', () => {
    const result = getErrorMessage(null, 'Custom default')

    expect(result).toBe('Custom default')
  })

  it('should use custom default for unknown error type', () => {
    const result = getErrorMessage(123, 'Something went wrong')

    expect(result).toBe('Something went wrong')
  })

  // ========== EDGE CASES ==========

  it('should handle empty string error', () => {
    const result = getErrorMessage('')

    expect(result).toBe('')
  })

  it('should handle object with empty error string', () => {
    const error = {
      error: '',
    }

    const result = getErrorMessage(error)

    expect(result).toBe('')
  })

  it('should handle object with empty message string', () => {
    const error = {
      message: '',
    }

    const result = getErrorMessage(error)

    expect(result).toBe('')
  })

  it('should handle deeply nested objects', () => {
    const error = {
      data: {
        error: 'Nested error',
      },
    }

    const result = getErrorMessage(error)

    expect(result).toBe("Une erreur inattendue s'est produite")
  })

  // ========== AXIOS ERROR STRUCTURE ==========

  it('should handle Axios-like error structure', () => {
    const error = {
      response: {
        data: {
          message: 'API error',
        },
      },
      message: 'Network Error',
    }

    const result = getErrorMessage(error)

    expect(result).toBe('Network Error')
  })

  // ========== API ERROR STRUCTURE ==========

  it('should handle API response with error property', () => {
    const error = {
      error: 'Invalid credentials',
      code: 401,
    }

    const result = getErrorMessage(error)

    expect(result).toBe('Invalid credentials')
  })
})
