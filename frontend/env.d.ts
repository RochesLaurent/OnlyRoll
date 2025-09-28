/// <reference types="vite/client" />

interface ImportMetaEnv {
  readonly VITE_API_BASE_URL: string
  readonly VITE_APP_NAME: string
  readonly VITE_APP_VERSION: string
  readonly VITE_APP_ENV: string
  readonly VITE_TERMS_URL: string
  readonly VITE_PRIVACY_URL: string
  readonly VITE_SUPPORT_EMAIL: string
  // Ajoute d'autres variables d'environnement ici
}

interface ImportMeta {
  readonly env: ImportMetaEnv
}