<template>
    <LoginForm />
</template>

<script setup lang="ts">
    import LoginForm from '@/components/auth/LoginForm.vue'
    import { useAuth } from '@/composables/useAuth'
    import { onMounted } from 'vue'

    const { requireGuest } = useAuth()

    onMounted(() => {
        requireGuest()
    })
</script>