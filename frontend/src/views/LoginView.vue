<script setup>
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { api } from '../api'

const router = useRouter()
const route = useRoute()
const email = ref('')
const password = ref('')
const error = ref('')
const loading = ref(false)

async function submit() {
  error.value = ''
  loading.value = true
  try {
    await api.login(email.value, password.value)
    const redirect = route.query.redirect || '/todos'
    router.push(redirect)
  } catch (e) {
    error.value = e.message || 'ログインに失敗しました。'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen flex flex-col bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100">
    <nav class="bg-white/80 dark:bg-slate-900/80 backdrop-blur-md border-b border-slate-200/80 dark:border-slate-700/80">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-14 items-center">
          <span class="text-lg font-bold bg-gradient-to-r from-blue-600 to-violet-600 bg-clip-text text-transparent">Todo App</span>
          <div class="flex gap-1">
            <router-link to="/login" class="px-3 py-1.5 rounded-lg text-sm font-medium text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition-colors">ログイン</router-link>
            <router-link to="/register" class="px-3 py-1.5 rounded-lg text-sm font-medium text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition-colors">ユーザー登録</router-link>
          </div>
        </div>
      </div>
    </nav>
    <main class="max-w-4xl mx-auto w-full px-4 py-8 flex-1 flex items-center justify-center">
      <div class="page-card max-w-md w-full overflow-hidden">
        <div class="h-1.5 bg-gradient-to-r from-blue-500 to-violet-500"></div>
        <div class="p-8">
          <h2 class="text-2xl font-bold text-slate-800 dark:text-slate-100 mb-6">ログイン</h2>
          <form class="space-y-5" @submit.prevent="submit">
            <div v-if="error" class="rounded-lg bg-red-50 dark:bg-red-950 text-red-700 dark:text-red-300 border border-red-200 dark:border-red-800 p-3 text-sm">
              {{ error }}
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">メールアドレス</label>
              <input v-model="email" type="email" required class="form-input" autocomplete="email" />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">パスワード</label>
              <input v-model="password" type="password" required class="form-input" autocomplete="current-password" />
            </div>
            <button type="submit" class="btn-primary w-full py-2.5" :disabled="loading">
              {{ loading ? '送信中...' : 'ログイン' }}
            </button>
          </form>
          <p class="mt-5 text-center text-sm text-slate-500 dark:text-slate-400">
            アカウントをお持ちでない方は
            <router-link to="/register" class="text-blue-600 dark:text-blue-400 hover:underline font-medium">ユーザー登録</router-link>
          </p>
        </div>
      </div>
    </main>
  </div>
</template>
