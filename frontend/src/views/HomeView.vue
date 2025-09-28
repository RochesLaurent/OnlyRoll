<template>
  <div style="background: linear-gradient(135deg, #1a0b2e, #2d1b44, #0f172a); min-height: 100vh; color: #f8fafc;">
    <!-- Navigation simple -->
    <nav style="background: rgba(30, 41, 59, 0.5); backdrop-filter: blur(12px); border-bottom: 1px solid #334155; padding: 1rem 0;">
      <div style="max-width: 1200px; margin: 0 auto; padding: 0 1rem; display: flex; justify-content: space-between; align-items: center;">
        <!-- Logo -->
        <div style="display: flex; align-items: center; gap: 0.75rem;">
          <div style="width: 2rem; height: 2rem; background: linear-gradient(135deg, #6366f1, #818cf8); border-radius: 0.5rem; display: flex; align-items: center; justify-content: center;">
            <span style="color: white; font-size: 1rem;">🎲</span>
          </div>
          <span style="font-size: 1.25rem; font-weight: bold; color: #f8fafc;">OnlyRoll</span>
        </div>

        <!-- Boutons auth -->
        <div style="display: flex; align-items: center; gap: 1rem;">
          <router-link 
            to="/auth/login" 
            style="padding: 0.5rem 1rem; color: #cbd5e1; text-decoration: none; transition: color 0.2s;"
          >
            Connexion
          </router-link>
          <router-link 
            to="/auth/register" 
            style="padding: 0.5rem 1rem; background: #6366f1; color: white; text-decoration: none; border-radius: 0.5rem; font-weight: 500; transition: background-color 0.2s;"
          >
            Inscription
          </router-link>
        </div>
      </div>
    </nav>

    <!-- Hero Section -->
    <section style="padding: 5rem 1rem; text-align: center;">
      <div style="max-width: 1200px; margin: 0 auto;">
        <!-- Titre principal -->
        <h1 style="font-size: 3rem; font-weight: bold; color: #f8fafc; margin-bottom: 1.5rem;">
          OnlyRoll
        </h1>
        <p style="font-size: 1.5rem; color: #e2e8f0; margin-bottom: 1rem;">
          One site to Roll them all
        </p>
        <p style="font-size: 1.125rem; color: #94a3b8; margin-bottom: 3rem; max-width: 600px; margin-left: auto; margin-right: auto;">
          Jouez à distance avec vos amis !<br>
          Interface simple, données SRD intégrées,<br>
          aucune installation requise.
        </p>

        <!-- Boutons d'action -->
        <div style="display: flex; flex-direction: column; gap: 1rem; justify-content: center; align-items: center; max-width: 400px; margin: 0 auto;">
          <router-link
            to="/auth/register"
            style="padding: 1rem 2rem; background: #6366f1; color: white; text-decoration: none; font-size: 1.125rem; font-weight: 600; border-radius: 0.5rem; box-shadow: 0 4px 14px rgba(99, 102, 241, 0.39); transition: all 0.2s; transform: translateY(0); display: block; width: 100%; text-align: center;"
            @mouseenter="$event.target.style.background = '#818cf8'; $event.target.style.transform = 'translateY(-2px)'"
            @mouseleave="$event.target.style.background = '#6366f1'; $event.target.style.transform = 'translateY(0)'"
          >
            Inscription
          </router-link>
          <router-link
            to="/auth/login"
            style="padding: 1rem 2rem; background: #334155; color: #f8fafc; text-decoration: none; font-size: 1.125rem; font-weight: 600; border-radius: 0.5rem; border: 1px solid #475569; transition: background-color 0.2s; display: block; width: 100%; text-align: center;"
            @mouseenter="$event.target.style.background = '#475569'"
            @mouseleave="$event.target.style.background = '#334155'"
          >
            Connexion
          </router-link>
        </div>
      </div>
    </section>

    <!-- Section fonctionnalités simple -->
    <section style="padding: 5rem 1rem; background: rgba(248, 250, 252, 0.05);">
      <div style="max-width: 1200px; margin: 0 auto;">
        <h2 style="font-size: 2rem; font-weight: bold; text-align: center; color: #f8fafc; margin-bottom: 4rem;">
          Fonctionnalités
        </h2>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
          <!-- Feature 1 -->
          <div style="background: #1e293b; border-radius: 0.75rem; padding: 1.5rem; border: 1px solid #334155;">
            <div style="width: 3rem; height: 3rem; background: rgba(99, 102, 241, 0.1); border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; margin-bottom: 1rem;">
              📚
            </div>
            <h3 style="font-size: 1.125rem; font-weight: 600; color: #f8fafc; margin-bottom: 0.5rem;">
              Wiki D&D
            </h3>
            <p style="color: #94a3b8; font-size: 0.875rem; line-height: 1.5;">
              Toutes les données officielles de D&D 5e à portée de main !
            </p>
          </div>

          <!-- Feature 2 -->
          <div style="background: #1e293b; border-radius: 0.75rem; padding: 1.5rem; border: 1px solid #334155;">
            <div style="width: 3rem; height: 3rem; background: rgba(16, 185, 129, 0.1); border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; margin-bottom: 1rem;">
              👤
            </div>
            <h3 style="font-size: 1.125rem; font-weight: 600; color: #f8fafc; margin-bottom: 0.5rem;">
              Feuille perso
            </h3>
            <p style="color: #94a3b8; font-size: 0.875rem; line-height: 1.5;">
              Des feuilles de personnage personnalisées et automatisées depuis le SRD
            </p>
          </div>

          <!-- Feature 3 -->
          <div style="background: #1e293b; border-radius: 0.75rem; padding: 1.5rem; border: 1px solid #334155;">
            <div style="width: 3rem; height: 3rem; background: rgba(6, 182, 212, 0.1); border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; margin-bottom: 1rem;">
              💬
            </div>
            <h3 style="font-size: 1.125rem; font-weight: 600; color: #f8fafc; margin-bottom: 0.5rem;">
              Chat Temps Réel
            </h3>
            <p style="color: #94a3b8; font-size: 0.875rem; line-height: 1.5;">
              Vos résultats de dés et vos meilleurs vannes en temps réel
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- Footer simple -->
    <footer style="background: #0f172a; border-top: 1px solid #334155; padding: 3rem 1rem;">
      <div style="max-width: 1200px; margin: 0 auto; text-align: center;">
        <div style="display: flex; align-items: center; justify-content: center; gap: 0.75rem; margin-bottom: 1rem;">
          <div style="width: 2rem; height: 2rem; background: linear-gradient(135deg, #6366f1, #818cf8); border-radius: 0.5rem; display: flex; align-items: center; justify-content: center;">
            <span style="color: white; font-size: 1rem;">🎲</span>
          </div>
          <span style="font-size: 1.25rem; font-weight: bold; color: #f8fafc;">OnlyRoll</span>
        </div>
        <p style="color: #64748b; font-size: 0.875rem;">
          © 2024 OnlyRoll • Tous droits réservés 🇫🇷 Français
        </p>
      </div>
    </footer>
  </div>
</template>

<script setup lang="ts">
// Version simplifiée sans dépendances pour tester le routage
</script>