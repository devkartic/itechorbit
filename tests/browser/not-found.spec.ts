import { test, expect } from '@playwright/test';

test('404 follows the website theme and provides working recovery navigation', async ({ page }, testInfo) => {
  await page.emulateMedia({ reducedMotion: 'reduce' });
  for (const url of ['/not-a-real-page', '/not-a-real-directory/nested/', '/storage/missing-photo.png', '/build/assets/missing.js']) {
    const response = await page.goto(url);
    expect(response?.status()).toBe(404);
    await expect(page.getByRole('heading', { name: 'Page not found. Possibilities ahead.' })).toBeVisible();
    await expect(page.getByRole('navigation', { name: 'Suggested destinations' })).toBeVisible();
    await expect(page.locator('meta[name="robots"]')).toHaveAttribute('content', 'noindex,follow');
    await expect(page.locator('.site-header .brand')).toBeVisible();
    await expect(page.locator('.site-footer')).toBeVisible();
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBeTruthy();
  }
  await page.screenshot({ path: testInfo.outputPath('themed-404.png'), fullPage: true });
  await page.keyboard.press('Tab');
  await expect(page.getByRole('link', { name: 'Skip to content', exact: true })).toBeFocused();
  await page.getByRole('link', { name: 'Back to home', exact: true }).focus();
  await page.keyboard.press('Enter');
  await expect(page).toHaveURL('/');
  await expect(page.locator('.hero-copy h1')).toBeVisible();
  await page.goto('/not-a-real-page');
  await page.getByRole('link', { name: 'Contact us', exact: true }).click();
  await expect(page).toHaveURL('/contact/info');
  await expect(page.getByRole('heading', { level: 1 })).toBeVisible();
});

test('404 is styled and usable before JavaScript hydration', async ({ browser, baseURL }, testInfo) => {
  const context = await browser.newContext({ baseURL, javaScriptEnabled: false, viewport: testInfo.project.use.viewport });
  try {
    const page = await context.newPage();
    const response = await page.goto('/not-a-real-page');
    expect(response?.status()).toBe(404);
    await expect(page.getByRole('heading', { level: 1 })).toBeVisible();
    await expect(page.locator('.error-page-grid')).toHaveCSS('display', 'grid');
    await expect(page.locator('.error-page-art')).toBeVisible();
    await page.getByRole('link', { name: 'Back to home', exact: true }).click();
    await expect(page).toHaveURL('/');
  } finally {
    await context.close();
  }
});
