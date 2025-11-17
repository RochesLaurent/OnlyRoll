/**
 * Tests unitaires pour le composable useFormValidation
 *
 * @covers src/composables/useFormValidation.ts
 */

import { describe, it, expect, beforeEach } from 'vitest'
import {
  useFormValidation,
  usePasswordVisibility,
  validators,
} from '@/composables/useFormValidation'

describe('useFormValidation', () => {
  let formValidation: ReturnType<typeof useFormValidation>

  beforeEach(() => {
    formValidation = useFormValidation()
  })

  // ========== INITIAL STATE ==========

  it('should initialize with empty errors', () => {
    expect(formValidation.validationErrors.value).toEqual([])
    expect(formValidation.hasErrors.value).toBe(false)
  })

  // ========== VALIDATE FIELD ==========

  it('should validate field successfully', () => {
    const isValid = formValidation.validateField('email', 'test@example.com', [
      {
        validator: (v) => typeof v === 'string' && v.length > 0,
        message: 'Email requis',
      },
    ])

    expect(isValid).toBe(true)
    expect(formValidation.validationErrors.value).toEqual([])
  })

  it('should fail validation and add error', () => {
    const isValid = formValidation.validateField('email', '', [
      {
        validator: (v) => typeof v === 'string' && v.length > 0,
        message: 'Email requis',
      },
    ])

    expect(isValid).toBe(false)
    expect(formValidation.validationErrors.value).toHaveLength(1)
    expect(formValidation.validationErrors.value[0]).toEqual({
      field: 'email',
      message: 'Email requis',
    })
  })

  it('should replace previous error for same field', () => {
    formValidation.validateField('email', '', [
      {
        validator: () => false,
        message: 'First error',
      },
    ])

    formValidation.validateField('email', '', [
      {
        validator: () => false,
        message: 'Second error',
      },
    ])

    expect(formValidation.validationErrors.value).toHaveLength(1)
    expect(formValidation.validationErrors.value[0].message).toBe('Second error')
  })

  it('should stop at first failed rule', () => {
    const isValid = formValidation.validateField('password', 'short', [
      {
        validator: (v) => typeof v === 'string' && v.length >= 8,
        message: 'Password must be at least 8 characters',
      },
      {
        validator: () => false,
        message: 'This should not be added',
      },
    ])

    expect(isValid).toBe(false)
    expect(formValidation.validationErrors.value).toHaveLength(1)
    expect(formValidation.validationErrors.value[0].message).toBe(
      'Password must be at least 8 characters'
    )
  })

  // ========== VALIDATE FIELDS ==========

  it('should validate multiple fields successfully', () => {
    const isValid = formValidation.validateFields([
      {
        field: 'email',
        value: 'test@example.com',
        rules: [
          {
            validator: (v) => typeof v === 'string' && v.length > 0,
            message: 'Email requis',
          },
        ],
      },
      {
        field: 'password',
        value: 'password123',
        rules: [
          {
            validator: (v) => typeof v === 'string' && v.length >= 8,
            message: 'Password too short',
          },
        ],
      },
    ])

    expect(isValid).toBe(true)
    expect(formValidation.validationErrors.value).toEqual([])
  })

  it('should fail validation if any field invalid', () => {
    const isValid = formValidation.validateFields([
      {
        field: 'email',
        value: 'valid@example.com',
        rules: [
          {
            validator: () => true,
            message: 'Valid',
          },
        ],
      },
      {
        field: 'password',
        value: 'short',
        rules: [
          {
            validator: () => false,
            message: 'Password invalid',
          },
        ],
      },
    ])

    expect(isValid).toBe(false)
    expect(formValidation.validationErrors.value).toHaveLength(1)
  })

  it('should clear errors before validating fields', () => {
    formValidation.validationErrors.value = [
      { field: 'old', message: 'Old error' },
    ]

    formValidation.validateFields([
      {
        field: 'email',
        value: 'test@example.com',
        rules: [
          {
            validator: () => true,
            message: 'Valid',
          },
        ],
      },
    ])

    expect(formValidation.validationErrors.value).toEqual([])
  })

  // ========== CLEAR ERRORS ==========

  it('should clear all errors', () => {
    formValidation.validationErrors.value = [
      { field: 'email', message: 'Email error' },
      { field: 'password', message: 'Password error' },
    ]

    formValidation.clearErrors()

    expect(formValidation.validationErrors.value).toEqual([])
  })

  // ========== CLEAR FIELD ERROR ==========

  it('should clear error for specific field', () => {
    formValidation.validationErrors.value = [
      { field: 'email', message: 'Email error' },
      { field: 'password', message: 'Password error' },
    ]

    formValidation.clearFieldError('email')

    expect(formValidation.validationErrors.value).toHaveLength(1)
    expect(formValidation.validationErrors.value[0].field).toBe('password')
  })

  // ========== HAS ERRORS ==========

  it('should compute hasErrors correctly', () => {
    expect(formValidation.hasErrors.value).toBe(false)

    formValidation.validationErrors.value = [
      { field: 'email', message: 'Error' },
    ]

    expect(formValidation.hasErrors.value).toBe(true)
  })

  // ========== GET FIELD ERROR ==========

  it('should get error for specific field', () => {
    formValidation.validationErrors.value = [
      { field: 'email', message: 'Email error' },
      { field: 'password', message: 'Password error' },
    ]

    expect(formValidation.getFieldError('email')).toBe('Email error')
    expect(formValidation.getFieldError('password')).toBe('Password error')
    expect(formValidation.getFieldError('nonexistent')).toBeUndefined()
  })
})

describe('usePasswordVisibility', () => {
  // ========== INITIAL STATE ==========

  it('should initialize with password hidden', () => {
    const { showPassword } = usePasswordVisibility()

    expect(showPassword.value).toBe(false)
  })

  // ========== TOGGLE VISIBILITY ==========

  it('should toggle password visibility', () => {
    const { showPassword, togglePasswordVisibility } = usePasswordVisibility()

    expect(showPassword.value).toBe(false)

    togglePasswordVisibility()
    expect(showPassword.value).toBe(true)

    togglePasswordVisibility()
    expect(showPassword.value).toBe(false)
  })
})

describe('validators', () => {
  // ========== REQUIRED ==========

  describe('required', () => {
    it('should validate non-empty string', () => {
      expect(validators.required('test')).toBe(true)
      expect(validators.required('  test  ')).toBe(true)
    })

    it('should fail for empty string', () => {
      expect(validators.required('')).toBe(false)
      expect(validators.required('   ')).toBe(false)
    })

    it('should validate non-null values', () => {
      expect(validators.required(123)).toBe(true)
      expect(validators.required(true)).toBe(true)
      expect(validators.required([])).toBe(true)
    })

    it('should fail for null or undefined', () => {
      expect(validators.required(null)).toBe(false)
      expect(validators.required(undefined)).toBe(false)
    })
  })

  // ========== IS EMAIL ==========

  describe('isEmail', () => {
    it('should validate correct email formats', () => {
      expect(validators.isEmail('test@example.com')).toBe(true)
      expect(validators.isEmail('user.name@domain.co.uk')).toBe(true)
      expect(validators.isEmail('first+last@example.org')).toBe(true)
    })

    it('should fail for invalid email formats', () => {
      expect(validators.isEmail('notanemail')).toBe(false)
      expect(validators.isEmail('missing@domain')).toBe(false)
      expect(validators.isEmail('@example.com')).toBe(false)
      expect(validators.isEmail('user@')).toBe(false)
    })

    it('should fail for non-string values', () => {
      expect(validators.isEmail(123)).toBe(false)
      expect(validators.isEmail(null)).toBe(false)
      expect(validators.isEmail(undefined)).toBe(false)
    })
  })

  // ========== MIN LENGTH ==========

  describe('minLength', () => {
    it('should validate strings meeting minimum length', () => {
      const minLength5 = validators.minLength(5)

      expect(minLength5('12345')).toBe(true)
      expect(minLength5('123456')).toBe(true)
    })

    it('should fail for strings below minimum length', () => {
      const minLength5 = validators.minLength(5)

      expect(minLength5('1234')).toBe(false)
      expect(minLength5('')).toBe(false)
    })

    it('should fail for non-string values', () => {
      const minLength5 = validators.minLength(5)

      expect(minLength5(12345)).toBe(false)
      expect(minLength5(null)).toBe(false)
    })
  })

  // ========== MAX LENGTH ==========

  describe('maxLength', () => {
    it('should validate strings within maximum length', () => {
      const maxLength5 = validators.maxLength(5)

      expect(maxLength5('12345')).toBe(true)
      expect(maxLength5('1234')).toBe(true)
      expect(maxLength5('')).toBe(true)
    })

    it('should fail for strings exceeding maximum length', () => {
      const maxLength5 = validators.maxLength(5)

      expect(maxLength5('123456')).toBe(false)
    })

    it('should fail for non-string values', () => {
      const maxLength5 = validators.maxLength(5)

      expect(maxLength5(12345)).toBe(false)
      expect(maxLength5(null)).toBe(false)
    })
  })

  // ========== IS STRONG PASSWORD ==========

  describe('isStrongPassword', () => {
    it('should validate strong passwords', () => {
      expect(validators.isStrongPassword('Password123')).toBe(true)
      expect(validators.isStrongPassword('MyP4ssw0rd')).toBe(true)
      expect(validators.isStrongPassword('Aa1bcdefgh')).toBe(true)
    })

    it('should fail for weak passwords', () => {
      expect(validators.isStrongPassword('password')).toBe(false) // no uppercase or digit
      expect(validators.isStrongPassword('PASSWORD123')).toBe(false) // no lowercase
      expect(validators.isStrongPassword('Password')).toBe(false) // no digit
      expect(validators.isStrongPassword('12345678')).toBe(false) // no letters
    })

    it('should fail for non-string values', () => {
      expect(validators.isStrongPassword(123456)).toBe(false)
      expect(validators.isStrongPassword(null)).toBe(false)
    })
  })

  // ========== MATCHES ==========

  describe('matches', () => {
    it('should validate matching values', () => {
      const matchesTest = validators.matches('test')

      expect(matchesTest('test')).toBe(true)
    })

    it('should fail for non-matching values', () => {
      const matchesTest = validators.matches('test')

      expect(matchesTest('different')).toBe(false)
      expect(matchesTest('TEST')).toBe(false)
    })

    it('should work with non-string values', () => {
      const matches123 = validators.matches(123)

      expect(matches123(123)).toBe(true)
      expect(matches123(456)).toBe(false)
    })
  })

  // ========== IS NUMBER ==========

  describe('isNumber', () => {
    it('should validate numbers', () => {
      expect(validators.isNumber(123)).toBe(true)
      expect(validators.isNumber(0)).toBe(true)
      expect(validators.isNumber(-42)).toBe(true)
      expect(validators.isNumber(3.14)).toBe(true)
    })

    it('should validate numeric strings', () => {
      expect(validators.isNumber('123')).toBe(true)
      expect(validators.isNumber('3.14')).toBe(true)
    })

    it('should fail for non-numeric values', () => {
      expect(validators.isNumber('abc')).toBe(false)
      expect(validators.isNumber(null)).toBe(false)
      expect(validators.isNumber(undefined)).toBe(false)
    })
  })

  // ========== IN RANGE ==========

  describe('inRange', () => {
    it('should validate numbers within range', () => {
      const inRange1to10 = validators.inRange(1, 10)

      expect(inRange1to10(1)).toBe(true)
      expect(inRange1to10(5)).toBe(true)
      expect(inRange1to10(10)).toBe(true)
    })

    it('should fail for numbers outside range', () => {
      const inRange1to10 = validators.inRange(1, 10)

      expect(inRange1to10(0)).toBe(false)
      expect(inRange1to10(11)).toBe(false)
      expect(inRange1to10(-5)).toBe(false)
    })

    it('should validate numeric strings', () => {
      const inRange1to10 = validators.inRange(1, 10)

      expect(inRange1to10('5')).toBe(true)
      expect(inRange1to10('15')).toBe(false)
    })

    it('should fail for non-numeric values', () => {
      const inRange1to10 = validators.inRange(1, 10)

      expect(inRange1to10('abc')).toBe(false)
      expect(inRange1to10(null)).toBe(false)
    })
  })
})
