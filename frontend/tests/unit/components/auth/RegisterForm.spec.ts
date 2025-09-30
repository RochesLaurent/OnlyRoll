/**
 * Tests unitaires pour le composant RegisterForm
 * 
 * @covers src/components/auth/RegisterForm.vue
 */

import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount, VueWrapper } from '@vue/test-utils'
import { setActivePinia, createPinia } from 'pinia'
import RegisterForm from '@/components/auth/RegisterForm.vue'

// Mock du composable useAuth
const mockRegister = vi.fn()
const mockClearError = vi.fn()

vi.mock('@/composables/useAuth', () => ({
  useAuth: () => ({
    register: mockRegister,
    isLoading: false,
    error: null,
    clearError: mockClearError,
  }),
}))

describe('RegisterForm Component', () => {
  let wrapper: VueWrapper

  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()

    wrapper = mount(RegisterForm)
  })

  describe('Rendu initial', () => {
    it('affiche tous les champs du formulaire', () => {
      expect(wrapper.find('input[name="pseudo"]').exists()).toBe(true)
      expect(wrapper.find('input[name="email"]').exists()).toBe(true)
      expect(wrapper.find('input[name="password"]').exists()).toBe(true)
      expect(wrapper.find('input[name="confirmPassword"]').exists()).toBe(true)
      expect(wrapper.find('input[type="checkbox"]').exists()).toBe(true)
    })

    it('affiche le bouton de soumission', () => {
      const submitButton = wrapper.find('button[type="submit"]')
      expect(submitButton.exists()).toBe(true)
      expect(submitButton.text()).toContain('Créer mon compte')
    })

    it('le bouton de soumission est désactivé par défaut', () => {
      const submitButton = wrapper.find('button[type="submit"]')
      expect(submitButton.attributes('disabled')).toBeDefined()
    })
  })

  describe('Validation du pseudo', () => {
    it('affiche une erreur si le pseudo est vide', async () => {
      const pseudoInput = wrapper.find('input[name="pseudo"]')
      
      await pseudoInput.setValue('')
      await pseudoInput.trigger('blur')
      await wrapper.vm.$nextTick()

      const errors = wrapper.findAll('.text-red-400')
      expect(errors.length).toBeGreaterThan(0)
    })

    it('affiche une erreur si le pseudo est trop court', async () => {
      const pseudoInput = wrapper.find('input[name="pseudo"]')
      
      await pseudoInput.setValue('ab') // Moins de 3 caractères
      await pseudoInput.trigger('blur')
      
      // Le formulaire devrait être invalide
      expect(wrapper.vm.isFormValid).toBe(false)
    })

    it('accepte un pseudo valide', async () => {
      const pseudoInput = wrapper.find('input[name="pseudo"]')
      
      await pseudoInput.setValue('ValidPseudo')
      await pseudoInput.trigger('blur')
      
      const vm = wrapper.vm as any
      expect(vm.form.pseudo).toBe('ValidPseudo')
    })
  })

  describe('Validation de l\'email', () => {
    it('affiche une erreur pour un email invalide', async () => {
      const emailInput = wrapper.find('input[name="email"]')
      
      await emailInput.setValue('invalid-email')
      await emailInput.trigger('blur')
      
      expect(wrapper.vm.isFormValid).toBe(false)
    })

    it('accepte un email valide', async () => {
      const emailInput = wrapper.find('input[name="email"]')
      
      await emailInput.setValue('valid@onlyroll.com')
      await emailInput.trigger('blur')
      
      const vm = wrapper.vm as any
      expect(vm.form.email).toBe('valid@onlyroll.com')
    })
  })

  describe('Validation du mot de passe', () => {
    it('vérifie la longueur minimale (8 caractères)', async () => {
      const passwordInput = wrapper.find('input[name="password"]')
      
      await passwordInput.setValue('Short1!')
      
      const vm = wrapper.vm as any
      expect(vm.passwordRules.minLength).toBe(false)
    })

    it('vérifie la présence d\'une minuscule', async () => {
      const passwordInput = wrapper.find('input[name="password"]')
      
      await passwordInput.setValue('PASSWORD123!')
      
      const vm = wrapper.vm as any
      expect(vm.passwordRules.lowercase).toBe(false)
    })

    it('vérifie la présence d\'une majuscule', async () => {
      const passwordInput = wrapper.find('input[name="password"]')
      
      await passwordInput.setValue('password123!')
      
      const vm = wrapper.vm as any
      expect(vm.passwordRules.uppercase).toBe(false)
    })

    it('vérifie la présence d\'un chiffre', async () => {
      const passwordInput = wrapper.find('input[name="password"]')
      
      await passwordInput.setValue('Password!')
      
      const vm = wrapper.vm as any
      expect(vm.passwordRules.number).toBe(false)
    })

    it('accepte un mot de passe valide', async () => {
      const passwordInput = wrapper.find('input[name="password"]')
      
      await passwordInput.setValue('ValidPass123!')
      
      const vm = wrapper.vm as any
      expect(vm.passwordRules.minLength).toBe(true)
      expect(vm.passwordRules.lowercase).toBe(true)
      expect(vm.passwordRules.uppercase).toBe(true)
      expect(vm.passwordRules.number).toBe(true)
    })
  })

  describe('Validation de la confirmation du mot de passe', () => {
    it('affiche une erreur si les mots de passe ne correspondent pas', async () => {
      const passwordInput = wrapper.find('input[name="password"]')
      const confirmInput = wrapper.find('input[name="confirmPassword"]')
      
      await passwordInput.setValue('Password123!')
      await confirmInput.setValue('DifferentPass123!')
      
      const vm = wrapper.vm as any
      expect(vm.passwordsMatch).toBe(false)
    })

    it('valide si les mots de passe correspondent', async () => {
      const passwordInput = wrapper.find('input[name="password"]')
      const confirmInput = wrapper.find('input[name="confirmPassword"]')
      
      const samePassword = 'Password123!'
      await passwordInput.setValue(samePassword)
      await confirmInput.setValue(samePassword)
      
      const vm = wrapper.vm as any
      expect(vm.passwordsMatch).toBe(true)
    })
  })

  describe('Toggle de visibilité du mot de passe', () => {
    it('bascule entre password et text pour le champ password', async () => {
      const passwordInput = wrapper.find('input[name="password"]')
      const toggleButton = wrapper.findAll('button[type="button"]')[0]
      
      expect(passwordInput.attributes('type')).toBe('password')
      
      await toggleButton.trigger('click')
      expect(passwordInput.attributes('type')).toBe('text')
      
      await toggleButton.trigger('click')
      expect(passwordInput.attributes('type')).toBe('password')
    })
  })

  describe('Soumission du formulaire', () => {
    it('n\'envoie pas le formulaire si invalide', async () => {
      await wrapper.find('form').trigger('submit.prevent')
      
      expect(mockRegister).not.toHaveBeenCalled()
    })

    it('envoie le formulaire avec les bonnes données si valide', async () => {
      const vm = wrapper.vm as any
      
      // Remplir le formulaire avec des données valides
      await wrapper.find('input[name="pseudo"]').setValue('TestUser')
      await wrapper.find('input[name="email"]').setValue('test@onlyroll.com')
      await wrapper.find('input[name="password"]').setValue('ValidPass123!')
      await wrapper.find('input[name="confirmPassword"]').setValue('ValidPass123!')
      await wrapper.find('input[type="checkbox"]').setValue(true)
      
      await wrapper.find('form').trigger('submit.prevent')
      await wrapper.vm.$nextTick()
      
      expect(mockRegister).toHaveBeenCalledWith({
        pseudo: 'TestUser',
        email: 'test@onlyroll.com',
        password: 'ValidPass123!',
        confirmPassword: 'ValidPass123!',
      })
    })

    it('n\'envoie pas si les conditions ne sont pas acceptées', async () => {
      await wrapper.find('input[name="pseudo"]').setValue('TestUser')
      await wrapper.find('input[name="email"]').setValue('test@onlyroll.com')
      await wrapper.find('input[name="password"]').setValue('ValidPass123!')
      await wrapper.find('input[name="confirmPassword"]').setValue('ValidPass123!')
      // Ne pas cocher la checkbox
      
      const vm = wrapper.vm as any
      expect(vm.form.acceptTerms).toBe(false)
      expect(vm.isFormValid).toBe(false)
    })

    it('désactive le bouton pendant le chargement', async () => {
      // Remount avec isLoading = true
      vi.mock('@/composables/useAuth', () => ({
        useAuth: () => ({
          register: mockRegister,
          isLoading: true, // Loading
          error: null,
          clearError: mockClearError,
        }),
      }))

      wrapper = mount(RegisterForm)
      
      const submitButton = wrapper.find('button[type="submit"]')
      expect(submitButton.attributes('disabled')).toBeDefined()
      expect(submitButton.text()).toContain('Création du compte...')
    })
  })

  describe('Affichage des erreurs', () => {
    it('affiche les erreurs de validation', async () => {
      const vm = wrapper.vm as any
      
      // Soumettre un formulaire invalide
      await wrapper.find('input[name="pseudo"]').setValue('ab') // Trop court
      await wrapper.find('form').trigger('submit.prevent')
      
      expect(vm.validationErrors.length).toBeGreaterThan(0)
    })

    it('efface l\'erreur globale lors de la saisie', async () => {
      const emailInput = wrapper.find('input[name="email"]')
      
      await emailInput.trigger('input')
      
      expect(mockClearError).toHaveBeenCalled()
    })
  })

  describe('Règles de mot de passe visuelles', () => {
    it('affiche les indicateurs de règles de mot de passe', () => {
      const passwordInput = wrapper.find('input[name="password"]')
      
      // Les indicateurs devraient être présents dans le DOM
      expect(wrapper.html()).toContain('8 caractères minimum')
      expect(wrapper.html()).toContain('Une minuscule')
      expect(wrapper.html()).toContain('Une majuscule')
      expect(wrapper.html()).toContain('Un chiffre')
    })

    it('met à jour les indicateurs en temps réel', async () => {
      const passwordInput = wrapper.find('input[name="password"]')
      
      await passwordInput.setValue('pass')
      const vm = wrapper.vm as any
      
      expect(vm.passwordRules.minLength).toBe(false)
      expect(vm.passwordRules.lowercase).toBe(true)
      expect(vm.passwordRules.uppercase).toBe(false)
      expect(vm.passwordRules.number).toBe(false)
    })
  })

  describe('Computed isFormValid', () => {
    it('retourne false si le formulaire est incomplet', () => {
      const vm = wrapper.vm as any
      expect(vm.isFormValid).toBe(false)
    })

    it('retourne true si tous les champs sont valides', async () => {
      await wrapper.find('input[name="pseudo"]').setValue('ValidUser')
      await wrapper.find('input[name="email"]').setValue('valid@onlyroll.com')
      await wrapper.find('input[name="password"]').setValue('ValidPass123!')
      await wrapper.find('input[name="confirmPassword"]').setValue('ValidPass123!')
      await wrapper.find('input[type="checkbox"]').setValue(true)
      
      const vm = wrapper.vm as any
      expect(vm.isFormValid).toBe(true)
    })
  })

  describe('Gestion des erreurs API', () => {
    it('gère les erreurs lors de l\'inscription', async () => {
      mockRegister.mockRejectedValueOnce(new Error('Email déjà utilisé'))
      
      await wrapper.find('input[name="pseudo"]').setValue('TestUser')
      await wrapper.find('input[name="email"]').setValue('existing@onlyroll.com')
      await wrapper.find('input[name="password"]').setValue('ValidPass123!')
      await wrapper.find('input[name="confirmPassword"]').setValue('ValidPass123!')
      await wrapper.find('input[type="checkbox"]').setValue(true)
      
      await wrapper.find('form').trigger('submit.prevent')
      
      // L'erreur devrait être gérée par le composable
      expect(mockRegister).toHaveBeenCalled()
    })
  })

  describe('Informations supplémentaires', () => {
    it('affiche un message informatif sur la vérification email', () => {
      expect(wrapper.html()).toContain('Un email de vérification sera envoyé')
    })
  })

  describe('Scénario complet d\'inscription', () => {
    it('remplit et soumet le formulaire complet avec succès', async () => {
      const credentials = {
        pseudo: 'NewPlayer',
        email: 'newplayer@onlyroll.com',
        password: 'SecurePass123!',
      }
      
      // 1. Remplir tous les champs
      await wrapper.find('input[name="pseudo"]').setValue(credentials.pseudo)
      await wrapper.find('input[name="email"]').setValue(credentials.email)
      await wrapper.find('input[name="password"]').setValue(credentials.password)
      await wrapper.find('input[name="confirmPassword"]').setValue(credentials.password)
      await wrapper.find('input[type="checkbox"]').setValue(true)
      
      // 2. Vérifier que le formulaire est valide
      const vm = wrapper.vm as any
      expect(vm.isFormValid).toBe(true)
      
      // 3. Soumettre
      await wrapper.find('form').trigger('submit.prevent')
      
      // 4. Vérifier l'appel
      expect(mockRegister).toHaveBeenCalledWith({
        pseudo: credentials.pseudo,
        email: credentials.email,
        password: credentials.password,
        confirmPassword: credentials.password,
      })
    })
  })
})