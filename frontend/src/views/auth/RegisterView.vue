<template>
  <RegisterForm />
</template>

<script setup lang="ts">
import RegisterForm from '@/components/auth/RegisterForm.vue'
import { useAuth } from '@/composables/useAuth'
import { onMounted } from 'vue'

const { requireGuest } = useAuth()

onMounted(() => {
  requireGuest()
})
</script>
