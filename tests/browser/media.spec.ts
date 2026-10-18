import { test, expect } from '@playwright/test';

test('failed saved previews and portfolio images provide usable fallbacks', async ({ page }, testInfo) => {
  await page.emulateMedia({ reducedMotion: 'reduce' });
  await page.goto('/login');
  await page.getByLabel('Email address').fill('admin@example.test');
  await page.getByLabel('Password', { exact: true }).fill('Browser-test-password-42');
  await page.getByRole('button', { name: 'Sign in', exact: true }).click();
  await expect(page).toHaveURL(/\/home$/);
  const png = 'tests/browser/fixtures/project-screenshot.png';
  await page.goto('/basics/1/edit');
  for (const [label, value] of [['Mobile *', '01234567890'], ['Address *', 'Synthetic test address'], ['Gender *', 'Other'], ['Marital status *', 'Single']]) await page.getByLabel(label, { exact: true }).fill(value);
  await page.getByLabel('Banner image', { exact: true }).setInputFiles(png);
  await page.getByLabel('Profile photo', { exact: true }).setInputFiles(png);
  await page.getByRole('button', { name: 'Save changes', exact: true }).click();
  await expect(page).toHaveURL(/\/basics$/);
  await page.goto('/projects/1/edit');
  await page.getByLabel('Project screenshot', { exact: true }).setInputFiles(png);
  await page.getByRole('button', { name: 'Save changes', exact: true }).click();
  await expect(page).toHaveURL(/\/projects$/);
  try {
    await page.route('**/workspace/media/**', route => route.fulfill({ status: 404, body: '' }));
    await page.goto('/basics/1/edit');
    await expect(page.getByText('This saved image could not be loaded. Upload a replacement.', { exact: true })).toHaveCount(2);
    await expect(page.locator('.workspace-existing-media img')).toHaveCount(0);
    await page.screenshot({ path: testInfo.outputPath('unavailable-admin-previews.png'), fullPage: true });
    await page.unroute('**/workspace/media/**');
    await page.route('**/uploads/**', route => route.fulfill({ status: 404, body: '' }));
    await page.goto('/workspace/portfolio-templates');
    for (const name of ['Executive', 'Blueprint', 'Editorial', 'Spotlight', 'Studio']) {
      await page.locator('.template-choice').getByText(name, { exact: true }).click();
      const preview = page.getByRole('region', { name: `${name} portfolio preview` });
      await expect(preview.locator('.pf-banner')).toHaveCount(0);
      await expect(preview.locator('.pf-portrait')).toHaveCount(0);
      await expect(preview.locator('.pf-monogram')).toBeVisible();
      await expect(preview.locator('.pf-project-image img')).toHaveCount(0);
      expect(await preview.evaluate(node => node.scrollWidth <= node.clientWidth + 1)).toBeTruthy();
      await preview.screenshot({ path: testInfo.outputPath(`missing-media-${name.toLowerCase()}.png`) });
    }
  } finally {
    await page.unroute('**/uploads/**');
    const token = await page.locator('meta[name="csrf-token"]').getAttribute('content');
    for (const [path, id, fields] of [['/basics-file-delete', 1, ['banner_image_path', 'profile_image_path']], ['/project-file-delete', 1, ['image_path']]] as const) {
      for (const field of fields) {
        const result = await page.request.post(path, { headers: { 'X-CSRF-TOKEN': token ?? '', Accept: 'application/json' }, data: { id, field_name: field } });
        expect(result.ok()).toBeTruthy();
      }
    }
  }
});
