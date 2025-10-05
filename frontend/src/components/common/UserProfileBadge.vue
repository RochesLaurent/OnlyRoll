<template>
  <div v-if="isAuthenticated" class="relative">
    <!-- Badge cliquable -->
    <button
      @click="toggleMenu"
      class="flex items-center space-x-3 hover:bg-secondary-700/50 rounded-lg p-2 transition-colors"
    >
      <!-- Avatar -->
      <div class="w-8 h-8 bg-primary-500 rounded-full flex items-center justify-center">
        <span class="text-white text-sm font-medium">
          {{ userInitials }}
        </span>
      </div>

      <!-- Info utilisateur -->
      <div class="flex flex-col items-start">
        <span class="text-secondary-50 text-sm font-medium">
          {{ user?.pseudo }}
        </span>
        <span class="text-secondary-400 text-xs">
          {{ user?.email }}
        </span>
      </div>

      <!-- Icône chevron -->
      <svg
        class="w-4 h-4 text-secondary-400 transition-transform"
        :class="{ 'rotate-180': isMenuOpen }"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
      </svg>
    </button>

    <!-- Menu déroulant -->
    <Transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="transform opacity-0 scale-95"
      enter-to-class="transform opacity-100 scale-100"
      leave-active-class="transition ease-in duration-150"
      leave-from-class="transform opacity-100 scale-100"
      leave-to-class="transform opacity-0 scale-95"
    >
      <div
        v-if="isMenuOpen"
        class="absolute right-0 mt-2 w-56 bg-secondary-800 border border-secondary-700 rounded-lg shadow-lg z-50"
      >
        <div class="py-1">
          <!-- Profil -->
          <RouterLink
            to="/profile"
            @click="closeMenu"
            class="flex items-center px-4 py-2 text-sm text-secondary-200 hover:bg-secondary-700 transition-colors"
          >
            <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
              />
            </svg>
            Mon profil
          </RouterLink>

          <!-- Paramètres -->
          <RouterLink
            to="/settings"
            @click="closeMenu"
            class="flex items-center px-4 py-2 text-sm text-secondary-200 hover:bg-secondary-700 transition-colors"
          >
            <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
              />
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
              />
            </svg>
            Paramètres
          </RouterLink>

          <!-- Divider -->
          <div class="border-t border-secondary-700 my-1"></div>

          <!-- Déconnexion -->
          <button
            @click="handleLogout"
            :disabled="isLoggingOut"
            class="w-full flex items-center px-4 py-2 text-sm text-error hover:bg-secondary-700 transition-colors disabled:opacity-50"
          >
            <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
              />
            </svg>
            {{ isLoggingOut ? 'Déconnexion...' : 'Se déconnecter' }}
          </button>
        </div>
      </div>
    </Transition>

    <!-- Overlay pour fermer le menu -->
    <div v-if="isMenuOpen" @click="closeMenu" class="fixed inset-0 z-40"></div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useAuth } from '@/composables/useAuth'

const { user, isAuthenticated, logout } = useAuth()

const isMenuOpen = ref(false)
const isLoggingOut = ref(false)

const userInitials = computed(() => {
  if (!user.value?.pseudo) return '?'
  const names = user.value.pseudo.split(' ')
  if (names.length >= 2) {
    return (names[0][0] + names[1][0]).toUpperCase()
  }
  return user.value.pseudo.slice(0, 2).toUpperCase()
})

const toggleMenu = () => {
  isMenuOpen.value = !isMenuOpen.value
}

const closeMenu = () => {
  isMenuOpen.value = false
}

const handleLogout = async () => {
  isLoggingOut.value = true
  try {
    await logout()
  } catch (err) {
    console.error('Erreur lors de la déconnexion:', err)
  } finally {
    isLoggingOut.value = false
    closeMenu()
  }
}
</script>
