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
  <div class="min-h-screen flex flex-col bg-slate-50 text-slate-900">
    <nav class="bg-white shadow-sm border-b border-slate-200">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-14 items-center">
          <span class="text-xl font-semibold text-slate-800">Todo App</span>
          <div class="flex gap-4">
            <router-link to="/login" class="text-slate-600 hover:text-slate-900 font-medium">ログイン</router-link>
            <router-link to="/register" class="text-slate-600 hover:text-slate-900 font-medium">ユーザー登録</router-link>
          </div>
        </div>
      </div>
    </nav>
    <main class="max-w-4xl mx-auto w-full px-4 py-8 flex-1 flex items-center justify-center">
      <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-8 max-w-md w-full">
        <h2 class="text-2xl font-bold text-slate-800 mb-6">ログイン</h2>
        <form class="space-y-6" @submit.prevent="submit">
          <div v-if="error" class="rounded-lg bg-red-50 text-red-800 border border-red-200 p-4 text-sm">
            {{ error }}
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">メールアドレス</label>
            <input
              v-model="email"
              type="email"
              required
              class="form-input"
              autocomplete="email"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">パスワード</label>
            <input
              v-model="password"
              type="password"
              required
              class="form-input"
              autocomplete="current-password"
            />
          </div>
          <button type="submit" class="btn-primary w-full" :disabled="loading">
            {{ loading ? '送信中...' : 'ログイン' }}
          </button>
        </form>
        <p class="mt-4 text-center text-sm text-slate-600">
          <router-link to="/register" class="text-blue-600 hover:text-blue-800 font-medium">ユーザー登録</router-link>
        </p>
      </div>
    </main>
  </div>
</template>
