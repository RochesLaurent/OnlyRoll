import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import './styles/tailwind.css'
import { useAuthStore } from './stores/auth'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)

const authStore = useAuthStore()

authStore
  .initialize()
  .catch((error) => {
    console.error("Erreur lors de l'initialisation:", error)
  })
  .finally(() => {
    app.mount('#app')
  })
