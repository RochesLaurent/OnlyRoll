import { computed, type Ref } from 'vue'

/**
 * Interface pour les métadonnées de pagination.
 */
export interface PaginationMeta {
  page: number
  limit: number
  total: number
  totalPages: number
}

/**
 * Options pour le composable usePagination.
 */
export interface UsePaginationOptions {
  /**
   * Callback appelé lors d'un changement de page.
   */
  onPageChange?: (page: number) => void | Promise<void>

  /**
   * Nombre de pages à afficher de chaque côté de la page courante.
   * @default 2
   */
  delta?: number

  /**
   * Faire défiler vers le haut lors du changement de page.
   * @default true
   */
  scrollToTop?: boolean

  /**
   * Comportement du défilement.
   * @default 'smooth'
   */
  scrollBehavior?: ScrollBehavior
}

/**
 * Composable pour gérer la pagination.
 *
 * @param paginationMeta - Référence réactive contenant les métadonnées de pagination
 * @param options - Options de configuration
 *
 * @example
 * const pagination = ref({ page: 1, limit: 12, total: 100, totalPages: 9 })
 * const { goToPage, nextPage, prevPage, paginationRange, canGoNext, canGoPrev } = usePagination(
 *   pagination,
 *   { onPageChange: (page) => fetchData(page) }
 * )
 */
export function usePagination(
  paginationMeta: Ref<PaginationMeta>,
  options: UsePaginationOptions = {}
) {
  const { onPageChange, delta = 2, scrollToTop = true, scrollBehavior = 'smooth' } = options

  /**
   * Navigue vers une page spécifique.
   */
  const goToPage = async (page: number): Promise<void> => {
    if (page < 1 || page > paginationMeta.value.totalPages) {
      return
    }

    if (onPageChange) {
      await onPageChange(page)
    }

    if (scrollToTop) {
      window.scrollTo({ top: 0, behavior: scrollBehavior })
    }
  }

  /**
   * Navigue vers la page suivante.
   */
  const nextPage = async (): Promise<void> => {
    if (canGoNext.value) {
      await goToPage(paginationMeta.value.page + 1)
    }
  }

  /**
   * Navigue vers la page précédente.
   */
  const prevPage = async (): Promise<void> => {
    if (canGoPrev.value) {
      await goToPage(paginationMeta.value.page - 1)
    }
  }

  /**
   * Navigue vers la première page.
   */
  const goToFirstPage = async (): Promise<void> => {
    await goToPage(1)
  }

  /**
   * Navigue vers la dernière page.
   */
  const goToLastPage = async (): Promise<void> => {
    await goToPage(paginationMeta.value.totalPages)
  }

  /**
   * Vérifie si on peut aller à la page suivante.
   */
  const canGoNext = computed(() => {
    return paginationMeta.value.page < paginationMeta.value.totalPages
  })

  /**
   * Vérifie si on peut aller à la page précédente.
   */
  const canGoPrev = computed(() => {
    return paginationMeta.value.page > 1
  })

  /**
   * Génère la plage de numéros de pages à afficher avec ellipses.
   *
   * @returns Tableau contenant les numéros de pages et les ellipses ('...')
   *
   * @example
   * // Pour 100 pages, page courante 50
   * // Retourne: [1, '...', 48, 49, 50, 51, 52, '...', 100]
   */
  const paginationRange = computed((): (number | string)[] => {
    const current = paginationMeta.value.page
    const total = paginationMeta.value.totalPages

    if (total <= 7) {
      // Afficher toutes les pages si <= 7
      return Array.from({ length: total }, (_, i) => i + 1)
    }

    // Logique avec ellipses
    const range: (number | string)[] = []

    // Toujours afficher la première page
    range.push(1)

    // Ajouter une ellipse si nécessaire avant les pages du milieu
    if (current > delta + 2) {
      range.push('...')
    }

    // Pages autour de la page courante
    const start = Math.max(2, current - delta)
    const end = Math.min(total - 1, current + delta)

    for (let i = start; i <= end; i++) {
      range.push(i)
    }

    // Ajouter une ellipse si nécessaire après les pages du milieu
    if (current < total - delta - 1) {
      range.push('...')
    }

    // Toujours afficher la dernière page
    if (total > 1) {
      range.push(total)
    }

    return range
  })

  /**
   * Calcule l'index de début pour l'affichage (ex: "Affichage de 1 à 12 sur 100").
   */
  const startIndex = computed(() => {
    return (paginationMeta.value.page - 1) * paginationMeta.value.limit + 1
  })

  /**
   * Calcule l'index de fin pour l'affichage.
   */
  const endIndex = computed(() => {
    const end = paginationMeta.value.page * paginationMeta.value.limit
    return Math.min(end, paginationMeta.value.total)
  })

  /**
   * Vérifie si la pagination a des résultats.
   */
  const hasResults = computed(() => {
    return paginationMeta.value.total > 0
  })

  /**
   * Vérifie si la pagination a plusieurs pages.
   */
  const hasMultiplePages = computed(() => {
    return paginationMeta.value.totalPages > 1
  })

  return {
    // Navigation
    goToPage,
    nextPage,
    prevPage,
    goToFirstPage,
    goToLastPage,

    // État
    canGoNext,
    canGoPrev,
    hasResults,
    hasMultiplePages,

    // Affichage
    paginationRange,
    startIndex,
    endIndex,
  }
}

/**
 * Helper pour créer une pagination initiale.
 */
export function createPagination(page = 1, limit = 12, total = 0): PaginationMeta {
  return {
    page,
    limit,
    total,
    totalPages: Math.ceil(total / limit),
  }
}
