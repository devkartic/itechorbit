import { test, expect } from '@playwright/test';

test('header compacts on scroll without shifting the logo and menus fit all screens', async ({ page }, testInfo) => {
  for (const width of [320, 390, 768, 1024, 1280, 1440]) {
    await page.setViewportSize({ width, height: 960 });
    await page.goto('/');
    const header = page.getByRole('banner');
    const initial = await header.boundingBox();
    const logoX = (await header.locator('.brand-logo').boundingBox())!.x;
    expect(initial?.height).toBe(width < 1024 ? 72 : 80);
    const layout = await header.evaluate(element => {
      const brand = element.querySelector('.brand')!.getBoundingClientRect();
      const actions = element.querySelector('.header-actions')!.getBoundingClientRect();
      const nav = element.querySelector('.header-navigation')!;
      const rect = nav.getBoundingClientRect();
      return { overflow: document.documentElement.scrollWidth > innerWidth, overlap: brand.right > actions.left,
        navOverlap: getComputedStyle(nav).display !== 'none' && (brand.right > rect.left || rect.right > actions.left) };
    });
    expect(layout).toEqual({ overflow: false, overlap: false, navOverlap: false });
    if (width === (testInfo.project.name === 'mobile' ? 390 : 1440)) {
      await page.screenshot({ path: testInfo.outputPath('modern-homepage.png'), animations: 'disabled' });
    }
    await page.evaluate(() => window.scrollTo({ top: 1200, behavior: 'instant' }));
    await expect(header).toHaveClass(/is-scrolled/);
    await expect(header.locator('.header-inner')).toHaveCSS('height', width < 1024 ? '64px' : '68px');
    const scrolled = await header.boundingBox();
    expect(scrolled?.height).toBe(width < 1024 ? 64 : 68);
    expect(scrolled?.y).toBe(0);
    expect((await header.locator('.brand-logo').boundingBox())!.x).toBe(logoX);
    if (width < 1024) {
      const trigger = page.getByRole('button', { name: 'Open navigation', exact: true });
      await trigger.click();
      const menu = page.getByRole('navigation', { name: 'Mobile navigation' });
      await expect(menu).toBeVisible();
      await page.keyboard.press('Escape');
      await expect(menu).toBeHidden();
      await expect(trigger).toBeFocused();
      await trigger.click();
      await menu.getByRole('link', { name: 'Services', exact: true }).click();
      await expect(page).toHaveURL(/\/service\/info$/);
      await expect(menu).toBeHidden();
    }
    await page.evaluate(() => window.scrollTo({ top: 0, behavior: 'instant' }));
    await expect(header).not.toHaveClass(/is-scrolled/);
    await expect(header.locator('.header-inner')).toHaveCSS('height', width < 1024 ? '72px' : '80px');
  }
});

test('hero motion respects preferences and architecture remains keyboard accessible', async ({ page }) => {
  await page.emulateMedia({ reducedMotion: 'no-preference' });
  await page.goto('/');
  await expect(page.locator('.hero-ambient > span').first()).toHaveCSS('animation-duration', '18s');
  await page.emulateMedia({ reducedMotion: 'reduce' });
  await expect(page.locator('.site-header .header-inner')).toHaveCSS('transition-property', 'none');
  await page.evaluate(() => window.scrollTo({ top: 1200, behavior: 'instant' }));
  await expect(page.getByRole('banner')).toHaveClass(/is-scrolled/);
  await expect(page.locator('.hero-ambient > span').first()).toHaveCSS('animation-name', 'none');
  await expect(page.locator('.hero-copy h1')).toHaveCSS('animation-name', 'none');
  await expect(page.getByRole('heading', { level: 1 })).toBeVisible();
  const application = page.getByRole('tab', { name: 'Application', exact: true });
  await application.focus();
  await page.keyboard.press('ArrowRight');
  await expect(page.getByRole('tab', { name: 'Data', exact: true })).toBeFocused();
  await expect(page.getByRole('tabpanel')).toContainText('The right data. At the right time.');
});

test('public and authentication links navigate through Inertia without replacing the document', async ({ page }) => {
  await page.goto('/');
  await page.evaluate(() => Reflect.set(window, '__inertiaNavigationCheck', 'same-document'));
  const signInResponse = page.waitForResponse(response => response.url().endsWith('/login') && response.request().headers()['x-inertia'] === 'true');
  await page.getByRole('banner').getByRole('link', { name: 'Sign in', exact: true }).click();
  expect((await signInResponse).headers()['content-type']).toContain('application/json');
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Welcome back.');
  await page.getByRole('link', { name: 'Create an account', exact: true }).click();
  await expect(page).toHaveURL(/\/register$/);
  await page.goBack();
  await expect(page).toHaveURL(/\/login$/);
  await page.goForward();
  await expect(page).toHaveURL(/\/register$/);
  await page.getByRole('banner').getByRole('link', { name: 'Back to website', exact: true }).click();
  await expect(page).toHaveURL(/\/$/);
  expect(await page.evaluate(() => Reflect.get(window, '__inertiaNavigationCheck'))).toBe('same-document');
});
