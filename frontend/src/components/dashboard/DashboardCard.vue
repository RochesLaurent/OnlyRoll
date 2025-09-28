<template>
    <div 
        class="relative bg-secondary-800 rounded-xl p-6 border border-secondary-700 hover:border-secondary-600 cursor-pointer transition-all duration-200 hover:shadow-lg group"
        :class="{ 'opacity-75': comingSoon }"
        @click="handleClick"
    >
        <!-- Badge "Bientôt" -->
        <div 
            v-if="comingSoon" 
            class="absolute top-3 right-3 px-2 py-1 bg-warning/20 text-warning text-xs font-medium rounded-full border border-warning/30"
        >
            Bientôt
        </div>

        <!-- Icône -->
        <div class="flex items-center justify-center w-12 h-12 bg-primary-500/10 rounded-lg mb-4 group-hover:scale-110 transition-transform duration-200">
            <!-- Icône Gamepad -->
            <svg v-if="icon === 'gamepad'" class="w-6 h-6 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            
            <!-- Icône Book -->
            <svg v-else-if="icon === 'book'" class="w-6 h-6 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
            </svg>
            
            <!-- Icône User -->
            <svg v-else-if="icon === 'user'" class="w-6 h-6 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            
            <!-- Icône Settings -->
            <svg v-else-if="icon === 'settings'" class="w-6 h-6 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            
            <!-- Icône Zap -->
            <svg v-else-if="icon === 'zap'" class="w-6 h-6 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
            
            <!-- Icône Users -->
            <svg v-else-if="icon === 'users'" class="w-6 h-6 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            
            <!-- Icône par défaut (gamepad) -->
            <svg v-else class="w-6 h-6 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        
        <!-- Contenu -->
        <h3 class="text-lg font-semibold text-secondary-50 mb-2">
            {{ title }}
        </h3>
        <p class="text-secondary-400 text-sm leading-relaxed">
            {{ description }}
        </p>

        <!-- Flèche d'action -->
        <div class="flex justify-end mt-4">
            <svg class="w-5 h-5 text-secondary-500 group-hover:text-primary-400 group-hover:translate-x-1 transition-all duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
            </svg>
        </div>
    </div>
</template>

<script setup lang="ts">
    interface Props {
        title: string
        description: string
        icon: string
        comingSoon?: boolean
    }

    const props = withDefaults(defineProps<Props>(), {
        comingSoon: false
    })

    const emit = defineEmits<{
        click: []
    }>()

    const handleClick = () => {
        if (!props.comingSoon) {
            emit('click')
        }
    }
</script>