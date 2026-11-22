import { chromium } from '@playwright/test'
import { execSync } from 'child_process'
import path from 'path'
import { fileURLToPath } from 'url'
import fs from 'fs'

const ROOT = path.resolve(fileURLToPath(import.meta.url), '../../')
const E2E_EMAIL = 'e2etest@example.com'
const E2E_PASSWORD = 'E2ePass1!'

export default async function globalSetup() {
  // テストユーザーを作成 (存在すれば無視)
  try {
    execSync(
      `printf '${E2E_PASSWORD}\n${E2E_PASSWORD}\n' | bin/cake user create ${E2E_EMAIL}`,
      { cwd: ROOT, stdio: 'pipe' },
    )
  } catch {
    // 既に存在する場合は無視
  }

  // ログインしてセッションを .auth/state.json に保存
  const browser = await chromium.launch()
  const page = await browser.newPage()
  await page.goto('http://localhost:8765/app/login')
  await page.fill('input[type="email"]', E2E_EMAIL)
  await page.fill('input[type="password"]', E2E_PASSWORD)
  await page.click('button[type="submit"]')
  await page.waitForURL('**/todos')

  fs.mkdirSync(path.join(ROOT, 'e2e/.auth'), { recursive: true })
  await page.context().storageState({ path: path.join(ROOT, 'e2e/.auth/state.json') })
  await browser.close()
}
