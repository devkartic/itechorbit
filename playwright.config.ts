import { defineConfig, devices } from '@playwright/test';
import { mkdtempSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';

process.env.ITECHORBIT_TEST_MAILBOX_PATH ??= join(mkdtempSync(join(tmpdir(), 'itechorbit-mailbox-')), 'messages.jsonl');
export default defineConfig({
  testDir: './tests/browser', fullyParallel: false, workers: 1, retries: process.env.CI ? 1 : 0,
  reporter: [['list'], ['html', { open: 'never' }]],
  use: { baseURL: 'http://127.0.0.1:8010', trace: 'retain-on-failure', screenshot: 'only-on-failure' },
  projects: [{ name: 'desktop', use: { ...devices['Desktop Chrome'] } }, { name: 'mobile', use: { ...devices['iPhone 13'], defaultBrowserType: 'chromium' } }],
  webServer: { command: 'node tests/browser/server.mjs', url: 'http://127.0.0.1:8010/healthz', reuseExistingServer: false, timeout: 60000 },
});
