import { test, expect } from '@playwright/test';
import { readdirSync, rmSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { basename, dirname, join } from 'node:path';

test.beforeEach(() => {
  // Each test gets independent rate limits; production middleware stays enabled.
  const cache = process.env.ITECHORBIT_BROWSER_CACHE_PATH;
  if (!cache || dirname(cache) !== tmpdir() || !basename(cache).startsWith('itechorbit-browser-cache-')) throw new Error('Expected an isolated browser cache directory.');
  for (const entry of readdirSync(cache)) rmSync(join(cache, entry), { recursive: true, force: true });
});
test('public pages render crawlable HTML and responsive navigation', async ({ page, request, isMobile }, testInfo) => {
  const errors: string[] = []; page.on('pageerror', error => errors.push(error.message));
  const response = await request.get('/'); const html = await response.text();
  expect(html).toContain('<h1'); expect(html).toContain('with Confidence.'); expect(html).toContain('rel="canonical"');
  await page.goto('/'); await expect(page.getByRole('heading', { level: 1 })).toContainText('Build, Modernize');
  await page.evaluate(() => document.fonts.ready);
  expect(await page.evaluate(() => document.fonts.check('600 16px Inter'))).toBeTruthy();
  await page.screenshot({ path: testInfo.outputPath('homepage.png'), fullPage: true });
  await page.locator('.site-header').screenshot({ path: testInfo.outputPath('header.png') });
  const panel = await page.locator('.studio-window').boundingBox();
  const viewportWidth = await page.evaluate(() => window.innerWidth);
  expect(panel?.x).toBeGreaterThanOrEqual(0);
  expect((panel?.x ?? 0) + (panel?.width ?? 0)).toBeLessThanOrEqual(viewportWidth);
  await page.locator('.architecture-preview').screenshot({ path: testInfo.outputPath('architecture-typography.png') });
  if (isMobile) { await page.getByRole('button', { name: 'Open navigation', exact: true }).click(); await expect(page.getByRole('navigation', { name: 'Mobile navigation' })).toBeVisible(); await page.getByRole('navigation', { name: 'Mobile navigation' }).getByRole('link', { name: 'Services' }).click(); }
  else await page.getByRole('navigation', { name: 'Main navigation' }).getByRole('link', { name: 'Services' }).click();
  await expect(page.getByRole('heading', { level: 1 })).toContainText('Practical engineering');
  await page.getByRole('link', { name: /Modernization & upgrades/ }).click(); await expect(page.getByRole('heading', { level: 1 })).toContainText('Laravel modernization');
  for (const url of ['/work', '/about/info', '/contact/info', '/portfolios/TESTPROFILE', '/privacy-n-policy']) { await page.goto(url); await expect(page.getByRole('heading', { level: 1 })).toBeVisible(); expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBeTruthy(); }
  expect(errors).toEqual([]);
});
test('SSR renders auth pages and escapes untrusted article titles', async ({ page, request }) => {
  for (const url of ['/login', '/register', '/password/reset']) {
    const response = await request.get(url); expect(response.status()).toBe(200);
    expect(await response.text()).toContain('<h1');
  }
  const response = await request.get('/blogs/13'); const html = await response.text();
  expect(response.status()).toBe(200);
  expect(html).not.toContain('<script id="title-injection">');
  await page.goto('/blogs/13');
  await expect(page).toHaveTitle(/<script id="title-injection">/);
  await expect(page.locator('#title-injection')).toHaveCount(0);
});
test('logo stays loaded and aligned between the homepage and authentication pages', async ({ page }, testInfo) => {
  await page.goto('/');
  const header = page.getByRole('banner');
  const logo = header.getByRole('img', { name: 'iTechOrbit — Engineering with AI', exact: true });
  await expect(logo).toBeVisible();
  const initialHeader = await header.boundingBox();
  const initialLogo = await logo.boundingBox();
  await header.getByRole('link', { name: 'Sign in', exact: true }).click();
  for (const path of ['/login', '/register', '/password/reset', '/password/reset/test-token']) {
    if (path !== '/login') await page.goto(path);
    await expect(logo).toBeVisible();
    await expect.poll(() => logo.evaluate((image: HTMLImageElement) => image.complete && image.naturalWidth > 0)).toBeTruthy();
    const authHeader = await header.boundingBox();
    const authLogo = await logo.boundingBox();
    expect(authHeader?.height).toBe(initialHeader?.height);
    for (const key of ['x', 'y', 'width', 'height'] as const) expect(authLogo?.[key]).toBeCloseTo(initialLogo?.[key] ?? 0, 1);
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBeTruthy();
    const originalViewport = page.viewportSize()!;
    for (const width of [390, 768, 1440]) {
      await page.setViewportSize({ width, height: 900 });
      const alignment = await page.locator('main').evaluate(main => {
        const card = main.querySelector('form')!.parentElement!.getBoundingClientRect();
        const back = main.querySelector('a[href="/"]')!.getBoundingClientRect();
        return { cardCenter: card.left + card.width / 2, linkCenter: back.left + back.width / 2, viewportCenter: innerWidth / 2, cardWidth: card.width, overflow: document.documentElement.scrollWidth > innerWidth };
      });
      expect(Math.abs(alignment.cardCenter - alignment.viewportCenter)).toBeLessThan(1);
      expect(Math.abs(alignment.linkCenter - alignment.cardCenter)).toBeLessThan(1);
      expect(alignment.cardWidth).toBeLessThanOrEqual(448);
      expect(alignment.overflow).toBeFalsy();
    }
    await page.setViewportSize(originalViewport);
    if (path === '/login' || path === '/register') await page.screenshot({ path: testInfo.outputPath(`${path.slice(1)}-branding.png`), fullPage: true });
  }
  await header.getByRole('link', { name: 'iTechOrbit home', exact: true }).click();
  await expect(page).toHaveURL(/\/$/);
  await expect(page.getByRole('heading', { level: 1 })).toContainText('Build, Modernize');
});
test('blog search, article TOC, code and pagination preserve existing URLs', async ({ page, request }) => {
  await page.goto('/blogs'); await expect(page.getByText('13 articles', { exact: true })).toBeVisible();
  await page.getByRole('searchbox', { name: 'Search articles' }).fill('maintainable'); await page.getByRole('button', { name: 'Search articles' }).click();
  await page.getByRole('link', { name: 'Designing maintainable Laravel applications', exact: true }).click();
  await expect(page).toHaveURL(/\/blogs\/1$/); await expect(page.getByRole('navigation', { name: 'Table of contents' })).toBeVisible(); await expect(page.locator('pre code')).toContainText('echo');
  const html = await (await request.get('/blogs/1')).text(); expect(html).toContain('Start with the domain'); expect(html).toContain('application/ld+json');
  await page.goto('/blogs?page=2'); await expect(page.getByRole('navigation', { name: 'Pagination' }).getByRole('link', { name: '2', exact: true })).toHaveAttribute('aria-current', 'page');
  await page.goto('/blogs/14'); await expect(page.getByRole('heading', { level: 1 })).toContainText('couldn’t be found');
});
test('contact validation and successful inquiry', async ({ page }) => {
  await page.goto('/contact/info'); await page.getByLabel('Your name', { exact: true }).fill('Browser client'); await page.getByLabel('Email address', { exact: true }).fill('client@example.test'); await page.getByLabel('What can we help with?').fill('Browser smoke inquiry'); await page.getByLabel('Tell us a little about it').fill('Synthetic browser test inquiry.'); await page.getByRole('button', { name: 'Send inquiry' }).click(); await expect(page.getByRole('status')).toContainText('Your inquiry has been received');
});
test('authentication, admin CRUD, inbox and permission interface', async ({ page, isMobile }) => {
  await page.goto('/posts'); await expect(page).toHaveURL(/\/login$/);
  await page.getByLabel('Email address').fill('admin@example.test'); await page.getByLabel('Password', { exact: true }).fill('Browser-test-password-42'); await page.getByRole('button', { name: 'Sign in', exact: true }).click(); await expect(page).toHaveURL(/\/(home|posts)$/);
  if (isMobile) await page.getByRole('button', { name: 'Open workspace navigation', exact: true }).click();
  const workspaceLogo = page.getByRole('link', { name: 'iTechOrbit workspace', exact: true }).getByRole('img');
  await expect(workspaceLogo).toBeVisible();
  await expect.poll(() => workspaceLogo.evaluate((image: HTMLImageElement) => image.complete && image.naturalWidth > 0)).toBeTruthy();
  if (isMobile) await page.getByRole('button', { name: 'Close workspace navigation', exact: true }).click();
  await page.goto('/posts/create'); await page.getByLabel('Title *', { exact: true }).fill(`Browser CRUD ${isMobile ? 'mobile' : 'desktop'}`); await page.getByLabel('Category *', { exact: true }).selectOption({ label: 'Laravel' }); await page.getByLabel('Body *', { exact: true }).fill('<p>Browser regression article.</p>'); await page.getByRole('button', { name: 'Save changes' }).click(); await expect(page).toHaveURL(/\/posts$/);
  const title = `Browser CRUD ${isMobile ? 'mobile' : 'desktop'}`; await page.getByRole('link', { name: `Edit ${title}`, exact: true }).click(); await page.getByLabel('Title *', { exact: true }).fill(title + ' revised'); await page.getByRole('button', { name: 'Save changes' }).click(); await expect(page).toHaveURL(/\/posts$/); await page.getByRole('button', { name: `Delete ${title} revised`, exact: true }).click(); await expect(page.getByRole('dialog')).toBeVisible(); await page.getByRole('button', { name: 'Delete record', exact: true }).click(); await expect(page.getByText(title + ' revised', { exact: true })).toHaveCount(0);
  await page.goto('/contacts'); await expect(page.getByRole('heading', { level: 1 })).toHaveText('Contact inquiries'); await page.goto('/permissions'); await expect(page.getByLabel('Select role')).toBeVisible(); await page.getByLabel('Select role').selectOption('4'); await expect(page.getByRole('heading', { level: 1 })).toHaveText('Roles & permissions');
  await page.getByRole('button', { name: 'Sign out' }).click(); await page.goto('/posts'); await expect(page).toHaveURL(/\/login$/);
});

test('public navigation and calls to action work without JavaScript', async ({ browser, isMobile }) => {
  const context = await browser.newContext({ javaScriptEnabled: false, viewport: { width: isMobile ? 390 : 1280, height: 900 }, baseURL: 'http://127.0.0.1:8010' });
  const page = await context.newPage();
  for (const [label, destination] of [['Services', '/service/info'], ['Work', '/work'], ['Insights', '/blogs'], ['About', '/about/info']]) {
    await page.goto('/');
    if (isMobile) await page.getByRole('button', { name: 'Open navigation', exact: true }).click();
    await page.getByRole('navigation', { name: isMobile ? 'Mobile navigation' : 'Main navigation' }).getByRole('link', { name: label, exact: true }).click();
    await expect(page).toHaveURL(new RegExp(destination + '$'));
    await expect(page.getByRole('heading', { level: 1 })).toBeVisible();
  }
  await page.goto('/'); await page.getByRole('link', { name: 'Let’s build something' }).click();
  await expect(page).toHaveURL(/\/contact\/info$/);
  await page.goto('/'); await page.getByRole('link', { name: 'Laravel development', exact: false }).first().click();
  await expect(page).toHaveURL(/\/services\/laravel-development$/);
  await context.close();
});

test('header stays visible when scrolling and fits tablet widths', async ({ page }) => {
  for (const width of [390, 768, 1024, 1280]) {
    await page.setViewportSize({ width, height: 900 });
    await page.goto('/');
    await page.evaluate(() => window.scrollTo({ top: 1200, behavior: 'instant' }));
    const header = page.locator('.site-header');
    await expect(header).toHaveClass(/is-scrolled/);
    const box = await header.boundingBox();
    expect(box?.y).toBe(0);
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBeTruthy();
    if (width < 1024) {
      await page.getByRole('button', { name: 'Open navigation', exact: true }).click();
      await expect(page.getByRole('navigation', { name: 'Mobile navigation' })).toBeVisible();
      await page.keyboard.press('Escape');
      await expect(page.getByRole('navigation', { name: 'Mobile navigation' })).not.toBeVisible();
    }
  }
});

test('search works when the browser cannot encrypt history', async ({ page }) => {
  await page.addInitScript(() => Object.defineProperty(window.crypto, 'subtle', { value: undefined, configurable: true }));
  const errors: string[] = [];
  page.on('pageerror', error => errors.push(error.message));
  await page.goto('/blogs');
  await page.getByRole('searchbox', { name: 'Search articles' }).fill('maintainable');
  await page.getByRole('button', { name: 'Search articles' }).click();
  await expect(page.getByRole('link', { name: 'Designing maintainable Laravel applications', exact: true })).toBeVisible();
  await page.goBack();
  await expect(page.getByRole('searchbox', { name: 'Search articles' })).toBeVisible();
  expect(errors).toEqual([]);
});

test('architecture preview supports pointer and keyboard exploration', async ({ page }) => {
  await page.goto('/');
  const application = page.getByRole('tab', { name: 'Application', exact: true });
  await expect(application).toHaveAttribute('aria-selected', 'true');
  await page.getByRole('tab', { name: 'Interface', exact: true }).click();
  await expect(page.getByRole('tabpanel')).toContainText('ProjectCard.tsx');
  await page.getByRole('tab', { name: 'Interface', exact: true }).press('ArrowRight');
  await expect(application).toBeFocused();
  await expect(page.getByRole('tabpanel')).toContainText('routes/web.php');
  await application.press('End');
  await expect(page.getByRole('tab', { name: 'Data', exact: true })).toBeFocused();
  await expect(page.getByRole('tabpanel')).toContainText('paginate(12)');
});

test('AI highlight leads to the service page and a relevant inquiry', async ({ page, request }, testInfo) => {
  await page.goto('/');
  const section = page.locator('[aria-labelledby="ai-highlight-title"]');
  await expect(section.getByRole('heading', { level: 2 })).toContainText('part of your workflow');
  await section.screenshot({ path: testInfo.outputPath('ai-highlight.png') });
  await section.getByRole('link', { name: 'Explore AI solutions' }).click();
  await expect(page).toHaveURL(/\/services\/ai-workflow-integration$/);
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('AI integration and workflow automation');
  await expect(page.getByRole('heading', { name: 'Where AI can fit' })).toBeVisible();
  const html = await (await request.get('/services/ai-workflow-integration')).text();
  expect(html).toContain('application/ld+json');
  expect(html).toContain('/services/ai-workflow-integration');
  await page.getByRole('link', { name: 'Discuss this service' }).click();
  await expect(page.getByLabel('What can we help with?')).toHaveValue('AI integration and workflow automation');
});

test('header sign-in and account dropdown support profile settings and logout', async ({ page, isMobile }, testInfo) => {
  await page.goto('/');
  await page.getByRole('banner').getByRole('link', { name: 'Sign in', exact: true }).click();
  await expect(page).toHaveURL(/\/login$/);
  await page.getByLabel('Email address').fill('admin@example.test');
  await page.getByLabel('Password', { exact: true }).fill('Browser-test-password-42');
  await page.getByRole('button', { name: 'Sign in', exact: true }).click();
  await expect(page).toHaveURL(/\/home$/);
  await page.goto('/');
  const trigger = page.getByRole('button', { name: 'Open account menu' });
  await trigger.click();
  const navigation = page.getByRole('navigation', { name: 'Account navigation' });
  await expect(navigation.getByRole('link', { name: 'My profile' })).toBeVisible();
  await page.screenshot({ path: testInfo.outputPath('account-dropdown.png') });
  await page.keyboard.press('Escape');
  await expect(navigation).not.toBeVisible();
  await expect(trigger).toBeFocused();
  await trigger.press('ArrowDown');
  await expect(navigation.getByRole('link', { name: 'My profile' })).toBeFocused();
  await navigation.getByRole('link', { name: 'My profile' }).click();
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('My profile');
  await page.getByRole('link', { name: 'Edit account details' }).click();
  await page.getByLabel('Full name', { exact: true }).fill(`Test Engineer ${isMobile ? 'mobile' : 'desktop'}`);
  await page.getByRole('button', { name: 'Save account details' }).click();
  await expect(page.getByRole('status')).toContainText('Account details updated');
  await page.getByLabel('Full name', { exact: true }).fill('Test Engineer');
  await page.getByRole('button', { name: 'Save account details' }).click();
  await expect(page.getByRole('status')).toContainText('Account details updated');
  const security = page.getByRole('form', { name: 'Change password' });
  await security.getByLabel('Current password', { exact: true }).fill('Browser-test-password-42');
  await security.getByLabel('New password', { exact: true }).fill('Browser-Updated-password-42');
  await security.getByLabel('Confirm new password', { exact: true }).fill('Browser-Updated-password-42');
  await Promise.all([page.waitForEvent('load'), security.getByRole('button', { name: 'Update password' }).click()]);
  await expect(page.getByRole('status')).toContainText('Password updated');
  await security.getByLabel('Current password', { exact: true }).fill('Browser-Updated-password-42');
  await security.getByLabel('New password', { exact: true }).fill('Browser-test-password-42');
  await security.getByLabel('Confirm new password', { exact: true }).fill('Browser-test-password-42');
  await Promise.all([page.waitForEvent('load'), security.getByRole('button', { name: 'Update password' }).click()]);
  await expect(page.getByRole('status')).toContainText('Password updated');
  await page.goto('/'); await trigger.click();
  await page.locator('main').click({ position: { x: 5, y: 5 } });
  await expect(navigation).not.toBeVisible();
  await trigger.click(); await navigation.getByRole('button', { name: 'Sign out' }).click();
  await expect(page.getByRole('banner').getByRole('link', { name: 'Sign in', exact: true })).toBeVisible();
  await page.goto('/account/settings'); await expect(page).toHaveURL(/\/login$/);
});

test('workspace tables load server-side search sorting pagination and recover from errors', async ({ page, isMobile }, testInfo) => {
  await page.goto('/login'); await page.getByLabel('Email address').fill('admin@example.test'); await page.getByLabel('Password', { exact: true }).fill('Browser-test-password-42'); await page.getByRole('button', { name: 'Sign in', exact: true }).click(); await expect(page).toHaveURL(/\/home$/);
  await page.getByRole('button', { name: 'Dark theme', exact: true }).click();
  await expect(page.locator('.workspace')).toHaveAttribute('data-theme', 'dark');
  await page.reload();
  await expect(page.locator('.workspace')).toHaveAttribute('data-theme', 'dark');
  await page.screenshot({ path: testInfo.outputPath('admin-dashboard.png'), fullPage: true });
  if (isMobile) await page.getByRole('button', { name: 'Open workspace navigation', exact: true }).click();
  await expect(page.getByRole('navigation', { name: 'Workspace navigation' }).getByText('Test workspace', { exact: true })).toBeVisible();
  await expect(page.locator('.workspace-module-label').first()).toHaveText('Module');
  await expect(page.locator('.workspace-nav-group>p small').first()).toHaveText('Category');
  const sidebarLabels = await page.locator('.workspace-nav-module').filter({ hasText: 'Management' }).locator('.workspace-nav-link span:first-of-type').allTextContents();
  expect(sidebarLabels.length).toBeGreaterThan(1);
  expect(sidebarLabels).toEqual([...sidebarLabels].sort((left, right) => left.localeCompare(right, 'en', { numeric: true, sensitivity: 'base' })));
  await page.getByLabel('Find a module').fill('Articles');
  await expect(page.getByRole('navigation', { name: 'Workspace navigation' }).getByRole('link', { name: 'Articles', exact: true })).toBeVisible();
  await expect(page.getByRole('navigation', { name: 'Workspace navigation' }).getByRole('link', { name: 'Categories', exact: true })).toHaveCount(0);
  await page.getByLabel('Find a module').fill('');
  if (isMobile) await page.keyboard.press('Escape');
  await page.goto('/posts');
  await expect(page.locator('.workspace')).toHaveAttribute('data-theme', 'dark');
  await page.evaluate(() => { document.documentElement.dataset.tableDocument = 'retained'; });
  await Promise.all([page.waitForResponse(response => response.url().includes('/workspace/data/posts?') && response.url().includes('per_page=10')), page.getByLabel('Rows per page').selectOption('10')]);
  await expect(page.locator('.admin-table tbody tr')).toHaveCount(10);
  await Promise.all([page.waitForResponse(response => response.url().includes('/workspace/data/posts?') && response.url().includes('page=2')), page.getByRole('button', { name: 'Next page', exact: true }).click()]);
  await expect(page.getByText(/Page 2 of/)).toBeVisible();
  await Promise.all([page.waitForResponse(response => response.url().includes('/workspace/data/posts?') && response.url().includes('sort=title')), page.getByRole('button', { name: 'Sort by Title', exact: true }).click()]);
  await expect(page.getByRole('columnheader', { name: 'Sort by Title' })).toHaveAttribute('aria-sort', 'ascending');
  await Promise.all([page.waitForResponse(response => response.url().includes('/workspace/data/posts?') && response.url().includes('key=zz-no-result-zz')), page.getByRole('searchbox', { name: 'Search Articles' }).fill('zz-no-result-zz')]);
  await expect(page.getByRole('heading', { name: 'No articles found' })).toBeVisible();
  await page.getByRole('button', { name: 'Clear filters', exact: true }).click();
  await expect(page.locator('.admin-table tbody tr')).toHaveCount(10);
  await page.route('**/workspace/data/posts?**', route => route.fulfill({ status: 500, contentType: 'application/json', body: '{}' }), { times: 1 });
  await page.getByRole('button', { name: 'Refresh records', exact: true }).click();
  await expect(page.getByRole('alert')).toContainText('The request could not be completed.');
  await page.getByRole('button', { name: 'Retry', exact: true }).click();
  await expect(page.getByRole('alert')).toHaveCount(0);
  expect(await page.evaluate(() => document.documentElement.dataset.tableDocument)).toBe('retained');
  await Promise.all([page.waitForResponse(response => response.url().includes('/workspace/data/posts?') && response.url().includes('key=Engineering')), page.getByRole('searchbox', { name: 'Search Articles' }).fill('Engineering')]);
  await expect(page.locator('.admin-table tbody tr')).toHaveCount(10);
  await page.evaluate(() => window.scrollTo({ top: 0, behavior: 'instant' }));
  await page.screenshot({ path: testInfo.outputPath('admin-table-dark.png'), fullPage: true });
  await page.getByRole('button', { name: 'Light theme', exact: true }).click();
  await expect(page.locator('.workspace')).toHaveAttribute('data-theme', 'light');
  await page.reload();
  await expect(page.locator('.workspace')).toHaveAttribute('data-theme', 'light');
  await page.screenshot({ path: testInfo.outputPath('admin-table.png'), fullPage: true });
});

test('CKEditor formats links and preserves published content after save and reload', async ({ page, isMobile }, testInfo) => {
  const errors: string[] = []; page.on('pageerror', error => errors.push(error.message));
  await page.goto('/login'); await page.getByLabel('Email address').fill('admin@example.test'); await page.getByLabel('Password', { exact: true }).fill('Browser-test-password-42'); await page.getByRole('button', { name: 'Sign in', exact: true }).click(); await expect(page).toHaveURL(/\/home$/);
  await page.goto('/posts/create'); const title = `CKEditor QA ${isMobile ? 'mobile' : 'desktop'}`;
  await page.getByLabel('Title *', { exact: true }).fill(title); await page.getByLabel('Category *', { exact: true }).selectOption({ label: 'Laravel' });
  const body = page.getByRole('textbox', { name: 'Body *', exact: true }); await expect(body).toBeVisible();
  const field = page.locator('.workspace-ckeditor-field');
  await page.getByRole('button', { name: 'Save changes', exact: true }).click(); await expect(page.getByText('Body is required.', { exact: true })).toBeVisible();
  await body.fill('Clear engineering decisions'); await body.press('ControlOrMeta+A');
  await field.getByRole('button', { name: 'Bold', exact: true }).click();
  await field.getByRole('button', { name: 'Paragraph, Heading', exact: true }).click();
  await page.getByRole('menuitemradio', { name: 'Heading 2', exact: true }).click();
  await field.getByRole('button', { name: 'Font Size', exact: true }).click();
  await field.locator('.ck-font-size-dropdown').getByText('20', { exact: true }).click();
  await field.getByRole('button', { name: 'Font Family', exact: true }).click();
  await field.locator('.ck-font-family-dropdown').getByText('Georgia', { exact: true }).click();
  await field.getByRole('button', { name: 'Link', exact: true }).click();
  await page.getByLabel('Link URL', { exact: true }).fill('https://example.com/careers');
  await page.getByRole('button', { name: 'Insert', exact: true }).click();
  await expect(body.getByRole('link')).toHaveAttribute('href', 'https://example.com/careers');
  await expect(body.locator('strong')).toHaveText('Clear engineering decisions');
  await page.getByRole('button', { name: 'Dark theme', exact: true }).click();
  await page.screenshot({ path: testInfo.outputPath('ckeditor-dark.png'), fullPage: true });
  await page.getByLabel('Title *', { exact: true }).fill('x'.repeat(201));
  await page.getByRole('button', { name: 'Save changes', exact: true }).click(); await expect(page.locator('#title-error')).toContainText('200');
  await expect(body).toContainText('Clear engineering decisions'); await page.getByLabel('Title *', { exact: true }).fill(title);
  await page.getByRole('button', { name: 'Save changes', exact: true }).click(); await expect(page).toHaveURL(/\/posts$/);
  await expect(page.getByRole('status')).toContainText('Post created successfully');
  await page.getByRole('link', { name: `View ${title}`, exact: true }).click();
  await expect(page.locator('.workspace-record-details h2')).toHaveText('Clear engineering decisions');
  const articleId = page.url().split('/').at(-1);
  await page.goto(`/blogs/${articleId}`);
  await expect(page.locator('.prose-content h2')).toHaveText('Clear engineering decisions');
  const formatted = page.locator('.prose-content span[data-font-size="20"][data-text-font="serif"]');
  await expect(formatted).toHaveText('Clear engineering decisions'); await expect(formatted).toHaveCSS('font-size', '20px');
  await expect(page.locator('.prose-content a')).toHaveAttribute('href', 'https://example.com/careers');
  await page.goto(`/posts/${articleId}/edit`);
  await expect(page.getByRole('textbox', { name: 'Body *', exact: true })).toContainText('Clear engineering decisions');
  await page.getByRole('button', { name: 'Source', exact: true }).click();
  const source = page.locator('.ck-source-editing-area textarea');
  await source.fill('<h2>Updated through HTML source</h2><p><a href="https://example.com/updated">Updated link</a></p><table><tbody><tr><td>Preserved table cell</td></tr></tbody></table>');
  // Saving directly from source mode must include edits without manually returning to visual mode.
  await page.getByRole('button', { name: 'Save changes', exact: true }).click(); await expect(page).toHaveURL(/\/posts$/);
  await page.goto(`/blogs/${articleId}`);
  await expect(page.locator('.prose-content h2')).toHaveText('Updated through HTML source');
  await expect(page.locator('.prose-content a')).toHaveAttribute('href', 'https://example.com/updated');
  await expect(page.locator('.prose-content table td')).toHaveText('Preserved table cell');
  await page.goto('/posts'); await page.getByRole('button', { name: `Delete ${title}`, exact: true }).click(); await page.getByRole('button', { name: 'Delete record', exact: true }).click(); await expect(page.getByRole('button', { name: `Delete ${title}`, exact: true })).toHaveCount(0);
  expect(errors).toEqual([]);
});

test('portfolio template studio previews five layouts and persists the public selection', async ({ page, request, isMobile }, testInfo) => {
  test.setTimeout(60000); // Five themes, twenty section-anchor checks and public return navigation.
  const errors: string[] = []; page.on('pageerror', error => errors.push(error.message));
  await page.goto('/login'); await page.getByLabel('Email address').fill('admin@example.test'); await page.getByLabel('Password', { exact: true }).fill('Browser-test-password-42'); await page.getByRole('button', { name: 'Sign in', exact: true }).click(); await expect(page).toHaveURL(/\/home$/);
  if (isMobile) await page.getByRole('button', { name: 'Open workspace navigation', exact: true }).click();
  await page.getByRole('link', { name: 'Portfolio templates', exact: true }).click();
  await expect(page.getByRole('radio')).toHaveCount(5);
  await expect(page.getByRole('button', { name: 'Save template', exact: true })).toBeDisabled();
  for (const name of ['Executive', 'Blueprint', 'Editorial', 'Spotlight', 'Studio']) {
    await page.locator('.template-choice').getByText(name, { exact: true }).click();
    const preview = page.getByRole('region', { name: `${name} portfolio preview` });
    await expect(preview.locator('.portfolio-view')).toHaveAttribute('data-template', name.toLowerCase());
    const previewFilters = preview.getByRole('group', { name: 'Filter portfolio projects' });
    await previewFilters.getByRole('button', { name: 'API platform', exact: true }).click();
    await expect(preview.locator('.pf-project')).toHaveCount(1);
    await previewFilters.getByRole('button', { name: /All work/ }).click();
    await expect(preview.locator('.pf-project')).toHaveCount(4);
    await page.getByRole('button', { name: 'Mobile preview', exact: true }).click();
    await expect(page.getByRole('button', { name: 'Mobile preview', exact: true })).toHaveAttribute('aria-pressed', 'true');
    expect(await preview.evaluate(element => element.scrollWidth <= element.clientWidth + 1)).toBeTruthy();
    await page.getByRole('button', { name: 'Desktop preview', exact: true }).click();
    if (await page.getByRole('button', { name: 'Save template', exact: true }).isEnabled()) {
      await page.getByRole('button', { name: 'Save template', exact: true }).click();
      await expect(page.locator('.workspace-notice[role="status"]')).toContainText('Portfolio template saved.');
    }
    await page.reload();
    await expect(page.getByRole('radio', { name: new RegExp(name) })).toBeChecked();
    await page.goto('/portfolios/TESTPROFILE');
    await expect(page.locator('.portfolio-view')).toHaveAttribute('data-template', name.toLowerCase());
    await expect(page.getByRole('heading', { level: 1 })).toHaveText(/Test Engineer/);
    await expect(page.getByRole('navigation', { name: 'Main navigation' })).toHaveCount(0);
    await expect(page.locator('.pf-candidate-role')).toHaveText('Full-stack software engineer');
    await expect(page.getByRole('link', { name: 'Discuss an opportunity', exact: true })).toHaveAttribute('href', '#connect');
    await expect(page.getByRole('navigation', { name: 'Portfolio sections' })).toBeVisible();
    await expect(page.locator('.pf-local-header')).toBeVisible();
    await expect(page.locator('.pf-brand-copy strong')).toBeVisible();
    await expect(page.locator('.pf-brand-copy strong')).toHaveText('Test Engineer');
    await expect(page.locator('.pf-local-header').getByRole('link', { name: 'Main site', exact: true })).toBeVisible();
    await expect(page.getByRole('link', { name: 'Main site', exact: true })).toHaveAttribute('href', '/');
    await expect(page.locator('.pf-signature')).toHaveText(`© ${new Date().getFullYear()} Test Engineer. All rights reserved.`);
    await expect(page.locator('.pf-signature a')).toHaveCount(0);
    await expect(page.getByRole('heading', { name: 'Built with purpose.' })).toBeVisible();
    const profileRows = await page.locator('.pf-profile-sections').evaluate(container => {
      const box = container.getBoundingClientRect();
      const about = container.querySelector('#about')!.getBoundingClientRect();
      const expertise = container.querySelector('#expertise')!.getBoundingClientRect();
      return { width: box.width, aboutWidth: about.width, expertiseWidth: expertise.width, gap: about.top - expertise.bottom };
    });
    expect(Math.abs(profileRows.aboutWidth - profileRows.width)).toBeLessThan(1);
    expect(Math.abs(profileRows.expertiseWidth - profileRows.width)).toBeLessThan(1);
    expect(profileRows.gap).toBeGreaterThanOrEqual(0);
    await expect(page.locator('.pf-profile-sections > section').first()).toHaveAttribute('id', 'expertise');
    expect(await page.locator('.pf-profile-sections > section').evaluateAll(sections => sections.map(section => section.id))).toEqual(['expertise', 'experience', 'projects', 'services', 'about']);

    const originalViewport = page.viewportSize()!;
    const alignmentSizes = [originalViewport, ...(!isMobile ? [{ width: 768, height: 900 }, { width: 1440, height: 900 }] : [])];
    for (const viewport of alignmentSizes) {
      await page.setViewportSize(viewport);
      const skillColumns = await page.locator('.pf-skills').evaluate(element => getComputedStyle(element).gridTemplateColumns.split(' ').length);
      expect(skillColumns).toBe(viewport.width <= 680 ? 2 : viewport.width <= 1023 ? 3 : 6);
      const masthead = await page.locator('.pf-masthead').boundingBox();
      expect(Math.abs(masthead!.x)).toBeLessThan(1);
      expect(Math.abs(masthead!.width - viewport.width)).toBeLessThan(1);
      const edges = await page.evaluate(() => {
        const contentEdges = (selector: string) => {
          const element = document.querySelector<HTMLElement>(selector)!;
          const box = element.getBoundingClientRect(); const css = getComputedStyle(element);
          return [box.left + parseFloat(css.paddingLeft), box.right - parseFloat(css.paddingRight)];
        };
        return { header: contentEdges('.pf-local-header'), portfolio: contentEdges('.pf-profile-sections'), footer: contentEdges('.pf-signature') };
      });
      for (const edge of [0, 1]) {
        expect(Math.abs(edges.portfolio[edge] - edges.header[edge])).toBeLessThan(1);
        expect(Math.abs(edges.portfolio[edge] - edges.footer[edge])).toBeLessThan(1);
      }
    }
    await page.setViewportSize(originalViewport);

    const filters = page.getByRole('group', { name: 'Filter portfolio projects' });
    await filters.getByRole('button', { name: 'Developer tooling', exact: true }).click();
    await expect(page.locator('.pf-project')).toHaveCount(1);
    await expect(page.locator('.pf-project h3')).toHaveText('Synthetic developer workspace');
    const allWork = filters.getByRole('button', { name: /All work/ });
    await allWork.focus(); await page.keyboard.press('Enter');
    await expect(page.locator('.pf-project')).toHaveCount(4);
    const portfolioNav = page.getByRole('navigation', { name: 'Portfolio sections' });
    for (const [label, id] of [['Skills', 'expertise'], ['Experience', 'experience'], ['Work', 'projects'], ['Connect', 'connect']]) {
      await portfolioNav.getByRole('link', { name: label, exact: true }).click();
      await expect(page).toHaveURL(new RegExp(`#${id}$`));
      await expect(page.locator(`#${id}`)).toBeInViewport();
      await expect(page.locator('.pf-local-header')).toBeInViewport();
      await expect.poll(() => page.evaluate((sectionId: string) => {
        const header = document.querySelector('.pf-local-header')!.getBoundingClientRect();
        const target = document.getElementById(sectionId)!.getBoundingClientRect();
        return header.top >= -1 && header.top <= 1 && target.top >= header.bottom - 1;
      }, id)).toBeTruthy();
    }
    await expect(page.getByRole('link', { name: 'Explore the work', exact: true })).toHaveAttribute('href', '#projects');
    const gallery = await page.locator('.pf-projects').boundingBox();
    const finalRow = await page.locator('.pf-projects').evaluate(grid => {
      const boxes = [...grid.querySelectorAll<HTMLElement>('.pf-project')].map(card => ({ y: card.offsetTop, left: card.getBoundingClientRect().left, right: card.getBoundingClientRect().right }));
      const finalY = Math.max(...boxes.map(box => box.y));
      const row = boxes.filter(box => Math.abs(box.y - finalY) < 2);
      return { left: Math.min(...row.map(box => box.left)), right: Math.max(...row.map(box => box.right)) };
    });
    expect(Math.abs((gallery!.x + gallery!.width / 2) - (finalRow.left + finalRow.right) / 2)).toBeLessThan(2);
    const projectCard = page.locator('.pf-project').first();
    expect((await projectCard.innerText()).split('Synthetic project for testing')).toHaveLength(2);
    expect(await projectCard.locator('.pf-project-accent').evaluate(element => element.getBoundingClientRect().height)).toBeLessThanOrEqual(80);
    await projectCard.screenshot({ path: testInfo.outputPath(`project-card-${name.toLowerCase()}.png`) });
    if (!isMobile) {
      const card = page.locator('.pf-project').first();
      const restingShadow = await card.evaluate(element => getComputedStyle(element).boxShadow);
      await card.hover();
      await expect.poll(() => card.evaluate(element => getComputedStyle(element).boxShadow)).not.toBe(restingShadow);
    }
    await page.emulateMedia({ reducedMotion: 'reduce' });
    await expect(page.locator('.pf-floating-stack')).toHaveCSS('animation-name', 'none');
    await page.emulateMedia({ reducedMotion: 'no-preference' });

    expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBeTruthy();
    const lowContrast = await page.locator('.portfolio-view').evaluate(root => {
      const luminance = (rgb: number[]) => rgb.slice(0, 3).map(value => value / 255).map(value => value <= .04045 ? value / 12.92 : ((value + .055) / 1.055) ** 2.4).reduce((total, value, i) => total + value * [.2126, .7152, .0722][i], 0);
      const channels = (color: string) => (color.match(/[\d.]+/g) ?? []).map(Number);
      return [...root.querySelectorAll<HTMLElement>('.pf-hero h1, .pf-section h2, .pf-rich, .pf-project h3, .pf-connect h2, .pf-connect h2>span')].flatMap(element => {
        let surface: Element | null = element;
        let background: number[] = [];
        while (surface) {
          background = channels(getComputedStyle(surface).backgroundColor);
          if (background.length >= 3 && (background.length < 4 || background[3] === 1)) break;
          surface = surface.parentElement;
        }
        if (!surface) return [];
        const text = luminance(channels(getComputedStyle(element).color)); const backdrop = luminance(background);
        const ratio = (Math.max(text, backdrop) + .05) / (Math.min(text, backdrop) + .05);
        const threshold = /^H[123]$/.test(element.tagName) || element.parentElement?.tagName === 'H2' ? 3 : 4.5;
        return ratio + .01 < threshold ? [{ text: element.textContent?.slice(0, 55), ratio: Math.round(ratio * 100) / 100 }] : [];
      });
    });
    expect(lowContrast).toEqual([]);
    const html = await (await request.get('/portfolios/TESTPROFILE')).text();
    expect(html).toContain(`data-template="${name.toLowerCase()}"`); expect(html).toContain('Synthetic project for testing');
    await page.evaluate(() => { (document.activeElement as HTMLElement)?.blur(); window.scrollTo({ top: 0, behavior: 'instant' }); });
    await expect.poll(() => page.evaluate(() => window.scrollY)).toBe(0);
    await expect(page.getByRole('link', { name: 'Main site', exact: true })).toBeInViewport();
    await page.screenshot({ path: testInfo.outputPath(`portfolio-${name.toLowerCase()}.png`), fullPage: true });
    await page.locator('.pf-connect').screenshot({ path: testInfo.outputPath(`contact-${name.toLowerCase()}.png`) });
    await page.getByRole('link', { name: 'Main site', exact: true }).click();
    await expect(page).toHaveURL(/\/$/);
    await expect(page.getByRole('heading', { level: 1 })).toContainText('Build, Modernize');
    await page.goto(`/portfolios/SPARSE-${name.toUpperCase()}`);
    await expect(page.getByRole('heading', { level: 1 })).toContainText('Minimal Engineer');
    await expect(page.locator('.pf-project, .pf-section')).toHaveCount(0);
    expect(await page.locator('.pf-hero').evaluate(element => element.getBoundingClientRect().height)).toBeLessThan(isMobile ? 900 : 600);
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBeTruthy();
    if (name === 'Studio') await page.screenshot({ path: testInfo.outputPath('portfolio-sparse.png'), fullPage: true });
    await page.goto('/workspace/portfolio-templates');
  }
  await page.screenshot({ path: testInfo.outputPath('portfolio-template-studio.png'), fullPage: true });
  expect(errors).toEqual([]);
});

test('portfolio contact belongs to its owner and supports personal inquiries', async ({ page }, testInfo) => {
  await page.goto('/portfolios/TESTPROFILE');
  const form = page.getByRole('form', { name: 'Contact Test Engineer', exact: true });
  await expect(form.getByText('Your message is sent directly to Test Engineer.')).toBeVisible();
  await form.getByLabel('Your name', { exact: true }).fill('Portfolio recruiter');
  await form.getByLabel('Email address', { exact: true }).fill('recruiter@example.test');
  await form.getByLabel('Subject', { exact: true }).fill('Personal engineering opportunity');
  await form.getByLabel('Your message', { exact: true }).fill('A personal portfolio inquiry from the synthetic browser fixture.');
  await Promise.all([page.waitForResponse(response => response.request().method() === 'POST' && response.url().endsWith('/portfolios/TESTPROFILE/inquiries')), form.getByRole('button', { name: 'Send personal message' }).click()]);
  await expect(form.getByRole('status')).toContainText('received for Test Engineer');
  await page.goto('/contact/info?portfolio_id=TESTPROFILE');
  await expect(page).toHaveURL(/\/portfolios\/TESTPROFILE\/contact$/);
  await expect(page.getByRole('heading', { level: 1 })).toContainText('Test Engineer');
  await expect(page.getByRole('navigation', { name: 'Main navigation' })).toHaveCount(0);
  await expect(page.getByRole('link', { name: 'iTechOrbit home' })).toHaveCount(0);
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBeTruthy();
  await page.screenshot({ path: testInfo.outputPath('portfolio-personal-contact.png'), fullPage: true });
});

test('uploaded project screenshots appear as compact uncropped portfolio thumbnails', async ({ page, isMobile }, testInfo) => {
  await page.goto('/login'); await page.getByLabel('Email address').fill('admin@example.test'); await page.getByLabel('Password', { exact: true }).fill('Browser-test-password-42'); await page.getByRole('button', { name: 'Sign in', exact: true }).click(); await expect(page).toHaveURL(/\/home$/);
  try {
    await page.goto('/projects/1/edit');
    await page.getByLabel('Project screenshot', { exact: true }).setInputFiles('tests/browser/fixtures/project-screenshot.png');
    await page.getByRole('button', { name: 'Save changes', exact: true }).click();
    await expect(page).toHaveURL(/\/projects$/);
    await page.goto('/portfolios/TESTPROFILE');
    const card = page.locator('.pf-project').filter({ hasText: 'Synthetic project for testing' });
    const image = card.getByRole('img');
    await expect(image).toBeVisible();
    await expect.poll(() => image.evaluate((node: HTMLImageElement) => node.complete && node.naturalWidth > 0)).toBeTruthy();
    await expect(image).toHaveCSS('object-fit', 'contain');
    expect(await card.locator('.pf-project-image').evaluate(node => node.getBoundingClientRect().height)).toBeLessThanOrEqual((isMobile ? 160 : 190) + 0.5);
    await expect(card.locator('.pf-project-accent')).toHaveCount(0);
    await card.screenshot({ path: testInfo.outputPath('uploaded-project-thumbnail.png') });
    await card.getByRole('link', { name: 'View Synthetic project for testing', exact: true }).click();
    await expect(page).toHaveURL(/\/work\/1$/);
  } finally {
    const token = await page.locator('meta[name="csrf-token"]').getAttribute('content');
    const cleanup = await page.request.post('/project-file-delete', { headers: { 'X-CSRF-TOKEN': token ?? '', Accept: 'application/json' }, data: { id: 1, field_name: 'image_path' } });
    expect(cleanup.ok()).toBeTruthy();
  }
});

test('CKEditor replaces every rich-text resource and supports native link insertion', async ({ page }, testInfo) => {
  await page.goto('/login'); await page.getByLabel('Email address').fill('admin@example.test'); await page.getByLabel('Password', { exact: true }).fill('Browser-test-password-42'); await page.getByRole('button', { name: 'Sign in', exact: true }).click(); await expect(page).toHaveURL(/\/home$/);
  for (const [resource, count] of [['posts', 1], ['projects', 2], ['basics', 3], ['services', 1], ['experiences', 2], ['testimonials', 1], ['about-info', 1], ['service-info', 1], ['privacy-policies', 1], ['contact-info', 1]] as const) {
    await page.goto(resource === 'basics' ? '/basics/1/edit' : `/${resource}/create`);
    await expect(page.locator('.ck-editor__editable')).toHaveCount(count);
    await expect(page.locator('.workspace-ckeditor-field')).toHaveCount(count);
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBeTruthy();
  }
  await page.goto('/posts/create');
  const body = page.getByRole('textbox', { name: 'Body *', exact: true });
  const field = page.locator('.workspace-ckeditor-field');
  await expect(body).toBeVisible(); await body.click();
  let saves = 0; page.on('request', request => { if (request.method() === 'POST' && new URL(request.url()).pathname === '/posts') saves++; });
  await field.getByRole('button', { name: 'Link', exact: true }).click();
  await page.getByLabel('Displayed text', { exact: true }).fill('Read the guide');
  await page.getByLabel('Link URL', { exact: true }).fill('example.com/guide');
  await page.getByLabel('Link URL', { exact: true }).press('Enter');
  await expect(body.getByRole('link')).toHaveText('Read the guide');
  await expect(body.getByRole('link')).toHaveAttribute('href', 'https://example.com/guide');
  await expect(page).toHaveURL(/\/posts\/create$/); expect(saves).toBe(0);
  await body.getByRole('link').click();
  await page.getByRole('button', { name: 'Unlink', exact: true }).click();
  await expect(body.getByRole('link')).toHaveCount(0); await expect(body).toHaveText('Read the guide');
  await field.getByRole('button', { name: 'Undo', exact: true }).click();
  await expect(body.getByRole('link')).toHaveAttribute('href', 'https://example.com/guide');
  await field.getByRole('button', { name: 'Redo', exact: true }).click();
  await expect(body.getByRole('link')).toHaveCount(0);
  await page.screenshot({ path: testInfo.outputPath('ckeditor-native-links.png'), fullPage: true });
});

test('privacy policy saves long pasted content and reopens without truncation', async ({ page, isMobile }) => {
  await page.goto('/login'); await page.getByLabel('Email address').fill('admin@example.test'); await page.getByLabel('Password', { exact: true }).fill('Browser-test-password-42'); await page.getByRole('button', { name: 'Sign in', exact: true }).click(); await expect(page).toHaveURL(/\/home$/);
  await page.goto('/privacy-policies/create');
  const title = `Privacy paste QA ${isMobile ? 'mobile' : 'desktop'}`;
  await page.getByLabel('Title *', { exact: true }).fill(title);
  await page.getByLabel('Subtitle *', { exact: true }).fill('How we protect visitor information');
  const editor = page.getByRole('textbox', { name: 'Descriptions *', exact: true });
  const paragraph = 'At iTechOrbit.com, accessible from iTechOrbit, one of our main priorities is the privacy of our visitors. This Privacy Policy document contains types of information that is collected and recorded by iTechOrbit and how we use it.';
  const text = `${Array.from({ length: 400 }, () => paragraph).join('\n\n')}\n\nChildren’s Information\n\nWe protect the privacy of children under the age of 13.`;
  expect(new TextEncoder().encode(text).length).toBeGreaterThan(65535);
  await editor.fill(text);
  const saved = page.waitForResponse(response => response.url().endsWith('/privacy-policies') && response.request().method() === 'POST');
  await page.getByRole('button', { name: 'Save changes', exact: true }).click();
  expect((await saved).status()).toBe(200);
  await expect(page).toHaveURL(/\/privacy-policies$/);
  await page.getByRole('link', { name: `Edit ${title}`, exact: true }).click();
  await expect(page.getByRole('textbox', { name: 'Descriptions *', exact: true })).toContainText('We protect the privacy of children under the age of 13.');
  await page.goto('/privacy-policies');
  await page.getByRole('button', { name: `Delete ${title}`, exact: true }).click(); await page.getByRole('button', { name: 'Delete record', exact: true }).click();
  await expect(page.getByRole('button', { name: `Delete ${title}`, exact: true })).toHaveCount(0);
});

test('privileged Users table opens public portfolios and retains links after filtering', async ({ page, context }, testInfo) => {
  await page.goto('/login'); await page.getByLabel('Email address').fill('admin@example.test'); await page.getByLabel('Password', { exact: true }).fill('Browser-test-password-42'); await page.getByRole('button', { name: 'Sign in', exact: true }).click(); await expect(page).toHaveURL(/\/home$/);
  await page.goto('/users');
  await expect(page.getByRole('columnheader', { name: 'Sort by Portfolio', exact: true })).toBeVisible();
  await expect(page.locator('thead th').filter({ hasText: 'Portfolio' })).toHaveCount(1);
  await expect(page.getByRole('button', { name: 'Sort by Public portfolio', exact: true })).toHaveCount(0);
  const profile = page.getByRole('link', { name: 'View public portfolio for Test Engineer', exact: true });
  await expect(profile).toHaveAttribute('href', '/portfolios/TESTPROFILE');
  await expect(profile).toHaveAttribute('target', '_blank');
  await Promise.all([page.waitForResponse(response => response.url().includes('/workspace/data/users?') && response.url().includes('key=Test')), page.getByRole('searchbox', { name: 'Search Users', exact: true }).fill('Test')]);
  await expect(profile).toHaveAttribute('href', '/portfolios/TESTPROFILE');
  const popup = context.waitForEvent('page'); await profile.click(); const portfolio = await popup;
  await portfolio.waitForLoadState(); await expect(portfolio).toHaveURL(/\/portfolios\/TESTPROFILE$/);
  await expect(portfolio.getByRole('heading', { level: 1 })).toHaveText('Test Engineer.');
  await portfolio.close(); await expect(page).toHaveURL(/\/users$/);
  await page.screenshot({ path: testInfo.outputPath('users-public-portfolios.png'), fullPage: true });
});

test('Users table combines portfolio status with links and red inactive badges', async ({ page, context }) => {
  await page.goto('/login'); await page.getByLabel('Email address').fill('admin@example.test'); await page.getByLabel('Password', { exact: true }).fill('Browser-test-password-42'); await page.getByRole('button', { name: 'Sign in', exact: true }).click(); await expect(page).toHaveURL(/\/home$/);
  await page.goto('/users');
  await Promise.all([page.waitForResponse(response => response.url().includes('/workspace/data/users?') && response.url().includes('sparse-studio')), page.getByRole('searchbox', { name: 'Search Users', exact: true }).fill('sparse-studio')]);
  const row = page.locator('.admin-table tbody tr');
  const link = row.getByRole('link', { name: 'View public portfolio for Minimal Engineer', exact: true });
  await expect(link).toBeVisible(); await expect(link).toHaveClass(/is-active/);
  await page.getByRole('button', { name: 'Disable user', exact: true }).click();
  await expect(page.getByRole('button', { name: 'Enable user', exact: true })).toBeVisible();
  await expect(link).toHaveCount(0);
  await expect(row.locator('.workspace-badge.is-inactive')).toHaveCount(2);
  const border = await row.locator('.workspace-badge.is-inactive').last().evaluate(element => {
    const style = getComputedStyle(element); return { width: style.borderTopWidth, color: style.borderTopColor };
  });
  expect(border.width).toBe('1px'); expect(border.color).toBe('rgb(237, 196, 203)');
  await page.getByRole('button', { name: 'Enable user', exact: true }).click();
  await expect(link).toBeVisible();
  await page.getByRole('button', { name: 'Hide portfolio', exact: true }).click();
  await expect(page.getByRole('button', { name: 'Publish portfolio', exact: true })).toBeVisible();
  await expect(link).toHaveCount(0); await expect(row.locator('.workspace-badge.is-inactive')).toHaveText('Inactive');
  await page.getByRole('button', { name: 'Dark theme', exact: true }).click();
  await expect(row.locator('.workspace-badge.is-inactive')).toHaveCSS('border-top-width', '1px');
  await page.getByRole('button', { name: 'Publish portfolio', exact: true }).click();
  await expect(link).toBeVisible();
  const popup = context.waitForEvent('page'); await link.click(); const portfolio = await popup;
  await portfolio.waitForLoadState(); await expect(portfolio).toHaveURL(/\/portfolios\/SPARSE-STUDIO$/); await portfolio.close();
});

test('CV PDF uploads save and clearing a replacement preserves the existing document', async ({ page, context }) => {
  await page.goto('/login'); await page.getByLabel('Email address').fill('admin@example.test'); await page.getByLabel('Password', { exact: true }).fill('Browser-test-password-42'); await page.getByRole('button', { name: 'Sign in', exact: true }).click(); await expect(page).toHaveURL(/\/home$/);
  await page.goto('/basics/1/edit');
  await expect(page.locator('.ck-editor__editable')).toHaveCount(3);
  await page.getByLabel('Mobile *', { exact: true }).fill('01234567890');
  await page.getByLabel('Address *', { exact: true }).fill('Synthetic test address');
  await page.getByLabel('Gender *', { exact: true }).fill('Other');
  await page.getByLabel('Marital status *', { exact: true }).fill('Single');
  const pdf = { name: 'Kartic-Resume-SP.pdf', mimeType: 'application/pdf', buffer: Buffer.from('%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\n%%EOF') };
  await page.getByLabel('CV document', { exact: true }).setInputFiles(pdf);
  const upload = page.waitForResponse(response => response.url().endsWith('/basics/1') && response.request().method() === 'POST');
  await page.getByRole('button', { name: 'Save changes', exact: true }).click();
  expect((await upload).status()).toBe(200); await expect(page).toHaveURL(/\/basics$/);
  await page.goto('/basics/1/edit');
  const cv = page.locator('.workspace-existing-media[data-storage-field="cv_path"]');
  await expect(cv).toBeVisible(); const savedPath = await cv.locator('small').innerText(); expect(savedPath).toMatch(/.+\.pdf$/);
  const pdfLink = cv.getByRole('link', { name: 'Open CV document PDF', exact: true });
  await expect(pdfLink).toHaveAttribute('target', '_blank');
  const pdfResponse = await page.request.get((await pdfLink.getAttribute('href'))!); expect(pdfResponse.headers()['content-type']).toBe('application/pdf');
  expect(pdfResponse.headers()['content-disposition']).toMatch(/^inline;/);
  // Headless Chromium downloads PDFs instead of providing the desktop PDF viewer.
  const requested = context.waitForEvent('request', request => request.url().endsWith('/workspace/media/basics/1/cv_path'));
  const opened = context.waitForEvent('page'); await pdfLink.click(); const document = await opened;
  await requested; await document.close();
  await page.getByLabel('CV document', { exact: true }).setInputFiles({ ...pdf, name: 'replacement.pdf' });
  await page.getByLabel('CV document', { exact: true }).setInputFiles([]);
  const save = page.waitForResponse(response => response.url().endsWith('/basics/1') && response.request().method() === 'POST');
  await page.getByRole('button', { name: 'Save changes', exact: true }).click();
  const response = await save; expect(response.status()).toBe(200); expect(response.request().postData()).not.toContain('name="uploaded_cv"');
  await expect(page).toHaveURL(/\/basics$/); await page.goto('/basics/1/edit');
  await expect(cv.locator('small')).toHaveText(savedPath);
  const removed = page.waitForResponse(response => response.url().endsWith('/basics-file-delete') && response.request().method() === 'POST');
  await cv.getByRole('button', { name: 'Remove file', exact: true }).click(); expect((await removed).status()).toBe(200);
  await expect(cv).toHaveCount(0);
});

test('all image uploads preview selections and saved files with consistent validation', async ({ page, isMobile }, testInfo) => {
  await page.goto('/login'); await page.getByLabel('Email address').fill('admin@example.test'); await page.getByLabel('Password', { exact: true }).fill('Browser-test-password-42'); await page.getByRole('button', { name: 'Sign in', exact: true }).click(); await expect(page).toHaveURL(/\/home$/);
  const png = 'tests/browser/fixtures/project-screenshot.png';
  const title = `Image preview QA ${isMobile ? 'mobile' : 'desktop'}`;
  for (const [path, labels] of [['/posts/create', ['Cover image']], ['/projects/1/edit', ['Project screenshot']], ['/basics/1/edit', ['Banner image', 'Profile photo']]] as const) {
    await page.goto(path);
    if (path === '/posts/create') {
      await page.getByLabel('Title *', { exact: true }).fill(title); await page.getByLabel('Category *', { exact: true }).selectOption({ label: 'Laravel' });
      await page.getByRole('textbox', { name: 'Body *', exact: true }).fill('Synthetic image upload verification.');
      await page.getByLabel('Cover image', { exact: true }).setInputFiles({ name: 'invalid.txt', mimeType: 'text/plain', buffer: Buffer.from('invalid') });
      await expect(page.getByRole('alert')).toContainText('Choose a JPG, PNG or WebP image.');
      await page.getByLabel('Cover image', { exact: true }).setInputFiles({ name: 'oversized.png', mimeType: 'image/png', buffer: Buffer.alloc(2048 * 1024 + 1) });
      await expect(page.getByRole('alert')).toContainText('2,048 KB');
    }
    if (path === '/basics/1/edit') {
      await page.getByLabel('Mobile *', { exact: true }).fill('01234567890'); await page.getByLabel('Address *', { exact: true }).fill('Synthetic test address');
      await page.getByLabel('Gender *', { exact: true }).fill('Other'); await page.getByLabel('Marital status *', { exact: true }).fill('Single');
    }
    for (const label of labels) {
      await page.getByLabel(label, { exact: true }).setInputFiles(png);
      const selectedPreview = page.getByRole('img', { name: `Selected ${label} preview`, exact: true });
      await expect(selectedPreview).toBeVisible();
      await expect.poll(() => selectedPreview.evaluate((image: HTMLImageElement) => image.complete && image.naturalWidth > 0)).toBeTruthy();
      const upload = page.locator('.workspace-upload').filter({ has: page.getByLabel(label, { exact: true }) });
      await upload.getByRole('button', { name: 'Cancel selection', exact: true }).click();
      await expect(page.getByRole('img', { name: `Selected ${label} preview`, exact: true })).toHaveCount(0);
      await page.getByLabel(label, { exact: true }).setInputFiles(png);
    }
    await page.getByRole('button', { name: 'Save changes', exact: true }).click();
    await expect(page).toHaveURL(path.startsWith('/posts') ? /\/posts$/ : path.startsWith('/projects') ? /\/projects$/ : /\/basics$/);
    if (path === '/posts/create') await page.getByRole('link', { name: `Edit ${title}`, exact: true }).click();
    else await page.goto(path);
    for (const label of labels) {
      const preview = page.getByRole('img', { name: `${label} preview`, exact: true });
      await expect(preview).toBeVisible(); await expect.poll(() => preview.evaluate((image: HTMLImageElement) => image.complete && image.naturalWidth > 0)).toBeTruthy();
      expect(await preview.getAttribute('src')).toContain('/workspace/media/');
    }
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBeTruthy();
    await page.screenshot({ path: testInfo.outputPath(`saved-upload-${path.split('/')[1]}.png`), fullPage: true });
    if (path === '/posts/create') {
      await page.goto('/posts'); await page.getByRole('button', { name: `Delete ${title}`, exact: true }).click(); await page.getByRole('button', { name: 'Delete record', exact: true }).click(); await expect(page.getByRole('button', { name: `Delete ${title}`, exact: true })).toHaveCount(0);
    } else {
      for (const label of labels) {
        const attachment = page.locator('.workspace-existing-media').filter({ has: page.getByRole('img', { name: `${label} preview`, exact: true }) });
        await attachment.getByRole('button', { name: 'Remove file', exact: true }).click(); await expect(page.getByRole('img', { name: `${label} preview`, exact: true })).toHaveCount(0);
      }
    }
  }
});

test('portfolio banners align and crop across all templates and device previews', async ({ page }, testInfo) => {
  test.setTimeout(60000);
  await page.goto('/login'); await page.getByLabel('Email address').fill('admin@example.test'); await page.getByLabel('Password', { exact: true }).fill('Browser-test-password-42'); await page.getByRole('button', { name: 'Sign in', exact: true }).click(); await expect(page).toHaveURL(/\/home$/);
  async function image(width: number, height: number) {
    const data = await page.evaluate(({ width, height }) => {
      const canvas = document.createElement('canvas'); canvas.width = width; canvas.height = height;
      const context = canvas.getContext('2d')!;
      context.fillStyle = '#7040cf'; context.fillRect(0, 0, width, height);
      context.fillStyle = '#d6f576'; context.fillRect(width / 3, 0, width / 3, height);
      context.fillStyle = '#18152c'; context.fillRect(0, height * 0.7, width, height * 0.3);
      return canvas.toDataURL('image/png').split(',')[1];
    }, { width, height });
    return { name: `banner-${width}-${height}.png`, mimeType: 'image/png', buffer: Buffer.from(data, 'base64') };
  }
  async function upload(file: Awaited<ReturnType<typeof image>>, photo = false) {
    await page.goto('/basics/1/edit'); await expect(page.locator('.ck-editor__editable')).toHaveCount(3);
    await page.getByLabel('Mobile *', { exact: true }).fill('01234567890'); await page.getByLabel('Address *', { exact: true }).fill('Synthetic address');
    await page.getByLabel('Gender *', { exact: true }).fill('Other'); await page.getByLabel('Marital status *', { exact: true }).fill('Single');
    await page.getByLabel('Banner image', { exact: true }).setInputFiles(file);
    if (photo) await page.getByLabel('Profile photo', { exact: true }).setInputFiles(await image(200, 200));
    await page.getByRole('button', { name: 'Save changes', exact: true }).click(); await expect(page).toHaveURL(/\/basics$/);
  }
  async function check(root: ReturnType<typeof page.locator>) {
    await expect(root.locator('.pf-banner img')).toBeVisible();
    const dimensions = await root.evaluate(element => {
      const banner = element.querySelector('.pf-banner')!.getBoundingClientRect();
      const header = element.querySelector('.pf-local-header')!.getBoundingClientRect();
      const footer = element.querySelector('.pf-signature')!.getBoundingClientRect();
      const hero = element.querySelector('.pf-hero')!.getBoundingClientRect();
      const image = element.querySelector<HTMLImageElement>('.pf-banner img')!;
      return { ratio: banner.width / banner.height, expected: element.clientWidth <= 680 ? 2 : 4, left: banner.left - header.left, right: banner.right - header.right,
        footerLeft: banner.left - footer.left, footerRight: banner.right - footer.right, aboveHero: banner.bottom <= hero.top,
        objectFit: getComputedStyle(image).objectFit, loaded: image.complete && image.naturalWidth > 0 };
    });
    expect(Math.abs(dimensions.ratio - dimensions.expected)).toBeLessThan(0.02);
    for (const edge of [dimensions.left, dimensions.right, dimensions.footerLeft, dimensions.footerRight]) expect(Math.abs(edge)).toBeLessThan(1);
    expect(dimensions.aboveHero).toBeTruthy(); expect(dimensions.loaded).toBeTruthy(); expect(dimensions.objectFit).toBe('cover');
  }
  const landscape = await image(1600, 400); await upload(landscape, true);
  for (const name of ['Executive', 'Blueprint', 'Editorial', 'Spotlight', 'Studio']) {
    await page.goto('/workspace/portfolio-templates'); await page.locator('.template-choice').getByText(name, { exact: true }).click();
    const root = page.getByRole('region', { name: `${name} portfolio preview` }).locator('.portfolio-view');
    for (const device of ['Desktop preview', 'Mobile preview']) { await page.getByRole('button', { name: device, exact: true }).click(); await check(root); }
    const save = page.getByRole('button', { name: 'Save template', exact: true });
    if (await save.isEnabled()) { await save.click(); await expect(page.locator('.workspace-notice[role="status"]')).toContainText('Portfolio template saved.'); }
    await page.goto('/portfolios/TESTPROFILE'); await check(page.locator('.portfolio-view'));
    await expect(page.locator('.pf-portrait')).toBeVisible();
    expect((await (await page.request.get((await page.locator('.pf-banner img').getAttribute('src'))!)).body()).equals(landscape.buffer)).toBeTruthy();
    await page.screenshot({ path: testInfo.outputPath(`banner-${name.toLowerCase()}.png`), fullPage: true });
  }
  for (const [width, height] of [[400, 1200], [600, 600]]) {
    await upload(await image(width, height)); await page.goto('/portfolios/TESTPROFILE'); await check(page.locator('.portfolio-view'));
  }
  await page.route('**/uploads/profiles/images/banner_images/**', route => route.fulfill({ status: 404, body: '' }));
  await page.goto('/portfolios/TESTPROFILE'); await expect(page.locator('.pf-banner')).toHaveCount(0);
  await page.unroute('**/uploads/profiles/images/banner_images/**');
  await page.goto('/basics/1/edit');
  for (const storage of ['banner_image_path', 'profile_image_path']) {
    const attachment = page.locator(`.workspace-existing-media[data-storage-field="${storage}"]`);
    await attachment.getByRole('button', { name: 'Remove file', exact: true }).click(); await expect(attachment).toHaveCount(0);
  }
  await page.goto('/portfolios/TESTPROFILE'); await expect(page.locator('.pf-banner')).toHaveCount(0);
});

test('contact inquiry opens as an organized theme-aware message', async ({ page, isMobile }, testInfo) => {
  const name = `Message reader ${isMobile ? 'mobile' : 'desktop'}`;
  const subject = `Engineering & scope? ${'LongSubject'.repeat(12)}`;
  const message = `Hello Test Engineer,\n\nPlease review our project & requirements.\n<script>alert("unsafe")</script>\n\n${'LongMessage'.repeat(100)}\n\nKind regards,\nSynthetic sender`;
  await page.goto('/contact/info');
  await page.getByLabel('Your name', { exact: true }).fill(name);
  await page.getByLabel('Email address', { exact: true }).fill('reader+tag@example.test');
  await page.getByLabel('What can we help with?').fill(subject);
  await page.getByLabel('Tell us a little about it').fill(message);
  await page.getByRole('button', { name: 'Send inquiry', exact: true }).click();
  await expect(page.getByRole('status')).toContainText('Your inquiry has been received');
  await page.goto('/login'); await page.getByLabel('Email address').fill('admin@example.test'); await page.getByLabel('Password', { exact: true }).fill('Browser-test-password-42'); await page.getByRole('button', { name: 'Sign in', exact: true }).click(); await expect(page).toHaveURL(/\/home$/);
  await page.goto('/contacts');
  const row = page.getByRole('row').filter({ hasText: name });
  await page.getByRole('combobox', { name: 'Filter by status', exact: true }).selectOption('0');
  await page.getByRole('searchbox', { name: 'Search Contact inquiries', exact: true }).fill(name);
  await expect(page.getByLabel('Applied filters')).toContainText(`Search: ${name}`);
  await page.getByRole('button', { name: 'Sort by Subject', exact: true }).click();
  await expect(row).toHaveClass('is-unread-inquiry');
  await expect(row.getByText('Unread', { exact: true })).toHaveClass('workspace-badge is-unread');
  for (const theme of ['Light', 'Dark']) {
    await page.getByRole('button', { name: `${theme} theme`, exact: true }).click();
    const dot = row.locator('.workspace-badge.is-unread>span');
    await expect(dot).toBeVisible();
    await expect(dot).toHaveCSS('width', '5px');
    await expect(dot).toHaveCSS('height', '5px');
    await expect(dot).toHaveCSS('flex-shrink', '0');
    await expect(dot).toHaveCSS('border-radius', '50%');
    const dotColor = await dot.evaluate(element => { const probe = document.createElement('span'); probe.style.color = 'var(--ws-accent)'; element.append(probe); const color = getComputedStyle(probe).color; probe.remove(); return { color, background: getComputedStyle(element).backgroundColor }; });
    expect(dotColor.background).toBe(dotColor.color);
    const normal = await row.evaluate(element => {
      const cell = element.querySelector('td')!;
      return { background: getComputedStyle(element).backgroundColor, marker: getComputedStyle(cell).boxShadow,
        senderWeight: getComputedStyle(element.querySelector('.table-record-title')!).fontWeight,
        subjectWeight: getComputedStyle(element.querySelector('.table-inquiry-subject')!).fontWeight };
    });
    expect(normal.background).not.toBe('rgba(0, 0, 0, 0)'); expect(normal.marker).not.toBe('none');
    expect(normal.senderWeight).toBe('400'); expect(normal.subjectWeight).toBe('400');
    await row.hover();
    expect(await row.evaluate(element => getComputedStyle(element.querySelector('td')!).boxShadow)).toBe(normal.marker);
    await page.screenshot({ path: testInfo.outputPath(`unread-inquiry-${theme.toLowerCase()}.png`), fullPage: true });
  }
  await page.getByRole('button', { name: 'Reset filters', exact: true }).click();
  await row.getByRole('link', { name: `View ${name}`, exact: true }).click();
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Contact inquiry');
  const article = page.getByRole('article', { name: subject, exact: true });
  await expect(article).toBeVisible();
  await expect(article.locator('.workspace-message-body')).toHaveText(message);
  expect(await article.locator('script').count()).toBe(0);
  expect(await article.getByRole('link', { name: 'Reply', exact: true }).getAttribute('href')).toBe(`mailto:reader%2Btag@example.test?subject=${encodeURIComponent(`Re: ${subject}`)}`);
  await expect(article.locator('time')).toBeVisible();
  await expect(page.locator('.workspace-record-details')).toHaveCount(0);
  for (const width of [390, 768, 1440]) {
    await page.setViewportSize({ width, height: 900 });
    for (const theme of ['Light', 'Dark']) {
      await page.getByRole('button', { name: `${theme} theme`, exact: true }).click();
      await expect(page.locator('.workspace')).toHaveAttribute('data-theme', theme.toLowerCase());
      expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBeTruthy();
      const layout = await article.evaluate(element => {
        const body = element.querySelector('.workspace-message-body')!;
        const style = getComputedStyle(body);
        return { padding: style.paddingLeft, color: style.color, expected: getComputedStyle(element).getPropertyValue('--ws-ink').trim(), wraps: style.whiteSpace };
      });
      expect(layout.padding).toBe(width < 640 ? '16px' : '24px'); expect(layout.wraps).toBe('pre-wrap');
      const reply = article.getByRole('link', { name: 'Reply', exact: true });
      await reply.focus(); await page.keyboard.press('Tab'); await page.keyboard.press('Shift+Tab'); await expect(reply).toBeFocused();
      expect(await reply.evaluate(element => getComputedStyle(element).outlineStyle)).toBe('solid');
      if (width === (isMobile ? 390 : 1440)) await page.screenshot({ path: testInfo.outputPath(`contact-message-${theme.toLowerCase()}.png`), fullPage: true });
    }
  }
  await page.getByRole('link', { name: 'Back to contact inquiries', exact: true }).click();
  await expect(row).toContainText('Read');
  await expect(row).not.toHaveClass(/is-unread-inquiry/);
  await expect(row.getByText('Read', { exact: true })).toHaveClass('table-read-status');
  for (const selector of ['.table-record-title', '.table-inquiry-subject']) await expect(row.locator(selector)).toHaveCSS('font-weight', '400');
  await expect(row.locator('.workspace-badge')).toHaveCount(0);
  for (const width of [390, 1280]) {
    await page.setViewportSize({ width, height: 900 });
    for (const theme of ['Light', 'Dark']) {
      await page.getByRole('button', { name: `${theme} theme`, exact: true }).click();
      const readDot = row.locator('.table-read-status>span');
      await expect(readDot).toBeVisible();
      await expect(readDot).toHaveCSS('width', '5px');
      await expect(readDot).toHaveCSS('height', '5px');
      await expect(readDot).toHaveCSS('flex-shrink', '0');
      await expect(readDot).toHaveCSS('border-radius', '50%');
      const colors = await readDot.evaluate(element => ({ dot: getComputedStyle(element).backgroundColor, text: getComputedStyle(element.parentElement!).color }));
      expect(colors.dot).toBe(colors.text);
    }
  }
  await page.getByRole('combobox', { name: 'Filter by status', exact: true }).selectOption('0');
  await expect(row).toHaveCount(0);
  await page.getByRole('combobox', { name: 'Filter by status', exact: true }).selectOption('1');
  await expect(row).toBeVisible();
  await row.getByRole('button', { name: `Delete ${name}`, exact: true }).click(); await page.getByRole('button', { name: 'Delete record', exact: true }).click(); await expect(row).toHaveCount(0);
});

test('social sign-in buttons and connected accounts support provider redirects and cancellation', async ({ page }, testInfo) => {
  let providerRedirect = '';
  await page.route('**/login/{google,linkedin,github}', async route => {
    const response = await route.fetch({ maxRedirects: 0 });
    expect(response.status()).toBe(302);
    providerRedirect = response.headers().location;
    await route.fulfill({ status: 200, contentType: 'text/html', body: '<!doctype html><title>Synthetic consent</title><p id=provider-redirect>Provider redirect captured.</p>' });
  });
  for (const host of ['accounts.google.com', 'github.com', 'www.linkedin.com']) {
    await page.route(`https://${host}/**`, route => route.fulfill({ status: 200, contentType: 'text/html', body: '<!doctype html><title>Synthetic OAuth consent</title><p>Provider redirect intercepted for this test.</p>' }));
  }
  for (const path of ['/login', '/register']) {
    await page.goto(path);
    for (const name of ['Google', 'LinkedIn', 'GitHub']) await expect(page.getByRole('link', { name: `Continue with ${name}`, exact: true })).toHaveAttribute('href', `/login/${name.toLowerCase()}`);
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBeTruthy();
    const center = await page.locator('main form').evaluate(form => { const box = form.parentElement!.getBoundingClientRect(); return box.left + box.width / 2 - innerWidth / 2; });
    expect(Math.abs(center)).toBeLessThan(1);
    expect(await page.content()).not.toContain('browser-test-secret');
    await page.screenshot({ path: testInfo.outputPath(`social-${path.slice(1)}.png`), fullPage: true });
  }
  for (const [name, host] of [['Google', 'accounts.google.com'], ['LinkedIn', 'www.linkedin.com'], ['GitHub', 'github.com']]) {
    await page.goto('/login');
    await page.getByRole('link', { name: `Continue with ${name}`, exact: true }).click();
    await expect(page.locator('#provider-redirect')).toBeVisible();
    expect(new URL(providerRedirect).hostname).toBe(host);
    expect(new URL(providerRedirect).searchParams.get('state')).toBeTruthy();
    await page.goto(`/login/${name.toLowerCase()}/callback?error=access_denied`);
    await expect(page).toHaveURL(/\/login$/);
    await expect(page.getByRole('alert')).toContainText('cancelled or declined');
  }
  await page.getByLabel('Email address').fill('admin@example.test'); await page.getByLabel('Password', { exact: true }).fill('Browser-test-password-42'); await page.getByRole('button', { name: 'Sign in', exact: true }).click(); await expect(page).toHaveURL(/\/home$/);
  await page.goto('/account/settings');
  const connections = page.locator('section.account-card').filter({ has: page.getByRole('heading', { name: 'Connected accounts', exact: true }) });
  await expect(connections.getByText('Connected', { exact: true })).toHaveCount(1);
  await expect(connections.getByRole('button', { name: 'Connect Google', exact: true })).toHaveCount(0);
  await expect(connections.getByRole('button', { name: 'Connect LinkedIn', exact: true })).toBeVisible();
  await expect(connections.getByRole('button', { name: 'Connect GitHub', exact: true })).toBeVisible();
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBeTruthy();
  expect(await page.content()).not.toContain('browser-google-identity');
  await page.screenshot({ path: testInfo.outputPath('connected-accounts.png'), fullPage: true });
  await connections.getByRole('button', { name: 'Connect GitHub', exact: true }).click();
  await expect.poll(() => new URL(page.url()).hostname).toBe('github.com');
  await page.goto('/login/github/callback?error=access_denied');
  await expect(page).toHaveURL(/\/account\/settings$/);
  await expect(connections.getByRole('alert')).toContainText('cancelled or declined');
});

test('admins compose reusable notices and the queue delivers individually to selected users', async ({ page }, testInfo) => {
  test.setTimeout(60000);
  const { readFileSync } = await import('node:fs');
  const captured = () => readFileSync(process.env.ITECHORBIT_TEST_MAILBOX_PATH!, 'utf8').split('\n').filter(Boolean).map(line => JSON.parse(line) as { to: string[]; body: string });
  const name = `Synthetic notice ${testInfo.project.name}`;
  await page.goto('/login'); await page.getByLabel('Email address').fill('admin@example.test'); await page.getByLabel('Password', { exact: true }).fill('Browser-test-password-42'); await page.getByRole('button', { name: 'Sign in', exact: true }).click(); await expect(page).toHaveURL(/\/home$/);
  await page.goto('/workspace/email-notices');
  await expect(page).toHaveURL(/\/workspace\/email-notices\/compose$/);
  if (testInfo.project.name === 'mobile') await page.getByRole('button', { name: 'Open workspace navigation', exact: true }).click();
  const order = await page.getByRole('navigation', { name: 'Workspace navigation', exact: true }).evaluate(nav => [...nav.children].slice(0, 3).map(element => element.textContent));
  expect(order[0]).toContain('Overview'); expect(order[0]).toContain('Portfolio templates'); expect(order[1]).toContain('Administration');
  const sidebar = page.getByRole('navigation', { name: 'Workspace navigation', exact: true });
  await expect(sidebar.getByRole('link', { name: 'Compose', exact: true })).toHaveAttribute('aria-current', 'page');
  await expect(sidebar.getByRole('link', { name: 'Templates', exact: true })).toHaveCount(1);
  await expect(sidebar.getByRole('link', { name: 'Send history', exact: true })).toHaveCount(1);
  if (testInfo.project.name === 'mobile') await page.getByRole('button', { name: 'Close workspace navigation', exact: true }).click();
  await page.getByRole('navigation', { name: 'Email notice pages', exact: true }).getByRole('link', { name: 'Templates', exact: true }).click();
  await page.getByLabel('Template name', { exact: true }).fill(name);
  await page.getByLabel('Template subject', { exact: true }).fill(name);
  await expect(page.locator('.ck-editor__editable')).toHaveCount(1);
  await page.locator('.ck-editor__editable').fill('A synthetic update for selected accounts.\nSecond paragraph.');
  await page.getByRole('button', { name: 'Save template', exact: true }).click();
  await expect(page.getByRole('status')).toContainText('Template saved.');
  const template = page.locator('.notice-template-list>li').filter({ hasText: name });
  await template.getByRole('button', { name: 'Use template', exact: true }).click();
  await expect(page).toHaveURL(/\/workspace\/email-notices\/compose\?template=\d+$/);
  await expect(page.getByLabel('Subject', { exact: true })).toHaveValue(name);
  await page.getByRole('radio', { name: 'Selected users', exact: true }).check();
  await page.getByLabel('Find users by name or email', { exact: true }).fill('notice-studio@');
  await expect(page.locator('.notice-recipient-list')).toContainText('notice-studio@example.test');
  await page.locator('.notice-recipient-list').getByRole('checkbox').check();
  await page.getByLabel('Find users by name or email', { exact: true }).fill('notice-blueprint@');
  await expect(page.locator('.notice-recipient-list')).toContainText('notice-blueprint@example.test');
  await page.locator('.notice-recipient-list').getByRole('checkbox').check();
  await expect(page.locator('.notice-recipient-count')).toContainText('2 recipients selected');
  let releasePreview!: () => void;
  const previewGate = new Promise<void>(resolve => { releasePreview = resolve; });
  await page.route('**/workspace/email-notices/preview', async route => { await previewGate; await route.continue(); }, { times: 1 });
  await page.getByRole('button', { name: 'Preview email', exact: true }).click();
  await expect(page.getByRole('button', { name: 'Previewing…', exact: true })).toBeDisabled();
  await expect(page.getByRole('button', { name: 'Send notice', exact: true })).toBeDisabled();
  releasePreview();
  await expect(page.frameLocator('iframe[title="Notice email preview"]').getByRole('heading', { name, exact: true })).toBeVisible();
  await expect(page.frameLocator('iframe[title="Notice email preview"]').getByText('Dear iTechOrbit member,', { exact: true })).toBeVisible();
  await expect(page.frameLocator('iframe[title="Notice email preview"]').getByText('The iTechOrbit Team', { exact: true })).toBeVisible();
  await expect(page.getByRole('button', { name: 'Send notice', exact: true })).toBeEnabled();
  await expect(page.locator('.notice-compose-feedback')).toContainText('Ready to send.');
  await page.getByLabel('Subject', { exact: true }).fill(name + ' updated');
  await expect(page.locator('.notice-compose-feedback')).toContainText('Preview again before sending.');
  await expect(page.getByRole('button', { name: 'Send notice', exact: true })).toBeDisabled();
  await page.getByLabel('Subject', { exact: true }).fill(name);
  for (const theme of ['Light', 'Dark']) {
    await page.getByRole('button', { name: `${theme} theme`, exact: true }).click();
    await expect(page.locator('.workspace')).toHaveAttribute('data-theme', theme.toLowerCase());
    await page.locator('.notice-compose-action').evaluateAll(async buttons => {
      await Promise.all(buttons.flatMap(button => button.getAnimations().map(animation => animation.finished.catch(() => {}))));
    });
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBeTruthy();
    for (const action of ['Preview email', 'Send notice']) {
      await page.getByRole('button', { name: action, exact: true }).hover();
      await expect.poll(() => page.getByRole('button', { name: action, exact: true }).evaluate(button => {
        const luminance = (color: string) => {
          const rgb = color.match(/[\d.]+/g)!.slice(0, 3).map(Number).map(value => { const channel = value / 255; return channel <= 0.04045 ? channel / 12.92 : ((channel + 0.055) / 1.055) ** 2.4; });
          return rgb[0] * 0.2126 + rgb[1] * 0.7152 + rgb[2] * 0.0722;
        };
        const style = getComputedStyle(button); const foreground = luminance(style.color); const background = luminance(style.backgroundColor);
        return (Math.max(foreground, background) + 0.05) / (Math.min(foreground, background) + 0.05);
      })).toBeGreaterThanOrEqual(4.5);
    }
    await page.getByRole('button', { name: 'Preview email', exact: true }).focus();
    const sendButton = page.getByRole('button', { name: 'Send notice', exact: true });
    for (let attempt = 0; attempt < 4 && !await sendButton.evaluate(button => button === document.activeElement); attempt++) await page.keyboard.press('Tab');
    await expect(sendButton).toBeFocused();
    await expect(sendButton).toHaveCSS('outline-style', 'solid');
    await page.screenshot({ path: testInfo.outputPath(`notice-compose-${theme.toLowerCase()}.png`), fullPage: true, animations: 'disabled' });
  }
  let releaseSend!: () => void;
  const sendGate = new Promise<void>(resolve => { releaseSend = resolve; });
  await page.route('**/workspace/email-notices/send', async route => { await sendGate; await route.continue(); }, { times: 1 });
  await page.getByRole('button', { name: 'Send notice', exact: true }).click();
  await expect(page.getByRole('button', { name: 'Sending…', exact: true })).toBeDisabled();
  releaseSend();
  await expect(page).toHaveURL(/\/workspace\/email-notices\/history\?campaign=\d+$/);
  await expect(page.getByRole('status')).toContainText('Notice queued.');
  await expect.poll(() => captured().filter(mail => mail.body.includes(name)).length, { timeout: 20000 }).toBe(2);
  const messages = captured().filter(mail => mail.body.includes(name));
  for (const message of messages) {
    const recipientName = message.to[0] === 'notice-studio@example.test' ? 'Studio' : 'Blueprint';
    const unfoldedBody = message.body.replace(/=\r\n/g, '');
    expect(unfoldedBody).toContain(`Dear Notice recipient ${recipientName},`);
    expect(unfoldedBody).toContain('Kind regards,');
    expect(unfoldedBody).toContain('The iTechOrbit Team');
    expect(unfoldedBody).not.toContain('Hello Recipient name');
  }
  expect(messages.map(mail => mail.to[0]).sort()).toEqual(['notice-blueprint@example.test', 'notice-studio@example.test']);
  expect(messages.every(mail => mail.to.length === 1 && !/\r\n(?:Cc|Bcc):/i.test(mail.body))).toBeTruthy();
  await expect.poll(() => page.locator('.notice-counts>div').filter({ hasText: 'Sent' }).innerText(), { timeout: 15000 }).toContain('2');
  await expect(page.getByRole('table', { name: 'Recipient send results', exact: true })).toContainText('Sent');
  await page.screenshot({ path: testInfo.outputPath('notice-send-history.png'), fullPage: true });
  await page.getByRole('navigation', { name: 'Email notice pages', exact: true }).getByRole('link', { name: 'Templates', exact: true }).click();
  await template.getByRole('button', { name: 'Edit', exact: true }).click();
  await page.getByLabel('Template subject', { exact: true }).fill(name + ' revised');
  await page.getByRole('button', { name: 'Save template', exact: true }).click();
  await expect(page.getByRole('status')).toContainText('Template saved.');
  await template.getByRole('button', { name: `Delete template ${name}`, exact: true }).click();
  await page.getByRole('button', { name: 'Delete template', exact: true }).click();
  await expect(template).toHaveCount(0);
  await page.getByRole('navigation', { name: 'Email notice pages', exact: true }).getByRole('link', { name: 'Send history', exact: true }).click();
  await expect(page.locator('.notice-history-list')).toContainText(name);
  expect(captured().filter(mail => mail.body.includes(name)).length).toBe(2);
  await page.getByRole('navigation', { name: 'Email notice pages', exact: true }).getByRole('link', { name: 'Compose', exact: true }).click();
  await expect(page.getByLabel('Subject', { exact: true })).toHaveValue('');
  await page.getByRole('radio', { name: 'All eligible users (3)', exact: true }).check();
  await page.getByLabel('Subject', { exact: true }).fill(`${name} everyone`);
  await page.locator('.ck-editor__editable').fill('A synthetic update for all eligible accounts.');
  await page.getByRole('button', { name: 'Preview email', exact: true }).click();
  await expect(page.getByRole('button', { name: 'Send notice', exact: true })).toBeEnabled();
  await page.getByRole('button', { name: 'Send notice', exact: true }).click();
  await expect.poll(() => captured().filter(mail => mail.body.includes(`${name} everyone`)).length, { timeout: 20000 }).toBe(3);
  expect(captured().filter(mail => mail.body.includes(`${name} everyone`)).map(mail => mail.to[0]).sort()).toEqual(['admin@example.test', 'notice-blueprint@example.test', 'notice-studio@example.test']);
});

test('email notice pages preserve drafts during navigation and support direct refresh', async ({ page, isMobile }, testInfo) => {
  await page.goto('/login');
  await page.getByLabel('Email address').fill('admin@example.test');
  await page.getByLabel('Password', { exact: true }).fill('Browser-test-password-42');
  await page.getByRole('button', { name: 'Sign in', exact: true }).click();
  await expect(page).toHaveURL(/\/home$/);
  await page.goto('/workspace/email-notices/compose');
  const pages = page.getByRole('navigation', { name: 'Email notice pages', exact: true });
  await expect(page.getByRole('heading', { name: 'Compose', level: 1, exact: true })).toBeVisible();
  await page.getByLabel('Subject', { exact: true }).fill('Unfinished notice');
  await page.locator('.ck-editor__editable').fill('Preserve this compose draft.');
  await pages.getByRole('link', { name: 'Templates', exact: true }).click();
  await expect(page).toHaveURL(/\/email-notices\/templates$/);
  await expect(page.getByRole('heading', { name: 'Templates', level: 1, exact: true })).toBeVisible();
  await pages.getByRole('link', { name: 'Compose', exact: true }).click();
  await expect(page.getByLabel('Subject', { exact: true })).toHaveValue('Unfinished notice');
  await pages.getByRole('link', { name: 'Templates', exact: true }).click();
  await page.getByLabel('Template name', { exact: true }).fill('Unfinished template');
  await page.getByLabel('Template subject', { exact: true }).fill('Draft template subject');
  await page.locator('.ck-editor__editable').fill('Preserve this template draft.');
  await pages.getByRole('link', { name: 'Send history', exact: true }).click();
  await expect(page).toHaveURL(/\/email-notices\/history$/);
  await expect(page.locator('.ck-editor__editable')).toHaveCount(0);
  await expect(page.getByRole('heading', { name: 'Send history', level: 1, exact: true })).toBeVisible();
  await pages.getByRole('link', { name: 'Compose', exact: true }).click();
  await expect(page.getByLabel('Subject', { exact: true })).toHaveValue('Unfinished notice');
  await expect(page.locator('.ck-editor__editable')).toContainText('Preserve this compose draft.');
  await pages.getByRole('link', { name: 'Templates', exact: true }).click();
  await expect(page.getByLabel('Template name', { exact: true })).toHaveValue('Unfinished template');
  await expect(page.getByLabel('Template subject', { exact: true })).toHaveValue('Draft template subject');
  await expect(page.locator('.ck-editor__editable')).toContainText('Preserve this template draft.');
  await page.goBack();
  await expect(page.getByLabel('Subject', { exact: true })).toHaveValue('Unfinished notice');
  await page.goForward();
  await expect(page.getByLabel('Template name', { exact: true })).toHaveValue('Unfinished template');
  for (const section of ['Compose', 'Templates', 'Send history']) {
    if (isMobile) await page.getByRole('button', { name: 'Open workspace navigation', exact: true }).click();
    const sidebar = page.getByRole('navigation', { name: 'Workspace navigation', exact: true });
    await sidebar.getByRole('link', { name: section, exact: true }).click();
    if (isMobile) await page.getByRole('button', { name: 'Open workspace navigation', exact: true }).click();
    await expect(sidebar.locator('a[aria-current="page"]')).toHaveCount(1);
    await expect(sidebar.getByRole('link', { name: section, exact: true })).toHaveAttribute('aria-current', 'page');
    await expect(sidebar.getByText('Email notices', { exact: true })).toBeVisible();
    if (isMobile) await page.getByRole('button', { name: 'Close workspace navigation', exact: true }).click();
    for (const theme of ['Light', 'Dark']) {
      await page.getByRole('button', { name: `${theme} theme`, exact: true }).click();
      expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBeTruthy();
      await page.screenshot({ path: testInfo.outputPath(`notices-${section.toLowerCase().replace(' ', '-')}-${theme.toLowerCase()}.png`), fullPage: true });
    }
  }
  for (const [path, title] of [['compose', 'Compose'], ['templates', 'Templates'], ['history', 'Send history']]) {
    await page.goto(`/workspace/email-notices/${path}`);
    await page.reload();
    await expect(page.getByRole('heading', { name: title, level: 1, exact: true })).toBeVisible();
  }
});

test('sidebar groups modules and links follow navigation management changes', async ({ page, isMobile }, testInfo) => {
  const suffix = testInfo.project.name;
  const group = `Synthetic section ${suffix}`;
  const module = `Synthetic module ${suffix}`;
  const link = `Write an update ${suffix}`;
  await page.goto('/login'); await page.getByLabel('Email address').fill('admin@example.test'); await page.getByLabel('Password', { exact: true }).fill('Browser-test-password-42'); await page.getByRole('button', { name: 'Sign in', exact: true }).click(); await expect(page).toHaveURL(/\/home$/);
  await page.goto('/menus/create');
  await page.getByLabel('Name *', { exact: true }).fill(group);
  await page.getByLabel('Order number', { exact: true }).fill('2');
  await page.getByLabel('Published / active', { exact: true }).check();
  await page.getByRole('button', { name: 'Save changes', exact: true }).click();
  await expect(page).toHaveURL(/\/menus$/);
  const groupEdit = await page.getByRole('link', { name: `Edit ${group}`, exact: true }).getAttribute('href');
  await page.goto('/modules/create');
  await page.getByLabel('Name *', { exact: true }).fill(module);
  await page.getByLabel('Menu *', { exact: true }).selectOption({ label: group });
  await page.getByLabel('Published / active', { exact: true }).check();
  await page.getByRole('button', { name: 'Save changes', exact: true }).click();
  await expect(page).toHaveURL(/\/modules$/);
  const moduleEdit = await page.getByRole('link', { name: `Edit ${module}`, exact: true }).getAttribute('href');
  await page.goto('/links/create');
  await page.getByLabel('Name *', { exact: true }).fill(link);
  await page.getByLabel('Resource path *', { exact: true }).fill('workspace/email-notices/compose');
  await page.getByLabel('Module *', { exact: true }).selectOption({ label: module });
  await page.getByLabel('Published / active', { exact: true }).check();
  await page.getByRole('button', { name: 'Save changes', exact: true }).click();
  await expect(page).toHaveURL(/\/links$/);
  await page.getByRole('searchbox', { name: 'Search Navigation links', exact: true }).fill(link);
  const linkEdit = await page.getByRole('link', { name: `Edit ${link}`, exact: true }).getAttribute('href');
  if (isMobile) await page.getByRole('button', { name: 'Open workspace navigation', exact: true }).click();
  await page.getByLabel('Find a module', { exact: true }).fill(link);
  const sidebar = page.getByRole('navigation', { name: 'Workspace navigation', exact: true });
  await expect(sidebar.getByText(group, { exact: true })).toBeVisible();
  await expect(sidebar.getByText(module, { exact: true })).toBeVisible();
  await sidebar.getByRole('link', { name: link, exact: true }).click();
  await expect(page).toHaveURL(/\/workspace\/email-notices\/compose$/);
  await expect(page.getByRole('heading', { name: 'Compose', level: 1, exact: true })).toBeVisible();
  await page.goto(groupEdit!);
  await page.getByLabel('Name *', { exact: true }).fill(group + ' renamed');
  await page.getByRole('button', { name: 'Save changes', exact: true }).click();
  await expect(page).toHaveURL(/\/menus$/);
  if (isMobile) await page.getByRole('button', { name: 'Open workspace navigation', exact: true }).click();
  await expect(sidebar.getByText(group + ' renamed', { exact: true })).toBeVisible();
  if (isMobile) await page.getByRole('button', { name: 'Close workspace navigation', exact: true }).click();
  await page.goto(moduleEdit!);
  await page.getByLabel('Published / active', { exact: true }).uncheck();
  await page.getByRole('button', { name: 'Save changes', exact: true }).click();
  await expect(page).toHaveURL(/\/modules$/);
  await expect(sidebar.getByRole('link', { name: link, exact: true })).toHaveCount(0);
  await page.goto('/workspace/email-notices/compose');
  await expect(page.getByRole('heading', { name: 'Compose', level: 1, exact: true })).toBeVisible();
  const csrf = await page.locator('meta[name="csrf-token"]').getAttribute('content');
  for (const path of [linkEdit, moduleEdit, groupEdit]) {
    const response = await page.request.delete(path!.replace(/\/edit$/, ''), { headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf! } });
    expect(response.status()).toBe(200);
  }
});
