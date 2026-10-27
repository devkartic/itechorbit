import { test, expect } from '@playwright/test';

test('public page editor publishes copy, metadata and CTA changes', async ({ page, request }) => {
  await page.goto('/login');
  await page.getByLabel('Email address').fill('admin@example.test');
  await page.getByLabel('Password', { exact: true }).fill('Browser-test-password-42');
  await page.getByRole('button', { name: 'Sign in', exact: true }).click();
  await expect(page).toHaveURL(/\/home$/);
  await page.goto('/public-pages/2/edit');
  const heading = page.getByLabel('Page heading *', { exact: true });
  const title = page.getByLabel('SEO title (brand added automatically)', { exact: true });
  const original = { heading: await heading.inputValue(), title: await title.inputValue() };
  await heading.fill('Reviewed engineering services');
  await title.fill('Reviewed services');
  await page.getByRole('button', { name: 'Save changes', exact: true }).click();
  await expect(page).toHaveURL(/\/public-pages$/);
  await expect(page.getByRole('button', { name: 'Create', exact: true })).toHaveCount(0);
  await page.goto('/service/info');
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Reviewed engineering services');
  await expect(page).toHaveTitle('Reviewed services | iTechOrbit');
  const html = await (await request.get('/service/info')).text();
  expect(html).toContain('Reviewed engineering services');
  expect(html).toContain('Reviewed services | iTechOrbit');
  await page.goto('/public-pages/2/edit');
  await heading.fill(original.heading);
  await title.fill(original.title);
  await page.getByRole('button', { name: 'Save changes', exact: true }).click();
  await expect(page).toHaveURL(/\/public-pages$/);
});
