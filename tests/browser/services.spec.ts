import { test, expect } from '@playwright/test';

test('growth services are discoverable and lead to a scoped inquiry', async ({ page }) => {
  const offers = [
    ['Website Speed Optimization', 'website-speed-optimization'],
    ['AI-Generated Code Review & Fixing', 'ai-generated-code-review-fixing'],
    ['Technical SEO Optimization', 'technical-seo-optimization'],
    ['Google Analytics & Tag Manager', 'google-analytics-tag-manager'],
    ['Website Security Audit', 'website-security-audit'],
  ];
  await page.goto('/service/info');
  expect((await page.locator('.service-card h3').allTextContents()).slice(0, 5)).toEqual(offers.map(([title]) => title));
  for (const [title, slug] of offers) {
    await page.goto(`/services/${slug}`);
    await expect(page.getByRole('heading', { level: 1 })).toHaveText(title);
    await expect(page.getByRole('heading', { name: 'What is included', exact: true })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'How we work', exact: true })).toBeVisible();
    await page.getByRole('link', { name: 'Discuss this service', exact: true }).click();
    await expect(page.getByLabel('What can we help with?')).toHaveValue(title);
  }
});

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
    const renderedSchemas = async (html: string) => page.evaluate(markup => [...new DOMParser().parseFromString(markup, 'text/html').querySelectorAll('script[type="application/ld+json"]')].map(node => JSON.parse(node.textContent ?? '{}')), html);
    const pricedSchemas = await renderedSchemas(await (await page.request.get(`/products/${slug}`)).text());
    expect(pricedSchemas.find(schema => schema['@type'] === 'Product')).toMatchObject({ offers: { '@type': 'Offer', price: '1250.50', priceCurrency: 'USD' } });
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
    await page.getByLabel('Package price (optional)').fill('');
    await page.getByLabel('Currency (3 uppercase letters, e.g. USD)').fill('');
    await page.getByRole('button', { name: 'Save changes', exact: true }).click();
    await expect(page).toHaveURL(/\/product-info$/);
    // Verify quote-based markup after editing and navigating through Inertia.
    await page.goto(`/products/${slug}`);
    await expect(page.getByText('USD 1250.50', { exact: true })).toHaveCount(0);
    const quoteSchemas = await renderedSchemas(await (await page.request.get(`/products/${slug}`)).text());
    expect(quoteSchemas.some(schema => schema['@type'] === 'Product')).toBeFalsy();
    expect(quoteSchemas.find(schema => schema['@type'] === 'WebPage')).toMatchObject({ name: 'Launch package' });
    await page.getByRole('navigation', { name: 'Breadcrumb' }).getByRole('link', { name: 'Products', exact: true }).click();
    await page.getByRole('link', { name: /Launch package/ }).click();
    await expect.poll(async () => page.locator('head script[type="application/ld+json"]').evaluateAll(nodes => nodes.map(node => JSON.parse(node.textContent ?? '{}')['@type']))).toEqual(['WebPage', 'BreadcrumbList']);
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
