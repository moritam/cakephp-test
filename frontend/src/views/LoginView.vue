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
  <div class="min-h-screen flex bg-slate-50 dark:bg-slate-950">
    <!-- 左パネル (装飾) -->
    <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-blue-600 to-violet-700 p-12 flex-col justify-between">
      <div class="flex items-center gap-3">
        <div class="w-9 h-9 rounded-xl bg-white/20 flex items-center justify-center">
          <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
          </svg>
        </div>
        <span class="text-white font-bold text-lg">Todo App</span>
      </div>
      <div>
        <h1 class="text-4xl font-bold text-white mb-4 leading-tight">
          タスクを管理して、<br />毎日を整理しよう。
        </h1>
        <p class="text-blue-100 text-base">優先度・タグ・期限で Todo を整理。</p>
      </div>
    </div>

    <!-- 右パネル (フォーム) -->
    <div class="flex-1 flex flex-col items-center justify-center px-6 py-12">
      <div class="w-full max-w-sm">
        <!-- モバイル用ロゴ -->
        <div class="flex lg:hidden items-center gap-2 mb-8">
          <div class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center">
            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
            </svg>
          </div>
          <span class="font-bold text-slate-800 dark:text-slate-100">Todo App</span>
        </div>

        <h2 class="text-2xl font-bold text-slate-800 dark:text-slate-100 mb-1">ログイン</h2>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-8">アカウントにサインインしてください</p>

        <form class="space-y-4" @submit.prevent="submit">
          <div v-if="error" class="rounded-xl bg-red-50 dark:bg-red-950 text-red-700 dark:text-red-300 border border-red-200 dark:border-red-800 p-3 text-sm">
            {{ error }}
          </div>
          <div>
            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">メールアドレス</label>
            <input v-model="email" type="email" required class="form-input" autocomplete="email" placeholder="you@example.com" />
          </div>
          <div>
            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">パスワード</label>
            <input v-model="password" type="password" required class="form-input" autocomplete="current-password" placeholder="••••••••" />
          </div>
          <button type="submit" class="btn-primary w-full mt-2 py-3 text-base" :disabled="loading">
            {{ loading ? 'サインイン中...' : 'サインイン' }}
          </button>
        </form>

        <p class="mt-6 text-center text-sm text-slate-500 dark:text-slate-400">
          アカウントをお持ちでない方は
          <router-link to="/register" class="text-blue-600 dark:text-blue-400 font-semibold hover:underline">登録</router-link>
        </p>
      </div>
    </div>
  </div>
</template>
