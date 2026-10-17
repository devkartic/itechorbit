import { defineConfig, devices } from '@playwright/test';
import { mkdtempSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';

// Inherit one disposable cache directory in workers and the isolated web server.
process.env.ITECHORBIT_BROWSER_CACHE_PATH ??= mkdtempSync(join(tmpdir(), 'itechorbit-browser-cache-'));
export default defineConfig({
  testDir: './tests/browser', fullyParallel: false, workers: 1, retries: process.env.CI ? 1 : 0,
  reporter: [['list'], ['html', { open: 'never' }]],
  use: { baseURL: 'http://127.0.0.1:8010', trace: 'retain-on-failure', screenshot: 'only-on-failure' },
  projects: [{ name: 'desktop', use: { ...devices['Desktop Chrome'] } }, { name: 'mobile', use: { ...devices['iPhone 13'], defaultBrowserType: 'chromium' } }],
  webServer: { command: 'node tests/browser/server.mjs', url: 'http://127.0.0.1:8010/healthz', reuseExistingServer: false, timeout: 60000 },
});
