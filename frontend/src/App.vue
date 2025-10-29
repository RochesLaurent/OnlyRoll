<template>
  <div id="app">
    <RouterView />

    <!-- Easter Egg: Konami Code -->
    <KonamiVictory
      :is-active="konamiActivated"
      @close="resetKonami"
    />
  </div>
</template>

<script setup lang="ts">
import { useKonamiCode } from '@/composables/useKonamiCode'
import KonamiVictory from '@/components/common/KonamiVictory.vue'

// Détection du Konami Code
const { isActivated: konamiActivated, reset: resetKonami } = useKonamiCode()
</script>

<style></style>
