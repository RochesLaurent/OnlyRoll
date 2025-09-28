<template>
  <div class="min-h-screen bg-gradient-to-br from-primary-900 via-primary-800 to-secondary-900">
    <!-- Navigation temporaire -->
    <nav class="bg-secondary-800/50 backdrop-blur-sm border-b border-secondary-700">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
          <!-- Logo -->
          <RouterLink to="/" class="flex items-center space-x-3">
            <div class="w-8 h-8 bg-gradient-to-br from-primary-500 to-primary-400 rounded-lg flex items-center justify-center">
              <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zM9.5 16.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5zm5 0c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
              </svg>
            </div>
            <span class="text-xl font-bold text-secondary-50">OnlyRoll</span>
          </RouterLink>

          <!-- Menu utilisateur -->
          <div class="flex items-center space-x-4">
            <UserProfileBadge />
            <button
              @click="logout"
              class="px-4 py-2 text-sm text-secondary-300 hover:text-secondary-50 transition-colors"
            >
              Déconnexion
            </button>
          </div>
        </div>
      </div>
    </nav>

    <!-- Contenu principal -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <!-- Message de bienvenue -->
      <div class="text-center mb-12">
        <h1 class="text-4xl font-bold text-secondary-50 mb-4">
          Bienvenue, {{ user?.pseudo }} !
        </h1>
        <p class="text-lg text-secondary-400">
          Votre dashboard OnlyRoll - Prêt pour l'aventure ?
        </p>
      </div>

      <!-- Sections du dashboard -->
      <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
        <!-- Mes parties -->
        <DashboardCard
          title="Mes Parties"
          description="Créez ou rejoignez une partie de D&D"
          icon="gamepad"
          @click="navigateTo('/games')"
          :coming-soon="true"
        />

        <!-- Wiki D&D -->
        <DashboardCard
          title="Wiki D&D"
          description="Consultez toutes les données du SRD"
          icon="book"
          @click="navigateTo('/wiki')"
          :coming-soon="true"
        />

        <!-- Personnages -->
        <DashboardCard
          title="Mes Personnages"
          description="Gérez vos feuilles de personnage"
          icon="user"
          @click="navigateTo('/characters')"
          :coming-soon="true"
        />

        <!-- Profil -->
        <DashboardCard
          title="Mon Profil"
          description="Paramètres et préférences"
          icon="settings"
          @click="navigateTo('/profile')"
          :coming-soon="true"
        />

        <!-- Partie rapide -->
        <DashboardCard
          title="Partie Rapide"
          description="Rejoindre une partie publique"
          icon="zap"
          @click="navigateTo('/games/public')"
          :coming-soon="true"
        />

        <!-- Communauté -->
        <DashboardCard
          title="Communauté"
          description="Discord et réseaux sociaux"
          icon="users"
          @click="openDiscord"
          :coming-soon="true"
        />
      </div>

      <!-- Informations de développement -->
      <div class="mt-16 bg-secondary-800/50 rounded-xl p-8 border border-secondary-700">
        <div class="text-center">
          <h2 class="text-xl font-semibold text-secondary-50 mb-4">
            🚧 Développement en cours
          </h2>
          <p class="text-secondary-400 mb-6">
            Félicitations ! Votre système d'authentification fonctionne parfaitement.<br>
            Les autres fonctionnalités arrivent bientôt...
          </p>
          
          <!-- Infos utilisateur -->
          <div class="bg-secondary-800 rounded-lg p-4 max-w-md mx-auto">
            <h3 class="text-sm font-medium text-secondary-300 mb-2">Informations de session</h3>
            <div class="text-left space-y-1 text-xs text-secondary-400">
              <p><span class="text-secondary-300">ID:</span> {{ user?.id }}</p>
              <p><span class="text-secondary-300">Email:</span> {{ user?.email }}</p>
              <p><span class="text-secondary-300">Pseudo:</span> {{ user?.pseudo }}</p>
              <p><span class="text-secondary-300">Rôles:</span> {{ user?.roles.join(', ') }}</p>
              <p><span class="text-secondary-300">Vérifié:</span> {{ user?.isVerified ? 'Oui' : 'Non' }}</p>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { useAuth } from '@/composables/useAuth'
import { useRouter } from 'vue-router'
import UserProfileBadge from '@/components/common/UserProfileBadge.vue'
import DashboardCard from '@/components/dashboard/DashboardCard.vue'

const { user, logout } = useAuth()
const router = useRouter()

const navigateTo = (path: string) => {
  // Pour l'instant, on affiche juste un message
  alert(`Navigation vers ${path} - Fonctionnalité en cours de développement`)
}

const openDiscord = () => {
  window.open('https://discord.gg/your-server', '_blank')
}
</script>