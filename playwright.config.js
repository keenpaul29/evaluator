import { defineConfig } from '@playwright/test';

// CI installs the bundled Chromium. Locally the Playwright browser download is
// often blocked, so fall back to the system Chrome (override with E2E_BROWSER=chromium).
const useSystemChrome = process.env.E2E_BROWSER !== 'chromium';

export default defineConfig({
  testDir: './e2e',
  timeout: 60_000,
  expect: { timeout: 10_000 },
  fullyParallel: false,
  workers: 1,
  retries: process.env.CI ? 1 : 0,
  reporter: process.env.CI
    ? [['github'], ['list']]
    : [['list']],
  use: {
    baseURL: 'http://127.0.0.1:8123',
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
    ...(useSystemChrome ? { channel: 'chrome' } : {}),
  },
  webServer: {
    command: 'php -S 127.0.0.1:8123 -t public public/index.php',
    url: 'http://127.0.0.1:8123/health',
    reuseExistingServer: !process.env.CI,
    timeout: 60_000,
    env: {
      APP_ENV: 'testing',
    },
  },
});
