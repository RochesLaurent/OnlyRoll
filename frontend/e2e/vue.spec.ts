import { test, expect } from '@playwright/test';

test('visits the app root url', async ({ page }) => {
  await page.goto('/');
  
  // Attendre que l'app Vue soit montée en attendant un élément stable
  await page.waitForSelector('#app', { state: 'attached' });
  
  // Attendre que le h1 soit visible avant de vérifier son contenu
  const h1 = page.locator('h1').first();
  await expect(h1).toBeVisible();
  await expect(h1).toHaveText('OnlyRoll');
});