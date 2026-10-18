import { test, expect } from '@playwright/test';

test('website products support independent CRUD with responsive public pages', async ({ page }, testInfo) => {
  const errors: string[] = [];
  page.on('pageerror', error => errors.push(error.message));
  await page.goto('/login');
  await page.getByLabel('Email address').fill('admin@example.test');
  await page.getByLabel('Password', { exact: true }).fill('Browser-test-password-42');
  await page.getByRole('button', { name: 'Sign in', exact: true }).click();
  await expect(page).toHaveURL(/\/home$/);
  await page.goto('/product-info/create');
  await page.getByLabel('Title *', { exact: true }).fill('Launch package');
  await page.getByLabel('Card title (optional)').fill('Launch package');
  const slug = `launch-package-${testInfo.project.name}`;
  await page.getByLabel('URL slug *', { exact: true }).fill(slug);
  await page.getByLabel('Summary *').fill('A defined package to launch your application.');
  await page.getByRole('textbox', { name: 'Description *', exact: true }).fill('Application implementation and documented handover.');
  await page.getByLabel('Deliverables (one per line)').fill('Application setup\nDeployment guide');
  await page.getByLabel('Package price (optional)').fill('1250.50');
  await page.getByLabel('Currency (3 uppercase letters, e.g. USD)').fill('USD');
  await page.getByLabel('Estimated delivery time').fill('Two weeks');
  await page.getByLabel('Product image', { exact: true }).setInputFiles('tests/browser/fixtures/project-screenshot.png');
  await page.getByRole('button', { name: 'Save changes', exact: true }).click();
  await expect(page).toHaveURL(/\/product-info$/);
  const row = page.getByRole('row').filter({ hasText: 'Launch package' });
  const editUrl = await row.getByRole('link', { name: /edit/i }).getAttribute('href');
  expect(editUrl).toBeTruthy();
  try {
    await page.goto('/products');
    await expect(page.getByRole('heading', { name: 'Helpful products. Built for your website.', exact: true })).toBeVisible();
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBeTruthy();
    await page.screenshot({ path: testInfo.outputPath('services-listing.png'), fullPage: true });
    await page.getByRole('link', { name: /Launch package/ }).click();
    await expect(page).toHaveURL(new RegExp(`/products/${slug}$`));
    await expect(page.getByRole('heading', { level: 1 })).toHaveText('Launch package');
    await expect(page.getByText('USD 1250.50', { exact: true })).toBeVisible();
    await expect(page.getByText('Estimated delivery: Two weeks')).toBeVisible();
    await expect(page.getByRole('img', { name: 'Launch package', exact: true })).toBeVisible();
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBeTruthy();
    await page.screenshot({ path: testInfo.outputPath('product-service.png'), fullPage: true });
    await page.route('**/uploads/website-products/**', route => route.fulfill({ status: 404, body: '' }));
    await page.reload();
    await expect(page.getByRole('img', { name: 'Launch package', exact: true })).toHaveCount(0);
    await page.goto('/products');
    const productCard = page.locator('.product-card').filter({ has: page.getByRole('heading', { name: 'Launch package', exact: true }) });
    await expect(productCard.locator('img')).toHaveCount(0);
    await expect(productCard.locator('.product-card-emblem')).toBeVisible();
    await page.unroute('**/uploads/website-products/**');
    await page.goto(`/products/${slug}`);
    const oldUrl = await page.request.get(`/services/${slug}`, { maxRedirects: 0 });
    expect(oldUrl.status()).toBe(301);
    expect(oldUrl.headers().location).toMatch(new RegExp(`/products/${slug}$`));
    await page.getByRole('link', { name: 'Request a demo or quote' }).click();
    await expect(page.getByLabel('What can we help with?', { exact: true })).toHaveValue('Launch package');
    await page.goto(editUrl!);
    await page.getByRole('switch', { name: 'Published / active' }).uncheck();
    await page.getByRole('button', { name: 'Save changes', exact: true }).click();
    await expect(page).toHaveURL(/\/product-info$/);
    expect((await page.request.get(`/products/${slug}`)).status()).toBe(404);
    expect(errors).toEqual([]);
  } finally {
    const token = await page.locator('meta[name="csrf-token"]').getAttribute('content');
    const result = await page.request.delete(editUrl!.replace(/\/edit$/, ''), { headers: { 'X-CSRF-TOKEN': token ?? '', Accept: 'application/json' } });
    expect(result.ok()).toBeTruthy();
  }
});

test('website admin separates product and service fields in light and dark themes', async ({ page }, testInfo) => {
  await page.emulateMedia({ reducedMotion: 'reduce' });
  await page.goto('/login');
  await page.getByLabel('Email address').fill('admin@example.test');
  await page.getByLabel('Password', { exact: true }).fill('Browser-test-password-42');
  await page.getByRole('button', { name: 'Sign in', exact: true }).click();
  await expect(page).toHaveURL(/\/home$/);
  for (const theme of ['light', 'dark']) {
    await page.getByRole('button', { name: theme === 'light' ? 'Light theme' : 'Dark theme', exact: true }).click();
    for (const resource of ['service-info', 'product-info']) {
      for (const action of ['', '/create']) {
        await page.goto(`/${resource}${action}`);
        await expect(page.locator('.workspace')).toHaveAttribute('data-theme', theme);
        await expect(page.getByLabel('Service type *')).toHaveCount(0);
        if (action) await expect(page.getByLabel('Package price (optional)')).toHaveCount(resource === 'product-info' ? 1 : 0);
        expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBeTruthy();
        await page.screenshot({ path: testInfo.outputPath(`${resource}-${action ? 'create' : 'list'}-${theme}.png`), fullPage: true });
      }
    }
  }
  await page.goto('/services');
  await expect(page).toHaveURL(/\/services$/);
});
