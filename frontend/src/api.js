const base = ''
const SAFE_METHODS = ['GET', 'HEAD', 'OPTIONS']

// CSRF トークン (メモリー上に保持。POST/PUT/PATCH/DELETE の X-CSRF-Token ヘッダーに付ける)
let csrfToken = null

async function fetchCsrfToken() {
  const r = await fetch(base + '/users/csrf-token', {
    credentials: 'include',
    headers: { Accept: 'application/json' },
  })
  if (!r.ok) throw new Error('CSRF トークンを取得できませんでした。')
  csrfToken = (await r.json()).csrfToken
  return csrfToken
}

async function request(path, options = {}, retried = false) {
  const url = path.startsWith('http') ? path : base + path
  const method = (options.method || 'GET').toUpperCase()
  const headers = {
    'Content-Type': 'application/json',
    Accept: 'application/json',
    ...options.headers,
  }
  if (!SAFE_METHODS.includes(method)) {
    headers['X-CSRF-Token'] = csrfToken || (await fetchCsrfToken())
  }
  const opts = { ...options, method, credentials: 'include', headers }
  if (options.body && typeof options.body === 'object' && !(options.body instanceof FormData)) {
    opts.body = JSON.stringify(options.body)
  }

  const r = await fetch(url, opts)
  // Cookie 側のトークンが変わった (期限切れ・削除など) 場合は、取り直して 1 回だけ再送する
  if (r.status === 403 && !SAFE_METHODS.includes(method) && !retried) {
    csrfToken = null
    return request(path, options, true)
  }
  return r
}

export const api = {
  async getTodos() {
    const r = await request('/api/todos')
    if (!r.ok) throw new Error(r.status === 401 ? 'Unauthorized' : await r.text())
    return r.json()
  },
  async getTodo(id) {
    const r = await request(`/api/todos/${id}`)
    if (!r.ok) throw new Error(r.status === 401 ? 'Unauthorized' : await r.text())
    return r.json()
  },
  async createTodo(data) {
    const r = await request('/api/todos', { method: 'POST', body: data })
    if (!r.ok) throw new Error(await r.text())
    return r.json()
  },
  async updateTodo(id, data) {
    const r = await request(`/api/todos/${id}`, { method: 'PUT', body: data })
    if (!r.ok) throw new Error(await r.text())
    return r.json()
  },
  async deleteTodo(id) {
    const r = await request(`/api/todos/${id}`, { method: 'DELETE' })
    if (r.status !== 204 && !r.ok) throw new Error(await r.text())
  },
  async login(email, password) {
    const r = await request('/users/login', {
      method: 'POST',
      body: { email, password },
    })
    const data = await r.json().catch(() => ({}))
    if (!r.ok) throw new Error(data.message || 'Login failed')
    return data
  },
  async logout() {
    const r = await request('/users/logout', { method: 'POST' })
    if (!r.ok) throw new Error('Logout failed')
    return r.json().catch(() => ({}))
  },
  async register(email, password) {
    const r = await request('/users/add', {
      method: 'POST',
      body: { email, password },
    })
    const data = await r.json().catch(() => ({}))
    if (!r.ok) throw new Error(data.errors ? JSON.stringify(data.errors) : 'Register failed')
    return data
  },
}

/** 認証チェック（API が 401 を返すか） */
export async function checkAuth() {
  const r = await request('/api/todos', { method: 'GET' })
  return r.ok
}
