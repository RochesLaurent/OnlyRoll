<template>
  <nav class="bg-secondary-800/50 backdrop-blur-sm border-b border-secondary-700">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between items-center h-16">
        <!-- Logo -->
        <RouterLink to="/dashboard" class="flex items-center space-x-3">
          <div
            class="w-8 h-8 bg-gradient-to-br from-primary-500 to-primary-400 rounded-lg flex items-center justify-center"
          >
            <img style="width: 100%; height: 100%" src="/logo.png" />
          </div>
          <span class="text-xl font-bold text-secondary-50">OnlyRoll</span>
        </RouterLink>

        <!-- Menu de navigation -->
        <div class="flex items-center space-x-1">
          <RouterLink
            to="/dashboard"
            class="px-4 py-2 rounded-lg text-sm font-medium transition-colors"
            :class="[
              route.path === '/dashboard'
                ? 'bg-primary-500 text-white'
                : 'text-secondary-300 hover:text-secondary-50 hover:bg-secondary-700',
            ]"
          >
            Dashboard
          </RouterLink>
          <RouterLink
            to="/games"
            class="px-4 py-2 rounded-lg text-sm font-medium transition-colors"
            :class="[
              route.path.startsWith('/games')
                ? 'bg-primary-500 text-white'
                : 'text-secondary-300 hover:text-secondary-50 hover:bg-secondary-700',
            ]"
          >
            Parties
          </RouterLink>
        </div>

        <!-- Menu utilisateur -->
        <div class="flex items-center space-x-4">
          <UserProfileBadge />
          <button
            @click="handleLogout"
            class="px-4 py-2 text-sm text-secondary-300 hover:text-secondary-50 transition-colors"
          >
            Déconnexion
          </button>
        </div>
      </div>
    </div>
  </nav>
</template>

<script setup lang="ts">
import { useRoute } from 'vue-router'
import { useAuth } from '@/composables/useAuth'
import UserProfileBadge from '@/components/common/UserProfileBadge.vue'

const route = useRoute()
const { logout } = useAuth()

const handleLogout = async () => {
  await logout()
}
</script>
