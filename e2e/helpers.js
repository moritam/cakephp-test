export const E2E_EMAIL = 'e2etest@example.com'
export const E2E_PASSWORD = 'E2ePass1!'

/** ログイン */
export async function login(page) {
  await page.goto('/app/login')
  await page.fill('input[type="email"]', E2E_EMAIL)
  await page.fill('input[type="password"]', E2E_PASSWORD)
  await page.click('button[type="submit"]')
  await page.waitForURL('**/todos')
}

/** CSRF トークンを取得して API ヘッダーにセット */
export async function getCsrfToken(page) {
  const r = await page.request.get('/users/csrf-token')
  const data = await r.json()
  return data.csrfToken
}

/** テスト用 Todo を API 経由で作成 */
export async function createTodo(page, title, options = {}) {
  const csrf = await getCsrfToken(page)
  const r = await page.request.post('/api/todos', {
    data: { title, priority: options.priority ?? 'medium', ...options },
    headers: { 'X-CSRF-Token': csrf },
  })
  return (await r.json()).todo
}

/** ユーザーの全 Todo を API 経由で削除 */
export async function clearTodos(page) {
  const r = await page.request.get('/api/todos?per_page=200')
  const data = await r.json()
  const ids = (data.todos ?? []).map((t) => t.id)
  if (ids.length === 0) return
  const csrf = await getCsrfToken(page)
  await page.request.post('/api/todos/bulk', {
    data: { action: 'delete', ids },
    headers: { 'X-CSRF-Token': csrf },
  })
}
