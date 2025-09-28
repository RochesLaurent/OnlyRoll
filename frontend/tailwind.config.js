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
      fontSize: {
        'xs': ['0.75rem', '1rem'],     // 12px
        'sm': ['0.875rem', '1.25rem'], // 14px
        'base': ['1rem', '1.5rem'],    // 16px
        'lg': ['1.125rem', '1.75rem'], // 18px
        'xl': ['1.25rem', '1.75rem'],  // 20px
        '2xl': ['1.5rem', '2rem'],     // 24px
        '3xl': ['1.875rem', '2.25rem'], // 30px
        '4xl': ['2.25rem', '2.5rem'],   // 36px
        '5xl': ['3rem', '1'],           // 48px
        '6xl': ['3.75rem', '1'],        // 60px
      },
      borderRadius: {
        'none': '0',
        'sm': '0.375rem',   // 6px
        'DEFAULT': '0.5rem', // 8px
        'md': '0.75rem',    // 12px
        'lg': '1rem',       // 16px
        'xl': '1.5rem',     // 24px
        '2xl': '2rem',      // 32px
        'full': '9999px'
      },
      spacing: {
        '18': '4.5rem',   // 72px
        '88': '22rem',    // 352px
        '104': '26rem',   // 416px
        '112': '28rem',   // 448px
        '128': '32rem',   // 512px
      },
      boxShadow: {
        'sm': '0 1px 2px 0 rgba(0, 0, 0, 0.05)',
        'DEFAULT': '0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06)',
        'md': '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)',
        'lg': '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)',
        'xl': '0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)',
        '2xl': '0 25px 50px -12px rgba(0, 0, 0, 0.25)',
        'inner': 'inset 0 2px 4px 0 rgba(0, 0, 0, 0.06)',
        'purple': '0 4px 14px 0 rgba(99, 102, 241, 0.39)',
        'purple-lg': '0 10px 25px rgba(99, 102, 241, 0.3)',
      },
      backgroundImage: {
        'gradient-radial': 'radial-gradient(var(--tw-gradient-stops))',
        'gradient-conic': 'conic-gradient(from 180deg at 50% 50%, var(--tw-gradient-stops))',
      },
      animation: {
        'fade-in': 'fadeIn 0.5s ease-in-out',
        'slide-up': 'slideUp 0.3s ease-out',
        'bounce-gentle': 'bounceGentle 2s infinite',
      },
      keyframes: {
        fadeIn: {
          '0%': { opacity: '0' },
          '100%': { opacity: '1' },
        },
        slideUp: {
          '0%': { transform: 'translateY(10px)', opacity: '0' },
          '100%': { transform: 'translateY(0)', opacity: '1' },
        },
        bounceGentle: {
          '0%, 20%, 53%, 80%, 100%': { transform: 'translate3d(0,0,0)' },
          '40%, 43%': { transform: 'translate3d(0,-15px,0)' },
          '70%': { transform: 'translate3d(0,-7px,0)' },
          '90%': { transform: 'translate3d(0,-2px,0)' },
        }
      }
    },
  },
  plugins: [],
}