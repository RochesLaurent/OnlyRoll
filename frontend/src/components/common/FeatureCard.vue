<template>
    <div class="bg-secondary-800 rounded-xl p-6 border border-secondary-700 hover:border-secondary-600 transition-all duration-200 hover:shadow-lg group">
        <!-- Icône -->
        <div class="flex items-center justify-center w-12 h-12 rounded-lg mb-4 group-hover:scale-110 transition-transform duration-200" :class="iconBackgroundClass">
            <!-- Icône Book -->
            <svg v-if="icon === 'book'" class="w-6 h-6" :class="iconColorClass" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
            </svg>
            
            <!-- Icône User -->
            <svg v-else-if="icon === 'user'" class="w-6 h-6" :class="iconColorClass" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            
            <!-- Icône Chat -->
            <svg v-else-if="icon === 'chat'" class="w-6 h-6" :class="iconColorClass" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
            
            <!-- Icône Dice -->
            <svg v-else-if="icon === 'dice'" class="w-6 h-6" :class="iconColorClass" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zM9.5 16.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5zm5 0c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
            </svg>
            
            <!-- Icône Table -->
            <svg v-else-if="icon === 'table'" class="w-6 h-6" :class="iconColorClass" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" />
            </svg>
            
            <!-- Icône Users -->
            <svg v-else-if="icon === 'users'" class="w-6 h-6" :class="iconColorClass" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            
            <!-- Icône par défaut (book) -->
            <svg v-else class="w-6 h-6" :class="iconColorClass" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
            </svg>
        </div>
        
        <!-- Contenu -->
        <h3 class="text-lg font-semibold text-secondary-50 mb-2">
            {{ title }}
        </h3>
        <p class="text-secondary-400 text-sm leading-relaxed">
            {{ description }}
        </p>
    </div>
</template>

<script setup lang="ts">
    import { computed } from 'vue'

    interface Props {
    title: string
    description: string
    icon: string
    color?: string
    }

    const props = withDefaults(defineProps<Props>(), {
    color: 'primary'
    })

    const iconBackgroundClass = computed(() => {
        const colorMap = {
            'primary': 'bg-primary-500/10',
            'accent-emerald': 'bg-emerald-500/10',
            'accent-cyan': 'bg-cyan-500/10',
            'accent-amber': 'bg-amber-500/10',
            'accent-purple': 'bg-purple-500/10',
            'accent-rose': 'bg-rose-500/10'
        }
        return colorMap[props.color as keyof typeof colorMap] || colorMap.primary
    })

    const iconColorClass = computed(() => {
        const colorMap = {
            'primary': 'text-primary-500',
            'accent-emerald': 'text-emerald-500',
            'accent-cyan': 'text-cyan-500',
            'accent-amber': 'text-amber-500',
            'accent-purple': 'text-purple-500',
            'accent-rose': 'text-rose-500'
        }
        return colorMap[props.color as keyof typeof colorMap] || colorMap.primary
    })
</script>