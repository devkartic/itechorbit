import { expect, test } from '@playwright/test';

for (const scenario of [
  { status: 404, email: 'admin@example.test', password: 'Browser-test-password-42', path: '/posts/999999/edit', title: 'Page not found' },
  { status: 403, email: 'notice-studio@example.test', password: 'Synthetic-only-password-42', path: '/users', title: 'Access restricted' },
]) {
  test(`admin ${scenario.status} stays inside the workspace`, async ({ page }, testInfo) => {
    await page.goto('/login');
    await page.getByLabel('Email address').fill(scenario.email);
    await page.getByLabel('Password', { exact: true }).fill(scenario.password);
    await page.getByRole('button', { name: 'Sign in', exact: true }).click();
    await expect(page).toHaveURL(/\/home$/);

    const response = await page.goto(scenario.path);
    expect(response?.status()).toBe(scenario.status);
    await expect(page.getByRole('main').getByRole('heading', { name: scenario.title })).toBeVisible();
    await expect(page.locator('.workspace-topbar')).toBeVisible();
    await expect(page.locator('.workspace-footer')).toBeVisible();
    await expect(page.locator('.site-header')).toHaveCount(0);
    if (testInfo.project.name === 'mobile') await page.getByRole('button', { name: 'Open workspace navigation' }).click();
    await expect(page.getByRole('navigation', { name: 'Workspace navigation' })).toBeVisible();
    if (testInfo.project.name === 'mobile') await page.getByRole('button', { name: 'Close workspace navigation' }).click();
    await page.getByRole('button', { name: 'Dark theme', exact: true }).click();
    await expect(page.locator('.workspace')).toHaveAttribute('data-theme', 'dark');
    await page.screenshot({ path: `/tmp/itechorbit-admin-${scenario.status}-${testInfo.project.name}.png`, fullPage: true });
    await page.getByRole('link', { name: 'Back to dashboard', exact: true }).click();
    await expect(page).toHaveURL(/\/home$/);
  });
}
