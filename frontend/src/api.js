const base = ''

function request(path, options = {}) {
  const url = path.startsWith('http') ? path : base + path
  const opts = {
    credentials: 'include',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      ...options.headers,
    },
    ...options,
  }
  if (options.body && typeof options.body === 'object' && !(options.body instanceof FormData)) {
    opts.body = JSON.stringify(options.body)
  }
  return fetch(url, opts)
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
