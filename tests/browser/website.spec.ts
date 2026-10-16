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
  await expect(page.getByRole('button', { name: 'Open workspace navigation', exact: true })).toHaveAttribute('aria-expanded', 'false');
  await expect(page.locator('.workspace-sidebar')).toBeHidden();
  await page.getByRole('button', { name: 'Open workspace navigation', exact: true }).click();
  const workspaceLogo = page.getByRole('link', { name: 'iTechOrbit workspace', exact: true }).getByRole('img');
  await expect(workspaceLogo).toBeVisible();
  await expect.poll(() => workspaceLogo.evaluate((image: HTMLImageElement) => image.complete && image.naturalWidth > 0)).toBeTruthy();
  await page.getByRole('button', { name: 'Close workspace navigation', exact: true }).click();
  await expect(page.locator('.workspace-sidebar')).toBeHidden();
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

test('workspace tables load server-side search sorting pagination and recover from errors', async ({ page }, testInfo) => {
  await page.goto('/login'); await page.getByLabel('Email address').fill('admin@example.test'); await page.getByLabel('Password', { exact: true }).fill('Browser-test-password-42'); await page.getByRole('button', { name: 'Sign in', exact: true }).click(); await expect(page).toHaveURL(/\/home$/);
  await page.getByRole('button', { name: 'Dark theme', exact: true }).click();
  await expect(page.locator('.workspace')).toHaveAttribute('data-theme', 'dark');
  await page.reload();
  await expect(page.locator('.workspace')).toHaveAttribute('data-theme', 'dark');
  await page.screenshot({ path: testInfo.outputPath('admin-dashboard.png'), fullPage: true });
  await expect(page.getByRole('button', { name: 'Open workspace navigation', exact: true })).toHaveAttribute('aria-expanded', 'false');
  await expect(page.locator('.workspace-sidebar')).toBeHidden();
  await page.getByRole('button', { name: 'Open workspace navigation', exact: true }).click();
  await expect(page.getByRole('navigation', { name: 'Workspace navigation' }).getByText('Test workspace', { exact: true })).toBeVisible();
  await expect(page.locator('.workspace-module-label').first()).toHaveText('Module');
  await expect(page.locator('.workspace-nav-group>p small').first()).toHaveText('Category');
  const sidebarLabels = await page.locator('.workspace-nav-module .workspace-nav-link span:first-of-type').allTextContents();
  expect(sidebarLabels.length).toBeGreaterThan(1);
  expect(sidebarLabels).toEqual([...sidebarLabels].sort((left, right) => left.localeCompare(right, 'en', { numeric: true, sensitivity: 'base' })));
  await page.getByLabel('Find a module').fill('Articles');
  await expect(page.getByRole('navigation', { name: 'Workspace navigation' }).getByRole('link', { name: 'Articles', exact: true })).toBeVisible();
  await expect(page.getByRole('navigation', { name: 'Workspace navigation' }).getByRole('link', { name: 'Categories', exact: true })).toHaveCount(0);
  await page.getByLabel('Find a module').fill('');
  await page.keyboard.press('Escape');
  await expect(page.locator('.workspace-sidebar')).toBeHidden();
  await expect(page.getByRole('button', { name: 'Open workspace navigation', exact: true })).toBeFocused();
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
  await body.click(); await body.press('ControlOrMeta+A');
  await page.getByRole('combobox', { name: 'Body font size', exact: true }).selectOption('20');
  await page.getByRole('combobox', { name: 'Body font style', exact: true }).selectOption('serif');
  await page.getByRole('toolbar', { name: 'Body formatting' }).getByRole('button', { name: 'Insert link', exact: true }).click();
  await page.getByLabel('Link URL', { exact: true }).fill('https://example.com/careers');
  await page.getByRole('button', { name: 'Apply link', exact: true }).click();
  await expect(body.locator('span[data-font-size="20"][data-text-font="serif"]')).toHaveText('Clear engineering decisions');
  await expect(body.getByRole('link')).toHaveAttribute('href', 'https://example.com/careers');
  await page.getByRole('button', { name: 'Dark theme', exact: true }).click();

  await page.getByLabel('Title *', { exact: true }).fill('x'.repeat(201));
  await page.getByRole('button', { name: 'Save changes', exact: true }).click();
  await expect(page.locator('#title-error')).toContainText('200');
  await expect(page.getByRole('textbox', { name: 'Body *', exact: true })).toContainText('Clear engineering decisions');
  await page.getByLabel('Title *', { exact: true }).fill(title);
  await page.evaluate(() => window.scrollTo({ top: 0, behavior: 'instant' }));
  await page.screenshot({ path: testInfo.outputPath('admin-editor-dark.png'), fullPage: true });
  await page.getByRole('button', { name: 'Light theme', exact: true }).click();
  await page.screenshot({ path: testInfo.outputPath('admin-editor.png'), fullPage: true });
  await page.getByRole('button', { name: 'Save changes', exact: true }).click(); await expect(page).toHaveURL(/\/posts$/);
  await expect(page.getByRole('status')).toContainText('Post created successfully');
  await page.getByRole('link', { name: `View ${title}`, exact: true }).click();
  await expect(page.locator('.workspace-record-details h2')).toHaveText('Clear engineering decisions');
  await expect(page.locator('.workspace-record-details strong')).toHaveText('Clear engineering decisions');
  await page.getByRole('button', { name: 'Dark theme', exact: true }).click();
  const headingColor = await page.locator('.workspace-record-details h2').evaluate(node => getComputedStyle(node).color);
  expect(headingColor).toBe(await page.locator('.workspace').evaluate(node => getComputedStyle(node).color));
  const articleId = page.url().split('/').at(-1);
  await page.goto(`/blogs/${articleId}`);
  await expect(page.locator('.workspace')).toHaveCount(0);
  await expect(page.locator('.prose-content h2')).toHaveText('Clear engineering decisions');
  const formatted = page.locator('.prose-content span[data-font-size="20"][data-text-font="serif"]');
  await expect(formatted).toHaveText('Clear engineering decisions');
  await expect(formatted).toHaveCSS('font-size', '20px');
  await expect(page.locator('.prose-content a')).toHaveAttribute('href', 'https://example.com/careers');
  await page.goto(`/posts/${articleId}/edit`);
  await expect(page.getByRole('textbox', { name: 'Body *', exact: true }).locator('span[data-font-size="20"][data-text-font="serif"]')).toHaveText('Clear engineering decisions');
  await page.goto('/posts'); await page.getByRole('button', { name: `Delete ${title}`, exact: true }).click(); await page.getByRole('button', { name: 'Delete record', exact: true }).click(); await expect(page.getByRole('button', { name: `Delete ${title}`, exact: true })).toHaveCount(0);
});

test('portfolio template studio previews five layouts and persists the public selection', async ({ page, request, isMobile }, testInfo) => {
  test.setTimeout(60000); // Five themes, twenty section-anchor checks and public return navigation.
  const errors: string[] = []; page.on('pageerror', error => errors.push(error.message));
  await page.goto('/login'); await page.getByLabel('Email address').fill('admin@example.test'); await page.getByLabel('Password', { exact: true }).fill('Browser-test-password-42'); await page.getByRole('button', { name: 'Sign in', exact: true }).click(); await expect(page).toHaveURL(/\/home$/);
  await expect(page.getByRole('button', { name: 'Open workspace navigation', exact: true })).toHaveAttribute('aria-expanded', 'false');
  await expect(page.locator('.workspace-sidebar')).toBeHidden();
  await page.getByRole('button', { name: 'Open workspace navigation', exact: true }).click();
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
    await page.getByLabel('Project screenshot', { exact: true }).setInputFiles('docs/assets/business-inquiry-email-desktop.png');
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
