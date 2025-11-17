import { test, expect } from '@playwright/test';

test('visits the app root url', async ({ page }) => {
  await page.goto('/');
 
  await expect(page.locator('#app')).toBeAttached();
 
  // Attendre que le h1 soit visible avant de vérifier son contenu
  const h1 = page.locator('h1').first();
  await expect(h1).toBeVisible();
  await expect(h1).toHaveText('OnlyRoll');
});