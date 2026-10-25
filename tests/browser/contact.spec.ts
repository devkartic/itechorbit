import { test, expect } from '@playwright/test';

test('business contact has a unified panel with a wider form and compact details', async ({ page }, testInfo) => {
  for (const width of [320, 390, 1024, 1280, 1440]) {
    await page.setViewportSize({ width, height: 1000 });
    await page.goto('/contact/info');
    await expect(page.locator('.contact-business-details > div')).toHaveCount(5);
    await expect(page.locator('.contact-business-details').getByRole('link', { name: '+880 (2) 123-456', exact: true })).toHaveAttribute('href', 'tel:+8802123456');
    await expect(page.locator('.contact-business-details').getByRole('link', { name: 'https://example.test', exact: true })).toHaveAttribute('href', 'https://example.test');
    const copy = await page.locator('.contact-copy').boundingBox();
    const card = await page.locator('.contact-form-card').boundingBox();
    const form = await page.getByRole('form', { name: 'Project inquiry' }).boundingBox();
    const padding = width >= 1024 ? 32 : 24;
    expect(Math.abs(card!.y + card!.height - form!.y - form!.height - padding)).toBeLessThan(2);
    if (width >= 1024) {
      expect(Math.abs(copy!.width / card!.width - 5 / 7)).toBeLessThan(.02);
      expect(Math.abs(copy!.y - card!.y)).toBeLessThan(1);
      expect(Math.abs(copy!.height - card!.height)).toBeLessThan(1);
    } else {
      expect(Math.abs(card!.y - copy!.y - copy!.height)).toBeLessThan(1);
    }
    await expect(page.locator('.contact-copy').getByRole('link', { name: 'hello@example.test' })).toHaveAttribute('href', 'mailto:hello@example.test');
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBeTruthy();
    if (width === (testInfo.project.name === 'mobile' ? 390 : 1440)) {
      await page.screenshot({ path: testInfo.outputPath('unified-contact.png'), fullPage: true });
    }
  }
});

test('business contact preserves subject prefilling validation and submission', async ({ page }) => {
  await page.goto('/contact/info?subject=Website%20improvements');
  await expect(page.getByLabel('What can we help with?')).toHaveValue('Website improvements');
  await page.getByRole('button', { name: 'Send inquiry', exact: true }).click();
  expect(await page.getByLabel('Your name', { exact: true }).evaluate((input: HTMLInputElement) => input.validity.valueMissing)).toBeTruthy();
  await page.getByLabel('Your name', { exact: true }).fill('Layout Test Client');
  await page.getByLabel('Email address', { exact: true }).fill('layout@example.test');
  await page.getByLabel('Tell us a little about it').fill('A synthetic inquiry verifying the contact layout preserves form behavior.');
  await page.getByRole('button', { name: 'Send inquiry', exact: true }).click();
  await expect(page.getByRole('status')).toContainText('Your inquiry has been received');
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBeTruthy();
});

test('contact intro renders database text without interpreting markup', async ({ page }) => {
  await page.goto('/');
  await page.route('**/contact/info', async route => {
    const response = await route.fetch();
    expect(route.request().headers()['x-inertia']).toBe('true');
    const data = await response.json();
    data.props.contactTitle = '<script id="contact-title-injection">Example title</script>';
    data.props.contactSubtitle = '<strong>Project ideas & opportunities</strong>';
    await route.fulfill({ response, json: data });
  });
  await page.getByRole('contentinfo').getByRole('link', { name: 'Project inquiries', exact: true }).click();
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('<script id="contact-title-injection">Example title</script>');
  await expect(page.locator('.contact-page-intro > p:not(.eyebrow)')).toHaveText('<strong>Project ideas & opportunities</strong>');
  await expect(page.locator('#contact-title-injection')).toHaveCount(0);
  await expect(page.locator('.contact-page-intro > p strong')).toHaveCount(0);
});


test('admin edits business contact details and the public page displays escaped values', async ({ page }) => {
  await page.goto('/login');
  await page.getByLabel('Email address').fill('admin@example.test');
  await page.getByLabel('Password', { exact: true }).fill('Browser-test-password-42');
  await page.getByRole('button', { name: 'Sign in', exact: true }).click();
  await expect(page).toHaveURL(/\/home$/);
  await page.goto('/contact-info');
  await page.getByRole('link', { name: 'Edit What are you working on?', exact: true }).click();
  await expect(page).toHaveURL(/\/contact-info\/\d+\/edit$/);
  const editUrl = page.url();
  const form = page.getByRole('form', { name: 'Contact content form', exact: true });
  await expect(form.getByLabel('Email', { exact: true })).toHaveValue('hello@example.test');
  await form.getByLabel('Email', { exact: true }).fill('updated@example.test');
  await form.getByLabel('Address', { exact: true }).fill('<strong>Example office</strong>\nDhaka, Bangladesh');
  await form.getByRole('button', { name: 'Save changes', exact: true }).click();
  await expect(page).toHaveURL(/\/contact-info$/);
  await page.goto('/contact/info');
  await expect(page.locator('.contact-business-details').getByRole('link', { name: 'updated@example.test', exact: true })).toHaveAttribute('href', 'mailto:updated@example.test');
  await expect(page.locator('.contact-business-details')).toContainText('<strong>Example office</strong>');
  await expect(page.locator('.contact-business-details strong')).toHaveCount(0);
  // Restore the shared synthetic fixture for the other viewport project.
  await page.goto(editUrl);
  await form.getByLabel('Email', { exact: true }).fill('hello@example.test');
  await form.getByLabel('Address', { exact: true }).fill('Example office\nDhaka, Bangladesh');
  await form.getByRole('button', { name: 'Save changes', exact: true }).click();
  await expect(page).toHaveURL(/\/contact-info$/);
});
