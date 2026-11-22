import { test, expect } from '@playwright/test'
import { clearTodos, createTodo, getCsrfToken } from '../helpers.js'

test.beforeEach(async ({ page }) => {
  await clearTodos(page)
})

test.describe('統計カード', () => {
  test('統計ページに遷移できる', async ({ page }) => {
    await page.goto('/app/stats')
    await expect(page.getByRole('heading', { name: /統計|Stats/ })).toBeVisible()
  })

  test('Todo がない場合は 0 件と表示される', async ({ page }) => {
    await page.goto('/app/stats')
    // 合計 0 件
    await expect(page.locator('text=0').first()).toBeVisible()
  })

  test('Todo を作成すると合計件数が増える', async ({ page }) => {
    await createTodo(page, 'E2E 統計テスト1')
    await createTodo(page, 'E2E 統計テスト2')

    await page.goto('/app/stats')
    // 合計 2 件が表示されていること
    await expect(page.locator('text=2').first()).toBeVisible()
  })

  test('完了した Todo の件数が反映される', async ({ page }) => {
    const todo = await createTodo(page, 'E2E 完了カウント')
    // API で完了状態に更新
    const csrf = await getCsrfToken(page)
    await page.request.patch(`/api/todos/${todo.id}`, {
      data: { completed: true },
      headers: { 'X-CSRF-Token': csrf },
    })

    await page.goto('/app/stats')
    // 完了 1 件が表示されていること
    await expect(page.locator('text=1').first()).toBeVisible()
  })

  test('優先度別の統計が表示される', async ({ page }) => {
    await createTodo(page, 'E2E 高優先度統計', { priority: 'high' })
    await createTodo(page, 'E2E 中優先度統計', { priority: 'medium' })
    await createTodo(page, 'E2E 低優先度統計', { priority: 'low' })

    await page.goto('/app/stats')
    // 高・中・低の優先度ラベルが確認できる
    await expect(page.locator('text=高').first()).toBeVisible()
    await expect(page.locator('text=中').first()).toBeVisible()
    await expect(page.locator('text=低').first()).toBeVisible()
  })

  test('期限切れの統計が表示される', async ({ page }) => {
    // 過去の期限日で Todo を作成
    await createTodo(page, 'E2E 期限切れ統計', { due_date: '2020-01-01' })

    await page.goto('/app/stats')
    // 期限切れの件数が表示されている
    await expect(page.locator('text=期限切れ').first()).toBeVisible()
  })
})
