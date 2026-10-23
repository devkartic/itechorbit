import { test, expect } from '@playwright/test';
import { readFileSync } from 'node:fs';

const manifest = JSON.parse(readFileSync('public/build/manifest.json', 'utf8')) as Record<string, { file: string; css?: string[]; imports?: string[] }>;
function styles(entry: string, visited = new Set<string>()): string[] {
  if (visited.has(entry)) return [];
  visited.add(entry);
  const chunk = manifest[entry];
  return [...(chunk.css ?? []), ...(chunk.imports ?? []).flatMap(dependency => styles(dependency, visited))];
}

test('initial HTML contains rendered content and all page styles without JavaScript', async ({ browser, baseURL, isMobile }) => {
  const context = await browser.newContext({ baseURL, javaScriptEnabled: false, viewport: { width: isMobile ? 390 : 1280, height: 900 } });
  const page = await context.newPage();
  const routes = [
    ['/', 'Website/Home'], ['/blogs/1', 'Website/Article'], ['/services/laravel-development', 'Website/Service'], ['/products', 'Website/Products'],
    ...['studio', 'executive', 'blueprint', 'editorial', 'spotlight'].map(template => [`/portfolios/SPARSE-${template.toUpperCase()}`, 'Portfolio/Show']),
  ];
  for (const [url, component] of routes) {
    const response = await page.goto(url);
    expect(response?.status()).toBe(200);
    const html = await response!.text();
    expect(html).toMatch(/<main\b/);
    expect(html).toMatch(/<h1\b/);
    const head = html.split('</head>')[0];
    for (const css of styles(`resources/js/pages/${component}.tsx`)) {
      expect(head).toContain(`/build/${css}`);
    }
    expect(head).toContain('as="font"');
    expect(head).not.toContain('CkEditorInput');
    await expect(page.getByRole('heading', { level: 1 }).first()).toBeVisible();
  }
  await context.close();
});

test('the optional assistant waits for the initial document load', async ({ page }) => {
  await page.addInitScript(() => {
    const original = window.fetch;
    Object.assign(window, { assistantLoadStates: [] as string[] });
    window.fetch = (...args) => {
      if (String(args[0]).includes('/ai-pro/local-config')) {
        (window as unknown as { assistantLoadStates: string[] }).assistantLoadStates.push(document.readyState);
      }
      return original(...args);
    };
  });
  await page.goto('/');
  await expect.poll(() => page.evaluate(() => (window as unknown as { assistantLoadStates: string[] }).assistantLoadStates)).toEqual(['complete']);
  await expect(page.getByRole('button', { name: 'Ask iTechOrbit AI' })).toBeVisible();
});
