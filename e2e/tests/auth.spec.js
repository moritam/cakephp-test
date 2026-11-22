import { test, expect } from '@playwright/test'
import { E2E_EMAIL, E2E_PASSWORD } from '../helpers.js'

test.describe('認証', () => {
  test('ログイン画面が表示される', async ({ page }) => {
    await page.goto('/app/login')
    await expect(page.getByRole('heading', { name: 'ログイン' })).toBeVisible()
    await expect(page.getByPlaceholder('you@example.com')).toBeVisible()
    // 大画面ではグラデーション左パネルが表示される
    await expect(page.locator('text=タスクを管理して')).toBeVisible()
  })

  test('正しい認証情報でログインすると Todo 一覧に遷移する', async ({ page }) => {
    await page.goto('/app/login')
    await page.fill('input[type="email"]', E2E_EMAIL)
    await page.fill('input[type="password"]', E2E_PASSWORD)
    await page.click('button[type="submit"]')
    await page.waitForURL('**/todos')
    await expect(page.getByRole('heading', { name: 'Todo 一覧' })).toBeVisible()
  })

  test('間違ったパスワードでエラーが表示される', async ({ page }) => {
    await page.goto('/app/login')
    await page.fill('input[type="email"]', E2E_EMAIL)
    await page.fill('input[type="password"]', 'wrongpassword')
    await page.click('button[type="submit"]')
    await expect(page.locator('text=メールアドレスまたはパスワードが正しくありません。')).toBeVisible()
    // ログイン画面に留まる
    await expect(page).toHaveURL(/\/login/)
  })

  test('未ログイン状態で /app/todos にアクセスするとログイン画面にリダイレクトされる', async ({ page }) => {
    await page.goto('/app/todos')
    await expect(page).toHaveURL(/\/login/)
  })

  test('ログアウトするとログイン画面に遷移する', async ({ page }) => {
    // ログイン
    await page.goto('/app/login')
    await page.fill('input[type="email"]', E2E_EMAIL)
    await page.fill('input[type="password"]', E2E_PASSWORD)
    await page.click('button[type="submit"]')
    await page.waitForURL('**/todos')

    // ログアウト
    await page.getByRole('button', { name: 'ログアウト' }).click()
    await expect(page).toHaveURL(/\/login/)
  })
})
