import { test, expect } from '@playwright/test';
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
    if (path === '/login') await page.screenshot({ path: testInfo.outputPath('login-branding.png'), fullPage: true });
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
  await page.screenshot({ path: testInfo.outputPath('admin-dashboard.png'), fullPage: true });
  if (isMobile) await page.getByRole('button', { name: 'Open workspace navigation', exact: true }).click();
  await expect(page.getByRole('navigation', { name: 'Workspace navigation' }).getByText('Test workspace', { exact: true })).toBeVisible();
  const sidebarLabels = await page.locator('.workspace-nav-module .workspace-nav-link span:first-of-type').allTextContents();
  expect(sidebarLabels.length).toBeGreaterThan(1);
  expect(sidebarLabels).toEqual([...sidebarLabels].sort((left, right) => left.localeCompare(right, 'en', { numeric: true, sensitivity: 'base' })));
  await page.getByLabel('Find a module').fill('Articles');
  await expect(page.getByRole('navigation', { name: 'Workspace navigation' }).getByRole('link', { name: 'Articles', exact: true })).toBeVisible();
  await expect(page.getByRole('navigation', { name: 'Workspace navigation' }).getByRole('link', { name: 'Categories', exact: true })).toHaveCount(0);
  await page.getByLabel('Find a module').fill('');
  if (isMobile) await page.keyboard.press('Escape');
  await page.goto('/posts');
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
  await page.screenshot({ path: testInfo.outputPath('admin-table.png'), fullPage: true });
});

test('rich text forms validate save formatted content and retain success feedback', async ({ page, isMobile }, testInfo) => {
  await page.goto('/login'); await page.getByLabel('Email address').fill('admin@example.test'); await page.getByLabel('Password', { exact: true }).fill('Browser-test-password-42'); await page.getByRole('button', { name: 'Sign in', exact: true }).click(); await expect(page).toHaveURL(/\/home$/);
  await page.goto('/posts/create'); const title = `Editor QA ${isMobile ? 'mobile' : 'desktop'}`;
  await page.getByLabel('Title *', { exact: true }).fill(title); await page.getByLabel('Category *', { exact: true }).selectOption({ label: 'Laravel' });
  const body = page.getByRole('textbox', { name: 'Body *', exact: true }); await expect(body).toBeVisible();
  await page.getByRole('button', { name: 'Save changes', exact: true }).click(); await expect(page.getByText('Body is required.', { exact: true })).toBeVisible();
  await body.fill('Clear engineering decisions'); await body.press('ControlOrMeta+A');
  await page.getByRole('toolbar', { name: 'Body formatting' }).getByRole('button', { name: 'Bold', exact: true }).click();
  await page.getByRole('toolbar', { name: 'Body formatting' }).getByRole('button', { name: 'Heading 2', exact: true }).click();
  await page.getByRole('group', { name: 'Body editing mode' }).getByRole('button', { name: 'HTML', exact: true }).click();
  await expect(page.getByRole('textbox', { name: 'Body HTML', exact: true })).toHaveValue(/<h2><strong>Clear engineering decisions<\/strong><\/h2>/);
  await page.getByRole('group', { name: 'Body editing mode' }).getByRole('button', { name: 'Visual', exact: true }).click();
  await page.getByLabel('Title *', { exact: true }).fill('x'.repeat(201));
  await page.getByRole('button', { name: 'Save changes', exact: true }).click();
  await expect(page.locator('#title-error')).toContainText('200');
  await expect(page.getByRole('textbox', { name: 'Body *', exact: true })).toContainText('Clear engineering decisions');
  await page.getByLabel('Title *', { exact: true }).fill(title);
  await page.evaluate(() => window.scrollTo({ top: 0, behavior: 'instant' }));
  await page.screenshot({ path: testInfo.outputPath('admin-editor.png'), fullPage: true });
  await page.getByRole('button', { name: 'Save changes', exact: true }).click(); await expect(page).toHaveURL(/\/posts$/);
  await expect(page.getByRole('status')).toContainText('Post created successfully');
  await page.getByRole('link', { name: `View ${title}`, exact: true }).click();
  await expect(page.locator('.workspace-record-details h2')).toHaveText('Clear engineering decisions');
  await expect(page.locator('.workspace-record-details strong')).toHaveText('Clear engineering decisions');
  const articleId = page.url().split('/').at(-1);
  await page.goto(`/blogs/${articleId}`);
  await expect(page.locator('.prose-content h2')).toHaveText('Clear engineering decisions');
  await page.goto('/posts'); await page.getByRole('button', { name: `Delete ${title}`, exact: true }).click(); await page.getByRole('button', { name: 'Delete record', exact: true }).click(); await expect(page.getByRole('button', { name: `Delete ${title}`, exact: true })).toHaveCount(0);
});
