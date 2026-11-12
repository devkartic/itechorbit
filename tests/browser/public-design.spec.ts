import { test, expect } from '@playwright/test';

test('main website pages remain readable and consistent across viewport sizes', async ({ page }, testInfo) => {
  test.skip(testInfo.project.name !== 'desktop', 'This audit explicitly covers mobile, tablet and desktop viewports.');
  test.setTimeout(120000);
  const paths = ['/', '/service/info', '/services/laravel-development', '/products', '/products/design-audit-product', '/portfolio_service', '/work', '/work/1', '/blogs', '/blogs/1', '/blogs/13', '/about/info', '/contact/info', '/privacy-n-policy', '/blogs?key=no-matching-design-fixture', '/work?category=NoMatchingDesignFixture'];
  const errors: string[] = [];
  page.on('pageerror', error => errors.push(error.message));
  for (const width of [320, 390, 768, 1024, 1440]) {
    await page.setViewportSize({ width, height: 1000 });
    for (const path of paths) {
      const response = await page.goto(path);
      expect(response?.status(), path).toBe(200);
      const main = page.locator('main.public-site');
      await expect(main).toBeVisible();
      await expect(main.getByRole('heading', { level: 1 })).toHaveCount(1);
      expect(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth), `${path} at ${width}px`).toBe(false);
      const styles = await main.evaluate(element => {
        const style = getComputedStyle(element);
        return { font: style.fontFamily, size: style.fontSize, background: style.backgroundColor };
      });
      expect(styles.font).toContain('Inter');
      expect(styles.size).toBe('16px');
      expect(styles.background).toBe('rgb(252, 251, 248)');
      const title = main.locator('.page-intro h1,.contact-page-intro h1,.public-article-title');
      if (await title.count()) {
        const size = await title.evaluate(element => parseFloat(getComputedStyle(element).fontSize));
        expect(size).toBeGreaterThanOrEqual(32);
        expect(size).toBeLessThanOrEqual(40);
        await expect(title).toHaveCSS('color', 'rgb(24, 21, 44)');
      }
      if ((width === 390 || width === 1440) && !path.includes('?')) {
        await page.screenshot({ path: testInfo.outputPath(`${path === '/' ? 'home' : path.slice(1).replaceAll('/', '-')}-${width}.png`), fullPage: true, animations: 'disabled' });
      }
    }
  }
  expect(errors).toEqual([]);
});

test('public typography supports keyboard focus and keeps reading content within its column', async ({ page }) => {
  await page.goto('/contact/info');
  await page.getByLabel('Your name', { exact: true }).focus();
  await expect(page.getByLabel('Your name', { exact: true })).toBeFocused();
  await expect(page.getByLabel('Your name', { exact: true })).toHaveCSS('font-size', '16px');
  await expect(page.getByLabel('Your name', { exact: true })).toHaveCSS('outline-style', 'solid');
  await page.goto('/blogs/1');
  const prose = (await page.locator('.public-article .prose-content').boundingBox())!;
  expect(prose.width).toBeLessThanOrEqual(800);
  const code = (await page.locator('.prose-content pre').boundingBox())!;
  expect(code.width).toBeLessThanOrEqual(prose.width);
  await page.goto('/portfolio_service');
  for (const selector of ['.ps-features-heading', '.ps-templates-heading', '.ps-process-panel .ps-section-heading']) {
    const heading = (await page.locator(`${selector} h2`).boundingBox())!;
    const description = (await page.locator(`${selector} > p`).boundingBox())!;
    expect(description.x).toBeCloseTo(heading.x, 0);
    expect(description.y).toBeGreaterThan(heading.y + heading.height);
  }
  await page.emulateMedia({ reducedMotion: 'reduce' });
  await expect(page.getByRole('link', { name: 'Create your portfolio', exact: true }).first()).toHaveCSS('transition-property', 'none');
});

test('website design scope does not apply to authentication and personal portfolios', async ({ page }) => {
  for (const path of ['/login', '/register', '/portfolios/TESTPROFILE']) {
    await page.goto(path);
    await expect(page.locator('.public-site')).toHaveCount(0);
    await expect(page.getByRole('heading', { level: 1 })).toBeVisible();
  }
});
