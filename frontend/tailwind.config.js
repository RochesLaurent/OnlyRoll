import forms from '@tailwindcss/forms'

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./index.html",
        "./src/**/*.{vue,js,ts,jsx,tsx}",
    ],
    theme: {
        extend: {
            colors: {
                // Primary Colors - Violet mystique
                primary: {
                    900: '#1a0b2e',  // Fond principal
                    800: '#2d1b44',
                    700: '#402a5b',
                    600: '#553572',
                    500: '#6366f1',  // Accent principal
                    400: '#818cf8',
                    300: '#a5b4fc',
                    200: '#c7d2fe',
                    100: '#e0e7ff',
                    50: '#f0f4ff'
                },
                // Secondary Colors - Gris ardoise
                secondary: {
                    900: '#0f172a',
                    800: '#1e293b',  // Fond cartes
                    700: '#334155',  // Bordures
                    600: '#475569',
                    500: '#64748b',
                    400: '#94a3b8',  // Texte muet
                    300: '#cbd5e1',  // Texte secondaire
                    200: '#e2e8f0',
                    100: '#f1f5f9',
                    50: '#f8fafc'   // Texte principal
                },
                // Accent Colors D&D
                accent: {
                    amber: '#f59e0b',    // Or/Trésor
                    emerald: '#10b981',  // Succès/Nature
                    rose: '#f43f5e',     // Dégâts/Danger
                    cyan: '#06b6d4',     // Magie/Eau
                    purple: '#8b5cf6'    // Arcane
                },
                // System Colors
                success: '#22c55e',
                warning: '#eab308',
                error: '#ef4444',
                info: '#3b82f6'
            },
            fontFamily: {
                'display': ['Figtree', '-apple-system', 'sans-serif'],
                'body': ['Figtree', '-apple-system', 'sans-serif'],
                'mono': ['JetBrains Mono', 'Fira Code', 'monospace']
            },
            boxShadow: {
                'purple': '0 4px 14px 0 rgba(99, 102, 241, 0.39)',
                'purple-lg': '0 10px 25px rgba(99, 102, 241, 0.3)',
            },
            animation: {
                'bounce-gentle': 'bounceGentle 2s infinite',
            },
            keyframes: {
                bounceGentle: {
                    '0%, 20%, 53%, 80%, 100%': { transform: 'translate3d(0,0,0)' },
                    '40%, 43%': { transform: 'translate3d(0,-15px,0)' },
                    '70%': { transform: 'translate3d(0,-7px,0)' },
                    '90%': { transform: 'translate3d(0,-2px,0)' },
                }
            }
        },
    },
    plugins: [
        forms,
    ],
}