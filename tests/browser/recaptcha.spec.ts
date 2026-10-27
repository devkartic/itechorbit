import { test, expect } from '@playwright/test';
import { clearBrowserRateLimits } from './isolated-cache';

test.beforeEach(clearBrowserRateLimits);

// The browser exercises the widget contract; PHP feature tests mock Google's
// server verification independently. No production bypass is added.
test('reCAPTCHA survives navigation, expiry and submission retries', async ({ page }) => {
  const errors: string[] = [];
  page.on('pageerror', error => errors.push(error.message));
  await page.addInitScript(() => {
    type Options = { callback: (token: string) => void; 'expired-callback': () => void };
    const widgets: { element: HTMLElement; options: Options }[] = [];
    Object.assign(window, { grecaptcha: {
      render(element: HTMLElement, options: Options) {
        const id = widgets.push({ element, options }) - 1;
        element.innerHTML = '<button type="button">Solve test challenge</button><button type="button">Expire test challenge</button>';
        element.querySelectorAll('button')[0].onclick = () => options.callback('browser-test-token');
        element.querySelectorAll('button')[1].onclick = () => options['expired-callback']();
        return id;
      },
      reset(id: number) { widgets[id].options['expired-callback'](); },
    } });
  });
  await page.route(/\/(contact\/info|inquiries)(?:\?.*)?$/, async route => {
    const response = await route.fetch();
    const text = await response.text();
    if (response.headers()['content-type']?.includes('application/json')) {
      const data = JSON.parse(text);
      data.props.recaptcha = { enabled: true, siteKey: 'browser-public-key' };
      await route.fulfill({ response, json: data });
    } else {
      await route.fulfill({ response, body: text });
    }
  });
  await page.goto('/');
  await page.getByRole('link', { name: 'Project inquiries' }).click();
  const form = page.getByRole('form', { name: 'Project inquiry' });
  await expect(form.getByText('Protected by reCAPTCHA.')).toBeVisible();
  await form.getByRole('button', { name: 'Solve test challenge' }).click();
  await form.getByLabel('Your name', { exact: true }).fill('Browser Guest');
  await form.getByLabel('Email address', { exact: true }).fill(`captcha-${Date.now()}@example.test`);
  await form.getByLabel('What can we help with?', { exact: true }).fill('Test project');
  await form.getByLabel('Tell us a little about it', { exact: true }).fill('A useful engineering project.');
  await form.getByRole('button', { name: 'Expire test challenge' }).click();
  const first = page.waitForRequest(request => request.url().endsWith('/inquiries') && request.method() === 'POST');
  await form.getByRole('button', { name: 'Send inquiry' }).click();
  expect((await first).postDataJSON()['g-recaptcha-response']).toBe('');
  await expect(form.getByRole('status')).toContainText('Your inquiry has been received');
  await page.getByRole('navigation', { name: 'Footer navigation' }).getByRole('link', { name: 'About', exact: true }).click();
  await page.getByRole('link', { name: 'Project inquiries' }).click();
  await expect(page.getByRole('button', { name: 'Solve test challenge' })).toHaveCount(1);
  await page.getByRole('button', { name: 'Solve test challenge' }).click();
  expect(errors).toEqual([]);
});

test('v3 obtains a fresh action token for each submission', async ({ page }) => {
  await page.addInitScript(() => {
    let calls = 0;
    Object.assign(window, { grecaptcha: {
      render() { throw new Error('v3 must not render a checkbox.'); },
      reset() {}, ready(callback: () => void) { callback(); },
      execute(_key: string, { action }: { action: string }) { calls++; return Promise.resolve(`${action}-token-${calls}`); },
    } });
  });
  await page.route(/\/(contact\/info|inquiries)(?:\?.*)?$/, async route => {
    const response = await route.fetch();
    if (response.headers()['content-type']?.includes('application/json')) {
      const data = await response.json();
      data.props.recaptcha = { enabled: true, siteKey: 'browser-public-key', version: 'v3' };
      await route.fulfill({ response, json: data });
    } else await route.fulfill({ response });
  });
  await page.goto('/');
  await page.getByRole('link', { name: 'Project inquiries' }).click();
  const form = page.getByRole('form', { name: 'Project inquiry' });
  await expect(form.getByText('Protected by reCAPTCHA.')).toBeVisible();
  for (const count of [1, 2]) {
    await form.getByLabel('Your name', { exact: true }).fill('Browser Guest');
    await form.getByLabel('Email address', { exact: true }).fill(`captcha-v3-${count}@example.test`);
    await form.getByLabel('What can we help with?', { exact: true }).fill('Test project');
    await form.getByLabel('Tell us a little about it', { exact: true }).fill('A useful engineering project.');
    const sent = page.waitForRequest(request => request.url().endsWith('/inquiries') && request.method() === 'POST');
    await form.getByRole('button', { name: 'Send inquiry' }).click();
    expect((await sent).postDataJSON()['g-recaptcha-response']).toBe(`inquiry-token-${count}`);
    await expect(form.getByLabel('Your name', { exact: true })).toHaveValue('');
  }
  await expect(form.getByRole('alert')).toHaveCount(0);
});
