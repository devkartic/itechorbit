import { test, expect } from '@playwright/test';

test('portfolio service follows shared navigation and starts a relevant inquiry', async ({ page }, testInfo) => {
  await page.goto('/');
  const mobile = testInfo.project.name === 'mobile';
  if (mobile) await page.getByRole('button', { name: 'Open navigation', exact: true }).click();
  const navigation = page.getByRole('navigation', { name: mobile ? 'Mobile navigation' : 'Main navigation', exact: true });
  await navigation.getByRole('link', { name: 'Portfolio', exact: true }).click();
  await expect(page).toHaveURL(/\/portfolio_service$/);
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Your expertise. Professionally presented.');
  await expect(page.locator('link[rel="canonical"]')).toHaveAttribute('href', 'http://127.0.0.1:8010/portfolio_service');
  if (mobile) {
    await expect(navigation).toBeHidden();
    await page.getByRole('button', { name: 'Open navigation', exact: true }).click();
  }
  await expect(navigation.getByRole('link', { name: 'Portfolio', exact: true })).toHaveAttribute('aria-current', 'page');
  if (mobile) await page.keyboard.press('Escape');
  for (const name of ['Studio', 'Executive', 'Blueprint', 'Editorial', 'Spotlight']) {
    await expect(page.getByRole('heading', { name, exact: true })).toBeVisible();
  }
  if (!mobile) {
    const originalViewport = page.viewportSize()!;
    for (const width of [320, 390, 768, 1024, 1440]) {
      await page.setViewportSize({ width, height: 960 });
      expect(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth)).toBe(false);
      const headingLines = await page.locator('#portfolio-title > span').evaluateAll(elements => elements.map(element => {
        const style = getComputedStyle(element);
        return element.getBoundingClientRect().height / parseFloat(style.lineHeight);
      }));
      if (width >= 1024) {
        expect(headingLines).toHaveLength(2);
        headingLines.forEach(lines => expect(lines).toBeCloseTo(1, 1));
        const copy = (await page.locator('.ps-hero-copy').boundingBox())!;
        const preview = (await page.locator('.portfolio-service-preview').boundingBox())!;
        expect(preview.x).toBeGreaterThanOrEqual(copy.x + copy.width);
      }
      if (width !== 320) await page.screenshot({ path: testInfo.outputPath(`portfolio-service-${width}.png`), fullPage: true, animations: 'disabled' });
    }
    await page.setViewportSize(originalViewport);
  }
  await page.screenshot({ path: testInfo.outputPath('portfolio-service.png'), fullPage: true, animations: 'disabled' });
  expect(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth)).toBe(false);
  const sampleLink = page.getByRole('link', { name: 'View sample portfolio (opens in a new tab)', exact: true });
  await expect(sampleLink).toHaveAttribute('href', '/portfolios');
  await expect(sampleLink).toHaveAttribute('target', '_blank');
  await expect(sampleLink).toHaveAttribute('rel', 'noopener noreferrer');
  const popupPromise = page.waitForEvent('popup');
  await sampleLink.click();
  const sample = await popupPromise;
  await expect(sample).toHaveURL(/\/portfolios$/);
  await expect(sample.getByRole('heading', { level: 1 })).toContainText('Test Engineer');
  await sample.close();
  await expect(page).toHaveURL(/\/portfolio_service$/);
  await page.getByRole('link', { name: 'Request a portfolio', exact: true }).first().click();
  await expect(page).toHaveURL(/\/contact\/info\?subject=Professional%20portfolio%20service$/);
  await expect(page.locator('#inquiry-subject')).toHaveValue('Professional portfolio service');
  const historicalImage = await page.request.get('/portfolio/img/portfolio/portfolio-1.jpg');
  expect(historicalImage.ok()).toBe(true);
  expect(historicalImage.headers()['content-type']).toContain('image/jpeg');
});

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
