import { createRouter, createWebHistory } from 'vue-router'
import { checkAuth } from '../api'
import AppLayout from '../layouts/AppLayout.vue'

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: () => import('../views/LoginView.vue'),
    meta: { guest: true },
  },
  {
    path: '/register',
    name: 'Register',
    component: () => import('../views/RegisterView.vue'),
    meta: { guest: true },
  },
  {
    path: '/',
    component: AppLayout,
    meta: { requiresAuth: true },
    children: [
      { path: '', redirect: '/todos' },
      {
        path: 'todos',
        name: 'TodoList',
        component: () => import('../views/TodoListView.vue'),
      },
      {
        path: 'todos/new',
        name: 'TodoAdd',
        component: () => import('../views/TodoFormView.vue'),
        meta: { mode: 'add' },
      },
      {
        path: 'todos/:id',
        name: 'TodoView',
        component: () => import('../views/TodoDetailView.vue'),
      },
      {
        path: 'todos/:id/edit',
        name: 'TodoEdit',
        component: () => import('../views/TodoFormView.vue'),
        meta: { mode: 'edit' },
      },
      {
        path: 'tags',
        name: 'TagList',
        component: () => import('../views/TagListView.vue'),
      },
    ],
  },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
})

router.beforeEach(async (to) => {
  const requiresAuth = to.matched.some((r) => r.meta.requiresAuth)
  const guest = to.matched.some((r) => r.meta.guest)
  if (requiresAuth) {
    const ok = await checkAuth()
    if (!ok) return { name: 'Login', query: { redirect: to.fullPath } }
  }
  if (guest && to.name !== 'Login' && to.name !== 'Register') {
    const ok = await checkAuth()
    if (ok) return { path: '/todos' }
  }
  return true
})

export default router
