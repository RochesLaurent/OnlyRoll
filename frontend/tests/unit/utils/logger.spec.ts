/**
 * Tests unitaires pour le logger
 *
 * @covers src/utils/logger.ts
 */

import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest'

// Mock console methods
const mockConsoleLog = vi.spyOn(console, 'log').mockImplementation(() => {})
const mockConsoleError = vi.spyOn(console, 'error').mockImplementation(() => {})
const mockConsoleWarn = vi.spyOn(console, 'warn').mockImplementation(() => {})
const mockConsoleDebug = vi.spyOn(console, 'debug').mockImplementation(() => {})
const mockConsoleInfo = vi.spyOn(console, 'info').mockImplementation(() => {})

// Store original env
const originalEnv = import.meta.env.DEV

describe('logger', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  afterEach(() => {
    // Restore original env
    import.meta.env.DEV = originalEnv
  })

  describe('in development mode', () => {
    beforeEach(async () => {
      // Set DEV mode to true
      import.meta.env.DEV = true

      // Clear module cache to force reimport
      vi.resetModules()
    })

    it('should log messages in development', async () => {
      const { logger } = await import('@/utils/logger')

      logger.log('test message', 'extra data')

      expect(mockConsoleLog).toHaveBeenCalledWith('test message', 'extra data')
    })

    it('should log errors in development', async () => {
      const { logger } = await import('@/utils/logger')

      logger.error('error message', { code: 500 })

      expect(mockConsoleError).toHaveBeenCalledWith('error message', { code: 500 })
    })

    it('should log warnings in development', async () => {
      const { logger } = await import('@/utils/logger')

      logger.warn('warning message')

      expect(mockConsoleWarn).toHaveBeenCalledWith('warning message')
    })

    it('should log debug messages in development', async () => {
      const { logger } = await import('@/utils/logger')

      logger.debug('debug message', 123)

      expect(mockConsoleDebug).toHaveBeenCalledWith('debug message', 123)
    })
  })

  describe('in production mode', () => {
    beforeEach(async () => {
      // Set DEV mode to false
      import.meta.env.DEV = false

      // Clear module cache to force reimport
      vi.resetModules()
    })

    it('should not log messages in production', async () => {
      const { logger } = await import('@/utils/logger')

      logger.log('test message')

      expect(mockConsoleLog).not.toHaveBeenCalled()
    })

    it('should not log errors in production', async () => {
      const { logger } = await import('@/utils/logger')

      logger.error('error message')

      expect(mockConsoleError).not.toHaveBeenCalled()
    })

    it('should not log warnings in production', async () => {
      const { logger } = await import('@/utils/logger')

      logger.warn('warning message')

      expect(mockConsoleWarn).not.toHaveBeenCalled()
    })

    it('should not log debug messages in production', async () => {
      const { logger } = await import('@/utils/logger')

      logger.debug('debug message')

      expect(mockConsoleDebug).not.toHaveBeenCalled()
    })
  })

  describe('info method', () => {
    it('should always log info messages even in production', async () => {
      import.meta.env.DEV = false
      vi.resetModules()

      const { logger } = await import('@/utils/logger')

      logger.info('important info')

      expect(mockConsoleInfo).toHaveBeenCalledWith('important info')
    })

    it('should log info messages in development', async () => {
      import.meta.env.DEV = true
      vi.resetModules()

      const { logger } = await import('@/utils/logger')

      logger.info('info message')

      expect(mockConsoleInfo).toHaveBeenCalledWith('info message')
    })
  })

  describe('criticalLogger', () => {
    it('should always log critical errors even in production', async () => {
      import.meta.env.DEV = false
      vi.resetModules()

      const { criticalLogger } = await import('@/utils/logger')

      criticalLogger.error('critical error', { fatal: true })

      expect(mockConsoleError).toHaveBeenCalledWith('critical error', { fatal: true })
    })

    it('should log critical errors in development', async () => {
      import.meta.env.DEV = true
      vi.resetModules()

      const { criticalLogger } = await import('@/utils/logger')

      criticalLogger.error('critical error')

      expect(mockConsoleError).toHaveBeenCalledWith('critical error')
    })
  })

  describe('multiple arguments', () => {
    it('should handle multiple arguments', async () => {
      import.meta.env.DEV = true
      vi.resetModules()

      const { logger } = await import('@/utils/logger')

      logger.log('message', 123, true, { data: 'test' }, [1, 2, 3])

      expect(mockConsoleLog).toHaveBeenCalledWith(
        'message',
        123,
        true,
        { data: 'test' },
        [1, 2, 3]
      )
    })

    it('should handle objects and arrays', async () => {
      import.meta.env.DEV = true
      vi.resetModules()

      const { logger } = await import('@/utils/logger')
      const obj = { key: 'value' }
      const arr = [1, 2, 3]

      logger.error('Error:', obj, arr)

      expect(mockConsoleError).toHaveBeenCalledWith('Error:', obj, arr)
    })
  })

  describe('edge cases', () => {
    it('should handle empty calls', async () => {
      import.meta.env.DEV = true
      vi.resetModules()

      const { logger } = await import('@/utils/logger')

      logger.log()

      expect(mockConsoleLog).toHaveBeenCalledWith()
    })

    it('should handle null and undefined', async () => {
      import.meta.env.DEV = true
      vi.resetModules()

      const { logger } = await import('@/utils/logger')

      logger.log(null, undefined)

      expect(mockConsoleLog).toHaveBeenCalledWith(null, undefined)
    })

    it('should handle Error objects', async () => {
      import.meta.env.DEV = true
      vi.resetModules()

      const { logger } = await import('@/utils/logger')
      const error = new Error('Test error')

      logger.error('Caught error:', error)

      expect(mockConsoleError).toHaveBeenCalledWith('Caught error:', error)
    })
  })
})
