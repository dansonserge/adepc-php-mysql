// Runs the original site suite against the PHP site (Docker stack on :8080).
//   cd tools/e2e && npx playwright test   (node_modules links to ../adepc's dev tools)
import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
  testDir: '.',
  testMatch: 'site.spec.mjs',
  fullyParallel: true,
  workers: 4,
  reporter: 'list',
  use: { baseURL: process.env.BASE_URL ?? 'http://localhost:8080', trace: 'off' },
  projects: [
    { name: 'desktop', use: { ...devices['Desktop Chrome'], viewport: { width: 1440, height: 900 } } },
    { name: 'mobile', use: { ...devices['Pixel 7'] } },
  ],
});
