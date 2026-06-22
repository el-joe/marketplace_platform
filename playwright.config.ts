import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
  timeout: 30000,
  retries: 0,
  reporter: [['html'], ['list']],

  use: {
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
    trace: 'retain-on-failure',
  },

  projects: [
    // ── Admin panel ────────────────────────────────────────────────────────
    {
      name: 'admin-setup',
      testMatch: /admin\/auth\.setup\.ts/,
      use: { baseURL: 'http://admin.noon.loc' },
    },
    {
      name: 'admin',
      testMatch: /admin\/(?!auth\.setup).*\.spec\.ts/,
      use: {
        ...devices['Desktop Chrome'],
        baseURL: 'http://admin.noon.loc',
        storageState: 'tests/admin/.auth/admin.json',
      },
      dependencies: ['admin-setup'],
    },

    // ── Vendor (Partner) panel ─────────────────────────────────────────────
    {
      name: 'vendor-setup',
      testMatch: /vendor\/auth\.setup\.ts/,
      use: { baseURL: 'http://partner.noon.loc' },
    },
    {
      name: 'vendor',
      testMatch: /vendor\/(?!auth\.setup).*\.spec\.ts/,
      use: {
        ...devices['Desktop Chrome'],
        baseURL: 'http://partner.noon.loc',
        storageState: 'tests/vendor/.auth/vendor.json',
      },
      dependencies: ['vendor-setup'],
    },
  ],
});
