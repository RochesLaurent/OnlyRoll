/**
 * Tests unitaires pour le composant LoginForm
 *
 * @covers src/components/auth/LoginForm.vue
 */

import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount, VueWrapper } from '@vue/test-utils'
import { nextTick } from 'vue'
import LoginForm from '@/components/auth/LoginForm.vue'
import { useAuth } from '@/composables/useAuth'

// Mock du composable useAuth
vi.mock('@/composables/useAuth', () => ({
  useAuth: vi.fn(),
}))

vi.mock('@/utils/logger', () => ({
  logger: {
    log: vi.fn(),
    error: vi.fn(),
    warn: vi.fn(),
    debug: vi.fn(),
    info: vi.fn(),
  },
}))

describe('LoginForm.vue', () => {
  let wrapper: VueWrapper
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  let mockAuth: any

  beforeEach(() => {
    mockAuth = {
      login: vi.fn(),
      isLoading: false,
      error: null,
      clearError: vi.fn(),
    }

    vi.mocked(useAuth).mockReturnValue(mockAuth)

    wrapper = mount(LoginForm, {
      global: {
        stubs: {
          RouterLink: true,
        },
      },
    })
  })

  // ========== RENDERING ==========

  it('should render the form with all fields', () => {
    expect(wrapper.find('h2').text()).toBe('Connexion')
    expect(wrapper.find('#email').exists()).toBe(true)
    expect(wrapper.find('#password').exists()).toBe(true)
    expect(wrapper.find('#remember-me').exists()).toBe(true)
    expect(wrapper.find('button[type="submit"]').exists()).toBe(true)
  })

  it('should display form title and subtitle', () => {
    expect(wrapper.find('h2').text()).toBe('Connexion')
    expect(wrapper.text()).toContain('Accédez à votre table virtuelle')
  })

  it('should have test credentials button', () => {
    const buttons = wrapper.findAll('button[type="button"]')
    const hasTestButton = buttons.some(btn => btn.text().includes('données de test'))
    expect(hasTestButton).toBe(true)
  })

  // ========== FORM INPUT ==========

  it('should update form values on input', async () => {
    await wrapper.find('#email').setValue('test@example.com')
    await wrapper.find('#password').setValue('password123')

    expect((wrapper.find('#email').element as HTMLInputElement).value).toBe('test@example.com')
    expect((wrapper.find('#password').element as HTMLInputElement).value).toBe('password123')
  })

  it('should update remember me checkbox', async () => {
    const checkbox = wrapper.find('#remember-me')

    expect((checkbox.element as HTMLInputElement).checked).toBe(false)

    await checkbox.setValue(true)

    expect((checkbox.element as HTMLInputElement).checked).toBe(true)
  })

  // ========== PASSWORD VISIBILITY TOGGLE ==========

  it('should toggle password visibility', async () => {
    const passwordInput = wrapper.find('#password')
    const passwordButtons = wrapper.findAll('button[type="button"]')
    // Le premier bouton type="button" est le bouton de visibilité du mot de passe
    const toggleButton = passwordButtons.find(btn =>
      btn.element.parentElement?.classList.contains('absolute') ||
      btn.attributes('class')?.includes('absolute')
    )

    expect((passwordInput.element as HTMLInputElement).type).toBe('password')

    if (toggleButton) {
      await toggleButton.trigger('click')
      await nextTick()

      expect((passwordInput.element as HTMLInputElement).type).toBe('text')

      await toggleButton.trigger('click')
      await nextTick()

      expect((passwordInput.element as HTMLInputElement).type).toBe('password')
    }
  })

  // ========== TEST CREDENTIALS ==========

  it('should fill test credentials when button clicked', async () => {
    const buttons = wrapper.findAll('button[type="button"]')
    const testButton = buttons.find(btn => btn.text().includes('données de test'))

    expect(testButton).toBeDefined()

    if (testButton) {
      await testButton.trigger('click')
      await nextTick()

      expect((wrapper.find('#email').element as HTMLInputElement).value).toBe('test@onlyroll.com')
      expect((wrapper.find('#password').element as HTMLInputElement).value).toBe('password123')
    }
  })

  // ========== FORM VALIDATION ==========

  it('should disable submit button when form is invalid', async () => {
    const submitButton = wrapper.find('button[type="submit"]')

    expect(submitButton.attributes('disabled')).toBeDefined()
  })

  it('should enable submit button when form is valid', async () => {
    await wrapper.find('#email').setValue('test@example.com')
    await wrapper.find('#password').setValue('password123')
    await nextTick()

    const submitButton = wrapper.find('button[type="submit"]')

    expect(submitButton.attributes('disabled')).toBeUndefined()
  })

  it('should disable submit button with invalid email', async () => {
    await wrapper.find('#email').setValue('invalid-email')
    await wrapper.find('#password').setValue('password123')
    await nextTick()

    const submitButton = wrapper.find('button[type="submit"]')

    expect(submitButton.attributes('disabled')).toBeDefined()
  })

  it('should disable submit button with empty password', async () => {
    await wrapper.find('#email').setValue('test@example.com')
    await wrapper.find('#password').setValue('')
    await nextTick()

    const submitButton = wrapper.find('button[type="submit"]')

    expect(submitButton.attributes('disabled')).toBeDefined()
  })

  // ========== FORM SUBMISSION ==========

  it('should call login on valid form submission', async () => {
    await wrapper.find('#email').setValue('test@example.com')
    await wrapper.find('#password').setValue('password123')
    await nextTick()

    await wrapper.find('form').trigger('submit')
    await nextTick()

    expect(mockAuth.clearError).toHaveBeenCalled()
    expect(mockAuth.login).toHaveBeenCalledWith({
      email: 'test@example.com',
      password: 'password123',
    })
  })

  it('should not submit form if validation fails', async () => {
    await wrapper.find('#email').setValue('invalid-email')
    await wrapper.find('#password').setValue('12')
    await nextTick()

    await wrapper.find('form').trigger('submit')
    await nextTick()

    expect(mockAuth.login).not.toHaveBeenCalled()
  })

  it('should clear errors before submission', async () => {
    await wrapper.find('#email').setValue('test@example.com')
    await wrapper.find('#password').setValue('password123')
    await nextTick()

    await wrapper.find('form').trigger('submit')
    await nextTick()

    expect(mockAuth.clearError).toHaveBeenCalled()
  })

  // ========== LOADING STATE ==========

  it('should show loading state during submission', async () => {
    mockAuth.isLoading = true

    wrapper = mount(LoginForm, {
      global: {
        stubs: {
          RouterLink: true,
        },
      },
    })

    const submitButton = wrapper.find('button[type="submit"]')

    expect(submitButton.text()).toContain('Connexion...')
    expect(submitButton.attributes('disabled')).toBeDefined()
  })

  it('should disable inputs during loading', async () => {
    mockAuth.isLoading = true

    wrapper = mount(LoginForm, {
      global: {
        stubs: {
          RouterLink: true,
        },
      },
    })

    expect(wrapper.find('#email').attributes('disabled')).toBeDefined()
    expect(wrapper.find('#password').attributes('disabled')).toBeDefined()
    expect(wrapper.find('#remember-me').attributes('disabled')).toBeDefined()
  })

  // ========== ERROR DISPLAY ==========

  it('should display error message when error exists', async () => {
    mockAuth.error = 'Invalid credentials'

    wrapper = mount(LoginForm, {
      global: {
        stubs: {
          RouterLink: true,
        },
      },
    })

    await nextTick()

    expect(wrapper.text()).toContain('Invalid credentials')
  })

  it('should not display error block when no error', () => {
    mockAuth.error = null

    wrapper = mount(LoginForm, {
      global: {
        stubs: {
          RouterLink: true,
        },
      },
    })

    const errorDiv = wrapper.find('.bg-error\\/10')
    expect(errorDiv.exists()).toBe(false)
  })

  // ========== LINKS ==========

  it('should have forgot password link', () => {
    const forgotLink = wrapper.findComponent({ name: 'RouterLink' })
    expect(forgotLink.exists()).toBe(true)
    // Note: RouterLink is stubbed, so we can't check its text content
    // The actual text "Mot de passe oublié ?" is verified in e2e tests
  })

  // ========== ACCESSIBILITY ==========

  it('should have proper labels for inputs', () => {
    expect(wrapper.find('label[for="email"]').exists()).toBe(true)
    expect(wrapper.find('label[for="password"]').exists()).toBe(true)
    expect(wrapper.find('label[for="remember-me"]').exists()).toBe(true)
  })

  it('should have proper input types', () => {
    expect((wrapper.find('#email').element as HTMLInputElement).type).toBe('email')
    expect((wrapper.find('#password').element as HTMLInputElement).type).toBe('password')
    expect((wrapper.find('#remember-me').element as HTMLInputElement).type).toBe('checkbox')
  })

  it('should have autocomplete attributes', () => {
    expect(wrapper.find('#email').attributes('autocomplete')).toBe('email')
    expect(wrapper.find('#password').attributes('autocomplete')).toBe('current-password')
  })

  // ========== EDGE CASES ==========

  it('should handle login error gracefully', async () => {
    mockAuth.login.mockRejectedValueOnce(new Error('Network error'))

    await wrapper.find('#email').setValue('test@example.com')
    await wrapper.find('#password').setValue('password123')
    await nextTick()

    await wrapper.find('form').trigger('submit')
    await nextTick()

    // Should not crash
    expect(wrapper.exists()).toBe(true)
  })

  it('should trim whitespace from email', async () => {
    await wrapper.find('#email').setValue('  test@example.com  ')
    await wrapper.find('#password').setValue('password123')
    await nextTick()

    await wrapper.find('form').trigger('submit')
    await nextTick()

    expect(mockAuth.login).toHaveBeenCalledWith({
      email: 'test@example.com',
      password: 'password123',
    })
  })
})
