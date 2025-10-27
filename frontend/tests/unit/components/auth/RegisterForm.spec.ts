/**
 * Tests unitaires pour le composant RegisterForm
 * 
 * @covers src/components/auth/RegisterForm.vue
 */

import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount, VueWrapper } from '@vue/test-utils'
import { nextTick } from 'vue'
import RegisterForm from '@/components/auth/RegisterForm.vue'
import { useAuth } from '@/composables/useAuth'

// Mock du composable useAuth
vi.mock('@/composables/useAuth', () => ({
  useAuth: vi.fn(),
}))

describe('RegisterForm.vue', () => {
  let wrapper: VueWrapper
  let mockAuth: any

  beforeEach(() => {
    mockAuth = {
      register: vi.fn(),
      isLoading: false,
      error: null,
      clearError: vi.fn(),
    }

    vi.mocked(useAuth).mockReturnValue(mockAuth)

    wrapper = mount(RegisterForm, {
      global: {
        stubs: {
          RouterLink: true,
        },
      },
    })
  })

  // ========== RENDERING ==========

  it('should render the form with all fields', () => {
    expect(wrapper.find('h2').text()).toBe('Inscription')
    expect(wrapper.find('#pseudo').exists()).toBe(true)
    expect(wrapper.find('#email').exists()).toBe(true)
    expect(wrapper.find('#password').exists()).toBe(true)
    expect(wrapper.find('#confirmPassword').exists()).toBe(true)
    expect(wrapper.find('#acceptTerms').exists()).toBe(true)
    expect(wrapper.find('button[type="submit"]').exists()).toBe(true)
  })

  it('should display required field indicators', () => {
    const requiredIndicators = wrapper.findAll('.text-error')
    expect(requiredIndicators.length).toBeGreaterThan(0)
  })

  // ========== FORM INPUT ==========

  it('should update form values on input', async () => {
    await wrapper.find('#pseudo').setValue('TestUser')
    await wrapper.find('#email').setValue('test@example.com')
    await wrapper.find('#password').setValue('Password123')
    await wrapper.find('#confirmPassword').setValue('Password123')

    expect((wrapper.find('#pseudo').element as HTMLInputElement).value).toBe('TestUser')
    expect((wrapper.find('#email').element as HTMLInputElement).value).toBe('test@example.com')
    expect((wrapper.find('#password').element as HTMLInputElement).value).toBe('Password123')
    expect((wrapper.find('#confirmPassword').element as HTMLInputElement).value).toBe('Password123')
  })

  // ========== PASSWORD VISIBILITY TOGGLE ==========

  it('should toggle password visibility', async () => {
    const passwordInput = wrapper.find('#password')
    const toggleButton = wrapper.findAll('button[type="button"]')[0]

    expect((passwordInput.element as HTMLInputElement).type).toBe('password')

    await toggleButton.trigger('click')
    await nextTick()

    expect((passwordInput.element as HTMLInputElement).type).toBe('text')

    await toggleButton.trigger('click')
    await nextTick()

    expect((passwordInput.element as HTMLInputElement).type).toBe('password')
  })

  it('should toggle confirm password visibility', async () => {
    const confirmPasswordInput = wrapper.find('#confirmPassword')
    const toggleButton = wrapper.findAll('button[type="button"]')[1]

    expect((confirmPasswordInput.element as HTMLInputElement).type).toBe('password')

    await toggleButton.trigger('click')
    await nextTick()

    expect((confirmPasswordInput.element as HTMLInputElement).type).toBe('text')
  })

  // ========== PASSWORD STRENGTH ==========

  it('should calculate password strength correctly', async () => {
    const passwordInput = wrapper.find('#password')

    // Weak password
    await passwordInput.setValue('weak')
    await nextTick()
    expect(wrapper.text()).toContain('Mot de passe faible')

    // Medium password
    await passwordInput.setValue('Medium1')
    await nextTick()
    expect(wrapper.text()).toContain('Mot de passe moyen')

    // Strong password
    await passwordInput.setValue('Strong1Password!')
    await nextTick()
    expect(wrapper.text()).toContain('Mot de passe fort')
  })

  it('should display password rules validation', async () => {
    const passwordInput = wrapper.find('#password')

    await passwordInput.setValue('Pass1')
    await nextTick()

    const rulesText = wrapper.text()
    expect(rulesText).toContain('Au moins 8 caractères')
    expect(rulesText).toContain('Une minuscule')
    expect(rulesText).toContain('Une majuscule')
    expect(rulesText).toContain('Un chiffre')
  })

  it('should validate password rules individually', async () => {
    const passwordInput = wrapper.find('#password')

    // Test minlength
    await passwordInput.setValue('Short1A')
    await nextTick()
    expect(wrapper.html()).toContain('text-success')

    // Test lowercase
    await passwordInput.setValue('PASSWORD123')
    await nextTick()
    const html = wrapper.html()
    expect(html).toContain('○') // Some rules not met
  })

  // ========== VALIDATION ==========

  it('should validate pseudo field on blur', async () => {
    const pseudoInput = wrapper.find('#pseudo')

    await pseudoInput.setValue('ab')
    await pseudoInput.trigger('blur')
    await nextTick()

    expect(wrapper.text()).toContain('Le pseudo doit faire au moins 3 caractères')
  })

  it('should validate email field on blur', async () => {
    const emailInput = wrapper.find('#email')

    await emailInput.setValue('invalid-email')
    await emailInput.trigger('blur')
    await nextTick()

    expect(wrapper.text()).toContain("L'email n'est pas valide")
  })

  it('should show field error after touch', async () => {
    const pseudoInput = wrapper.find('#pseudo')

    await pseudoInput.trigger('blur')
    await nextTick()

    expect(wrapper.text()).toContain('Le pseudo est requis')
  })

  // ========== FORM VALIDATION ==========

  it('should not submit form with empty fields', async () => {
    const form = wrapper.find('form')
    await form.trigger('submit.prevent')
    await nextTick()

    expect(mockAuth.register).not.toHaveBeenCalled()
    expect(wrapper.text()).toContain('Le pseudo est requis')
  })

  it('should not submit form when passwords do not match', async () => {
    await wrapper.find('#pseudo').setValue('TestUser')
    await wrapper.find('#email').setValue('test@example.com')
    await wrapper.find('#password').setValue('Password123')
    await wrapper.find('#confirmPassword').setValue('DifferentPassword123')
    await wrapper.find('#acceptTerms').setValue(true)

    await wrapper.find('form').trigger('submit.prevent')
    await nextTick()

    expect(mockAuth.register).not.toHaveBeenCalled()
    expect(wrapper.text()).toContain('Les mots de passe ne correspondent pas')
  })

  it('should not submit form without accepting terms', async () => {
    await wrapper.find('#pseudo').setValue('TestUser')
    await wrapper.find('#email').setValue('test@example.com')
    await wrapper.find('#password').setValue('Password123')
    await wrapper.find('#confirmPassword').setValue('Password123')

    await wrapper.find('form').trigger('submit.prevent')
    await nextTick()

    expect(mockAuth.register).not.toHaveBeenCalled()
    expect(wrapper.text()).toContain("Vous devez accepter les conditions d'utilisation")
  })

  it('should not submit form with weak password', async () => {
    await wrapper.find('#pseudo').setValue('TestUser')
    await wrapper.find('#email').setValue('test@example.com')
    await wrapper.find('#password').setValue('weak')
    await wrapper.find('#confirmPassword').setValue('weak')
    await wrapper.find('#acceptTerms').setValue(true)

    await wrapper.find('form').trigger('submit.prevent')
    await nextTick()

    expect(mockAuth.register).not.toHaveBeenCalled()
  })

  // ========== SUCCESSFUL SUBMISSION ==========

  it('should submit form with valid data', async () => {
    mockAuth.register.mockResolvedValueOnce(undefined)

    await wrapper.find('#pseudo').setValue('TestUser')
    await wrapper.find('#email').setValue('test@example.com')
    await wrapper.find('#password').setValue('Password123')
    await wrapper.find('#confirmPassword').setValue('Password123')
    await wrapper.find('#acceptTerms').setValue(true)

    await wrapper.find('form').trigger('submit.prevent')
    await nextTick()

    expect(mockAuth.register).toHaveBeenCalledWith({
      pseudo: 'TestUser',
      email: 'test@example.com',
      password: 'Password123',
      confirmPassword: 'Password123',
    })
  })

  // ========== ERROR HANDLING ==========

  it('should display error from store', async () => {
    mockAuth.error = 'Email already exists'

    await wrapper.vm.$nextTick()

    expect(wrapper.text()).toContain('Email already exists')
  })

  it('should display validation errors', async () => {
    await wrapper.find('form').trigger('submit.prevent')
    await nextTick()

    const errorContainer = wrapper.find('.bg-error\\/10')
    expect(errorContainer.exists()).toBe(true)
  })

  it('should clear error on input change', async () => {
    await wrapper.find('#pseudo').setValue('TestUser')
    await nextTick()

    expect(mockAuth.clearError).toHaveBeenCalled()
  })

  // ========== LOADING STATE ==========

  it('should disable inputs when loading', async () => {
    mockAuth.isLoading = true

    wrapper = mount(RegisterForm, {
      global: {
        stubs: {
          RouterLink: true,
        },
      },
    })

    await nextTick()

    const pseudoInput = wrapper.find('#pseudo')
    const emailInput = wrapper.find('#email')
    const passwordInput = wrapper.find('#password')
    const submitButton = wrapper.find('button[type="submit"]')

    expect((pseudoInput.element as HTMLInputElement).disabled).toBe(true)
    expect((emailInput.element as HTMLInputElement).disabled).toBe(true)
    expect((passwordInput.element as HTMLInputElement).disabled).toBe(true)
    expect((submitButton.element as HTMLButtonElement).disabled).toBe(true)
  })

  it('should show loading state on submit button', async () => {
    mockAuth.isLoading = true

    wrapper = mount(RegisterForm, {
      global: {
        stubs: {
          RouterLink: true,
        },
      },
    })

    await nextTick()

    expect(wrapper.find('button[type="submit"]').text()).toContain('Inscription en cours')
  })

  // ========== EMAIL VALIDATION ==========

  it('should validate email format', async () => {
    const emailInput = wrapper.find('#email')

    // Invalid email
    await emailInput.setValue('notanemail')
    await emailInput.trigger('blur')
    await nextTick()

    expect(wrapper.text()).toContain("L'email n'est pas valide")

    // Valid email
    await emailInput.setValue('valid@example.com')
    await emailInput.trigger('blur')
    await nextTick()

    expect(wrapper.text()).not.toContain("L'email n'est pas valide")
  })

  // ========== PSEUDO VALIDATION ==========

  it('should validate pseudo length', async () => {
    const pseudoInput = wrapper.find('#pseudo')

    // Too short
    await pseudoInput.setValue('ab')
    await pseudoInput.trigger('blur')
    await nextTick()

    expect(wrapper.text()).toContain('Le pseudo doit faire au moins 3 caractères')

    // Valid length
    await pseudoInput.setValue('ValidPseudo')
    await pseudoInput.trigger('blur')
    await nextTick()

    expect(wrapper.text()).not.toContain('Le pseudo doit faire au moins 3 caractères')
  })

  // ========== SUBMIT BUTTON STATE ==========

  it('should disable submit button when form is invalid', async () => {
    const submitButton = wrapper.find('button[type="submit"]')

    // Form is empty initially
    expect((submitButton.element as HTMLButtonElement).disabled).toBe(true)
  })

  it('should enable submit button when form is valid', async () => {
    await wrapper.find('#pseudo').setValue('TestUser')
    await wrapper.find('#email').setValue('test@example.com')
    await wrapper.find('#password').setValue('Password123')
    await wrapper.find('#confirmPassword').setValue('Password123')
    await wrapper.find('#acceptTerms').setValue(true)

    await nextTick()

    const submitButton = wrapper.find('button[type="submit"]')
    expect((submitButton.element as HTMLButtonElement).disabled).toBe(false)
  })

  // ========== ERROR HANDLING ON SUBMIT ==========

  it('should handle registration error', async () => {
    const registerError = new Error('Registration failed')
    mockAuth.register.mockRejectedValueOnce(registerError)

    await wrapper.find('#pseudo').setValue('TestUser')
    await wrapper.find('#email').setValue('test@example.com')
    await wrapper.find('#password').setValue('Password123')
    await wrapper.find('#confirmPassword').setValue('Password123')
    await wrapper.find('#acceptTerms').setValue(true)

    await wrapper.find('form').trigger('submit.prevent')
    await nextTick()

    expect(mockAuth.register).toHaveBeenCalled()
  })
})