import { test, expect } from '@playwright/test';

test('iTechOrbit AI supports public chat, grounded sources, reset and availability errors', async ({ page }, testInfo) => {
  const errors: string[] = []; page.on('pageerror', error => errors.push(error.message));
  await page.goto('/');
  const launcher = page.getByRole('button', { name: 'Ask iTechOrbit AI' });
  await expect(launcher).toBeVisible(); await launcher.click();
  const assistant = page.getByRole('region', { name: 'iTechOrbit AI website assistant' });
  await expect(assistant).toBeVisible();
  await page.getByRole('textbox', { name: 'Your question' }).fill('What does this website offer?');
  await page.getByRole('button', { name: 'Send', exact: true }).click();
  await expect(assistant.getByText('I could not find an answer', { exact: false })).toBeVisible();
  await expect(assistant.getByRole('link', { name: 'Contact this website' })).toHaveAttribute('href', 'http://127.0.0.1:8010/contact/info');
  await page.route('**/api/ai-pro/v1/sites/itechorbit/messages', route => route.fulfill({ json: { answer: 'We build Laravel applications.', sources: [{ title: 'Software services', url: 'http://127.0.0.1:8010/service/info' }], status: 'answered' } }));
  await page.getByRole('textbox', { name: 'Your question' }).fill('Tell me about your services');
  await page.getByRole('button', { name: 'Send', exact: true }).click();
  await expect(assistant.getByText('We build Laravel applications.')).toBeVisible();
  await expect(assistant.getByRole('link', { name: 'Software services' })).toHaveAttribute('href', 'http://127.0.0.1:8010/service/info');
  expect(await assistant.evaluate(node => { const bounds = node.getBoundingClientRect(); return bounds.left >= 0 && bounds.right <= innerWidth && bounds.top >= 0 && bounds.bottom <= innerHeight; })).toBeTruthy();
  await page.screenshot({ path: testInfo.outputPath('itech-ai-chat.png') });
  await page.getByRole('button', { name: 'Reset conversation' }).click();
  await expect(assistant.getByText('We build Laravel applications.')).toHaveCount(0);
  await page.route('**/api/ai-pro/v1/sites/itechorbit/messages', route => route.fulfill({ status: 503, json: { status: 'unavailable', message: 'iTechOrbit AI is temporarily unavailable.' } }));
  await page.getByRole('textbox', { name: 'Your question' }).fill('Another question');
  await page.getByRole('button', { name: 'Send', exact: true }).click();
  await expect(assistant.getByText('iTechOrbit AI is temporarily unavailable.')).toBeVisible();
  await page.keyboard.press('Escape'); await expect(assistant).toBeHidden(); await expect(launcher).toBeFocused();
  expect(errors).toEqual([]);
});

test('embed works on a second origin and rejects unapproved origins and conversation access', async ({ page, request }) => {
  await page.goto('http://127.0.0.1:8011/');
  await page.getByRole('button', { name: 'Ask iTechOrbit AI' }).click();
  await expect(page.getByText('Embedded test website', { exact: true })).toBeVisible();
  await page.getByRole('textbox', { name: 'Your question' }).fill('What services do you offer?');
  await page.getByRole('button', { name: 'Send', exact: true }).click();
  await expect(page.getByText('I could not find an answer', { exact: false })).toBeVisible();
  const forbidden = await request.get('/api/ai-pro/v1/sites/browser-remote/config', { headers: { Origin: 'https://unapproved.test' } });
  expect(forbidden.status()).toBe(403); expect(forbidden.headers()['access-control-allow-origin']).toBeUndefined();
  const session = await request.post('/api/ai-pro/v1/sites/browser-remote/conversations', { headers: { Origin: 'http://127.0.0.1:8011' }, data: {} });
  const value = await session.json(); expect(session.status()).toBe(201);
  const invalid = await request.post('/api/ai-pro/v1/sites/itechorbit/messages', { headers: { Origin: 'http://127.0.0.1:8010', Authorization: `Bearer ${value.token}` }, data: { conversation_id: value.conversation_id, question: 'Access another site' } });
  expect(invalid.status()).toBe(403);
});

test('administrators can manage website knowledge and see the embed snippet', async ({ page }, testInfo) => {
  await page.goto('/login');
  await page.getByLabel('Email address').fill('admin@example.test');
  await page.getByLabel('Password', { exact: true }).fill('Browser-test-password-42');
  await page.getByRole('button', { name: 'Sign in', exact: true }).click();
  await expect(page).toHaveURL(/\/home$/);
  await page.goto('/workspace/ai-pro');
  await expect(page.getByRole('heading', { name: 'iTechOrbit AI', exact: true })).toBeVisible();
  await expect(page).toHaveURL(/\/workspace\/ai-pro\/overview$/);
  const tabs = page.getByRole('navigation', { name: 'iTechOrbit AI pages' });
  await tabs.getByRole('link', { name: 'Installation & Providers', exact: true }).click();
  await expect(page.getByLabel('Embed snippet')).toHaveValue(/data-site="itechorbit"/);
  const websiteId = await page.getByLabel('Website', { exact: true }).inputValue();
  await tabs.getByRole('link', { name: 'Websites', exact: true }).click();
  await expect(page).toHaveURL(new RegExp(`/websites\\?site=${websiteId}$`));
  await page.getByRole('button', { name: 'Edit settings', exact: true }).click();
  await page.getByLabel('Welcome message').fill('Welcome to iTechOrbit AI.');
  await page.getByRole('button', { name: 'Save website', exact: true }).click();
  await expect(page.getByRole('status')).toHaveText('Website saved.');
  await page.getByLabel('Welcome message').fill('Unsaved welcome message');
  await page.waitForTimeout(5500);
  await expect(page.getByLabel('Welcome message')).toHaveValue('Unsaved welcome message');
  await tabs.getByRole('link', { name: 'Knowledge', exact: true }).click();
  await page.getByLabel('Knowledge title').fill(`Approved FAQ ${testInfo.project.name}`);
  await page.getByLabel('Approved answer / knowledge').fill('Contact us to agree on project scope.');
  await page.getByRole('button', { name: 'Save knowledge', exact: true }).click();
  await expect(page.getByRole('status')).toHaveText('Knowledge saved. Synchronization queued.');
  await expect(page.getByText(`Approved FAQ ${testInfo.project.name}`, { exact: true })).toBeVisible();
  await page.screenshot({ path: testInfo.outputPath('itech-ai-knowledge.png'), fullPage: true });
  await tabs.getByRole('link', { name: 'Synchronization', exact: true }).click();
  await expect(page.getByRole('heading', { name: 'Synchronization', exact: true })).toBeVisible();
  await expect(page.getByText('queued · 0 sources', { exact: true })).toBeVisible();
  await tabs.getByRole('link', { name: 'Conversations', exact: true }).click();
  const conversation = page.getByRole('button', { name: /Conversation #/ }).first();
  await expect(conversation).toBeVisible(); await conversation.click();
  await expect(page).toHaveURL(/conversation=\d+/);
  await expect(page.getByLabel('Conversation messages')).toBeVisible();
  await page.reload();
  await expect(page.getByLabel('Conversation messages')).toBeVisible();
  await expect(page.getByLabel('Website', { exact: true })).toHaveValue(websiteId);
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBeTruthy();
  await page.screenshot({ path: testInfo.outputPath('itech-ai-conversations.png'), fullPage: true });
});

for (const theme of ['light', 'dark'] as const) {
  test(`AI admin pages use consistent ${theme} colors and controls`, async ({ page }, testInfo) => {
    const errors: string[] = []; page.on('pageerror', error => errors.push(error.message));
    await page.emulateMedia({ reducedMotion: 'reduce' });
    await page.addInitScript(value => localStorage.setItem('itechorbit-workspace-theme', value), theme);
    await page.goto('/login');
    await page.getByLabel('Email address').fill('admin@example.test');
    await page.getByLabel('Password', { exact: true }).fill('Browser-test-password-42');
    await page.getByRole('button', { name: 'Sign in', exact: true }).click();
    await expect(page).toHaveURL(/\/home$/);
    for (const section of ['overview', 'websites', 'knowledge', 'synchronization', 'conversations', 'installation']) {
      await page.goto(`/workspace/ai-pro/${section}`);
      await expect(page.locator('.workspace')).toHaveAttribute('data-theme', theme);
      await expect(page.getByLabel('Website', { exact: true })).toHaveValue('1');
      await expect(page.locator('.ai-pro-notice')).toHaveCount(0);
      const result = await page.locator('.ai-pro-workspace').evaluate(node => {
        const channel = (value: string) => value <= ' ' ? [] : (value.match(/[\d.]+/g) || []).slice(0, 3).map(Number);
        const luminance = (value: string) => { const rgb = channel(value).map(v => v / 255).map(v => v <= .04045 ? v / 12.92 : ((v + .055) / 1.055) ** 2.4); return rgb[0] * .2126 + rgb[1] * .7152 + rgb[2] * .0722; };
        const contrast = (a: string, b: string) => { const x = luminance(a); const y = luminance(b); return (Math.max(x, y) + .05) / (Math.min(x, y) + .05); };
        const card = node.querySelector('.ai-pro-card')!; const cardStyle = getComputedStyle(card);
        const control = node.querySelector('.ai-pro-button.primary:not(:disabled)');
        const controlStyle = control ? getComputedStyle(control) : null;
        return { textContrast: contrast(cardStyle.color, cardStyle.backgroundColor), buttonContrast: controlStyle ? contrast(controlStyle.color, controlStyle.backgroundColor) : 21,
          tooSmall: [...node.querySelectorAll<HTMLButtonElement>('.ai-pro-button')].some(button => button.getBoundingClientRect().height < 43), overflow: document.documentElement.scrollWidth > innerWidth };
      });
      expect(result.textContrast).toBeGreaterThanOrEqual(4.5); expect(result.buttonContrast).toBeGreaterThanOrEqual(4.5); expect(result.tooSmall).toBeFalsy(); expect(result.overflow).toBeFalsy();
      if (section === 'conversations') {
        await page.getByRole('button', { name: /Conversation #/ }).first().click();
        await expect(page.getByLabel('Conversation messages')).toBeVisible();
      }
      await page.evaluate(() => { (document.activeElement as HTMLElement | null)?.blur(); window.scrollTo(0, 0); });
      await page.screenshot({ path: testInfo.outputPath(`ai-admin-${section}-${theme}.png`), fullPage: true });
    }
    await page.getByRole('button', { name: theme === 'light' ? 'Dark theme' : 'Light theme', exact: true }).click();
    await expect(page.locator('.workspace')).toHaveAttribute('data-theme', theme === 'light' ? 'dark' : 'light');
    expect(errors).toEqual([]);
  });
}

test('widget themes, readable accents, long answers, and compact introduction adapt to the viewport', async ({ page }, testInfo) => {
  await page.emulateMedia({ colorScheme: 'dark', reducedMotion: 'reduce' });
  await page.route('**/api/ai-pro/v1/sites/itechorbit/config', route => route.fulfill({ json: { name: 'iTechOrbit AI', website: 'iTechOrbit', welcome: 'Ask about our services and published work.', color: '#fff099', notice: 'Chats are stored for 30 days. Do not share sensitive information.', suggestions: ['What do you offer?', 'How can I contact you?'] } }));
  await page.goto('/');
  // Embedded integrations can override the configured accent; the local launcher follows the site brand.
  await page.locator('script[data-site="itechorbit"]').evaluate((node: HTMLScriptElement) => { node.dataset.color = '#fff099'; });
  const host = page.locator('#ai-pro-itechorbit'); const launcher = page.getByRole('button', { name: 'Ask iTechOrbit AI' });
  await expect(host).toHaveAttribute('data-theme', 'dark'); await launcher.click();
  const panel = page.getByRole('region', { name: 'iTechOrbit AI website assistant' });
  await expect(panel.getByRole('heading', { name: 'How can I help?' })).toBeVisible();
  const initialHeight = await panel.evaluate(node => node.getBoundingClientRect().height);
  expect(initialHeight).toBeLessThan(550);
  const label = await launcher.evaluate(node => ({ text: getComputedStyle(node).color, background: getComputedStyle(node).backgroundColor }));
  expect(label.text).toBe('rgb(17, 17, 17)');
  await page.screenshot({ path: testInfo.outputPath('ai-widget-intro-dark.png') });
  await page.emulateMedia({ colorScheme: 'light' }); await expect(host).toHaveAttribute('data-theme', 'light');
  await page.locator('script[data-site="itechorbit"]').evaluate((node: HTMLScriptElement) => { node.dataset.theme = 'dark'; });
  await expect(host).toHaveAttribute('data-theme', 'dark');
  await page.emulateMedia({ colorScheme: 'light' }); await expect(host).toHaveAttribute('data-theme', 'dark');
  await page.locator('script[data-site="itechorbit"]').evaluate((node: HTMLScriptElement) => { node.dataset.theme = 'light'; });
  await expect(host).toHaveAttribute('data-theme', 'light');
  await page.screenshot({ path: testInfo.outputPath('ai-widget-intro-light.png') });
  let release: (() => void) | undefined;
  await page.route('**/api/ai-pro/v1/sites/itechorbit/messages', async route => { await new Promise<void>(resolve => { release = resolve; }); await route.fulfill({ json: { answer: 'Here is information about our services. '.repeat(80), sources: [{ title: 'Software engineering services and website development', url: 'http://127.0.0.1:8010/service/info' }], status: 'answered' } }); });
  const question = page.getByRole('textbox', { name: 'Your question' });
  await question.fill('Tell me about services'); await question.press('Shift+Enter'); await expect(question).toHaveValue('Tell me about services\n');
  await question.press('Enter'); await expect(panel.getByRole('status')).toHaveText('Finding an answer…'); await expect(page.getByRole('button', { name: 'Send', exact: true })).toBeDisabled();
  await page.screenshot({ path: testInfo.outputPath('ai-widget-loading.png') });
  await expect.poll(() => !!release).toBeTruthy(); release!();
  await expect(panel.getByRole('link', { name: 'Software engineering services and website development' })).toBeAttached();
  await expect(page.getByRole('button', { name: 'Send', exact: true })).toBeEnabled();
  expect(await panel.evaluate(node => node.getBoundingClientRect().height)).toBeGreaterThanOrEqual(initialHeight);
  expect(await question.evaluate(node => { const r = node.getBoundingClientRect(); return r.top >= 0 && r.bottom <= innerHeight; })).toBeTruthy();
  await page.screenshot({ path: testInfo.outputPath('ai-widget-long-answer-light.png') });
  await page.setViewportSize({ width: 360, height: 480 });
  await expect.poll(() => panel.evaluate(node => { const r = node.getBoundingClientRect(); return r.left >= 0 && r.top >= 0 && r.right <= innerWidth && r.bottom <= innerHeight; })).toBeTruthy();
  await page.getByRole('button', { name: 'Reset conversation' }).click();
  await expect(panel.getByRole('heading', { name: 'How can I help?' })).toBeVisible();
});
