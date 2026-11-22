import { test, expect } from '@playwright/test'
import { clearTodos, createTodo } from '../helpers.js'

test.beforeEach(async ({ page }) => {
  await clearTodos(page)
})

test.describe('カレンダービュー', () => {
  test('カレンダーページに遷移できる', async ({ page }) => {
    await page.goto('/app/calendar')
    await expect(page.getByRole('heading', { name: 'カレンダー' })).toBeVisible()
    // 7列のグリッドが表示される (日月火水木金土)
    await expect(page.getByText('日', { exact: true })).toBeVisible()
    await expect(page.getByText('土', { exact: true })).toBeVisible()
  })

  test('前月・次月へ移動できる', async ({ page }) => {
    await page.goto('/app/calendar')

    // 現在の月ヘッダーを取得 (monthLabel span)
    const heading = page.locator('span.w-28')
    const currentText = await heading.textContent()

    // 次月ボタン (aria-label="次の月")
    await page.getByRole('button', { name: '次の月' }).click()
    await page.waitForTimeout(300)
    const nextText = await heading.textContent()
    expect(nextText).not.toBe(currentText)

    // 前月ボタン (aria-label="前の月") で今月に戻る
    await page.getByRole('button', { name: '前の月' }).click()
    await page.waitForTimeout(300)
    const backText = await heading.textContent()
    expect(backText).toBe(currentText)
  })

  test('期限日付きの Todo がカレンダーに表示される', async ({ page }) => {
    // 今月の 15 日に Todo を作成
    const now = new Date()
    const year = now.getFullYear()
    const month = String(now.getMonth() + 1).padStart(2, '0')
    const dueDate = `${year}-${month}-15`

    await createTodo(page, 'E2E カレンダー Todo', { due_date: dueDate })

    await page.goto('/app/calendar')
    await expect(page.locator('text=E2E カレンダー Todo')).toBeVisible()
  })

  test('Todo チップをクリックすると詳細ページへ遷移する', async ({ page }) => {
    const now = new Date()
    const year = now.getFullYear()
    const month = String(now.getMonth() + 1).padStart(2, '0')
    const dueDate = `${year}-${month}-20`

    await createTodo(page, 'E2E チップクリック', { due_date: dueDate })

    await page.goto('/app/calendar')
    await page.locator('text=E2E チップクリック').click()
    await page.waitForURL(/\/todos\/\d+$/)
    await expect(page.locator('text=E2E チップクリック')).toBeVisible()
  })

  test('今日の日付がハイライトされる', async ({ page }) => {
    await page.goto('/app/calendar')
    // 今日の日付セルは bg-blue-600 の span がある (カレンダーの日付バッジ)
    const todayBadge = page.locator('span.bg-blue-600').first()
    await expect(todayBadge).toBeVisible()
  })

  test('期限日のない Todo はカレンダーに表示されない', async ({ page }) => {
    await createTodo(page, 'E2E 期限なし Todo')

    await page.goto('/app/calendar')
    await expect(page.locator('text=E2E 期限なし Todo')).not.toBeVisible()
  })
})
