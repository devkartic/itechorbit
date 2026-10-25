import { test, expect } from '@playwright/test';

test('public CSS and persistent uploads work before hydration, with cache and on 3G', async ({ page, browser, baseURL }) => {
  test.setTimeout(60000);
  await page.goto('/login');
  await page.getByLabel('Email address').fill('admin@example.test');
  await page.getByLabel('Password', { exact: true }).fill('Browser-test-password-42');
  await page.getByRole('button', { name: 'Sign in', exact: true }).click();
  await expect(page).toHaveURL(/\/home$/);
  await page.goto('/basics/1/edit');
  for (const [label, value] of [['Mobile *', '01234567890'], ['Address *', 'Synthetic test address'], ['Gender *', 'Other'], ['Marital status *', 'Single']]) await page.getByLabel(label, { exact: true }).fill(value);
  const png = 'tests/browser/fixtures/project-screenshot.png';
  await page.getByLabel('Banner image', { exact: true }).setInputFiles(png);
  await page.getByLabel('Profile photo', { exact: true }).setInputFiles(png);
  await page.getByRole('button', { name: 'Save changes', exact: true }).click();
  await expect(page).toHaveURL(/\/basics$/);
  const failures: string[] = [];
  page.on('response', response => {
    if (/\/(build|storage\/uploads)\//.test(response.url()) && response.status() >= 400) failures.push(`${response.status()} ${response.url()}`);
  });
  try {
    const session = await page.context().newCDPSession(page);
    await session.send('Network.enable');
    for (const mode of ['normal', 'cached', '3g']) {
      if (mode === '3g') {
        await session.send('Network.clearBrowserCache');
        await session.send('Network.emulateNetworkConditions', { offline: false, latency: 300, downloadThroughput: 100 * 1024, uploadThroughput: 50 * 1024, connectionType: 'cellular3g' });
      }
      await page.goto('/portfolios/TESTPROFILE');
      const portrait = page.locator('img.pf-portrait');
      await expect(portrait).toBeVisible();
      await expect(portrait).toHaveAttribute('src', /^\/storage\/uploads\//);
      await expect.poll(() => portrait.evaluate(image => (image as HTMLImageElement).naturalWidth)).toBeGreaterThan(0);
      await expect(page.locator('.pf-banner img')).toBeVisible();
      await expect(page.locator('.pf-hero')).toHaveCSS('display', /^(grid|flex)$/);
    }
    await session.detach();
    const staticContext = await browser.newContext({ baseURL, javaScriptEnabled: false });
    try {
      const staticPage = await staticContext.newPage();
      await staticPage.goto('/portfolios/TESTPROFILE');
      await expect(staticPage.locator('.pf-hero')).toHaveCSS('display', /^(grid|flex)$/);
      await expect.poll(() => staticPage.locator('img.pf-portrait').evaluate(image => (image as HTMLImageElement).naturalWidth)).toBeGreaterThan(0);
    } finally {
      await staticContext.close();
    }
    expect(failures).toEqual([]);
  } finally {
    const token = await page.locator('meta[name="csrf-token"]').getAttribute('content');
    for (const field of ['banner_image_path', 'profile_image_path']) {
      const result = await page.request.post('/basics-file-delete', { headers: { 'X-CSRF-TOKEN': token ?? '', Accept: 'application/json' }, data: { id: 1, field_name: field } });
      expect(result.ok()).toBeTruthy();
    }
  }
});
