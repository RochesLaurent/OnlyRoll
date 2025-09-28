<template>
    <div v-if="isAuthenticated" class="flex items-center space-x-3">
        <!-- Avatar par défaut -->
        <div class="w-8 h-8 bg-primary-500 rounded-full flex items-center justify-center">
            <span class="text-white text-sm font-medium">
                {{ userInitials }}
            </span>
        </div>
        
        <!-- Pseudo -->
        <div class="flex flex-col">
            <span class="text-secondary-50 text-sm font-medium">
                {{ user?.pseudo }}
            </span>
            <span class="text-secondary-400 text-xs">
                {{ user?.email }}
            </span>
        </div>
    </div>
</template>

<script setup lang="ts">
    import { computed } from 'vue'
    import { useAuth } from '@/composables/useAuth'

    const { user, isAuthenticated } = useAuth()

    const userInitials = computed(() => {
        if (!user.value?.pseudo) return '?'
        
        const names = user.value.pseudo.split(' ')
        if (names.length >= 2) {
            return (names[0][0] + names[1][0]).toUpperCase()
        }
        return user.value.pseudo.slice(0, 2).toUpperCase()
    })
</script>