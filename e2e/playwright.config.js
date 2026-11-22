import { defineConfig, devices } from '@playwright/test'

export default defineConfig({
  testDir: './tests',
  globalSetup: './global-setup.js',
  timeout: 15000,
  retries: 1,
  workers: 1, // 同一ユーザーのデータ競合を防ぐため順次実行
  reporter: [['list'], ['html', { open: 'never' }]],

  use: {
    baseURL: 'http://localhost:8765',
    trace: 'on-first-retry',
    storageState: '.auth/state.json',
  },

  projects: [
    // 認証テスト (ログイン画面を直接テスト、セッションなし)
    {
      name: 'auth',
      testMatch: /auth\.spec\.js/,
      use: { ...devices['Desktop Chrome'], storageState: { cookies: [], origins: [] } },
    },
    // その他のテスト (ログイン済みセッションを使用)
    {
      name: 'app',
      testIgnore: /auth\.spec\.js/,
      use: { ...devices['Desktop Chrome'] },
    },
  ],
})
