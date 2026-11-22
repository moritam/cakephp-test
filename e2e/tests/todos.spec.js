import { test, expect } from '@playwright/test'
import { clearTodos, createTodo } from '../helpers.js'

test.beforeEach(async ({ page }) => {
  await clearTodos(page)
  await page.goto('/app/todos')
})

test.describe('Todo 一覧', () => {
  test('Todo がない場合は空の状態が表示される', async ({ page }) => {
    await expect(page.locator('text=Todoが見つかりません。')).toBeVisible()
    await expect(page.getByText('全 0 件', { exact: true })).toBeVisible()
  })

  test('Todo を作成できる', async ({ page }) => {
    await page.getByRole('link', { name: '+ 新規作成' }).click()
    // SPA 遷移: フォームが描画されるまで待つ
    await page.getByRole('button', { name: '作成' }).waitFor()

    await page.fill('input[type="text"]', 'E2E テスト用 Todo')
    // フォーム内の唯一の select (優先度)
    await page.locator('select.form-input').selectOption('high')
    await page.getByRole('button', { name: '作成' }).click()

    // 作成後は一覧にリダイレクト
    await expect(page.locator('text=E2E テスト用 Todo')).toBeVisible()
  })

  test('Todo を編集できる', async ({ page }) => {
    await createTodo(page, 'E2E 編集テスト')
    await page.reload()

    // 一覧の View ボタンで詳細へ
    await page.getByRole('button', { name: 'View' }).first().click()
    await page.waitForURL(/\/todos\/\d+$/)

    // 詳細から Edit ボタンで編集へ
    await page.getByRole('button', { name: 'Edit Todo' }).click()
    await page.waitForURL(/\/edit$/)

    await page.fill('input[type="text"]', 'E2E 編集テスト 更新済み')
    await page.getByRole('button', { name: '保存' }).click()

    // 編集後は一覧にリダイレクト
    await page.waitForURL('**/todos')
    await expect(page.locator('text=E2E 編集テスト 更新済み')).toBeVisible()
  })

  test('Todo を削除できる', async ({ page }) => {
    await createTodo(page, 'E2E 削除テスト')
    await page.reload()

    // 一覧の Delete ボタンで削除
    page.on('dialog', (d) => d.accept())
    await page.getByRole('button', { name: 'Delete' }).first().click()

    await page.waitForTimeout(500)
    await expect(page.locator('text=E2E 削除テスト')).not.toBeVisible()
  })

  test('完了状態を切り替えられる', async ({ page }) => {
    await createTodo(page, 'E2E 完了テスト')
    await page.reload()

    // "Not done" ボタンをクリックして完了に
    await page.getByRole('button', { name: 'Not done' }).first().click()
    await page.waitForTimeout(500)

    // 完了フィルターで確認
    await page.locator('select.form-input').nth(1).selectOption('1')
    await page.waitForTimeout(500)
    await expect(page.locator('text=E2E 完了テスト')).toBeVisible()
  })

  test('優先度でフィルタリングできる', async ({ page }) => {
    await createTodo(page, 'E2E 高優先度', { priority: 'high' })
    await createTodo(page, 'E2E 低優先度', { priority: 'low' })
    await page.reload()

    // 高優先度でフィルター (最初の select が優先度)
    await page.locator('select.form-input').nth(0).selectOption('high')
    await page.waitForTimeout(500)
    await expect(page.locator('text=E2E 高優先度')).toBeVisible()
    await expect(page.locator('text=E2E 低優先度')).not.toBeVisible()
  })

  test('タイトルでキーワード検索できる', async ({ page }) => {
    await createTodo(page, 'E2E 検索ターゲット')
    await createTodo(page, 'E2E 別のTodo')
    await page.reload()

    await page.fill('input[placeholder="タイトル検索..."]', '検索ターゲット')
    await page.waitForTimeout(600)
    await expect(page.locator('text=E2E 検索ターゲット')).toBeVisible()
    await expect(page.locator('text=E2E 別のTodo')).not.toBeVisible()
  })

  test('一括完了ができる', async ({ page }) => {
    await createTodo(page, 'E2E 一括A')
    await createTodo(page, 'E2E 一括B')
    await page.reload()

    // thead のチェックボックスで全選択
    await page.locator('thead input[type="checkbox"]').click()
    await page.waitForTimeout(300)

    // 一括完了バーが表示される
    await expect(page.locator('text=完了にする')).toBeVisible()
    await page.getByRole('button', { name: '完了にする' }).click()
    await page.waitForTimeout(500)

    // 完了フィルターで確認
    await page.locator('select.form-input').nth(1).selectOption('1')
    await page.waitForTimeout(500)
    await expect(page.locator('text=E2E 一括A')).toBeVisible()
    await expect(page.locator('text=E2E 一括B')).toBeVisible()
  })

  test('一括削除ができる', async ({ page }) => {
    await createTodo(page, 'E2E 削除A')
    await createTodo(page, 'E2E 削除B')
    await page.reload()

    await page.locator('thead input[type="checkbox"]').click()
    await page.waitForTimeout(300)

    page.on('dialog', (d) => d.accept())
    await page.getByRole('button', { name: '削除' }).click()
    await page.waitForTimeout(500)

    await expect(page.locator('text=E2E 削除A')).not.toBeVisible()
    await expect(page.locator('text=E2E 削除B')).not.toBeVisible()
  })
})
