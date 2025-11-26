/**
 * Tests unitaires pour le composable usePagination
 *
 * @covers src/composables/usePagination.ts
 */

import { describe, it, expect, beforeEach, vi } from 'vitest'
import { ref, type Ref } from 'vue'
import { usePagination, createPagination } from '@/composables/usePagination'
import type { PaginationMeta } from '@/types/game'

// Mock window.scrollTo
global.scrollTo = vi.fn()

describe('usePagination', () => {
  let paginationMeta: Ref<PaginationMeta>

  beforeEach(() => {
    paginationMeta = ref<PaginationMeta>({
      page: 1,
      limit: 12,
      total: 100,
      totalPages: 9,
    })
    vi.clearAllMocks()
  })

  // ========== INITIAL STATE ==========

  it('should initialize with correct pagination data', () => {
    const pagination = usePagination(paginationMeta)

    expect(pagination.canGoNext.value).toBe(true)
    expect(pagination.canGoPrev.value).toBe(false)
    expect(pagination.hasResults.value).toBe(true)
    expect(pagination.hasMultiplePages.value).toBe(true)
  })

  // ========== GO TO PAGE ==========

  it('should go to specific page', async () => {
    const onPageChange = vi.fn()
    const pagination = usePagination(paginationMeta, { onPageChange })

    await pagination.goToPage(3)

    expect(onPageChange).toHaveBeenCalledWith(3)
    expect(window.scrollTo).toHaveBeenCalledWith({ top: 0, behavior: 'smooth' })
  })

  it('should not go to page below 1', async () => {
    const onPageChange = vi.fn()
    const pagination = usePagination(paginationMeta, { onPageChange })

    await pagination.goToPage(0)

    expect(onPageChange).not.toHaveBeenCalled()
  })

  it('should not go to page above total pages', async () => {
    const onPageChange = vi.fn()
    const pagination = usePagination(paginationMeta, { onPageChange })

    await pagination.goToPage(10)

    expect(onPageChange).not.toHaveBeenCalled()
  })

  it('should not scroll if scrollToTop is false', async () => {
    const pagination = usePagination(paginationMeta, { scrollToTop: false })

    await pagination.goToPage(2)

    expect(window.scrollTo).not.toHaveBeenCalled()
  })

  it('should use custom scroll behavior', async () => {
    const pagination = usePagination(paginationMeta, {
      scrollBehavior: 'auto',
    })

    await pagination.goToPage(2)

    expect(window.scrollTo).toHaveBeenCalledWith({ top: 0, behavior: 'auto' })
  })

  // ========== NEXT PAGE ==========

  it('should go to next page', async () => {
    paginationMeta.value.page = 5
    const onPageChange = vi.fn()
    const pagination = usePagination(paginationMeta, { onPageChange })

    await pagination.nextPage()

    expect(onPageChange).toHaveBeenCalledWith(6)
  })

  it('should not go to next page if on last page', async () => {
    paginationMeta.value.page = 9
    const onPageChange = vi.fn()
    const pagination = usePagination(paginationMeta, { onPageChange })

    await pagination.nextPage()

    expect(onPageChange).not.toHaveBeenCalled()
  })

  // ========== PREV PAGE ==========

  it('should go to previous page', async () => {
    paginationMeta.value.page = 5
    const onPageChange = vi.fn()
    const pagination = usePagination(paginationMeta, { onPageChange })

    await pagination.prevPage()

    expect(onPageChange).toHaveBeenCalledWith(4)
  })

  it('should not go to previous page if on first page', async () => {
    paginationMeta.value.page = 1
    const onPageChange = vi.fn()
    const pagination = usePagination(paginationMeta, { onPageChange })

    await pagination.prevPage()

    expect(onPageChange).not.toHaveBeenCalled()
  })

  // ========== FIRST AND LAST PAGE ==========

  it('should go to first page', async () => {
    paginationMeta.value.page = 5
    const onPageChange = vi.fn()
    const pagination = usePagination(paginationMeta, { onPageChange })

    await pagination.goToFirstPage()

    expect(onPageChange).toHaveBeenCalledWith(1)
  })

  it('should go to last page', async () => {
    paginationMeta.value.page = 1
    const onPageChange = vi.fn()
    const pagination = usePagination(paginationMeta, { onPageChange })

    await pagination.goToLastPage()

    expect(onPageChange).toHaveBeenCalledWith(9)
  })

  // ========== CAN GO NEXT/PREV ==========

  it('should compute canGoNext correctly', () => {
    const pagination = usePagination(paginationMeta)

    paginationMeta.value.page = 1
    expect(pagination.canGoNext.value).toBe(true)

    paginationMeta.value.page = 9
    expect(pagination.canGoNext.value).toBe(false)
  })

  it('should compute canGoPrev correctly', () => {
    const pagination = usePagination(paginationMeta)

    paginationMeta.value.page = 1
    expect(pagination.canGoPrev.value).toBe(false)

    paginationMeta.value.page = 2
    expect(pagination.canGoPrev.value).toBe(true)
  })

  // ========== PAGINATION RANGE ==========

  it('should show all pages if total pages <= 7', () => {
    paginationMeta.value.totalPages = 5
    const pagination = usePagination(paginationMeta)

    expect(pagination.paginationRange.value).toEqual([1, 2, 3, 4, 5])
  })

  it('should show ellipses for large page counts at beginning', () => {
    paginationMeta.value.page = 1
    paginationMeta.value.totalPages = 20
    const pagination = usePagination(paginationMeta)

    const range = pagination.paginationRange.value

    expect(range[0]).toBe(1)
    expect(range[range.length - 1]).toBe(20)
    expect(range).toContain('...')
  })

  it('should show ellipses for large page counts in middle', () => {
    paginationMeta.value.page = 10
    paginationMeta.value.totalPages = 20
    const pagination = usePagination(paginationMeta)

    const range = pagination.paginationRange.value

    expect(range[0]).toBe(1)
    expect(range[range.length - 1]).toBe(20)
    expect(range).toContain(10)
    expect(range.filter((p) => p === '...')).toHaveLength(2)
  })

  it('should show ellipses for large page counts at end', () => {
    paginationMeta.value.page = 20
    paginationMeta.value.totalPages = 20
    const pagination = usePagination(paginationMeta)

    const range = pagination.paginationRange.value

    expect(range[0]).toBe(1)
    expect(range[range.length - 1]).toBe(20)
    expect(range).toContain('...')
  })

  it('should show pages around current page with delta', () => {
    paginationMeta.value.page = 10
    paginationMeta.value.totalPages = 20
    const pagination = usePagination(paginationMeta, { delta: 2 })

    const range = pagination.paginationRange.value

    expect(range).toContain(8) // current - 2
    expect(range).toContain(9) // current - 1
    expect(range).toContain(10) // current
    expect(range).toContain(11) // current + 1
    expect(range).toContain(12) // current + 2
  })

  it('should handle single page', () => {
    paginationMeta.value.totalPages = 1
    const pagination = usePagination(paginationMeta)

    expect(pagination.paginationRange.value).toEqual([1])
  })

  // ========== START/END INDEX ==========

  it('should compute start index correctly', () => {
    const pagination = usePagination(paginationMeta)

    paginationMeta.value.page = 1
    expect(pagination.startIndex.value).toBe(1)

    paginationMeta.value.page = 2
    expect(pagination.startIndex.value).toBe(13)

    paginationMeta.value.page = 3
    expect(pagination.startIndex.value).toBe(25)
  })

  it('should compute end index correctly', () => {
    const pagination = usePagination(paginationMeta)

    paginationMeta.value.page = 1
    expect(pagination.endIndex.value).toBe(12)

    paginationMeta.value.page = 2
    expect(pagination.endIndex.value).toBe(24)

    paginationMeta.value.page = 9
    expect(pagination.endIndex.value).toBe(100)
  })

  it('should not exceed total for end index', () => {
    paginationMeta.value.page = 9
    paginationMeta.value.total = 100
    const pagination = usePagination(paginationMeta)

    expect(pagination.endIndex.value).toBe(100)
  })

  // ========== HAS RESULTS ==========

  it('should compute hasResults correctly', () => {
    const pagination = usePagination(paginationMeta)

    paginationMeta.value.total = 100
    expect(pagination.hasResults.value).toBe(true)

    paginationMeta.value.total = 0
    expect(pagination.hasResults.value).toBe(false)
  })

  // ========== HAS MULTIPLE PAGES ==========

  it('should compute hasMultiplePages correctly', () => {
    const pagination = usePagination(paginationMeta)

    paginationMeta.value.totalPages = 1
    expect(pagination.hasMultiplePages.value).toBe(false)

    paginationMeta.value.totalPages = 2
    expect(pagination.hasMultiplePages.value).toBe(true)
  })
})

describe('createPagination', () => {
  it('should create pagination with default values', () => {
    const pagination = createPagination()

    expect(pagination).toEqual({
      page: 1,
      limit: 12,
      total: 0,
      totalPages: 0,
    })
  })

  it('should create pagination with custom values', () => {
    const pagination = createPagination(3, 20, 100)

    expect(pagination).toEqual({
      page: 3,
      limit: 20,
      total: 100,
      totalPages: 5,
    })
  })

  it('should calculate total pages correctly', () => {
    expect(createPagination(1, 10, 100).totalPages).toBe(10)
    expect(createPagination(1, 10, 95).totalPages).toBe(10)
    expect(createPagination(1, 10, 91).totalPages).toBe(10)
    expect(createPagination(1, 10, 90).totalPages).toBe(9)
  })

  it('should handle edge cases', () => {
    expect(createPagination(1, 10, 0).totalPages).toBe(0)
    expect(createPagination(1, 10, 5).totalPages).toBe(1)
    expect(createPagination(1, 1, 100).totalPages).toBe(100)
  })
})
