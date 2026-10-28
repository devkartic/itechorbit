import { expect, test } from '@playwright/test';

test('super admin switches between own, selected user and all records', async ({ page }, testInfo) => {
  await page.goto('/login');
  await page.getByLabel('Email address').fill('admin@example.test');
  await page.getByLabel('Password', { exact: true }).fill('Browser-test-password-42');
  await page.getByRole('button', { name: 'Sign in', exact: true }).click();
  await expect(page).toHaveURL(/\/home$/);

  for (const resource of ['posts', 'basics', 'projects', 'skills']) {
    await page.goto(`/${resource}`);
    const filter = page.getByRole('combobox', { name: 'Filter by user', exact: true });
    await expect(filter).toContainText('My records');
    const initialRows = await page.locator('.admin-table tbody tr').count();
    expect(initialRows).toBeGreaterThan(0);
    await filter.click();
    const search = page.getByRole('combobox', { name: 'Search users', exact: true });
    await search.fill('studio');
    await expect(page.getByRole('listbox', { name: 'Users', exact: true }).getByRole('option')).toHaveCount(1);
    const other = page.getByRole('listbox', { name: 'Users', exact: true }).getByRole('option', { name: /Notice recipient Studio/ });
    const otherId = await other.getAttribute('data-value');
    expect(otherId).toBeTruthy();

    const selected = page.waitForResponse(response => response.url().includes(`/workspace/data/${resource}?`) && new URL(response.url()).searchParams.get('user_id') === otherId);
    await search.press('Enter');
    expect((await selected).ok()).toBe(true);
    await expect(page.getByText(`No ${resource === 'posts' ? 'articles' : resource === 'basics' ? 'profile information' : resource} found`, { exact: true })).toBeVisible();
    await expect(page.locator('.admin-table tbody tr')).toHaveCount(0);

    const all = page.waitForResponse(response => response.url().includes(`/workspace/data/${resource}?`) && new URL(response.url()).searchParams.get('user_id') === 'all');
    await filter.click();
    await search.fill('all users');
    await page.getByRole('listbox', { name: 'Users', exact: true }).getByRole('option', { name: 'All users', exact: true }).click();
    expect((await all).ok()).toBe(true);
    await expect(page.locator('.admin-table tbody tr')).toHaveCount(initialRows);
    await expect(page.getByLabel('Applied filters')).toContainText('User: All users');
    if (resource === 'skills') {
      await filter.click();
      await search.fill('NO-MATCHING-USER');
      await expect(page.getByRole('status').filter({ hasText: 'No users found.' })).toBeVisible();
      await expect(page.getByRole('listbox', { name: 'Users', exact: true }).getByRole('option')).toHaveCount(0);
      await search.press('Enter');
      await expect(filter).toContainText('All users');
      await search.fill('STUDIO');
      await expect(page.getByRole('listbox', { name: 'Users', exact: true }).getByRole('option')).toHaveCount(1);
      await page.screenshot({ path: `/tmp/itechorbit-searchable-user-filter-${testInfo.project.name}.png`, fullPage: true });
      await search.press('Escape');
      await expect(filter).toBeFocused();
      await expect(filter).toHaveAttribute('aria-expanded', 'false');
      await filter.press('ArrowDown');
      await search.fill(`#${otherId}`);
      await expect(page.getByRole('listbox', { name: 'Users', exact: true }).getByRole('option', { name: /Notice recipient Studio/ })).toBeVisible();
      await search.press('Tab');
      await expect(filter).toHaveAttribute('aria-expanded', 'false');
    }

    const reset = page.waitForResponse(response => response.url().includes(`/workspace/data/${resource}?`) && new URL(response.url()).searchParams.get('user_id') === 'mine');
    await page.getByRole('button', { name: 'Reset filters', exact: true }).click();
    expect((await reset).ok()).toBe(true);
    await expect(filter).toContainText('My records');
    await expect(page.getByLabel('Applied filters')).toHaveCount(0);
  }
  await page.goto('/categories');
  await expect(page.getByRole('combobox', { name: 'Filter by user', exact: true })).toHaveCount(0);
});
