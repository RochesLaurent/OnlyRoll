<template>
  <div class="p-8 bg-gray-900 text-white">
    <h1 class="text-2xl">Test Simple</h1>
    <p>Si tu vois ça, le routing fonctionne !</p>
    <p>GameID: {{ gameId }}</p>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{
  gameId: number
}>()
</script>
