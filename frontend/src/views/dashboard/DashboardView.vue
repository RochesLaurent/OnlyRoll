<template>
  <div class="min-h-screen bg-gradient-to-br from-primary-900 via-primary-800 to-secondary-900">
    <!-- Navigation -->
    <DashboardNav />

    <!-- Contenu principal -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <!-- Message de bienvenue -->
      <div class="text-center mb-12">
        <h1 class="text-4xl font-bold text-secondary-50 mb-4">Bienvenue, {{ user?.pseudo }} !</h1>
        <p class="text-lg text-secondary-400">Votre dashboard OnlyRoll - Prêt pour l'aventure ?</p>
      </div>

      <!-- Sections du dashboard -->
      <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
        <!-- Mes parties -->
        <DashboardCard
          title="Mes Parties"
          description="Créez ou rejoignez une partie de D&D"
          icon="gamepad"
          @click="navigateTo('games')"
          :coming-soon="false"
        />

        <!-- Wiki D&D -->
        <DashboardCard
          title="Wiki D&D"
          description="Consultez toutes les données du SRD"
          icon="book"
          @click="navigateTo('wiki')"
          :coming-soon="true"
        />

        <!-- Personnages -->
        <DashboardCard
          title="Mes Personnages"
          description="Gérez vos feuilles de personnage"
          icon="user"
          @click="navigateTo('characters')"
          :coming-soon="true"
        />

        <!-- Profil -->
        <DashboardCard
          title="Mon Profil"
          description="Paramètres et préférences"
          icon="settings"
          @click="navigateTo('profile')"
          :coming-soon="true"
        />

        <!-- Partie rapide -->
        <DashboardCard
          title="Partie Rapide"
          description="Rejoindre une partie publique"
          icon="zap"
          @click="navigateTo('games')"
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
          <h2 class="text-xl font-semibold text-secondary-50 mb-4">🚧 Développement en cours</h2>
          <p class="text-secondary-400 mb-6">
            Félicitations ! Votre système d'authentification fonctionne parfaitement.<br />
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
              <p>
                <span class="text-secondary-300">Vérifié:</span>
                {{ user?.isVerified ? 'Oui' : 'Non' }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router'
import { useAuth } from '@/composables/useAuth'
import DashboardNav from '@/components/dashboard/DashboardNav.vue'
import DashboardCard from '@/components/dashboard/DashboardCard.vue'

const router = useRouter()
const { user } = useAuth()

const navigateTo = (routeName: string) => {
  router.push({ name: routeName })
}

const openDiscord = () => {
  window.open('https://discord.gg/your-server', '_blank')
}
</script>
