<script setup>
import { ref } from 'vue'
import { api } from '../api'

const form = ref({ current_password: '', new_password: '', new_password_confirm: '' })
const loading = ref(false)
const success = ref('')
const errors = ref({})

async function submit() {
  loading.value = true
  success.value = ''
  errors.value = {}
  try {
    await api.changePassword(form.value.current_password, form.value.new_password, form.value.new_password_confirm)
    success.value = 'パスワードを変更しました。'
    form.value = { current_password: '', new_password: '', new_password_confirm: '' }
  } catch (e) {
    errors.value = e.errors || { general: e.message || 'パスワードの変更に失敗しました。' }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="max-w-md mx-auto">
    <h2 class="text-2xl font-bold text-slate-800 dark:text-slate-100 mb-6">アカウント設定</h2>

    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
      <h3 class="text-lg font-semibold text-slate-800 dark:text-slate-100 mb-4">パスワード変更</h3>

      <div v-if="success" class="rounded-lg bg-green-50 dark:bg-green-950 text-green-800 dark:text-green-200 border border-green-200 dark:border-green-800 p-4 mb-4 text-sm">
        {{ success }}
      </div>
      <div v-if="errors.general" class="rounded-lg bg-red-50 dark:bg-red-950 text-red-800 dark:text-red-200 border border-red-200 dark:border-red-800 p-4 mb-4 text-sm">
        {{ errors.general }}
      </div>

      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">現在のパスワード</label>
          <input
            v-model="form.current_password"
            type="password"
            required
            autocomplete="current-password"
            class="form-input"
          />
          <p v-if="errors.current_password" class="text-red-600 dark:text-red-400 text-xs mt-1">{{ errors.current_password }}</p>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">新しいパスワード</label>
          <input
            v-model="form.new_password"
            type="password"
            required
            minlength="8"
            autocomplete="new-password"
            class="form-input"
          />
          <p v-if="errors.new_password" class="text-red-600 dark:text-red-400 text-xs mt-1">{{ errors.new_password }}</p>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">新しいパスワード（確認）</label>
          <input
            v-model="form.new_password_confirm"
            type="password"
            required
            minlength="8"
            autocomplete="new-password"
            class="form-input"
          />
          <p v-if="errors.new_password_confirm" class="text-red-600 dark:text-red-400 text-xs mt-1">{{ errors.new_password_confirm }}</p>
        </div>
        <button type="submit" :disabled="loading" class="btn-primary w-full disabled:opacity-60">
          {{ loading ? '変更中...' : 'パスワードを変更する' }}
        </button>
      </form>
    </div>
  </div>
</template>
